<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'dashboard';
$page_title = '控制台';

// 获取统计数据
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_videos' => $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn(),
    'total_series' => $pdo->query("SELECT COUNT(*) FROM series")->fetchColumn(),
    'total_views' => $pdo->query("SELECT COUNT(*) FROM watch_history")->fetchColumn()
];

// 获取最近注册用户
$recent_users = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// 获取最新上传的内容
$recent_content = $pdo->query("
    SELECT 'video' as type, title, created_at FROM videos
    UNION ALL
    SELECT 'series' as type, title, created_at FROM series
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>控制台</h2>
    <p>管理员：admin</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>总用户数</h3>
        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
    </div>
    <div class="stat-card">
        <h3>视频总数</h3>
        <div class="stat-value"><?php echo number_format($stats['total_videos']); ?></div>
    </div>
    <div class="stat-card">
        <h3>剧集总数</h3>
        <div class="stat-value"><?php echo number_format($stats['total_series']); ?></div>
    </div>
    <div class="stat-card">
        <h3>总播放量</h3>
        <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
    </div>
</div>

<div class="card">
    <h3>最近注册用户</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>用户名</th>
                <th>邮箱</th>
                <th>注册时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>最新内容</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>类型</th>
                <th>标题</th>
                <th>上传时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_content as $content): ?>
            <tr>
                <td><?php echo $content['type'] === 'video' ? '视频' : '剧集'; ?></td>
                <td><?php echo htmlspecialchars($content['title']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($content['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'layout/footer.php'; ?>