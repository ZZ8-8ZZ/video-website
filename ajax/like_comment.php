<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => '未登录']));
}

$comment_id = intval($_POST['comment_id']);

try {
    $pdo->beginTransaction();
    
    // 检查是否已点赞
    $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $_SESSION['user_id']]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // 取消点赞
        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
        
        $stmt = $pdo->prepare("UPDATE comments SET likes = likes - 1 WHERE id = ?");
        $stmt->execute([$comment_id]);
        
        $action = 'unliked';
    } else {
        // 添加点赞
        $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
        
        $stmt = $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$comment_id]);
        
        $action = 'liked';
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'action' => $action]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '操作失败']);
} 