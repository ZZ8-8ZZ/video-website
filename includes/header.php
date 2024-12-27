<?php
if (!defined('SITE_NAME')) {
    require_once 'config/config.php';
}
require_once 'includes/functions.php';

// 获取未读消息数
$unread_messages = isset($_SESSION['user_id']) ? getUnreadMessageCount($_SESSION['user_id']) : 0;

// 获取当前页面的文件名
$current_page = basename($_SERVER['PHP_SELF']);
$current_type = isset($_GET['type']) ? $_GET['type'] : '';
?>

<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
        </div>
        
        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">首页</a></li>
                <li><a href="category.php?type=movie" class="<?php echo $current_page === 'category.php' && $current_type === 'movie' ? 'active' : ''; ?>">电影</a></li>
                <li><a href="category.php?type=tv" class="<?php echo $current_page === 'category.php' && $current_type === 'tv' ? 'active' : ''; ?>">电视剧</a></li>
                <li><a href="category.php?type=variety" class="<?php echo $current_page === 'category.php' && $current_type === 'variety' ? 'active' : ''; ?>">综艺</a></li>
                <li><a href="category.php?type=anime" class="<?php echo $current_page === 'category.php' && $current_type === 'anime' ? 'active' : ''; ?>">动漫</a></li>
                <li><a href="ranking.php" class="<?php echo $current_page === 'ranking.php' ? 'active' : ''; ?>">排行榜</a></li>
            </ul>
        </nav>
        
        <div class="search-box">
            <form action="search.php" method="get">
                <input type="text" name="keyword" placeholder="搜索...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-menu">
            <div class="user-avatar">
                <img src="https://cdn.jsdelivr.net/gh/ZZ8-8ZZ/image/202408242045317.webp" 
                     alt="用户头像" id="userAvatar">
                <?php if ($unread_messages > 0): ?>
                <span class="message-badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="dropdown-menu" id="userDropdown">
                <ul>
                    <li><a href="profile.php"><i class="fas fa-user"></i>个人中心</a></li>
                    <li><a href="playlist.php"><i class="fas fa-list"></i>我的片单</a></li>
                    <li><a href="history.php"><i class="fas fa-history"></i>观看历史</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>账号设置</a></li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li><a href="admin/"><i class="fas fa-shield-alt"></i>后台管理</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>退出登录</a></li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="auth-buttons">
            <a href="login.php" class="btn-login">登录</a>
            <a href="register.php" class="btn-register">注册</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        // 点击其他地方关闭下拉菜单
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userAvatar.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }
});
</script> 