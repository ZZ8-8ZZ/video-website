<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'settings';
$page_title = '系统设置';

// 处理设置更新
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
        $stmt->execute([$value, $key]);
    }
    $success = "设置已更新";
}

// 获取所有设置
$settings = $pdo->query("SELECT * FROM settings ORDER BY id")->fetchAll();

require 'layout/header.php';
?>

<div class="page-header">
    <h2>系统设置</h2>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <form method="post" class="settings-form">
        <?php foreach ($settings as $setting): ?>
        <div class="form-group">
            <label><?php echo htmlspecialchars($setting['key']); ?></label>
            <?php if (strpos($setting['key'], 'enable_') === 0): ?>
                <div class="toggle-switch">
                    <input type="checkbox" name="settings[<?php echo $setting['key']; ?>]" 
                           id="<?php echo $setting['key']; ?>"
                           value="1" <?php echo $setting['value'] ? 'checked' : ''; ?>>
                    <label for="<?php echo $setting['key']; ?>"></label>
                </div>
            <?php elseif (strpos($setting['description'], '多行文本') !== false): ?>
                <textarea name="settings[<?php echo $setting['key']; ?>]" 
                          class="form-control" rows="4"><?php echo htmlspecialchars($setting['value']); ?></textarea>
            <?php else: ?>
                <input type="text" name="settings[<?php echo $setting['key']; ?>]" 
                       class="form-control" value="<?php echo htmlspecialchars($setting['value']); ?>">
            <?php endif; ?>
            <?php if ($setting['description']): ?>
                <small class="form-text text-muted"><?php echo htmlspecialchars($setting['description']); ?></small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> 保存设置
            </button>
        </div>
    </form>
</div>

<?php require 'layout/footer.php'; ?> 