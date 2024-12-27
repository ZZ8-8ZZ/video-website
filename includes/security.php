<?php
// CSRF保护
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF验证失败');
    }
}

// XSS防护
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// SQL注入防护
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return strip_tags(trim($input));
}

// 文件上传安全检查
function validateUpload($file) {
    $errors = [];
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = '文件大小超过限制';
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $errors[] = '不支持的文件类型';
    }
    
    // 检查是否为真实的视频文件
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (strpos($mimeType, 'video/') !== 0) {
        $errors[] = '无效的视频文件';
    }
    
    return $errors;
} 