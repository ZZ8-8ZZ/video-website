<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['count' => 0]));
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages 
    WHERE to_user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$count = $stmt->fetchColumn();

echo json_encode(['count' => $count]);