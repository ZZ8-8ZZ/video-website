<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    die(json_encode(['success' => false, 'message' => '未授权的操作']));
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);

try {
    // 检查是否已收藏
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
    
    if ($stmt->rowCount() > 0) {
        // 取消收藏
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        echo json_encode(['success' => true, 'action' => 'unfavorited']);
    } else {
        // 添加收藏
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, video_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $video_id]);
        echo json_encode(['success' => true, 'action' => 'favorited']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 