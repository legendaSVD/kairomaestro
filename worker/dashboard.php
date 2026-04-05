<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'worker') {
    redirect('/login.php');
}
$userObj = new User();
$eventObj = new Event();
$notificationObj = new Notification();
$paymentObj = new Payment();
$userId = $_SESSION['user_id'];
$profile = $userObj->getWorkerProfile($userId);
$upcomingEvents = $eventObj->getByWorker($userId, ['status' => 'active']);
$upcomingEvents = array_filter($upcomingEvents, function($event) {
    return strtotime($event['event_date']) >= strtotime(date('Y-m-d'));
});
$notifications = $notificationObj->getUnread($userId);
$notificationCount = count($notifications);
$statistics = $paymentObj->getWorkerStatistics($userId);
$currentYear = date('Y');
$currentMonth = date('m');
$calendar = $eventObj->getWorkerCalendar($userId, $currentYear, $currentMonth);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/worker-header.php'; ?>
    <div class="dashboard">
        <div class="container">
            <h1>Привет, <?php echo escape($profile['first_name']); ?>!</h1>
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $statistics['total_events'] ?? 0; ?></div>
                        <div class="stat-label">Мероприятий</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($statistics['total_earnings'] ?? 0, 0, ',', ' '); ?> ₽</div>
                        <div class="stat-label">Заработано</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($statistics['total_hours'] ?? 0, 1); ?> ч</div>
                        <div class="stat-label">Часов работы</div>
                    </div>
                </div>
            </div>
            <!-- Уведомления -->
            <?php if ($notificationCount > 0): ?>
            <div class="notifications-section">
                <h2>Уведомления <span class="badge"><?php echo $notificationCount; ?></span></h2>
                <div class="notifications-list">
                    <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                    <div class="notification-item">
                        <div class="notification-content">
                            <strong><?php echo escape($notification['title']); ?></strong>
                            <p><?php echo escape($notification['message']); ?></p>
                            <small><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <?php if ($notification['link']): ?>
                        <a href="<?php echo escape($notification['link']); ?>" class="btn btn-sm">Открыть</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($notificationCount > 3): ?>
                <a href="/worker/notifications.php" class="btn btn-outline">Все уведомления</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <!-- Предстоящие мероприятия -->
            <div class="events-section">
                <div class="section-header">
                    <h2>📅 Предстоящие мероприятия</h2>
                    <a href="/worker/calendar.php" class="btn btn-outline">Календарь</a>
                </div>
                <?php if (empty($upcomingEvents)): ?>
                <div class="empty-state">
                    <p>У вас пока нет запланированных мероприятий</p>
                </div>
                <?php else: ?>
                <div class="events-list">
                    <?php foreach (array_slice($upcomingEvents, 0, 5) as $event): ?>
                    <div class="event-card">
                        <div class="event-date">
                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="event-info">
                            <h3><?php echo escape($event['title']); ?></h3>
                            <p>
                                <span class="icon">⏰</span> <?php echo date('H:i', strtotime($event['start_time'])); ?>
                                <?php if ($event['end_time']): ?>
                                    - <?php echo date('H:i', strtotime($event['end_time'])); ?>
                                <?php endif; ?>
                            </p>
                            <p><span class="icon">📍</span> <?php echo escape($event['location']); ?></p>
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
                        <div class="event-actions">
                            <a href="/worker/event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">Подробнее</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <!-- Мини-календарь -->
            <div class="mini-calendar-section">
                <h2>Календарь на <?php echo date('F Y'); ?></h2>
                <div class="mini-calendar" id="miniCalendar"></div>
            </div>
        </div>
    </div>
    <?php include '../includes/worker-footer.php'; ?>
    <script src="/assets/js/calendar.js"></script>
    <script>
        const calendarData = {
            workDays: <?php echo json_encode(array_keys($calendar['work_days'])); ?>,
            unavailableDays: <?php echo json_encode($calendar['unavailable_days']); ?>,
            year: <?php echo $currentYear; ?>,
            month: <?php echo $currentMonth; ?>
        };
        renderMiniCalendar('miniCalendar', calendarData);
    </script>
</body>
</html>