<?php
function hasPermission($permissionCode) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.id = ? AND p.code = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $permissionCode]);
    
    return $stmt->fetchColumn() > 0;
}

function requirePermission($permissionCode) {
    if (!hasPermission($permissionCode)) {
        header("Location: error.php?code=403");
        exit;
    }
}

function getUserRole($userId = null) {
    global $pdo;
    
    $userId = $userId ?? $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT r.* FROM roles r
        JOIN users u ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetch();
} 