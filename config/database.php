<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=video_website;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("连接数据库失败: " . $e->getMessage());
} 