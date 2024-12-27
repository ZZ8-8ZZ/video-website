<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'categories';
$page_title = '分类管理';

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        if ($stmt->execute([$name, $type])) {
            $success = "分类添加成功";
        }
        
    } elseif ($_POST['action'] == 'delete') {
        $id = intval($_POST['category_id']);
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "分类删除成功";
        }
    }
}

// 获取分类列表
$categories = $pdo->query("SELECT * FROM categories ORDER BY type, sort_order")->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>分类管理</h2>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus"></i> 添加分类
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>类型</th>
                <th>排序</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo $category['id']; ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo $category['type']; ?></td>
                <td><?php echo $category['sort_order']; ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editCategory(<?php echo $category['id']; ?>)">
                        <i class="fas fa-edit"></i> 编辑
                    </button>
                    <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除这个分类吗？');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
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

<!-- 添加分类模态框 -->
<div class="modal" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h4 class="modal-title">添加分类</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>分类名称</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>分类类型</label>
                        <select name="type" class="form-control" required>
                            <option value="movie">电影</option>
                            <option value="tv">电视剧</option>
                            <option value="variety">综艺</option>
                            <option value="anime">动漫</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'layout/footer.php'; ?> 