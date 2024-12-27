<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => '未登录']));
}

$content_id = intval($_GET['content_id']);
$content_type = $_GET['content_type'];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 获取评论总数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE content_id = ? AND content_type = ?");
$stmt->execute([$content_id, $content_type]);
$total = $stmt->fetchColumn();

// 获取评论列表
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar,
           EXISTS(SELECT 1 FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?) as is_liked
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.content_id = ? AND c.content_type = ?
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $content_id, $content_type, $per_page, $offset]);
$comments = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'total' => $total,
    'comments' => $comments,
    'has_more' => ($offset + $per_page) < $total
]);