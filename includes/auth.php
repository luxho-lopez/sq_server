<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    global $pdo;
    if (!isLoggedIn()) return false;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() === 'superadmin';
}

function hasPermission($page_key, $action = 'view') {
    global $pdo;
    if (!isLoggedIn()) return false;
    if (isSuperAdmin()) return true;

    $column = $action === 'edit' ? 'can_edit' : 'can_view';

    // Obtener page_id desde page_key (key_name)
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE key_name = ?");
    $stmt->execute([$page_key]);
    $page_id = $stmt->fetchColumn();

    if (!$page_id) return false;

    $stmt = $pdo->prepare("SELECT $column FROM permissions WHERE user_id = ? AND page_id = ?");
    $stmt->execute([$_SESSION['user_id'], $page_id]);
    return $stmt->fetchColumn() == 1;
}

function getAllowedPages() {
    global $pdo;
    if (!isLoggedIn()) return [];

    if (isSuperAdmin()) {
        // El superadmin puede ver todas las páginas
        $stmt = $pdo->query("SELECT key_name FROM pages");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $stmt = $pdo->prepare("
        SELECT p.key_name 
        FROM permissions perm
        JOIN pages p ON perm.page_id = p.id
        WHERE perm.user_id = ? AND (perm.can_view = 1 OR perm.can_edit = 1)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Redirigir al login si no ha iniciado sesión
if (!isLoggedIn() && !in_array(basename($_SERVER['PHP_SELF']), ['login.php'])) {
    header('Location: /main/login.php');
    exit;
}

// Obtener foto de perfil si aún no está en la sesión
if (isLoggedIn() && !isset($_SESSION['profile_picture'])) {
    $stmt = $pdo->prepare("SELECT profile_picture, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
    $_SESSION['username'] = $user['username'] ?? 'Usuario';
}
?>
