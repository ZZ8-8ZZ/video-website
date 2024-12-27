<?php
// 站点配置
define('SITE_NAME', '视频网站');
define('SITE_URL', 'http://localhost');
define('DEBUG_MODE', true);

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'video_website');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// 文件上传配置
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 1024 * 1024 * 500); // 500MB
define('ALLOWED_EXTENSIONS', ['mp4', 'mkv', 'avi', 'mov']);

// 缓存配置
define('CACHE_ENABLED', true);
define('CACHE_PATH', __DIR__ . '/../cache');
define('CACHE_LIFETIME', 3600); // 1小时