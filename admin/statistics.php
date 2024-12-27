<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'statistics';
$page_title = '数据统计';

// 获取时间范围
$range = isset($_GET['range']) ? $_GET['range'] : '7days';
$ranges = [
    'today' => '今天',
    'yesterday' => '昨天',
    '7days' => '最近7天',
    '30days' => '最近30天',
    'this_month' => '本月',
    'last_month' => '上月'
];

// 构建时间条件
$time_condition = match($range) {
    'today' => "DATE(created_at) = CURRENT_DATE",
    'yesterday' => "DATE(created_at) = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)",
    '7days' => "created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)",
    '30days' => "created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)",
    'this_month' => "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')",
    'last_month' => "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH), '%Y-%m')",
    default => "1=1"
};

// 获取统计数据
$stats = [
    'new_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE $time_condition")->fetchColumn(),
    'new_videos' => $pdo->query("SELECT COUNT(*) FROM videos WHERE $time_condition")->fetchColumn(),
    'total_views' => $pdo->query("SELECT COUNT(*) FROM watch_history WHERE $time_condition")->fetchColumn(),
    'total_comments' => $pdo->query("SELECT COUNT(*) FROM comments WHERE $time_condition")->fetchColumn()
];

// 获取每日数据趋势
$daily_stats = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM watch_history
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll(PDO::FETCH_KEY_PAIR);

// 获取分类统计
$category_stats = $pdo->query("
    SELECT 
        c.name,
        COUNT(v.id) as video_count,
        SUM(v.views) as view_count
    FROM categories c
    LEFT JOIN videos v ON c.id = v.category_id
    GROUP BY c.id
    ORDER BY view_count DESC
")->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>数据统计</h2>
    <div class="range-selector">
        <?php foreach ($ranges as $key => $label): ?>
            <a href="?range=<?php echo $key; ?>" 
               class="btn <?php echo $range === $key ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['new_users']); ?></div>
            <div class="stat-label">新增用户</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-video"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['new_videos']); ?></div>
            <div class="stat-label">新增视频</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
            <div class="stat-label">播放量</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['total_comments']); ?></div>
            <div class="stat-label">评论数</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>播放量趋势</h3>
            </div>
            <div class="card-body">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3>分类统计</h3>
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// 初始化播放量趋势图表
new Chart(document.getElementById('viewsChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($daily_stats)); ?>,
        datasets: [{
            label: '每日播放量',
            data: <?php echo json_encode(array_values($daily_stats)); ?>,
            borderColor: '#1a73e8',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// 初始化分类统计图表
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($category_stats, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($category_stats, 'view_count')); ?>,
            backgroundColor: [
                '#1a73e8', '#34a853', '#fbbc04', '#ea4335',
                '#4285f4', '#0f9d58', '#f4b400', '#db4437'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require 'layout/footer.php'; ?> 