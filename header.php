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
            <a href="/"><?php echo SITE_NAME; ?></a>
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
            <div class="user-avatar" onclick="toggleUserMenu()">
                <img src="<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'https://cdn.jsdelivr.net/gh/ZZ8-8ZZ/image/202408242045317.webp'); ?>" alt="用户头像">
                <?php if ($unread_messages > 0): ?>
                <span class="message-badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="dropdown-menu" id="userMenu">
                <ul>
                    <li><a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">个人中心</a></li>
                    <li><a href="messages.php" class="<?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">站内消息 <?php echo $unread_messages ? "({$unread_messages})" : ''; ?></a></li>
                    <li><a href="favorites.php" class="<?php echo $current_page === 'favorites.php' ? 'active' : ''; ?>">我的收藏</a></li>
                    <li><a href="history.php" class="<?php echo $current_page === 'history.php' ? 'active' : ''; ?>">观看历史</a></li>
                    <li><a href="logout.php">退出登录</a></li>
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
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
}

// 点击其他地方关闭下拉菜单
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu')) {
        document.getElementById('userMenu').classList.remove('show');
    }
});

// 获取未读消息数
function getUnreadMessages() {
    fetch('ajax/get_unread_messages.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.message-badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'message-badge';
                    newBadge.textContent = data.count;
                    document.querySelector('.user-avatar').appendChild(newBadge);
                }
            } else if (badge) {
                badge.remove();
            }
        });
}

// 定期检查未读消息
setInterval(getUnreadMessages, 60000); // 每分钟检查一次
</script> 