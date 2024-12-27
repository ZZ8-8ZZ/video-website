<?php
session_start();
require_once 'config/database.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户信息
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 获取用户片单统计信息
$stmt = $pdo->prepare("SELECT COUNT(*) FROM playlists WHERE user_id = ?");
$stmt->execute([$user_id]);
$playlists_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <!-- 左侧侧边栏 -->
        <div class="profile-sidebar">
            <div class="profile-avatar-wrapper">
                <img src="https://cdn.jsdelivr.net/gh/ZZ8-8ZZ/image/202408242045317.webp" 
                     alt="用户头像" class="profile-avatar">
                <label for="avatar-upload" class="avatar-upload">
                    <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="avatar-upload" style="display: none" accept="image/*">
            </div>

            <div class="profile-info">
                <h2 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="profile-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : '这个人很懒，什么都没写~'; ?></p>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $playlists_count; ?></div>
                    <div class="stat-label">片单</div>
                </div>
            </div>

            <nav class="profile-nav">
                <a href="#info" class="active"><i class="fas fa-user"></i>基本资料</a>
                <a href="#playlists"><i class="fas fa-list"></i>我的片单</a>
                <a href="#settings"><i class="fas fa-cog"></i>账号设置</a>
            </nav>
        </div>

        <!-- 右侧主要内容 -->
        <div class="profile-main">
            <!-- 基本资料 -->
            <section class="profile-section" id="info">
                <h2 class="section-title">基本资料</h2>
                <form class="profile-form" method="post" action="update_profile.php">
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">邮箱</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bio">个人简介</label>
                        <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-submit">
                        <button type="button" class="btn-cancel">取消</button>
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 头像上传
        const avatarUpload = document.getElementById('avatar-upload');
        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('avatar', file);

                fetch('upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.profile-avatar').src = data.url;
                    } else {
                        alert('上传失败：' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('上传失败，请重试');
                });
            }
        });

        // 导航切换
        const navLinks = document.querySelectorAll('.profile-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                targetSection.scrollIntoView({ behavior: 'smooth' });
            });
        });
    });
    </script>
</body>
</html> 