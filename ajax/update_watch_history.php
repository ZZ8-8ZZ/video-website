<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id']) || !isset($_POST['progress'])) {
    die(json_encode(['success' => false]));
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);
$progress = intval($_POST['progress']);

try {
    $stmt = $pdo->prepare("INSERT INTO watch_history (user_id, video_id, progress) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          watch_time = CURRENT_TIMESTAMP, 
                          progress = ?");
    $stmt->execute([$user_id, $video_id, $progress, $progress]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 