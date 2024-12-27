<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$video_id = intval($_GET['id']);

// 获取视频信息
$stmt = $pdo->prepare("SELECT v.*, c.name as category_name 
                       FROM videos v 
                       LEFT JOIN categories c ON v.category_id = c.id 
                       WHERE v.id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if (!$video) {
    header("Location: index.php");
    exit;
}

// 生成分享链接
$share_url = "http://" . $_SERVER['HTTP_HOST'] . "/play.php?id=" . $video_id;

// 生成分享图片（可以使用第三方服务或自己实现）
$share_image = $video['cover_image'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>分享 - <?php echo htmlspecialchars($video['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <!-- 添加社交分享SDK -->
    <script src="//connect.qq.com/qc_jssdk.js" data-appid="YOUR_QQ_APPID"></script>
    <script src="//res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="share-container">
        <div class="video-preview">
            <img src="<?php echo htmlspecialchars($video['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($video['title']); ?>">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <p class="description"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
        </div>
        
        <div class="share-options">
            <h2>分享到</h2>
            <div class="share-buttons">
                <button class="share-btn qq" onclick="shareToQQ()">QQ</button>
                <button class="share-btn wechat" onclick="shareToWechat()">微信</button>
                <button class="share-btn weibo" onclick="shareToWeibo()">微博</button>
            </div>
            
            <div class="share-link">
                <input type="text" value="<?php echo htmlspecialchars($share_url); ?>" readonly>
                <button onclick="copyShareLink()">复制链接</button>
            </div>
        </div>
    </div>
    
    <script>
    // 分享到QQ
    function shareToQQ() {
        QC.Share.shareToQQ({
            title: '<?php echo addslashes($video['title']); ?>',
            desc: '<?php echo addslashes($video['description']); ?>',
            imgUrl: '<?php echo addslashes($share_image); ?>',
            link: '<?php echo addslashes($share_url); ?>'
        });
    }
    
    // 复制分享链接
    function copyShareLink() {
        const input = document.querySelector('.share-link input');
        input.select();
        document.execCommand('copy');
        alert('链接已复制到剪贴板');
    }
    </script>
</body>
</html> 