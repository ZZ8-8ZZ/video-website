<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'videos';
$page_title = '添加视频';

// 获取分类列表
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'movie' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $source_url = trim($_POST['source_url']);
    $play_url = trim($_POST['play_url']);
    
    // 处理封面图片上传
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $upload_dir = '../uploads/covers/';
        $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_ext;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $file_name)) {
            $cover_image = 'uploads/covers/' . $file_name;
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO videos (title, description, category_id, cover_image, source_url, play_url, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if ($stmt->execute([$title, $description, $category_id, $cover_image ?? null, $source_url, $play_url])) {
        $success = "视频添加成功";
    }
}

require 'layout/header.php';
?>

<div class="page-header">
    <h2>添加视频</h2>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <form method="post" enctype="multipart/form-data" class="form">
        <div class="form-group">
            <label>标题</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>分类</label>
            <select name="category_id" class="form-control" required>
                <option value="">选择分类</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>封面图片</label>
            <input type="file" name="cover_image" class="form-control" accept="image/*">
        </div>
        
        <div class="form-group">
            <label>视频描述</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label>来源地址</label>
            <input type="url" name="source_url" class="form-control">
        </div>
        
        <div class="form-group">
            <label>播放地址</label>
            <input type="url" name="play_url" class="form-control" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="videos.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> 取消
            </a>
        </div>
    </form>
</div>

<?php require 'layout/footer.php'; ?> 