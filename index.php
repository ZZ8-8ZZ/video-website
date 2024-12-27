<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 获取轮播图数据
$stmt = $pdo->prepare("
    SELECT v.*, c.name as category_name 
    FROM videos v
    LEFT JOIN categories c ON v.category_id = c.id
    WHERE v.is_featured = 1
    ORDER BY v.created_at DESC
    LIMIT 3
");

try {
    $stmt->execute();
    $banners = $stmt->fetchAll();
    
    // 如果推荐视频少于3个，则补充播放量最高的视频
    if (count($banners) < 3) {
        $needed = 3 - count($banners);
        $featured_ids = array_column($banners, 'id');
        
        $additional_sql = "
            SELECT v.*, c.name as category_name 
            FROM videos v
            LEFT JOIN categories c ON v.category_id = c.id
            WHERE v.is_featured = 0 ";
            
        if (!empty($featured_ids)) {
            $additional_sql .= "AND v.id NOT IN (" . implode(',', $featured_ids) . ") ";
        }
        
        $additional_sql .= "
            ORDER BY v.views DESC
            LIMIT " . $needed;
            
        $stmt = $pdo->prepare($additional_sql);
        $stmt->execute();
        $additional_videos = $stmt->fetchAll();
        
        $banners = array_merge($banners, $additional_videos);
    }
} catch (PDOException $e) {
    error_log("Error fetching banner videos: " . $e->getMessage());
    $banners = [];
}

// 获取热门电影
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count,
           COUNT(DISTINCT v.id) as episode_count
    FROM series s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN videos v ON s.id = v.series_id
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE s.type = 'movie'
    GROUP BY s.id
    ORDER BY view_count DESC
    LIMIT 6
");
$stmt->execute();
$movies = $stmt->fetchAll();

// 获取热门电视剧
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count,
           COUNT(DISTINCT v.id) as episode_count
    FROM series s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN videos v ON s.id = v.series_id
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE s.type = 'tv'
    GROUP BY s.id
    ORDER BY view_count DESC
    LIMIT 6
");
$stmt->execute();
$tv_series = $stmt->fetchAll();

// 获取热门综艺
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count,
           COUNT(DISTINCT v.id) as episode_count
    FROM series s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN videos v ON s.id = v.series_id
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE s.type = 'variety'
    GROUP BY s.id
    ORDER BY view_count DESC
    LIMIT 6
");
$stmt->execute();
$variety_shows = $stmt->fetchAll();

// 获取热门动漫
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name,
           COUNT(DISTINCT wh.user_id) as view_count,
           COUNT(DISTINCT v.id) as episode_count
    FROM series s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN videos v ON s.id = v.series_id
    LEFT JOIN watch_history wh ON v.id = wh.video_id
    WHERE s.type = 'anime'
    GROUP BY s.id
    ORDER BY view_count DESC
    LIMIT 6
");
$stmt->execute();
$anime = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="main-container">
        <!-- 轮播图部分 -->
        <div class="banner-section">
            <div class="banner-slider">
                <?php foreach ($banners as $index => $banner): ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <a href="play.php?id=<?php echo $banner['id']; ?>">
                        <img src="<?php echo htmlspecialchars($banner['cover_image'] ?? DEFAULT_COVER); ?>" 
                             alt="<?php echo htmlspecialchars($banner['title']); ?>">
                        <div class="slide-info">
                            <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                            <p><?php echo htmlspecialchars($banner['description']); ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <button class="prev-btn"><i class="fas fa-chevron-left"></i></button>
                <button class="next-btn"><i class="fas fa-chevron-right"></i></button>
                
                <div class="dots">
                    <?php foreach ($banners as $index => $banner): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>"></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 电影部分 -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-film"></i>
                    热门电影
                </h2>
                <div class="section-tabs">
                    <a href="#" class="active" data-type="all">全部</a>
                    <a href="#" data-type="1">动作片</a>
                    <a href="#" data-type="2">喜剧片</a>
                    <a href="#" data-type="3">爱情片</a>
                    <a href="category.php?type=movie" class="more-link">更多 <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            
            <div class="video-grid" id="movieGrid">
                <?php foreach ($movies as $movie): ?>
                <a href="detail.php?id=<?php echo $movie['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?php echo htmlspecialchars($movie['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="play-btn">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <div class="video-meta">
                            <?php echo htmlspecialchars($movie['area']); ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="pagination-container">
                <div class="pagination" id="moviePagination"></div>
            </div>
        </section>

        <!-- 电视剧部分 -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-tv"></i>
                    热门电视剧
                </h2>
                <div class="section-tabs">
                    <a href="category.php?type=tv&sort=latest" class="active">最新</a>
                    <a href="category.php?type=tv&sort=hot">最热</a>
                    <a href="category.php?type=tv">更多</a>
                </div>
            </div>
            
            <div class="video-grid">
                <?php foreach ($tv_series as $series): ?>
                <a href="detail.php?id=<?php echo $series['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?php echo htmlspecialchars($series['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($series['title']); ?>">
                        <div class="play-btn">
                            <i class="fas fa-play"></i>
                        </div>
                        <?php if ($series['status'] === '连载中'): ?>
                        <span class="status-badge">连载中</span>
                        <?php endif; ?>
                        <span class="episode-badge"><?php echo $series['episode_count']; ?>集</span>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?php echo htmlspecialchars($series['title']); ?></h3>
                        <div class="video-meta">
                            <span class="area"><?php echo htmlspecialchars($series['area']); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo number_format($series['view_count']); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 综艺部分 -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-star"></i>
                    热门综艺
                </h2>
                <div class="section-tabs">
                    <a href="category.php?type=variety&sort=latest" class="active">最新</a>
                    <a href="category.php?type=variety&sort=hot">最热</a>
                    <a href="category.php?type=variety">更多</a>
                </div>
            </div>
            
            <div class="video-grid">
                <?php foreach ($variety_shows as $show): ?>
                <a href="detail.php?id=<?php echo $show['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?php echo htmlspecialchars($show['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($show['title']); ?>">
                        <div class="play-btn">
                            <i class="fas fa-play"></i>
                        </div>
                        <?php if ($show['status'] === '连载中'): ?>
                        <span class="status-badge">连载中</span>
                        <?php endif; ?>
                        <span class="episode-badge"><?php echo $show['episode_count']; ?>期</span>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?php echo htmlspecialchars($show['title']); ?></h3>
                        <div class="video-meta">
                            <span class="area"><?php echo htmlspecialchars($show['area']); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo number_format($show['view_count']); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 动漫部分 -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-heart"></i>
                    热门动漫
                </h2>
                <div class="section-tabs">
                    <a href="category.php?type=anime&sort=latest" class="active">最新</a>
                    <a href="category.php?type=anime&sort=hot">最热</a>
                    <a href="category.php?type=anime">更多</a>
                </div>
            </div>
            
            <div class="video-grid">
                <?php foreach ($anime as $item): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="play-btn">
                            <i class="fas fa-play"></i>
                        </div>
                        <?php if ($item['status'] === '连载中'): ?>
                        <span class="status-badge">连载中</span>
                        <?php endif; ?>
                        <span class="episode-badge"><?php echo $item['episode_count']; ?>集</span>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="video-meta">
                            <span class="area"><?php echo htmlspecialchars($item['area']); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo number_format($item['view_count']); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 轮播图功能
        const bannerSlider = {
            slides: document.querySelectorAll('.slide'),
            dots: document.querySelectorAll('.dot'),
            prevBtn: document.querySelector('.prev-btn'),
            nextBtn: document.querySelector('.next-btn'),
            currentSlide: 0,
            slideInterval: null,
            
            init() {
                if (this.slides.length === 0) return;
                
                this.showSlide(this.currentSlide);
                this.bindEvents();
                this.startSlideShow();
            },
            
            showSlide(index) {
                this.slides.forEach(slide => slide.classList.remove('active'));
                this.dots.forEach(dot => dot.classList.remove('active'));
                
                this.slides[index].classList.add('active');
                this.dots[index].classList.add('active');
            },
            
            nextSlide() {
                this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                this.showSlide(this.currentSlide);
            },
            
            prevSlide() {
                this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
                this.showSlide(this.currentSlide);
            },
            
            bindEvents() {
                this.prevBtn.addEventListener('click', () => {
                    this.prevSlide();
                    this.resetSlideShow();
                });
                
                this.nextBtn.addEventListener('click', () => {
                    this.nextSlide();
                    this.resetSlideShow();
                });
                
                this.dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        this.currentSlide = index;
                        this.showSlide(this.currentSlide);
                        this.resetSlideShow();
                    });
                });
                
                // 鼠标悬停时暂停轮播
                const slider = document.querySelector('.banner-slider');
                slider.addEventListener('mouseenter', () => this.stopSlideShow());
                slider.addEventListener('mouseleave', () => this.startSlideShow());
                
                // 触摸事件支持
                let touchStartX = 0;
                let touchEndX = 0;
                
                slider.addEventListener('touchstart', (e) => {
                    touchStartX = e.touches[0].clientX;
                    this.stopSlideShow();
                });
                
                slider.addEventListener('touchmove', (e) => {
                    touchEndX = e.touches[0].clientX;
                });
                
                slider.addEventListener('touchend', () => {
                    const difference = touchStartX - touchEndX;
                    if (Math.abs(difference) > 50) { // 最小滑动距离
                        if (difference > 0) {
                            this.nextSlide();
                        } else {
                            this.prevSlide();
                        }
                    }
                    this.startSlideShow();
                });
            },
            
            startSlideShow() {
                this.stopSlideShow();
                this.slideInterval = setInterval(() => this.nextSlide(), 5000);
            },
            
            stopSlideShow() {
                if (this.slideInterval) {
                    clearInterval(this.slideInterval);
                    this.slideInterval = null;
                }
            },
            
            resetSlideShow() {
                this.stopSlideShow();
                this.startSlideShow();
            }
        };
        
        bannerSlider.init();

        // 分页加载功能
        function loadContent(type, page, category = 'all') {
            const container = document.getElementById(`${type}Grid`);
            const section = container.closest('.section');
            
            // 显示加载动画
            container.innerHTML = '<div class="loading">加载中...</div>';
            
            // 发送 AJAX 请求
            fetch(`ajax/load_content.php?type=${type}&page=${page}&category=${category}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        container.innerHTML = data.html;
                        
                        // 更新分页按钮
                        if (data.pagination) {
                            const paginationContainer = section.querySelector('.pagination');
                            if (paginationContainer) {
                                paginationContainer.innerHTML = data.pagination;
                                
                                // 绑定分页按钮事件
                                paginationContainer.querySelectorAll('.page-btn').forEach(btn => {
                                    btn.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        const pageNum = parseInt(btn.dataset.page);
                                        const currentCategory = section.querySelector('.section-tabs a.active').dataset.type;
                                        loadContent(type, pageNum, currentCategory);
                                    });
                                });
                            }
                        }
                    } else {
                        container.innerHTML = '<div class="error">加载失败，请重试</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = '<div class="error">加载失败，请重试</div>';
                });
        }

        // 绑定分类标签点击事件
        document.querySelectorAll('.section-tabs a').forEach(tab => {
            if (!tab.classList.contains('more-link')) {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    const section = e.target.closest('.section');
                    const type = section.querySelector('.video-grid').id.replace('Grid', '');
                    const category = e.target.dataset.type;
                    
                    // 更新标签状态
                    section.querySelectorAll('.section-tabs a').forEach(t => {
                        if (!t.classList.contains('more-link')) {
                            t.classList.remove('active');
                        }
                    });
                    e.target.classList.add('active');
                    
                    // 加载内容
                    loadContent(type, 1, category);
                });
            }
        });
    });
    </script>
</body>
</html>