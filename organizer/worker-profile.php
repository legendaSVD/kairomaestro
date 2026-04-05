<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$userObj = new User();
$workerId = $_GET['id'] ?? 0;
$worker = $userObj->getUserById($workerId);
if (!$worker || $worker['role'] !== 'worker') {
    redirect('/organizer/staff.php');
}
$workerProfile = $userObj->getWorkerProfile($workerId);
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN es.status = 'completed' THEN 1 ELSE 0 END) as completed_events
    FROM event_staff es
    WHERE es.worker_id = ?
");
$stmt->execute([$workerId]);
$stats = $stmt->fetch();
$stmt = $db->prepare("
    SELECT e.*, es.status as worker_status
    FROM events e
    JOIN event_staff es ON e.id = es.event_id
    WHERE es.worker_id = ?
    ORDER BY e.event_date DESC
    LIMIT 5
");
$stmt->execute([$workerId]);
$recentEvents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?> - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/organizer-header.php'; ?>
    <div class="worker-profile-page">
        <div class="container">
            <div class="profile-header-card">
                <div class="profile-avatar-section">
                    <?php if ($worker['avatar']): ?>
                        <img src="/uploads/avatars/<?php echo escape($worker['avatar']); ?>" alt="Avatar" class="profile-avatar-large">
                    <?php else: ?>
                        <div class="profile-avatar-large profile-avatar-placeholder">
                            <?php echo mb_substr($worker['first_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info-section">
                    <h1><?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?></h1>
                    <?php if ($worker['middle_name']): ?>
                        <p class="middle-name"><?php echo escape($worker['middle_name']); ?></p>
                    <?php endif; ?>
                    <div class="profile-meta">
                        <span class="badge badge-large"><?php echo escape($workerProfile['specialty']); ?></span>
                    </div>
                </div>
            </div>
            <div class="profile-section-card">
                <h2>Контактная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="icon">📞</span>
                        <div>
                            <strong>Телефон</strong>
                            <p><a href="tel:<?php echo escape($worker['phone']); ?>"><?php echo escape($worker['phone']); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="profile-section-card">
                <h2>Профессиональная информация</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="icon">💼</span>
                        <div>
                            <strong>Специальность</strong>
                            <p><?php echo escape($workerProfile['specialty']); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="icon">💰</span>
                        <div>
                            <strong>Ставка</strong>
                            <p><?php echo number_format($workerProfile['hourly_rate'], 0, ',', ' '); ?> ₽/час</p>
                        </div>
                    </div>
                    <?php if ($workerProfile['has_car']): ?>
                    <div class="info-item">
                        <span class="icon">🚗</span>
                        <div>
                            <strong>Автомобиль</strong>
                            <p><?php echo escape($workerProfile['car_brand']); ?></p>
                            <?php if ($workerProfile['car_number']): ?>
                                <p><small><?php echo escape($workerProfile['car_number']); ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($workerProfile['additional_info']): ?>
                    <div class="info-item full-width">
                        <span class="icon">📝</span>
                        <div>
                            <strong>Дополнительная информация</strong>
                            <p><?php echo nl2br(escape($workerProfile['additional_info'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-section-card">
                <h2>Статистика</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['total_events'] ?? 0; ?></div>
                            <div class="stat-label">Всего мероприятий</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['completed_events'] ?? 0; ?></div>
                            <div class="stat-label">Завершено</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($recentEvents)): ?>
            <div class="profile-section-card">
                <h2>Последние мероприятия</h2>
                <div class="recent-events-list">
                    <?php foreach ($recentEvents as $event): ?>
                    <div class="recent-event-item">
                        <div class="event-date-badge">
                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                            <span class="month"><?php echo formatRussianDate($event['event_date'], 'month_short'); ?></span>
                        </div>
                        <div class="event-info">
                            <h3><?php echo escape($event['title']); ?></h3>
                            <p><?php echo formatRussianDate($event['event_date'], 'date_only'); ?></p>
                            <p><?php echo escape($event['location']); ?></p>
                        </div>
                        <div class="event-status">
                            <span class="badge badge-<?php echo $event['worker_status']; ?>">
                                <?php 
                                $statuses = [
                                    'pending' => 'Ожидает',
                                    'confirmed' => 'Подтверждено',
                                    'on_location' => 'На месте',
                                    'completed' => 'Завершено'
                                ];
                                echo $statuses[$event['worker_status']] ?? $event['worker_status'];
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="profile-actions">
                <a href="/organizer/staff.php" class="btn btn-secondary">← Назад к списку</a>
            </div>
        </div>
    </div>
    <?php include '../includes/organizer-footer.php'; ?>
</body>
</html>