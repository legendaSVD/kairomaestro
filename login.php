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
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($phone) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $userObj = new User();
        $result = $userObj->login($phone, $password);
        if ($result['success']) {
            if ($result['user']['role'] === 'worker') {
                redirect('/worker/dashboard.php');
            } else {
                redirect('/organizer/dashboard.php');
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - KairoMaestro</title>
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
                <h2>Вход</h2>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="phone">Номер телефона</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+7 (___) ___-__-__" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Введите пароль" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Войти</button>
                </form>
                <div class="auth-footer">
                    <p>Нет аккаунта? <a href="/register.php">Зарегистрироваться</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/theme-toggle.js"></script>
    <script src="/assets/js/phone-mask.js"></script>
</body>
</html>