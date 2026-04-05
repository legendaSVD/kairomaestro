<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$eventObj = new Event();
$notificationObj = new Notification();
$userId = $_SESSION['user_id'];
$upcomingEvents = $eventObj->getByOrganizer($userId, ['date_from' => date('Y-m-d')]);
$pastEvents = $eventObj->getByOrganizer($userId, ['date_to' => date('Y-m-d', strtotime('-1 day'))]);
$notifications = $notificationObj->getUnread($userId);
$notificationCount = count($notifications);
$totalEvents = count($upcomingEvents) + count($pastEvents);
$activeEvents = count(array_filter($upcomingEvents, function($e) {
    return $e['status'] === 'active';
}));
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
    <?php include '../includes/organizer-header.php'; ?>
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1>Привет, <?php echo escape($_SESSION['user_name']); ?>! 👋</h1>
                    <p class="dashboard-subtitle">Управляйте вашими мероприятиями</p>
                </div>
                <a href="/organizer/create-event.php" class="btn btn-primary btn-lg">+ Создать мероприятие</a>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $totalEvents; ?></div>
                        <div class="stat-label">Всего мероприятий</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $activeEvents; ?></div>
                        <div class="stat-label">Активных</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($pastEvents); ?></div>
                        <div class="stat-label">Завершено</div>
                    </div>
                </div>
            </div>
            <?php if ($notificationCount > 0): ?>
            <div class="notifications-section">
                <div class="section-header">
                    <h2>🔔 Уведомления <span class="badge badge-count"><?php echo $notificationCount; ?></span></h2>
                    <a href="/organizer/notifications.php" class="btn btn-outline btn-sm">Все уведомления</a>
                </div>
                <div class="notifications-list">
                    <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                    <div class="notification-card">
                        <div class="notification-content">
                            <h4><?php echo escape($notification['title']); ?></h4>
                            <p><?php echo escape($notification['message']); ?></p>
                            <small class="notification-time">
                                <?php echo formatRussianDateTime($notification['created_at']); ?>
                            </small>
                        </div>
                        <?php if ($notification['link']): ?>
                        <a href="<?php echo escape($notification['link']); ?>" class="btn btn-sm btn-primary">
                            Открыть
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="events-section">
                <div class="section-header">
                    <h2>📅 Предстоящие мероприятия</h2>
                    <a href="/organizer/events.php" class="btn btn-outline">Все мероприятия</a>
                </div>
                <?php if (empty($upcomingEvents)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>У вас пока нет запланированных мероприятий</h3>
                    <p>Создайте ваше первое мероприятие прямо сейчас</p>
                    <a href="/organizer/create-event.php" class="btn btn-primary btn-lg">Создать мероприятие</a>
                </div>
                <?php else: ?>
                <div class="events-grid">
                    <?php foreach (array_slice($upcomingEvents, 0, 6) as $event): ?>
                    <div class="event-card">
                        <div class="event-card-header">
                            <div class="event-date-badge">
                                <span class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                <span class="date-month"><?php echo formatRussianDate($event['event_date'], 'month_short'); ?></span>
                            </div>
                            <span class="event-status-badge badge-<?php echo $event['status']; ?>">
                                <?php 
                                $statuses = [
                                    'active' => 'Активно',
                                    'completed' => 'Завершено',
                                    'cancelled' => 'Отменено'
                                ];
                                echo $statuses[$event['status']] ?? $event['status'];
                                ?>
                            </span>
                        </div>
                        <div class="event-card-body">
                            <h3 class="event-title"><?php echo escape($event['title']); ?></h3>
                            <div class="event-details">
                                <div class="event-detail-item">
                                    <span class="icon">⏰</span>
                                    <span><?php echo formatTime($event['start_time']); ?></span>
                                </div>
                                <div class="event-detail-item">
                                    <span class="icon">📍</span>
                                    <span><?php echo escape($event['location']); ?></span>
                                </div>
                                <?php
                                $staff = $eventObj->getEventStaff($event['id']);
                                ?>
                                <div class="event-detail-item">
                                    <span class="icon">👥</span>
                                    <span><?php echo count($staff); ?> сотрудников</span>
                                </div>
                            </div>
                        </div>
                        <div class="event-card-footer">
                            <a href="/organizer/event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">
                                Управление
                            </a>
                            <a href="/organizer/edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">
                                Редактировать
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/organizer-footer.php'; ?>
</body>
</html>