<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$series_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$series_id) {
    header("Location: index.php");
    exit;
}

// 获取剧集信息
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count,
           COUNT(DISTINCT v.id) as episode_count
    FROM series s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN videos v ON s.id = v.series_id
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE s.id = :series_id
    GROUP BY s.id
");
$stmt->execute([':series_id' => $series_id]);
$series = $stmt->fetch();

if (!$series) {
    header("Location: index.php");
    exit;
}

// 获取该剧集的所有视频
$stmt = $pdo->prepare("
    SELECT v.*, COUNT(DISTINCT wh.user_id) as view_count
    FROM videos v
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE v.series_id = :series_id
    GROUP BY v.id
    ORDER BY v.episode_number ASC
");
$stmt->execute([':series_id' => $series_id]);
$episodes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($series['title']); ?> - 详情</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="detail-container">
        <div class="detail-header">
            <div class="detail-cover">
                <img src="<?php echo htmlspecialchars($series['cover_image']); ?>" 
                     alt="<?php echo htmlspecialchars($series['title']); ?>">
            </div>
            <div class="detail-info">
                <h1><?php echo htmlspecialchars($series['title']); ?></h1>
                <div class="detail-meta">
                    <span class="category"><?php echo htmlspecialchars($series['category_name']); ?></span>
                    <span class="area"><?php echo htmlspecialchars($series['area']); ?></span>
                    <span class="year"><?php echo $series['release_year']; ?></span>
                    <span class="status"><?php echo htmlspecialchars($series['status']); ?></span>
                </div>
                <div class="detail-stats">
                    <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($series['view_count']); ?>次观看</span>
                    <span class="episodes"><i class="fas fa-film"></i> 共<?php echo $series['episode_count']; ?>集</span>
                </div>
                <div class="detail-crew">
                    <p><strong>导演：</strong><?php echo htmlspecialchars($series['director'] ?? '未知'); ?></p>
                    <p><strong>主演：</strong><?php echo htmlspecialchars($series['actors'] ?? '未知'); ?></p>
                </div>
                <div class="detail-description">
                    <h3>剧情简介</h3>
                    <p><?php echo nl2br(htmlspecialchars($series['description'] ?? '暂无简介')); ?></p>
                </div>
                <?php if (!empty($episodes)): ?>
                <a href="play.php?id=<?php echo $episodes[0]['id']; ?>" class="watch-btn">立即观看</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($episodes)): ?>
        <div class="episodes-section">
            <h2>剧集列表</h2>
            <div class="episodes-grid">
                <?php foreach ($episodes as $episode): ?>
                    <a href="play.php?id=<?php echo $episode['id']; ?>" class="episode-item">
                        <span class="episode-number">第<?php echo $episode['episode_number']; ?>集</span>
                        <span class="episode-views"><?php echo number_format($episode['view_count']); ?>次观看</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 