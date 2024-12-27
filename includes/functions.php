<?php
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = json_encode($context);
    $logMessage = "[$timestamp] $message - Context: $contextStr\n";
    error_log($logMessage, 3, $logFile);
}

function handleError($errno, $errstr, $errfile, $errline) {
    logError($errstr, [
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
    
    if (DEBUG_MODE) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    } else {
        // 在生产环境中显示友好的错误信息
        include 'templates/error.php';
        exit;
    }
}

set_error_handler('handleError'); 

// 获取用户头像
function getUserAvatar($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $avatar = $stmt->fetchColumn();
    
    // 如果用户没有设置头像，返回默认头像
    return $avatar ?: 'images/default-avatar.png';
}

// 检查用户权限
function hasPermission($permissionCode) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // 如果是管理员，直接返回 true
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetchColumn()) {
        return true;
    }
    
    return false;
}

// 获取未读消息数量
function getUnreadMessageCount($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// XSS防护
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}