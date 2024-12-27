-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1:3306
-- 生成日期： 2024-12-26 14:38:19
-- 服务器版本： 8.0.31
-- PHP 版本： 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 创建数据库
--
CREATE DATABASE IF NOT EXISTS `video_website` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

--
-- 选择数据库
--
USE `video_website`;

--
-- 数据库： `video_website`
--

-- --------------------------------------------------------

--
-- 表的结构 `activation_codes`
--

DROP TABLE IF EXISTS `activation_codes`;
CREATE TABLE IF NOT EXISTS `activation_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_used` tinyint DEFAULT '0',
  `used_at` timestamp NULL DEFAULT NULL,
  `used_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `used_by` (`used_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `activation_codes`
--

INSERT INTO `activation_codes` (`id`, `code`, `batch`, `is_used`, `used_at`, `used_by`, `created_at`) VALUES
(1, '24C22395F046940C', '20241226143716', 1, '2024-12-26 14:38:05', 2, '2024-12-26 14:37:16');

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('movie','tv','variety','anime') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `parent_id`, `sort_order`) VALUES
(1, '动作片', 'movie', NULL, 0),
(2, '喜剧片', 'movie', NULL, 0),
(3, '爱情片', 'movie', NULL, 0),
(4, '科幻片', 'movie', NULL, 0),
(5, '恐怖片', 'movie', NULL, 0),
(6, '动画片', 'movie', NULL, 0),
(7, '剧情片', 'movie', NULL, 0),
(8, '纪录片', 'movie', NULL, 0),
(9, '国产剧', 'tv', NULL, 0),
(10, '港台剧', 'tv', NULL, 0),
(11, '日韩剧', 'tv', NULL, 0),
(12, '欧美剧', 'tv', NULL, 0),
(21, '综艺', 'variety', NULL, 0),
(22, '动漫', 'anime', NULL, 0);

-- --------------------------------------------------------

