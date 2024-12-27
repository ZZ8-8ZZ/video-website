<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-section footer-about">
            <h3>关于我们</h3>
            <p>我们致力于为用户提供优质的在线视频观看体验，包括电影、电视剧、综艺和动漫等丰富内容。</p>
            <p>欢迎加入我们，共同打造最好的视频分享平台。</p>
        </div>
        
        <div class="footer-section">
            <h3>快速链接</h3>
            <ul class="footer-links">
                <li><a href="about.php"><i class="fas fa-angle-right"></i>网站介绍</a></li>
                <li><a href="contact.php"><i class="fas fa-angle-right"></i>联系我们</a></li>
                <li><a href="terms.php"><i class="fas fa-angle-right"></i>服务条款</a></li>
                <li><a href="privacy.php"><i class="fas fa-angle-right"></i>隐私政策</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>帮助中心</h3>
            <ul class="footer-links">
                <li><a href="faq.php"><i class="fas fa-angle-right"></i>常见问题</a></li>
                <li><a href="feedback.php"><i class="fas fa-angle-right"></i>意见反馈</a></li>
                <li><a href="report.php"><i class="fas fa-angle-right"></i>内容举报</a></li>
                <li><a href="guide.php"><i class="fas fa-angle-right"></i>新手指南</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>关注我们</h3>
            <div class="social-links">
                <a href="#" class="social-icon weixin"><i class="fab fa-weixin"></i></a>
                <a href="#" class="social-icon weibo"><i class="fab fa-weibo"></i></a>
                <a href="#" class="social-icon qq"><i class="fab fa-qq"></i></a>
            </div>
            <ul class="footer-links">
                <li><a href="#"><i class="fas fa-angle-right"></i>官方微博</a></li>
                <li><a href="#"><i class="fas fa-angle-right"></i>微信公众号</a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <nav class="footer-nav">
            <a href="about.php">关于我们</a>
            <a href="contact.php">联系我们</a>
            <a href="terms.php">服务条款</a>
            <a href="privacy.php">隐私政策</a>
            <a href="sitemap.php">网站地图</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p>备案号：xxx-xxx号</p>
    </div>
</footer>

<!-- 返回顶部按钮 -->
<button id="backToTop" class="back-to-top" title="返回顶部">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// 返回顶部按钮
const backToTopBtn = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

backToTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script> 