<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'users';
$page_title = '用户管理';

// 处理用户状态更改
if (isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'disable') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
    } else if ($action === 'enable') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    }
    
    $stmt->execute([$user_id]);
    $success = "操作成功";
}

// 获取用户列表
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>用户管理</h2>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>邮箱</th>
                <th>注册时间</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                <td>
                    <span class="status-badge <?php echo $user['status']; ?>">
                        <?php echo $user['status']; ?>
                    </span>
                </td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php if ($user['status'] === 'active'): ?>
                            <button type="submit" name="action" value="disable" class="btn btn-danger btn-sm">
                                <i class="fas fa-ban"></i> 禁用
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="enable" class="btn btn-primary btn-sm">
                                <i class="fas fa-check"></i> 启用
                            </button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'layout/footer.php'; ?> 