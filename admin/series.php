<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'series';
$page_title = '剧集管理';

// 获取筛选条件
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 构建查询条件
$where = [];
$params = [];

if ($category) {
    $where[] = "s.category_id = ?";
    $params[] = $category;
}

if ($status) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

$where_str = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取分类列表
$categories = $pdo->query("SELECT * FROM categories WHERE type IN ('tv', 'anime') ORDER BY name")->fetchAll();

// 获取剧集列表
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(e.id) as episode_count
    FROM series s 
    LEFT JOIN categories c ON s.category_id = c.id 
    LEFT JOIN episodes e ON s.id = e.series_id
    {$where_str}
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$series_list = $stmt->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>剧集管理</h2>
    <a href="add_series.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> 添加剧集
    </a>
</div>

<div class="card">
    <div class="card-header">
        <form class="filter-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="category" class="form-control" onchange="this.form.submit()">
                        <option value="">全部分类</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">全部状态</option>
                        <option value="连载中" <?php echo $status === '连载中' ? 'selected' : ''; ?>>连载中</option>
                        <option value="已完结" <?php echo $status === '已完结' ? 'selected' : ''; ?>>已完结</option>
                        <option value="未开播" <?php echo $status === '未开播' ? 'selected' : ''; ?>>未开播</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>封面</th>
                <th>标题</th>
                <th>分类</th>
                <th>集数</th>
                <th>状态</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($series_list as $series): ?>
            <tr>
                <td><?php echo $series['id']; ?></td>
                <td>
                    <?php if ($series['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($series['cover_image']); ?>" alt="封面" class="series-cover">
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($series['title']); ?></td>
                <td><?php echo htmlspecialchars($series['category_name']); ?></td>
                <td>
                    <span class="badge badge-info">
                        <?php echo $series['episode_count']; ?>/<?php echo $series['total_episodes'] ?: '?'; ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge <?php echo strtolower($series['status']); ?>">
                        <?php echo $series['status']; ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($series['updated_at'])); ?></td>
                <td>
                    <a href="edit_series.php?id=<?php echo $series['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                    <a href="manage_episodes.php?series_id=<?php echo $series['id']; ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-list"></i> 管理分集
                    </a>
                    <button onclick="deleteSeries(<?php echo $series['id']; ?>)" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> 删除
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function deleteSeries(id) {
    if (!confirm('确定要删除这个剧集吗？删除后将无法恢复！')) {
        return;
    }
    
    fetch('ajax/delete_series.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '删除失败');
        }
    });
}
</script>

<?php require 'layout/footer.php'; ?>