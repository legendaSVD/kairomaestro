<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$eventObj = new Event();
$userId = $_SESSION['user_id'];
$statusFilter = $_GET['status'] ?? '';
$filters = [];
if ($statusFilter) {
    $filters['status'] = $statusFilter;
}
$events = $eventObj->getByOrganizer($userId, $filters);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои мероприятия - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/organizer-header.php'; ?>
    <div class="events-page">
        <div class="container">
            <div class="page-header">
                <h1>📅 Мои мероприятия</h1>
                <a href="/organizer/create-event.php" class="btn btn-primary">+ Создать мероприятие</a>
            </div>
            <div class="filters-tabs">
                <a href="/organizer/events.php" class="filter-tab <?php echo !$statusFilter ? 'active' : ''; ?>">
                    Все
                </a>
                <a href="/organizer/events.php?status=active" class="filter-tab <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
                    Активные
                </a>
                <a href="/organizer/events.php?status=completed" class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                    Завершённые
                </a>
                <a href="/organizer/events.php?status=cancelled" class="filter-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                    Отменённые
                </a>
            </div>
            <?php if (empty($events)): ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>Нет мероприятий</h3>
                <p>Создайте ваше первое мероприятие</p>
                <a href="/organizer/create-event.php" class="btn btn-primary">Создать мероприятие</a>
            </div>
            <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
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
    <?php include '../includes/organizer-footer.php'; ?>
</body>
</html>