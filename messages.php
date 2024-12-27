<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'inbox';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 获取消息列表
$where = match($type) {
    'sent' => "m.from_user_id = ?",
    'system' => "m.type = 'system'",
    default => "m.to_user_id = ? AND m.type != 'system'"
};

$params = [$type === 'sent' ? $_SESSION['user_id'] : $_SESSION['user_id']];

$stmt = $pdo->prepare("
    SELECT m.*, 
           u1.username as from_username,
           u2.username as to_username
    FROM messages m
    LEFT JOIN users u1 ON m.from_user_id = u1.id
    LEFT JOIN users u2 ON m.to_user_id = u2.id
    WHERE $where
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
");

$stmt->execute([...$params, $per_page, $offset]);
$messages = $stmt->fetchAll();

// 获取未读消息数
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages 
    WHERE to_user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>我的消息</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="message-container">
        <div class="message-sidebar">
            <div class="message-stats">
                <span class="unread-count">未读消息: <?php echo $unread_count; ?></span>
            </div>
            
            <div class="message-nav">
                <a href="?type=inbox" class="<?php echo $type === 'inbox' ? 'active' : ''; ?>">收件箱</a>
                <a href="?type=sent" class="<?php echo $type === 'sent' ? 'active' : ''; ?>">已发送</a>
                <a href="?type=system" class="<?php echo $type === 'system' ? 'active' : ''; ?>">系统消息</a>
            </div>
            
            <button onclick="showNewMessage()" class="new-message-btn">发送新消息</button>
        </div>
        
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
                <div class="message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?>" 
                     onclick="viewMessage(<?php echo $message['id']; ?>)">
                    <div class="message-header">
                        <?php if ($type === 'sent'): ?>
                            <span class="to">发送给: <?php echo h($message['to_username']); ?></span>
                        <?php else: ?>
                            <span class="from">
                                <?php echo $message['type'] === 'system' ? '系统消息' : h($message['from_username']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="time"><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></span>
                    </div>
                    <div class="message-title"><?php echo h($message['title']); ?></div>
                    <div class="message-preview"><?php echo h(mb_substr($message['content'], 0, 100)); ?>...</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 新消息对话框 -->
    <div id="new-message-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>发送新消息</h3>
            <form id="new-message-form" onsubmit="return sendMessage(event)">
                <div class="form-group">
                    <label>发送给：</label>
                    <input type="text" id="to-username" required>
                </div>
                <div class="form-group">
                    <label>标题：</label>
                    <input type="text" id="message-title" required>
                </div>
                <div class="form-group">
                    <label>内容：</label>
                    <textarea id="message-content" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit">发送</button>
                    <button type="button" onclick="closeModal()">取消</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showNewMessage() {
        document.getElementById('new-message-modal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('new-message-modal').style.display = 'none';
    }
    
    function sendMessage(event) {
        event.preventDefault();
        
        const data = {
            to_username: document.getElementById('to-username').value,
            title: document.getElementById('message-title').value,
            content: document.getElementById('message-content').value
        };
        
        fetch('ajax/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                alert(data.message || '发送失败');
            }
        });
        
        return false;
    }
    
    function viewMessage(messageId) {
        location.href = `view_message.php?id=${messageId}`;
    }
    </script>
</body>
</html> 