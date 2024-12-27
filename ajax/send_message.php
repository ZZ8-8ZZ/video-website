<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => '未登录']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['to_username']) || empty($data['title']) || empty($data['content'])) {
    die(json_encode(['success' => false, 'message' => '缺少必要参数']));
}

try {
    // 获取接收者ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$data['to_username']]);
    $to_user_id = $stmt->fetchColumn();
    
    if (!$to_user_id) {
        die(json_encode(['success' => false, 'message' => '用户不存在']));
    }
    
    // 发送消息
    $stmt = $pdo->prepare("
        INSERT INTO messages (from_user_id, to_user_id, type, title, content)
        VALUES (?, ?, 'user', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $to_user_id,
        $data['title'],
        $data['content']
    ]);
    
    // 检查用户的通知设置
    $stmt = $pdo->prepare("
        SELECT email_notify FROM notification_settings 
        WHERE user_id = ?
    ");
    $stmt->execute([$to_user_id]);
    $notify = $stmt->fetch();
    
    // 如果用户开启了邮件通知，发送邮件
    if ($notify && $notify['email_notify']) {
        // 发送邮件通知
        // ...
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '发送失败']);
} 