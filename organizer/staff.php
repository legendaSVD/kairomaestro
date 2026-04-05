<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$userObj = new User();
$userId = $_SESSION['user_id'];
$specialtyFilter = $_GET['specialty'] ?? '';
$filters = [];
if ($specialtyFilter) {
    $filters['specialty'] = $specialtyFilter;
}
$workers = $userObj->getAllWorkers($filters);
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT DISTINCT specialty FROM worker_profiles ORDER BY specialty");
$specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подбор сотрудников - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/organizer-header.php'; ?>
    <div class="staff-page">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1>👥 Подбор сотрудников</h1>
                    <p class="page-subtitle">Найдите лучших специалистов для вашего мероприятия</p>
                </div>
                <div class="page-header-badge">
                    <span class="total-count"><?php echo count($workers); ?></span>
                    <span class="total-label">сотрудников</span>
                </div>
            </div>
            <div class="filter-card">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="specialty" class="filter-label">
                            <span class="icon">🔍</span>
                            Фильтр по специальности
                        </label>
                        <select id="specialty" name="specialty" class="filter-select" onchange="this.form.submit()">
                            <option value="">Все специальности</option>
                            <?php foreach ($specialties as $specialty): ?>
                            <option value="<?php echo escape($specialty); ?>" <?php echo $specialtyFilter === $specialty ? 'selected' : ''; ?>>
                                <?php echo escape($specialty); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($specialtyFilter): ?>
                    <a href="/organizer/staff.php" class="btn btn-outline btn-sm">Сбросить фильтр</a>
                    <?php endif; ?>
                </form>
            </div>
            <?php if (empty($workers)): ?>
            <div class="empty-state">
                <div class="empty-icon">👷</div>
                <h3>Сотрудники не найдены</h3>
                <p>Попробуйте изменить фильтр или вернитесь позже</p>
            </div>
            <?php else: ?>
            <div class="workers-grid">
                <?php foreach ($workers as $worker): ?>
                <div class="worker-card">
                    <div class="worker-card-header">
                        <div class="worker-avatar">
                            <?php if ($worker['avatar']): ?>
                                <img src="/uploads/avatars/<?php echo escape($worker['avatar']); ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="worker-avatar-placeholder">
                                    <?php echo mb_substr($worker['first_name'], 0, 1, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="worker-info">
                            <h3><?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?></h3>
                            <span class="worker-specialty"><?php echo escape($worker['specialty']); ?></span>
                        </div>
                    </div>
                    <div class="worker-card-body">
                        <div class="worker-detail">
                            <span class="detail-icon">📞</span>
                            <a href="tel:<?php echo escape($worker['phone']); ?>" class="detail-link">
                                <?php echo escape($worker['phone']); ?>
                            </a>
                        </div>
                        <div class="worker-detail">
                            <span class="detail-icon">💰</span>
                            <span class="detail-text">
                                <?php echo number_format($worker['hourly_rate'], 0, ',', ' '); ?> ₽/час
                            </span>
                        </div>
                        <?php if ($worker['has_car']): ?>
                        <div class="worker-detail">
                            <span class="detail-icon">🚗</span>
                            <span class="detail-text">
                                <?php echo escape($worker['car_brand']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="worker-card-footer">
                        <a href="/organizer/worker-profile.php?id=<?php echo $worker['id']; ?>" class="btn btn-primary btn-block">
                            Подробнее
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../includes/organizer-footer.php'; ?>
</body>
</html>