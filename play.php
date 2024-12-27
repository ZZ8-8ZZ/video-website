<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 获取视频ID
$video_id = $_GET['id'] ?? 0;

// 获取视频信息
$stmt = $pdo->prepare("
    SELECT v.*, c.name as category_name
    FROM videos v
    LEFT JOIN categories c ON v.category_id = c.id
    WHERE v.id = :video_id
");
$stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
$stmt->execute();
$video = $stmt->fetch();

if (!$video) {
    header("Location: index.php");
    exit;
}

// 获取相关推荐
$stmt = $pdo->prepare("
    SELECT v.*, c.name as category_name
    FROM videos v
    LEFT JOIN categories c ON v.category_id = c.id
    WHERE v.category_id = :category_id
    AND v.id != :video_id
    ORDER BY v.views DESC
    LIMIT 10
");
$stmt->bindParam(':category_id', $video['category_id'], PDO::PARAM_INT);
$stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
$stmt->execute();
$recommended_videos = $stmt->fetchAll();

// 临时使用空数��
$comments = [];

// 更新观看次数
$stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = :video_id");
$stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
$stmt->execute();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($video['title']) . ' - ' . SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="video-detail-container">
        <!-- 左侧主要内容 -->
        <div class="video-main">
            <!-- 视频播放器 -->
            <div class="video-player-wrapper">
                <video class="video-player" controls autoplay>
                    <source src="<?php echo htmlspecialchars($video['play_url']); ?>" type="video/mp4">
                    您的浏览器不支持 HTML5 视频播放。
                </video>
            </div>

            <!-- 视频信息 -->
            <div class="video-info-section">
                <div class="video-title-section">
                    <h1 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h1>
                    <div class="video-stats">
                        <span class="category"><?php echo htmlspecialchars($video['category_name']); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?>次观看</span>
                        <span><i class="fas fa-thumbs-up"></i> <?php echo number_format($video['likes']); ?>点赞</span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($video['created_at'])); ?></span>
                    </div>
                </div>

                <div class="video-actions">
                    <button class="action-btn" id="likeBtn">
                        <i class="fas fa-thumbs-up"></i>
                        <span>点赞</span>
                    </button>
                    <button class="action-btn" id="favoriteBtn">
                        <i class="fas fa-star"></i>
                        <span>收藏</span>
                    </button>
                    <button class="action-btn" id="shareBtn">
                        <i class="fas fa-share"></i>
                        <span>分享</span>
                    </button>
                </div>

                <div class="video-description">
                    <?php echo nl2br(htmlspecialchars($video['description'])); ?>
                </div>
            </div>

            <!-- 评论区 -->
            <div class="comments-section">
                <div class="comments-header">
                    <h3 class="comments-title">评论</h3>
                    <div class="comment-sort">
                        <button class="sort-btn active">最新</button>
                        <button class="sort-btn">最热</button>
                    </div>
                </div>

                <div class="comment-form">
                    <textarea class="comment-input" placeholder="发表评论..."></textarea>
                    <button class="comment-submit">发表评论</button>
                </div>

                <div class="comment-list">
                    <?php if (empty($comments)): ?>
                    <div class="no-comments">
                        <p>暂无评论，快来抢沙发吧！</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-avatar">
                                <img src="<?php echo htmlspecialchars($comment['avatar'] ?? DEFAULT_AVATAR); ?>" alt="用户头像">
                            </div>
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-username"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <span class="comment-time"><?php echo time_ago($comment['created_at']); ?></span>
                                </div>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <div class="comment-actions">
                                    <span class="comment-action">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?php echo number_format($comment['likes']); ?></span>
                                    </span>
                                    <span class="comment-action">回复</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 右侧推荐列表 -->
        <div class="video-sidebar">
            <h3 class="sidebar-title">相关推荐</h3>
            <div class="recommended-list">
                <?php foreach ($recommended_videos as $rec_video): ?>
                <a href="play.php?id=<?php echo $rec_video['id']; ?>" class="recommended-item">
                    <div class="recommended-thumbnail">
                        <img src="<?php echo htmlspecialchars($rec_video['cover_image'] ?? DEFAULT_COVER); ?>" 
                             alt="<?php echo htmlspecialchars($rec_video['title']); ?>">
                        <span class="recommended-duration"><?php echo gmdate("i:s", $rec_video['duration']); ?></span>
                    </div>
                    <div class="recommended-info">
                        <h4 class="recommended-title"><?php echo htmlspecialchars($rec_video['title']); ?></h4>
                        <div class="recommended-meta">
                            <span><?php echo number_format($rec_video['views']); ?>次观看</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 点赞功能
        const likeBtn = document.getElementById('likeBtn');
        likeBtn.addEventListener('click', function() {
            this.classList.toggle('liked');
            // 这里添加点赞的 AJAX 请求
        });

        // 收藏功能
        const favoriteBtn = document.getElementById('favoriteBtn');
        favoriteBtn.addEventListener('click', function() {
            this.classList.toggle('liked');
            // 这里添加收藏的 AJAX 请求
        });

        // 分享功能
        const shareBtn = document.getElementById('shareBtn');
        shareBtn.addEventListener('click', function() {
            // 这里添加分享功能
            alert('分享功能开发中...');
        });

        // 评论提交
        const commentForm = document.querySelector('.comment-form');
        const commentInput = document.querySelector('.comment-input');
        const commentSubmit = document.querySelector('.comment-submit');

        commentSubmit.addEventListener('click', function() {
            const content = commentInput.value.trim();
            if (content) {
                // 这里添加评论提交的 AJAX 请求
                alert('评论功能开发中...');
            }
        });

        // 评论排序
        const sortBtns = document.querySelectorAll('.sort-btn');
        sortBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                sortBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // 这里添加评论排序的 AJAX 请求
            });
        });
    });
    </script>
</body>
</html> 