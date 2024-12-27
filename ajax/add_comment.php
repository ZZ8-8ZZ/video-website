<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => '未登录']));
}

$content_id = intval($_POST['content_id']);
$content_type = $_POST['content_type'];
$comment = trim($_POST['comment']);

if (empty($comment)) {
    die(json_encode(['success' => false, 'message' => '评论内容不能为空']));
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, content_id, content_type, comment)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $content_id, $content_type, $comment]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '评论失败']);
}