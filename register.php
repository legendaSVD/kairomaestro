<?php
require_once 'config/config.php';
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'worker') {
        redirect('/worker/dashboard.php');
    } else {
        redirect('/organizer/dashboard.php');
    }
}
$role = $_GET['role'] ?? '';
if ($role === 'worker') {
    redirect('/register-worker.php?step=1');
} elseif ($role === 'organizer') {
    redirect('/register-organizer.php?step=1');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-logo">
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Переключить тему" style="position: absolute; top: 1rem; right: 1rem;">
                    🌙
                </button>
                <h1>KairoMaestro</h1>
            </div>
            <div class="auth-card">
                <h2>Регистрация</h2>
                <div class="role-selection">
                    <p>Выберите свою роль:</p>
                    <a href="/register-worker.php?step=1" class="role-card">
                        <div class="role-icon">👷</div>
                        <h3>Я сотрудник</h3>
                        <p>Работаю на мероприятиях</p>
                    </a>
                    <a href="/register-organizer.php?step=1" class="role-card">
                        <div class="role-icon">👔</div>
                        <h3>Я организатор</h3>
                        <p>Организую мероприятия</p>
                    </a>
                </div>
                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="/login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/theme-toggle.js"></script>
</body>
</html>
