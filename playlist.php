<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$playlist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_playlist'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO playlists (user_id, name, description, is_public)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $name, $description, $is_public]);
        header("Location: playlist.php");
        exit;
    }
}

// 获取用户的播放列表
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(pi.id) as item_count
    FROM playlists p
    LEFT JOIN playlist_items pi ON p.id = pi.playlist_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$playlists = $stmt->fetchAll();

// 如果查看特定播放列表
if ($playlist_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username
        FROM playlists p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ? AND (p.user_id = ? OR p.is_public = 1)
    ");
    $stmt->execute([$playlist_id, $_SESSION['user_id']]);
    $playlist = $stmt->fetch();
    
    if ($playlist) {
        // 获取播放列表内容
        $stmt = $pdo->prepare("
            SELECT pi.*, 
                   CASE 
                       WHEN pi.content_type = 'series' THEN s.title 
                       ELSE v.title 
                   END as title,
                   CASE 
                       WHEN pi.content_type = 'series' THEN s.cover_image
                       ELSE v.cover_image
                   END as cover_image
            FROM playlist_items pi
            LEFT JOIN series s ON pi.content_type = 'series' AND pi.content_id = s.id
            LEFT JOIN videos v ON pi.content_type = 'video' AND pi.content_id = v.id
            WHERE pi.playlist_id = ?
            ORDER BY pi.sort_order
        ");
        $stmt->execute([$playlist_id]);
        $items = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>我的播放列表</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="playlist-container">
        <?php if ($action === 'create'): ?>
        <div class="create-playlist">
            <h2>创建新播放列表</h2>
            <form method="post" class="playlist-form">
                <input type="hidden" name="create_playlist" value="1">
                <div class="form-group">
                    <label>名称</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>描述</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_public" checked>
                        公开播放列表
                    </label>
                </div>
                <button type="submit">创建</button>
            </form>
        </div>
        
        <?php elseif ($playlist_id && $playlist): ?>
        <div class="playlist-detail">
            <div class="playlist-header">
                <h2><?php echo htmlspecialchars($playlist['name']); ?></h2>
                <div class="meta">
                    <span>创建者：<?php echo htmlspecialchars($playlist['username']); ?></span>
                    <span><?php echo $playlist['is_public'] ? '公开' : '私密'; ?></span>
                </div>
                <p class="description"><?php echo nl2br(htmlspecialchars($playlist['description'])); ?></p>
            </div>
            
            <div class="playlist-items">
                <?php foreach ($items as $item): ?>
                    <div class="playlist-item" data-id="<?php echo $item['id']; ?>">
                        <div class="item-cover">
                            <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        </div>
                        <?php if ($playlist['user_id'] == $_SESSION['user_id']): ?>
                            <button onclick="removeFromPlaylist(<?php echo $item['id']; ?>)" class="remove-btn">
                                删除
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="playlist-list">
            <div class="playlist-header">
                <h2>我的播放列表</h2>
                <a href="?action=create" class="create-btn">创建新播放列表</a>
            </div>
            
            <div class="playlists">
                <?php foreach ($playlists as $list): ?>
                    <div class="playlist-card">
                        <h3><?php echo htmlspecialchars($list['name']); ?></h3>
                        <div class="meta">
                            <span><?php echo $list['item_count']; ?>个内容</span>
                            <span><?php echo $list['is_public'] ? '公开' : '私密'; ?></span>
                        </div>
                        <p class="description"><?php echo nl2br(htmlspecialchars($list['description'])); ?></p>
                        <div class="actions">
                            <a href="?id=<?php echo $list['id']; ?>" class="view-btn">查看</a>
                            <button onclick="deletePlaylist(<?php echo $list['id']; ?>)" class="delete-btn">
                                删除
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // 删除播放列表
    function deletePlaylist(playlistId) {
        if (!confirm('确定要删除这个播放列表吗？')) {
            return;
        }
        
        fetch('ajax/delete_playlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `playlist_id=${playlistId}`
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
    
    // 从播放列表中移除项目
    function removeFromPlaylist(itemId) {
        if (!confirm('确定要从播放列表中移除这个内容吗？')) {
            return;
        }
        
        fetch('ajax/remove_from_playlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`.playlist-item[data-id="${itemId}"]`).remove();
            } else {
                alert(data.message || '移除失败');
            }
        });
    }
    </script>
</body>
</html> 