--
-- 表的结构 `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `content_id` int NOT NULL,
  `content_type` enum('video','series') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `likes` int DEFAULT '0',
  `parent_id` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_content` (`content_id`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `comment_likes`
--

DROP TABLE IF EXISTS `comment_likes`;
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`comment_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `episodes`
--

DROP TABLE IF EXISTS `episodes`;
CREATE TABLE IF NOT EXISTS `episodes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `series_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `episode_number` int NOT NULL,
  `play_url` text COLLATE utf8mb4_unicode_ci,
  `duration` int DEFAULT NULL COMMENT '时长（秒）',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_episode` (`series_id`,`episode_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `content_id` int NOT NULL,
  `content_type` enum('video','series') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`content_id`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_user_id` int DEFAULT NULL,
  `to_user_id` int NOT NULL,
  `type` enum('system','user','notification') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `idx_to_user` (`to_user_id`,`is_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `playlists`
--

DROP TABLE IF EXISTS `playlists`;
CREATE TABLE IF NOT EXISTS `playlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `playlist_items`
--

DROP TABLE IF EXISTS `playlist_items`;
CREATE TABLE IF NOT EXISTS `playlist_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `playlist_id` int NOT NULL,
  `content_id` int NOT NULL,
  `content_type` enum('video','series') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `playlist_id` (`playlist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `series`
--

DROP TABLE IF EXISTS `series`;
CREATE TABLE IF NOT EXISTS `series` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `director` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actors` text COLLATE utf8mb4_unicode_ci,
  `area` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_year` int DEFAULT NULL,
  `total_episodes` int DEFAULT '0',
  `views` int DEFAULT '0',
  `rating` decimal(3,1) DEFAULT '0.0',
  `status` enum('ongoing','completed','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'ongoing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `current_episode` int DEFAULT '0' COMMENT '当前更新集数',
  `type` enum('movie','tv','variety','anime') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `series`
--

INSERT INTO `series` (`title`, `description`, `category_id`, `cover_image`, `director`, `actors`, `area`, `release_year`, `total_episodes`, `views`, `rating`, `status`, `current_episode`, `type`) VALUES 
('狂飙', '2023年热播刑侦剧', 9, 'covers/kuangbiao.jpg', '徐纪周', '张译,张颂文,李一桐', '中国大陆', 2023, 39, 10000, 9.0, 'completed', 39, 'tv'),
('三体', '根据刘慈欣科幻小说改编', 9, 'covers/santi.jpg', '杨磊', '张鲁一,于和伟,陈瑾', '中国大陆', 2023, 30, 8000, 8.8, 'completed', 30, 'tv'),
('雪中悍刀行', '古装武侠剧', 9, 'covers/xuezhanghandaoxing.jpg', '崔宝珠', '张若昀,李庚希,胡军', '中国大陆', 2022, 38, 6000, 8.2, 'completed', 38, 'tv');

-- --------------------------------------------------------

--
-- 表的结构 `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `updated_at`) VALUES
(1, 'site_name', '视频网站', '网站名称', '2024-12-26 14:35:58'),
(2, 'site_description', '在线视频分享平台', '网站描述', '2024-12-26 14:35:58'),
(3, 'upload_max_size', '500', '最大上传大小(MB)', '2024-12-26 14:35:58'),
(4, 'allowed_extensions', 'mp4,mkv,avi,mov', '允许上传的视频格式', '2024-12-26 14:35:58');

-- --------------------------------------------------------

--
-- 表的结构 `statistics`
--

DROP TABLE IF EXISTS `statistics`;
CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL,
  `content_type` enum('video','series') COLLATE utf8mb4_unicode_ci NOT NULL,
  `views` int DEFAULT '0',
  `likes` int DEFAULT '0',
  `comments` int DEFAULT '0',
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stat` (`content_id`,`content_type`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `role_id` int DEFAULT NULL,
  `status` enum('active','banned','unverified') COLLATE utf8mb4_unicode_ci DEFAULT 'unverified',
  `is_admin` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中���数据 `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `avatar`, `bio`, `role_id`, `status`, `is_admin`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$mCQJdFiTuF/810xcwYV1uuv45V7KmD4H4WJc.YNDAVdkCU715Sd3O', 'admin@example.com', 'default-avatar.png', NULL, NULL, 'active', 1, '2024-12-26 14:36:50', NULL),
(2, 'cs', '$2y$10$7Zzku6OmwLJAwOhCnfJp8eVGZrhzvaFYJlIg9bFv6EKJCgHraY1s6', 'cs@cs.com', 'default-avatar.png', NULL, NULL, 'active', 0, '2024-12-26 14:38:05', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int NOT NULL,
  `email_notify` tinyint DEFAULT '1',
  `auto_play` tinyint DEFAULT '1',
  `theme` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `watch_history`
--

DROP TABLE IF EXISTS `watch_history`;
CREATE TABLE IF NOT EXISTS `watch_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `video_id` int NOT NULL,
  `progress` int DEFAULT '0' COMMENT '观看进度（秒）',
  `watch_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  KEY `idx_user_time` (`user_id`,`watch_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE IF NOT EXISTS `videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` text COLLATE utf8mb4_unicode_ci,
  `play_url` text COLLATE utf8mb4_unicode_ci,
  `duration` int DEFAULT NULL COMMENT '视频时长(秒)',
  `views` int DEFAULT '0',
  `likes` int DEFAULT '0',
  `rating` decimal(3,1) DEFAULT '0.0',
  `status` enum('public','private','pending','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_featured` tinyint DEFAULT '0' COMMENT '是否为推荐视频',
  `series_id` int DEFAULT NULL COMMENT '所属剧集ID',
  `episode_number` int DEFAULT NULL,
  FOREIGN KEY (series_id) REFERENCES series(id),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `videos`
--

INSERT INTO `videos` (`title`, `description`, `category_id`, `cover_image`, `play_url`, `duration`, `views`, `likes`, `rating`, `status`, `is_featured`, `series_id`, `episode_number`) VALUES 
-- 电影
('流浪地球2', '2023年科幻大片', 4, 'covers/liulangdiqiu2.jpg', 'videos/liulangdiqiu2.mp4', 7200, 1000, 500, 9.5, 'public', 1, NULL, NULL),
('满江红', '2023年悬疑动作片', 1, 'covers/manjianhong.jpg', 'videos/manjianhong.mp4', 7500, 800, 400, 8.5, 'public', 1, NULL, NULL),
('独行月球', '2022年科幻喜剧片', 2, 'covers/duxingyueqiu.jpg', 'videos/duxingyueqiu.mp4', 7000, 600, 300, 8.0, 'public', 1, NULL, NULL),

-- 电视剧
('狂飙', '2023年热播刑侦剧', 9, 'covers/kuangbiao.jpg', 'videos/kuangbiao_01.mp4', 2700, 1500, 750, 9.0, 'public', 0, 1, 1),
('三体', '根据刘慈欣科幻小说改编', 9, 'covers/santi.jpg', 'videos/santi_01.mp4', 2400, 1200, 600, 8.8, 'public', 0, 2, 1),
('雪中悍刀行', '古装武侠剧', 9, 'covers/xuezhanghandaoxing.jpg', 'videos/xuezhong_01.mp4', 2500, 900, 450, 8.2, 'public', 0, 3, 1),

-- 综艺
('快乐再出发', '经典综艺节目', 21, 'covers/kuailezaichufa.jpg', 'videos/kuaile_01.mp4', 5400, 2000, 1000, 9.2, 'public', 0, NULL, NULL),
('奔跑吧', '户外竞技真人秀', 21, 'covers/benpaoba.jpg', 'videos/benpaoba_01.mp4', 5100, 1800, 900, 8.7, 'public', 0, NULL, NULL),
('五十公里桃花坞', '文化类综艺', 21, 'covers/taohuawu.jpg', 'videos/taohuawu_01.mp4', 4800, 1500, 750, 8.9, 'public', 0, NULL, NULL),

-- 动漫
('斗罗大陆', '人气国产动画', 22, 'covers/douluodalu.jpg', 'videos/douluo_01.mp4', 1500, 2500, 1250, 9.3, 'public', 0, NULL, NULL),
('间谍过家家', '日本动画', 22, 'covers/jiandiguo.jpg', 'videos/jiandi_01.mp4', 1440, 2200, 1100, 9.4, 'public', 0, NULL, NULL),
('画江湖之不良人', '国产动画', 22, 'covers/huajianghu.jpg', 'videos/huajiang_01.mp4', 1380, 1900, 950, 8.6, 'public', 0, NULL, NULL);

-- 更新现有数据,将 videos 表中的电影数据迁移到 series 表
INSERT INTO series (title, type, description, category_id, cover_image, director, actors, area, release_year, total_episodes, views, rating, status, created_at, current_episode)
SELECT DISTINCT
    title,
    'movie' as type,
    description,
    category_id,
    cover_image,
    NULL as director,
    NULL as actors,
    NULL as area,
    NULL as release_year,
    1 as total_episodes,
    views,
    rating,
    CASE 
        WHEN status = 'public' THEN 'completed'
        WHEN status = 'private' THEN 'pending'
        ELSE 'pending'
    END as status,
    created_at,
    1 as current_episode
FROM videos v
JOIN categories c ON v.category_id = c.id
WHERE c.type = 'movie'
AND NOT EXISTS (
    SELECT 1 FROM series s 
    WHERE s.title = v.title AND s.type = 'movie'
);

-- 更新综艺数据
INSERT INTO series (title, type, description, category_id, cover_image, views, rating, status, created_at)
SELECT DISTINCT
    title,
    'variety' as type,
    description,
    category_id,
    cover_image,
    views,
    rating,
    CASE 
        WHEN status = 'public' THEN 'ongoing'
        WHEN status = 'private' THEN 'pending'
        ELSE 'pending'
    END as status,
    created_at
FROM videos v
JOIN categories c ON v.category_id = c.id
WHERE c.type = 'variety'
AND NOT EXISTS (
    SELECT 1 FROM series s 
    WHERE s.title = v.title AND s.type = 'variety'
);

-- 更新动漫数据
INSERT INTO series (title, type, description, category_id, cover_image, views, rating, status, created_at)
SELECT DISTINCT
    title,
    'anime' as type,
    description,
    category_id,
    cover_image,
    views,
    rating,
    CASE 
        WHEN status = 'public' THEN 'ongoing'
        WHEN status = 'private' THEN 'pending'
        ELSE 'pending'
    END as status,
    created_at
FROM videos v
JOIN categories c ON v.category_id = c.id
WHERE c.type = 'anime'
AND NOT EXISTS (
    SELECT 1 FROM series s 
    WHERE s.title = v.title AND s.type = 'anime'
);

-- 将原有视频数据关联到对应的系列
UPDATE videos v
JOIN series s ON v.title = s.title
SET v.series_id = s.id,
    v.episode_number = 1
WHERE v.series_id IS NULL;

-- 清理 videos 表中重复的数据
DELETE FROM videos 
WHERE id IN (
    SELECT v1.id
    FROM (SELECT * FROM videos) v1
    JOIN (SELECT * FROM videos) v2 
    ON v1.series_id = v2.series_id 
    AND v1.episode_number = v2.episode_number
    AND v1.id > v2.id
);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
