<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

// 构建时间范围条件
$time_condition = match($period) {
    'day' => "AND wh.watch_time >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)",
    'week' => "AND wh.watch_time >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)",
    'month' => "AND wh.watch_time >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)",
    default => ""
};

// 构建分类条件
$category_condition = $type !== 'all' ? "AND c.type = ?" : "";
$params = $type !== 'all' ? [$type] : [];

// 获取排行榜数据
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN e.id IS NOT NULL THEN s.title
            ELSE v.title 
        END as title,
        CASE 
            WHEN e.id IS NOT NULL THEN s.cover_image
            ELSE v.cover_image 
        END as cover_image,
        CASE 
            WHEN e.id IS NOT NULL THEN CONCAT('series/', s.id)
            ELSE CONCAT('video/', v.id)
        END as link,
        COUNT(DISTINCT wh.user_id) as view_count,
        c.name as category_name,
        c.type as category_type
    FROM watch_history wh
    LEFT JOIN episodes e ON wh.video_id = e.id
    LEFT JOIN series s ON e.series_id = s.id
    LEFT JOIN videos v ON wh.video_id = v.id
    LEFT JOIN categories c ON COALESCE(s.category_id, v.category_id) = c.id
    WHERE 1=1 
    $time_condition
    $category_condition
    GROUP BY COALESCE(s.id, v.id)
    ORDER BY view_count DESC
    LIMIT 50
");
$stmt->execute($params);
$rankings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>排行榜</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="ranking-container">
        <div class="ranking-header">
            <h1>热播排行榜</h1>
            
            <div class="ranking-filters">
                <div class="type-filter">
                    <a href="?type=all" class="<?php echo $type === 'all' ? 'active' : ''; ?>">全部</a>
                    <a href="?type=movie" class="<?php echo $type === 'movie' ? 'active' : ''; ?>">电影</a>
                    <a href="?type=tv" class="<?php echo $type === 'tv' ? 'active' : ''; ?>">电视剧</a>
                    <a href="?type=variety" class="<?php echo $type === 'variety' ? 'active' : ''; ?>">综艺</a>
                    <a href="?type=anime" class="<?php echo $type === 'anime' ? 'active' : ''; ?>">动漫</a>
                </div>
                
                <div class="period-filter">
                    <a href="?period=day" class="<?php echo $period === 'day' ? 'active' : ''; ?>">日榜</a>
                    <a href="?period=week" class="<?php echo $period === 'week' ? 'active' : ''; ?>">周榜</a>
                    <a href="?period=month" class="<?php echo $period === 'month' ? 'active' : ''; ?>">月榜</a>
                    <a href="?period=all" class="<?php echo $period === 'all' ? 'active' : ''; ?>">总榜</a>
                </div>
            </div>
        </div>
        
        <div class="ranking-list">
            <?php foreach ($rankings as $index => $item): ?>
                <div class="ranking-item">
                    <div class="rank-number <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                        <?php echo $index + 1; ?>
                    </div>
                    
                    <div class="item-cover">
                        <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                    </div>
                    
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="meta">
                            <span class="category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <span class="views"><?php echo number_format($item['view_count']); ?>人观看</span>
                        </div>
                    </div>
                    
                    <a href="detail.php?<?php echo strpos($item['link'], 'series/') === 0 ? 
                        'type=series&id=' . substr($item['link'], 7) : 
                        'id=' . substr($item['link'], 6); ?>" 
                       class="view-btn">查看详情</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>