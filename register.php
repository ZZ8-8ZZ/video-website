<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $activation_code = $_POST['activation_code'];
    
    // 验证激活码
    $stmt = $pdo->prepare("SELECT * FROM activation_codes WHERE code = ? AND is_used = 0");
    $stmt->execute([$activation_code]);
    
    if ($stmt->rowCount() > 0) {
        try {
            $pdo->beginTransaction();
            
            // 创建用户，设置状态为 active
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, status) VALUES (?, ?, ?, 'active')");
            if ($stmt->execute([$username, $password, $email])) {
                $user_id = $pdo->lastInsertId();
                
                // 标记激活码已使用
                $stmt = $pdo->prepare("UPDATE activation_codes SET is_used = 1, used_by = ?, used_at = CURRENT_TIMESTAMP WHERE code = ?");
                $stmt->execute([$user_id, $activation_code]);
                
                $pdo->commit();
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "注册失败：" . $e->getMessage();
        }
    } else {
        $error = "无效的激活码";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>注册</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>用户注册</h2>
                <p>创建您的账号</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="auth-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="用户名" required>
                    <i class="fas fa-user icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="密码" required>
                    <i class="fas fa-lock icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" placeholder="邮箱" required>
                    <i class="fas fa-envelope icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="text" name="activation_code" placeholder="激活码" required>
                    <i class="fas fa-key icon"></i>
                </div>
                
                <button type="submit" class="auth-btn">
                    <i class="fas fa-user-plus"></i> 注册
                </button>
                
                <div class="auth-links">
                    <a href="login.php">已有账号？立即登录</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 