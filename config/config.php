<?php
define('SITE_NAME', 'KairoMaestro');
define('SITE_URL', 'https://kairomaestro.ru');
define('SITE_DESCRIPTION', 'Платформа для организаторов мероприятий и сотрудников');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}
date_default_timezone_set('Europe/Moscow');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function getCurrentUser() {
    if (isLoggedIn()) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}
function redirect($url) {
    header("Location: $url");
    exit;
}
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    mkdir(UPLOAD_DIR . 'receipts', 0755, true);
    mkdir(UPLOAD_DIR . 'media', 0755, true);
    mkdir(UPLOAD_DIR . 'chat', 0755, true);
}