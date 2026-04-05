<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/worker/dashboard.php">KairoMaestro</a>
            </div>
            <nav class="main-nav">
                <a href="/worker/dashboard.php" class="nav-link">
                    <span class="icon">🏠</span>
                    <span class="nav-text">Главная</span>
                </a>
                <a href="/worker/calendar.php" class="nav-link">
                    <span class="icon">📅</span>
                    <span class="nav-text">Календарь</span>
                </a>
                <a href="/chat.php" class="nav-link">
                    <span class="icon">💬</span>
                    <span class="nav-text">Чаты</span>
                </a>
                <a href="/worker/statistics.php" class="nav-link">
                    <span class="icon">📊</span>
                    <span class="nav-text">Статистика</span>
                </a>
            </nav>
            <div class="header-actions">
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Переключить тему">
                    🌙
                </button>
                <?php
                $notificationObj = new Notification();
                $unreadCount = $notificationObj->getUnreadCount($_SESSION['user_id']);
                ?>
                <a href="/worker/notifications.php" class="notification-btn">
                    <span class="icon">🔔</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/worker/profile.php" class="profile-btn" title="Профиль">
                    <span class="icon">👤</span>
                </a>
                <a href="/logout.php" class="logout-btn" title="Выход">
                    <span class="icon">🚪</span>
                </a>
            </div>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
    <div class="mobile-menu" id="mobileMenu">
        <a href="/worker/dashboard.php">🏠 Главная</a>
        <a href="/worker/calendar.php">📅 Календарь</a>
        <a href="/chat.php">💬 Чаты</a>
        <a href="/worker/statistics.php">📊 Статистика</a>
        <a href="/worker/notifications.php">🔔 Уведомления <?php if ($unreadCount > 0): ?>(<?php echo $unreadCount; ?>)<?php endif; ?></a>
        <a href="/worker/profile.php">👤 Профиль</a>
        <a href="/logout.php">🚪 Выход</a>
    </div>
</header>
<script>
    function toggleMobileMenu() {
        document.getElementById('mobileMenu').classList.toggle('show');
    }
</script>