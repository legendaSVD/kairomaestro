<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KairoMaestro - Платформа для организаторов мероприятий и сотрудников</title>
    <meta name="description" content="KairoMaestro - удобная платформа для организаторов свадеб и мероприятий. Подбор сотрудников, управление расписанием, создание заданий и связь с командой.">
    <meta name="keywords" content="организация мероприятий, организация свадеб, найм сотрудников, календарь мероприятий, управление событиями, флористы, хелперы, монтажники, подбор персонала для мероприятий, свадебные сотрудники, event менеджмент, планирование мероприятий, команда для свадьбы, поиск работников мероприятий, кадры для ивентов, staffing events, wedding staff, event planning">
    <meta name="author" content="KairoMaestro">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="landing">
        <header class="landing-header">
            <div class="container">
                <div class="logo">
                    <h1>KairoMaestro</h1>
                </div>
                <nav class="nav-buttons">
                    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Переключить тему">
                        🌙
                    </button>
                    <a href="/login.php" class="btn btn-outline">Вход</a>
                    <a href="/register.php" class="btn btn-primary">Регистрация</a>
                </nav>
            </div>
        </header>
        <section class="hero">
            <div class="container">
                <h2 class="hero-title">Платформа для организаторов мероприятий и сотрудников</h2>
                <p class="hero-subtitle">Управляйте командой, планируйте события и общайтесь с сотрудниками в одном месте</p>
                <div class="hero-buttons">
                    <a href="/register-organizer.php" class="btn btn-primary btn-lg">Я организатор</a>
                    <a href="/register-worker.php" class="btn btn-secondary btn-lg">Я сотрудник</a>
                </div>
            </div>
        </section>
        <section class="features">
            <div class="container">
                <h3 class="section-title">Возможности платформы</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h4>Календарь событий</h4>
                        <p>Планируйте мероприятия, отмечайте доступность и управляйте расписанием</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h4>Управление командой</h4>
                        <p>Подбирайте сотрудников по специальности, назначайте на мероприятия</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💬</div>
                        <h4>Групповые чаты</h4>
                        <p>Общайтесь с командой, делитесь файлами и техническими заданиями</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h4>Учёт платежей</h4>
                        <p>Автоматический расчёт оплаты за работу и компенсация расходов на проезд</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h4>Статистика</h4>
                        <p>Отслеживайте количество смен и заработок сотрудников</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h4>Мобильная версия</h4>
                        <p>Работайте с любого устройства - телефона, планшета или компьютера</p>
                    </div>
                </div>
            </div>
        </section>
        <footer class="landing-footer">
        </footer>
    </div>
    <script src="/assets/js/theme-toggle.js"></script>
</body>
</html>