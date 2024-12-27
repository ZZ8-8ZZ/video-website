<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$current_page = 'activation_codes';
$page_title = '激活码管理';

// 检查并更新表结构
try {
    $pdo->query("SELECT batch FROM activation_codes LIMIT 1");
} catch (PDOException $e) {
    // batch 字段不存在,添加字段
    $pdo->exec("ALTER TABLE activation_codes ADD COLUMN batch VARCHAR(14) AFTER code");
    $pdo->exec("ALTER TABLE activation_codes ADD COLUMN used_at TIMESTAMP NULL DEFAULT NULL AFTER is_used");
}

// 生成激活码
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $count = isset($_POST['count']) ? min(intval($_POST['count']), 100) : 1;
    $batch = date('YmdHis'); // 生成批次号
    
    try {
        $pdo->beginTransaction();
        
        // 准备插入语句
        $stmt = $pdo->prepare("INSERT INTO activation_codes (code, batch) VALUES (?, ?)");
        
        // 批量生成激活码
        for ($i = 0; $i < $count; $i++) {
            // 生成16位大写字母和数字组合的激活码
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true) . $i), 0, 16));
            
            // 确保激活码唯一
            while (true) {
                try {
                    $stmt->execute([$code, $batch]);
                    break; // 如果成功插入，跳出循环
                } catch (PDOException $e) {
                    if ($e->getCode() == '23000') { // 如果是唯一键冲突
                        $code = strtoupper(substr(md5(uniqid(mt_rand(), true) . $i), 0, 16));
                        continue; // 重新生成激活码
                    }
                    throw $e; // 其他错误则抛出
                }
            }
        }
        
        $pdo->commit();
        $success = "成功生成 {$count} 个激活码";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "生成激活码失败：" . $e->getMessage();
    }
}

// 获取筛选条件
$status = isset($_GET['status']) ? $_GET['status'] : 'unused';
$batch = isset($_GET['batch']) ? $_GET['batch'] : '';

// 构建查询条件
$where = [];
$params = [];

if ($status === 'unused') {
    $where[] = "ac.is_used = 0";
} elseif ($status === 'used') {
    $where[] = "ac.is_used = 1";
}

if ($batch) {
    $where[] = "ac.batch = ?";
    $params[] = $batch;
}

$where_str = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取批次列表
try {
    $batches = $pdo->query("SELECT DISTINCT batch FROM activation_codes WHERE batch IS NOT NULL ORDER BY batch DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $batches = [];
}

// 获取激活码列表
$stmt = $pdo->prepare("
    SELECT ac.*, u.username 
    FROM activation_codes ac 
    LEFT JOIN users u ON ac.used_by = u.id 
    {$where_str}
    ORDER BY ac.created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$codes = $stmt->fetchAll();

// 定义模态框内容
$page_modals = <<<HTML
<!-- 生成激活码模态框 -->
<div class="modal fade" id="generateModal" data-backdrop="static" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">生成激活码</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="codeCount">生成数量</label>
                        <input type="number" id="codeCount" name="count" class="form-control" 
                               min="1" max="100" value="1" required>
                        <small class="form-text text-muted">单次最多可生成100个激活码</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确定生成</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 复制成功提示框 -->
<div class="toast" id="copyToast" role="alert" aria-live="assertive" aria-atomic="true" 
     style="position: fixed; top: 20px; right: 20px; z-index: 1060;">
    <div class="toast-header">
        <i class="fas fa-check-circle text-success mr-2"></i>
        <strong class="mr-auto">提示</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body">
        激活码已复制到剪贴板
    </div>
</div>
HTML;

require 'layout/header.php';
?>

<div class="page-header">
    <h2>激活码管理</h2>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#generateModal">
        <i class="fas fa-plus"></i> 生成激活码
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <form class="filter-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="all">全部状态</option>
                        <option value="unused" <?php echo $status === 'unused' ? 'selected' : ''; ?>>未使用</option>
                        <option value="used" <?php echo $status === 'used' ? 'selected' : ''; ?>>已使用</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select name="batch" class="form-control" onchange="this.form.submit()">
                        <option value="">全部批次</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b; ?>" <?php echo $batch === $b ? 'selected' : ''; ?>>
                            <?php echo date('Y-m-d H:i:s', strtotime($b)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>激活码</th>
                <th>批次</th>
                <th>状态</th>
                <th>使用者</th>
                <th>生成时间</th>
                <th>使用时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($codes as $code): ?>
            <tr>
                <td>
                    <span class="code-text"><?php echo htmlspecialchars($code['code']); ?></span>
                </td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($code['batch'])); ?></td>
                <td>
                    <span class="status-badge <?php echo $code['is_used'] ? 'used' : 'unused'; ?>">
                        <?php echo $code['is_used'] ? '已使用' : '未使用'; ?>
                    </span>
                </td>
                <td><?php echo $code['username'] ? htmlspecialchars($code['username']) : '-'; ?></td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($code['created_at'])); ?></td>
                <td><?php echo $code['used_at'] ? date('Y-m-d H:i:s', strtotime($code['used_at'])) : '-'; ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-info copy-btn" 
                            data-code="<?php echo htmlspecialchars($code['code']); ?>">
                        <i class="fas fa-copy"></i> 复制
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // 初始化模态框
    $('#generateModal').on('shown.bs.modal', function () {
        $(this).find('#codeCount').focus();
    });

    // 初始化提示框
    $('.toast').toast({
        delay: 2000,
        animation: true
    });

    // 复制激活码功能
    $('.copy-btn').click(function() {
        const code = $(this).data('code');
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(code).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // 显示提示
        $('#copyToast').toast('show');
    });
});
</script>

<style>
/* 添加一些样式 */
.code-text {
    font-family: monospace;
    padding: 2px 4px;
    background: #f8f9fa;
    border-radius: 3px;
}

.copy-btn {
    padding: 0.25rem 0.5rem;
}

.toast {
    background-color: white;
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, .1);
}
</style>

</body>
</html> 