<?php
require_once '../config/database.php';

// 创建管理员账号
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_email = "admin@example.com";

try {
    // 插入管理员用户
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email, is_admin, status) 
        VALUES (?, ?, ?, 1, 'active')
    ");
    $stmt->execute([$admin_username, $admin_password, $admin_email]);
    
    echo "管理员账号创建成功！<br>";
    echo "用户名: admin<br>";
    echo "密码: admin123<br>";
    echo "<a href='admin_login.php'>前往登录</a>";
    
} catch (PDOException $e) {
    die("创建管理员账号失败: " . $e->getMessage());
} 