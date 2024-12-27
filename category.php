<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// 获取筛选条件
$area = isset($_GET['area']) ? $_GET['area'] : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// 构建查询
$where = ["c.type = :type"];
$params = [':type' => $type];

if ($area) {
    $where[] = "s.area = :area";
    $params[':area'] = $area;
}

if ($year) {
    $where[] = "s.release_year = :year";
    $params[':year'] = $year;
}

$where_str = implode(" AND ", $where);

// 排序方式
$order_by = match($sort) {
    'hot' => "view_count DESC",
    'rating' => "rating DESC",
    default => "s.created_at DESC"
};

// 获取总数
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM series s
    JOIN categories c ON s.category_id = c.id
    WHERE $where_str
");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// 获取列表
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count
    FROM series s
    JOIN categories c ON s.category_id = c.id
    LEFT JOIN episodes e ON s.id = e.series_id
    LEFT JOIN watch_history wh ON e.id = wh.video_id
    WHERE $where_str
    GROUP BY s.id
    ORDER BY $order_by
    LIMIT :limit OFFSET :offset
");

// 绑定所有参数
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$series_list = $stmt->fetchAll();

// 获取筛选选项
$areas = $pdo->query("SELECT DISTINCT area FROM series WHERE area != '' ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT release_year FROM series WHERE release_year > 0 ORDER BY release_year DESC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo ucfirst($type); ?> - 分类</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="category-container">
        <div class="filter-bar">
            <div class="filter-group">
                <label>地区：</label>
                <a href="?type=<?php echo $type; ?>" class="<?php echo $area === '' ? 'active' : ''; ?>">全部</a>
                <?php foreach ($areas as $a): ?>
                    <a href="?type=<?php echo $type; ?>&area=<?php echo urlencode($a); ?>" 
                       class="<?php echo $area === $a ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($a); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="filter-group">
                <label>年份：</label>
                <a href="?type=<?php echo $type; ?>" class="<?php echo $year === 0 ? 'active' : ''; ?>">全部</a>
                <?php foreach ($years as $y): ?>
                    <a href="?type=<?php echo $type; ?>&year=<?php echo $y; ?>" 
                       class="<?php echo $year === $y ? 'active' : ''; ?>">
                        <?php echo $y; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sort-group">
                <a href="?type=<?php echo $type; ?>&sort=latest" class="<?php echo $sort === 'latest' ? 'active' : ''; ?>">最新</a>
                <a href="?type=<?php echo $type; ?>&sort=hot" class="<?php echo $sort === 'hot' ? 'active' : ''; ?>">最热</a>
                <a href="?type=<?php echo $type; ?>&sort=rating" class="<?php echo $sort === 'rating' ? 'active' : ''; ?>">评分</a>
            </div>
        </div>
        
        <div class="series-grid">
            <?php foreach ($series_list as $series): ?>
                <div class="series-card">
                    <div class="thumbnail">
                        <img src="<?php echo htmlspecialchars($series['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($series['title']); ?>">
                        <?php if ($series['status'] === '连载中'): ?>
                            <span class="status-badge">连载中</span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($series['title']); ?></h3>
                    <div class="meta">
                        <span class="area"><?php echo htmlspecialchars($series['area']); ?></span>
                        <span class="year"><?php echo $series['release_year']; ?></span>
                    </div>
                    <a href="detail.php?id=<?php echo $series['id']; ?>" class="view-btn">查看详情</a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?type=<?php echo $type; ?>&page=<?php echo $i; ?>" 
                   class="<?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>