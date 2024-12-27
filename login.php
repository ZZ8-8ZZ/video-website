<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 先检查用户是否存在
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // 用户存在，检查密码和状态
        if (password_verify($password, $user['password'])) {
            if ($user['status'] === 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = "账号未激活或已被禁用";
            }
        } else {
            $error = "密码错误";
        }
    } else {
        $error = "用户名不存在";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>用户登录</h2>
                <p>欢迎回来！</p>
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
                
                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i> 登录
                </button>
                
                <div class="auth-links">
                    <a href="register.php">还没有账号？立即注册</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 