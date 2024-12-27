<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            // 添加评论
            $video_id = intval($_POST['video_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            
            if (empty($content)) {
                throw new Exception('评论内容不能为空');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO comments (video_id, user_id, content, parent_id)
                VALUES (:video_id, :user_id, :content, :parent_id)
            ");
            
            $stmt->execute([
                'video_id' => $video_id,
                'user_id' => $user_id,
                'content' => $content,
                'parent_id' => $parent_id ?: null
            ]);
            
            // 获取新插入的评论信息
            $comment_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                SELECT c.*, u.username, u.avatar
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            $comment = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => '评论成功',
                'comment' => $comment
            ]);
            break;
            
        case 'like':
            // 点赞评论
            $comment_id = intval($_POST['comment_id'] ?? 0);
            
            // 检查是否已经点赞
            $stmt = $pdo->prepare("
                SELECT id FROM comment_likes
                WHERE comment_id = :comment_id AND user_id = :user_id
            ");
            $stmt->execute([
                'comment_id' => $comment_id,
                'user_id' => $user_id
            ]);
            
            if ($stmt->fetch()) {
                // 取消点赞
                $stmt = $pdo->prepare("
                    DELETE FROM comment_likes
                    WHERE comment_id = :comment_id AND user_id = :user_id
                ");
                $stmt->execute([
                    'comment_id' => $comment_id,
                    'user_id' => $user_id
                ]);
                
                // 更新评论点赞数
                $stmt = $pdo->prepare("
                    UPDATE comments SET likes = likes - 1
                    WHERE id = :comment_id
                ");
                $stmt->execute(['comment_id' => $comment_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => '取消点赞',
                    'action' => 'unlike'
                ]);
            } else {
                // 添加点赞
                $stmt = $pdo->prepare("
                    INSERT INTO comment_likes (comment_id, user_id)
                    VALUES (:comment_id, :user_id)
                ");
                $stmt->execute([
                    'comment_id' => $comment_id,
                    'user_id' => $user_id
                ]);
                
                // 更新评论点赞数
                $stmt = $pdo->prepare("
                    UPDATE comments SET likes = likes + 1
                    WHERE id = :comment_id
                ");
                $stmt->execute(['comment_id' => $comment_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => '点赞成功',
                    'action' => 'like'
                ]);
            }
            break;
            
        case 'list':
            // 获取评论列表
            $video_id = intval($_POST['video_id'] ?? 0);
            $sort = $_POST['sort'] ?? 'new'; // new 或 hot
            $page = intval($_POST['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $order_by = $sort === 'hot' ? 'c.likes DESC, c.created_at DESC' : 'c.created_at DESC';
            
            $stmt = $pdo->prepare("
                SELECT c.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as reply_count,
                       EXISTS(SELECT 1 FROM comment_likes WHERE comment_id = c.id AND user_id = :user_id) as is_liked
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.video_id = :video_id AND c.parent_id IS NULL
                ORDER BY {$order_by}
                LIMIT :offset, :limit
            ");
            
            $stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $comments = $stmt->fetchAll();
            
            // 获取每个评论的回复
            foreach ($comments as &$comment) {
                $stmt = $pdo->prepare("
                    SELECT c.*, u.username, u.avatar,
                           EXISTS(SELECT 1 FROM comment_likes WHERE comment_id = c.id AND user_id = :user_id) as is_liked
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.parent_id = :comment_id
                    ORDER BY c.created_at ASC
                    LIMIT 3
                ");
                
                $stmt->execute([
                    'comment_id' => $comment['id'],
                    'user_id' => $user_id
                ]);
                
                $comment['replies'] = $stmt->fetchAll();
            }
            
            // 获取总评论数
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM comments c
                WHERE c.video_id = :video_id AND c.parent_id IS NULL
            ");
            $stmt->execute(['video_id' => $video_id]);
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'comments' => $comments,
                'pagination' => [
                    'current' => $page,
                    'total' => ceil($total / $limit),
                    'limit' => $limit,
                    'total_comments' => $total
                ]
            ]);
            break;
            
        case 'delete':
            // 删除评论
            $comment_id = intval($_POST['comment_id'] ?? 0);
            
            // 检查是否是评论作者
            $stmt = $pdo->prepare("
                SELECT user_id FROM comments
                WHERE id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            $comment = $stmt->fetch();
            
            if (!$comment || $comment['user_id'] != $user_id) {
                throw new Exception('无权删除此评论');
            }
            
            // 删除评论及其回复
            $stmt = $pdo->prepare("
                DELETE FROM comments
                WHERE id = :comment_id OR parent_id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            
            echo json_encode([
                'success' => true,
                'message' => '评论已删除'
            ]);
            break;
            
        case 'edit':
            // 编辑评论
            $comment_id = intval($_POST['comment_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            
            if (empty($content)) {
                throw new Exception('评论内容不能为空');
            }
            
            // 检查是否是评论作者
            $stmt = $pdo->prepare("
                SELECT user_id FROM comments
                WHERE id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            $comment = $stmt->fetch();
            
            if (!$comment || $comment['user_id'] != $user_id) {
                throw new Exception('无权编辑此评论');
            }
            
            // 更新评论内容
            $stmt = $pdo->prepare("
                UPDATE comments
                SET content = :content, updated_at = CURRENT_TIMESTAMP
                WHERE id = :comment_id
            ");
            $stmt->execute([
                'comment_id' => $comment_id,
                'content' => $content
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '评论已更新'
            ]);
            break;
            
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 