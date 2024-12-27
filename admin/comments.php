<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'comments';
$page_title = '评论管理';

// 获取筛选条件
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// 构建查询条件
$where = [];
$params = [];

if ($status) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

if ($type !== 'all') {
    $where[] = "c.content_type = ?";
    $params[] = $type;
}

$where_str = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取评论列表
$stmt = $pdo->prepare("
    SELECT c.*, u.username,
           CASE 
               WHEN c.content_type = 'video' THEN v.title
               ELSE s.title
           END as content_title
    FROM comments c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN videos v ON c.content_type = 'video' AND c.content_id = v.id
    LEFT JOIN series s ON c.content_type = 'series' AND c.content_id = s.id
    {$where_str}
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$comments = $stmt->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>评论管理</h2>
</div>

<div class="card">
    <div class="card-header">
        <div class="filter-tabs">
            <a href="?status=pending" class="btn <?php echo $status === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-clock"></i> 待审核
            </a>
            <a href="?status=approved" class="btn <?php echo $status === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-check"></i> 已通过
            </a>
            <a href="?status=rejected" class="btn <?php echo $status === 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-ban"></i> 已拒绝
            </a>
        </div>
        
        <div class="type-filter">
            <select class="form-control" onchange="location.href='?status=<?php echo $status; ?>&type='+this.value">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>全部内容</option>
                <option value="video" <?php echo $type === 'video' ? 'selected' : ''; ?>>视频</option>
                <option value="series" <?php echo $type === 'series' ? 'selected' : ''; ?>>剧集</option>
            </select>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>内容</th>
                <th>评论对象</th>
                <th>点赞数</th>
                <th>时间</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?php echo $comment['id']; ?></td>
                <td><?php echo htmlspecialchars($comment['username']); ?></td>
                <td><?php echo htmlspecialchars($comment['comment']); ?></td>
                <td>
                    <span class="badge badge-info"><?php echo $comment['content_type']; ?></span>
                    <?php echo htmlspecialchars($comment['content_title']); ?>
                </td>
                <td><?php echo number_format($comment['likes']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                <td>
                    <span class="status-badge <?php echo $comment['status']; ?>">
                        <?php echo $comment['status']; ?>
                    </span>
                </td>
                <td>
                    <?php if ($comment['status'] === 'pending'): ?>
                    <button onclick="approveComment(<?php echo $comment['id']; ?>)" class="btn btn-success btn-sm">
                        <i class="fas fa-check"></i> 通过
                    </button>
                    <button onclick="rejectComment(<?php echo $comment['id']; ?>)" class="btn btn-warning btn-sm">
                        <i class="fas fa-ban"></i> 拒绝
                    </button>
                    <?php endif; ?>
                    <button onclick="deleteComment(<?php echo $comment['id']; ?>)" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> 删除
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function approveComment(id) {
    updateCommentStatus(id, 'approved');
}

function rejectComment(id) {
    updateCommentStatus(id, 'rejected');
}

function updateCommentStatus(id, status) {
    if (!confirm('确定要' + (status === 'approved' ? '通过' : '拒绝') + '这条评论吗？')) {
        return;
    }
    
    fetch('ajax/update_comment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '操作失败');
        }
    });
}

function deleteComment(id) {
    if (!confirm('确定要删除这条评论吗？')) {
        return;
    }
    
    fetch('ajax/delete_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '删除失败');
        }
    });
}
</script>

<?php require 'layout/footer.php'; ?>