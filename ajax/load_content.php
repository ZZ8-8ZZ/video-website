<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$type = $_GET['type'] ?? '';
$page = intval($_GET['page'] ?? 1);
$category = $_GET['category'] ?? 'all';
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    $where_clause = "";
    if ($category !== 'all') {
        $where_clause = "AND s.category_id = :category_id";
    }

    // 根据不同类型构建不同的SQL查询
    switch ($type) {
        case 'movie':
            $sql = "SELECT s.*, c.name as category_name,
                   (SELECT v.id FROM videos v WHERE v.series_id = s.id AND v.episode_number = 1) as video_id,
                   (SELECT COUNT(*) FROM videos v WHERE v.series_id = s.id) as episode_count
            FROM series s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.type = 'movie' AND c.type = 'movie' 
            {$where_clause}
            ORDER BY s.views DESC, s.created_at DESC
            LIMIT :offset, :limit";
            break;

        case 'tv':
            $sql = "SELECT s.*, c.name as category_name,
                   s.total_episodes,
                   COALESCE(s.current_episode, 0) as current_episode,
                   (SELECT v.id FROM videos v WHERE v.series_id = s.id AND v.episode_number = 1) as video_id,
                   (SELECT COUNT(*) FROM videos v WHERE v.series_id = s.id) as episode_count
            FROM series s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.type = 'tv' AND c.type = 'tv'
            {$where_clause}
            ORDER BY s.created_at DESC
            LIMIT :offset, :limit";
            break;

        case 'variety':
            $sql = "SELECT s.*, c.name as category_name,
                   (SELECT v.id FROM videos v WHERE v.series_id = s.id AND v.episode_number = 1) as video_id,
                   (SELECT COUNT(*) FROM videos v WHERE v.series_id = s.id) as episode_count
            FROM series s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.type = 'variety'
            {$where_clause}
            ORDER BY s.views DESC
            LIMIT :offset, :limit";
            break;

        case 'anime':
            $sql = "SELECT s.*, c.name as category_name,
                   (SELECT v.id FROM videos v WHERE v.series_id = s.id AND v.episode_number = 1) as video_id,
                   (SELECT COUNT(*) FROM videos v WHERE v.series_id = s.id) as episode_count
            FROM series s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.type = 'anime'
            {$where_clause}
            ORDER BY s.rating DESC, s.views DESC
            LIMIT :offset, :limit";
            break;

        default:
            echo json_encode(['success' => false, 'message' => '无效的内容类型']);
            exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($category !== 'all') {
        $stmt->bindValue(':category_id', $category, PDO::PARAM_INT);
    }
    $stmt->execute();
    $items = $stmt->fetchAll();

    // 获取总数以计算分页
    $count_sql = "SELECT COUNT(*) as total FROM series s 
                  LEFT JOIN categories c ON s.category_id = c.id
                  WHERE s.type = :type " . ($category !== 'all' ? "AND s.category_id = :category_id" : "");
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->bindValue(':type', $type);
    if ($category !== 'all') {
        $count_stmt->bindValue(':category_id', $category);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // 生成HTML
    ob_start();
    if (empty($items)) {
        ?>
        <div class="no-content">
            <i class="fas fa-inbox"></i>
            <p>暂无内容</p>
        </div>
        <?php
    } else {
        foreach ($items as $item): ?>
            <div class="video-card">
                <div class="video-thumbnail">
                    <img src="<?php echo htmlspecialchars($item['cover_image'] ?? DEFAULT_COVER); ?>" 
                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <span class="duration">
                        <?php if ($type === 'variety'): ?>
                            <?php echo $item['episode_count']; ?>期
                        <?php elseif ($type === 'tv'): ?>
                            <?php echo $item['episode_count'] . '/' . $item['total_episodes']; ?>集
                        <?php else: ?>
                            <?php echo $item['episode_count']; ?>集
                        <?php endif; ?>
                    </span>
                    <a href="play.php?id=<?php echo $item['video_id']; ?>" class="play-btn">
                        <i class="fas fa-play"></i>
                    </a>
                </div>
                <div class="video-info">
                    <h3 class="video-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <div class="video-meta">
                        <span class="category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($item['views']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach;
    }
    $html = ob_get_clean();

    // 生成分页HTML
    $pagination = '';
    if ($total_pages > 1) {
        $pagination .= '<div class="pagination">';
        if ($page > 1) {
            $pagination .= '<a href="#" class="page-btn" data-page="'.($page-1).'">上一页</a>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $pagination .= '<span class="current">'.$i.'</span>';
            } else {
                $pagination .= '<a href="#" class="page-btn" data-page="'.$i.'">'.$i.'</a>';
            }
        }
        if ($page < $total_pages) {
            $pagination .= '<a href="#" class="page-btn" data-page="'.($page+1).'">下一页</a>';
        }
        $pagination .= '</div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'pagination' => $pagination
    ]);

} catch (PDOException $e) {
    error_log("Error in load_content.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '加载失败，请重试'
    ]);
} 