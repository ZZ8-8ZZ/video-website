<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'videos';
$page_title = '视频管理';

// 处理删除视频
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    if ($stmt->execute([intval($_POST['delete_id'])])) {
        $success = "视频已删除";
    }
}

// 获取分类列表（用于筛选）
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'movie' ORDER BY name")->fetchAll();

// 构建查询条件
$where = [];
$params = [];

if (isset($_GET['category']) && $_GET['category']) {
    $where[] = "category_id = ?";
    $params[] = intval($_GET['category']);
}

if (isset($_GET['status']) && $_GET['status']) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['keyword']) && $_GET['keyword']) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%{$_GET['keyword']}%";
    $params[] = "%{$_GET['keyword']}%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 获取视频列表
$stmt = $pdo->prepare("
    SELECT v.*, c.name as category_name 
    FROM videos v 
    LEFT JOIN categories c ON v.category_id = c.id 
    {$where_clause}
    ORDER BY v.created_at DESC
");
$stmt->execute($params);
$videos = $stmt->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>视频管理</h2>
    <a href="add_video.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> 添加视频
    </a>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <form class="filter-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="category" class="form-control">
                        <option value="">所有分类</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select name="status" class="form-control">
                        <option value="">所有状态</option>
                        <option value="public" <?php echo isset($_GET['status']) && $_GET['status'] == 'public' ? 'selected' : ''; ?>>公开</option>
                        <option value="private" <?php echo isset($_GET['status']) && $_GET['status'] == 'private' ? 'selected' : ''; ?>>私有</option>
                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>待审核</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <input type="text" name="keyword" class="form-control" placeholder="搜索标题或描述..." 
                           value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 搜索
                    </button>
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
                <th>时长</th>
                <th>播放量</th>
                <th>状态</th>
                <th>上传时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($videos as $video): ?>
            <tr>
                <td><?php echo $video['id']; ?></td>
                <td>
                    <?php if ($video['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($video['cover_image']); ?>" alt="封面" class="video-cover">
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($video['title']); ?></td>
                <td><?php echo htmlspecialchars($video['category_name']); ?></td>
                <td><?php echo $video['duration'] ? gmdate("H:i:s", $video['duration']) : '-'; ?></td>
                <td><?php echo number_format($video['views']); ?></td>
                <td>
                    <span class="status-badge <?php echo $video['status']; ?>">
                        <?php echo $video['status']; ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($video['created_at'])); ?></td>
                <td>
                    <a href="edit_video.php?id=<?php echo $video['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                    <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除这个视频吗？');">
                        <input type="hidden" name="delete_id" value="<?php echo $video['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'layout/footer.php'; ?>