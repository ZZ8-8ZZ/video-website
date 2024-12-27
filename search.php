<?php
session_start();
require_once 'config/database.php';

// 获取搜索参数
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 24;

// 构建基础查询
$where_conditions = [];
$params = [];

// 关键词搜索
if ($keyword) {
    $where_conditions[] = "(v.title LIKE ? OR v.description LIKE ?)";
    $keyword_param = "%{$keyword}%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
}

// 类型筛选
if ($type !== 'all') {
    $where_conditions[] = "c.type = ?";
    $params[] = $type;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 排序方式
$order_by = match($sort) {
    'views' => 'v.views DESC',
    'rating' => 'v.rating DESC',
    default => 'v.created_at DESC'
};

try {
    // 获取总数
    $count_sql = "SELECT COUNT(*) 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                $where_clause";

    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // 限制页码范围
    $page = min($page, max(1, $total_pages));

    // 获取搜索结果
    $offset = ($page - 1) * $per_page;

    $sql = "SELECT 
            v.id,
            v.title,
            v.cover_image,
            v.description,
            v.views,
            v.rating,
            v.created_at,
            COALESCE(c.name, '未分类') as category_name,
            COALESCE(c.type, 'other') as category_type
        FROM videos v
        LEFT JOIN categories c ON v.category_id = c.id
        $where_clause
        ORDER BY $order_by
        LIMIT $per_page OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 调试信息
    $debug = [
        'count_sql' => $count_sql,
        'search_sql' => $sql,
        'params' => $params,
        'total' => $total,
        'page' => $page,
        'offset' => $offset
    ];

} catch (PDOException $e) {
    $error = "搜索时发生错误: " . $e->getMessage();
    $results = [];
    $total_pages = 0;
    $debug = [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// 如果是AJAX请求，返回JSON数据
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => !isset($error),
        'data' => $results ?? [],
        'total' => $total ?? 0,
        'debug' => $debug ?? null,
        'error' => $error ?? null
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索结果 - <?php echo htmlspecialchars($keyword); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="search-container">
    <div class="search-header">
        <h1>搜索结果: <?php echo htmlspecialchars($keyword); ?></h1>
        
        <form class="search-filters" method="GET" action="search.php">
            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
            
            <div class="search-filter-group">
                <label for="type">类型:</label>
                <select name="type" id="type">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>全部</option>
                    <option value="movie" <?php echo $type === 'movie' ? 'selected' : ''; ?>>电影</option>
                    <option value="tv" <?php echo $type === 'tv' ? 'selected' : ''; ?>>电视剧</option>
                    <option value="variety" <?php echo $type === 'variety' ? 'selected' : ''; ?>>综艺</option>
                    <option value="anime" <?php echo $type === 'anime' ? 'selected' : ''; ?>>动漫</option>
                </select>
            </div>
            
            <div class="search-filter-group">
                <label for="sort">排序:</label>
                <select name="sort" id="sort">
                    <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>最新</option>
                    <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>最多观看</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>最高评分</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">应用筛选</button>
        </form>
    </div>

    <?php if (isset($error)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
        <?php if (isset($debug)): ?>
        <pre class="debug-info" style="display:none;">
            <?php print_r($debug); ?>
        </pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($results)): ?>
    <div class="no-results">
        <p>未找到相关结果</p>
        <?php if (isset($debug)): ?>
        <pre class="debug-info" style="display:none;">
            <?php print_r($debug); ?>
        </pre>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="search-results">
        <?php foreach ($results as $result): ?>
        <a href="play.php?id=<?php echo $result['id']; ?>" class="search-result-card">
            <div class="search-result-image">
                <img src="<?php echo htmlspecialchars($result['cover_image']); ?>" 
                     alt="<?php echo htmlspecialchars($result['title']); ?>">
            </div>
            <div class="search-result-info">
                <h3 class="search-result-title"><?php echo htmlspecialchars($result['title']); ?></h3>
                <div class="search-result-meta">
                    <span class="search-result-category">
                        <?php echo htmlspecialchars($result['category_name']); ?>
                    </span>
                    <span><?php echo number_format($result['views']); ?> 次观看</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="search-pagination">
        <?php if ($page > 1): ?>
        <a href="?keyword=<?php echo urlencode($keyword); ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page-1; ?>">上一页</a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
            <a href="?keyword=<?php echo urlencode($keyword); ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=1">1</a>
            <?php if ($start_page > 2): ?>
            <span>...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?keyword=<?php echo urlencode($keyword); ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>" 
           class="<?php echo $i === $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
            <span>...</span>
            <?php endif; ?>
            <a href="?keyword=<?php echo urlencode($keyword); ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?keyword=<?php echo urlencode($keyword); ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page+1; ?>">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// 添加快捷键显示调试信息
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        document.querySelectorAll('.debug-info').forEach(el => {
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        });
    }
});
</script>

</body>
</html>