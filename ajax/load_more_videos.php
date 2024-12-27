<?php
require_once '../config/database.php';
require_once '../config/config.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$per_page = 12;
$offset = ($page - 1) * $per_page;

$params = [];
$sql = "
    SELECT v.*, c.name as category_name, c.type as category_type
    FROM videos v
    LEFT JOIN categories c ON v.category_id = c.id
    WHERE 1=1
";

if ($type !== 'all') {
    $sql .= " AND c.type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();

$html = '';
foreach ($videos as $video) {
    $html .= '<div class="video-card">';
    $html .= '<div class="video-thumbnail">';
    $html .= '<img src="' . htmlspecialchars($video['cover_image'] ?? DEFAULT_COVER) . '" 
                   alt="' . htmlspecialchars($video['title']) . '">';
    $html .= '<span class="duration">' . gmdate("i:s", $video['duration']) . '</span>';
    $html .= '<a href="play.php?id=' . $video['id'] . '" class="play-btn">';
    $html .= '<i class="fas fa-play"></i>';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '<div class="video-info">';
    $html .= '<h3 class="video-title">' . htmlspecialchars($video['title']) . '</h3>';
    $html .= '<div class="video-meta">';
    $html .= '<span class="category">' . htmlspecialchars($video['category_name']) . '</span>';
    $html .= '<span class="views"><i class="fas fa-eye"></i> ' . number_format($video['views']) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
}

echo json_encode([
    'html' => $html,
    'has_more' => count($videos) === $per_page
]);