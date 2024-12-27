<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($page_title) ? $page_title . ' - 管理后台' : '管理后台'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>管理后台</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>控制台
                    </a></li>
                    <li><a href="users.php" class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>用户管理
                    </a></li>
                    <li><a href="videos.php" class="<?php echo $current_page === 'videos' ? 'active' : ''; ?>">
                        <i class="fas fa-video"></i>视频管理
                    </a></li>
                    <li><a href="series.php" class="<?php echo $current_page === 'series' ? 'active' : ''; ?>">
                        <i class="fas fa-film"></i>剧集管理
                    </a></li>
                    <li><a href="categories.php" class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>分类管理
                    </a></li>
                    <li><a href="comments.php" class="<?php echo $current_page === 'comments' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>评论管理
                    </a></li>
                    <li><a href="statistics.php" class="<?php echo $current_page === 'statistics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>数据统计
                    </a></li>
                    <li><a href="settings.php" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>系统设置
                    </a></li>
                    <li><a href="activation_codes.php" class="<?php echo $current_page === 'activation_codes' ? 'active' : ''; ?>">
                        <i class="fas fa-key"></i>激活码管理
                    </a></li>
                    <li><a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>退出登录
                    </a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">