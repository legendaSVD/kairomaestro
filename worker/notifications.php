<?php
require_once '../config/config.php';
if (!isLoggedIn()) {
    redirect('/login.php');
}
$notificationObj = new Notification();
$userId = $_SESSION['user_id'];
$notifications = $notificationObj->getByUser($userId, 50);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $notificationObj->markAllAsRead($userId);
    redirect($_SESSION['user_role'] === 'worker' ? '/worker/notifications.php' : '/organizer/notifications.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php 
    if ($_SESSION['user_role'] === 'worker') {
        include '../includes/worker-header.php';
    } else {
        include '../includes/organizer-header.php';
    }
    ?>
    <div class="notifications-page">
        <div class="container">
            <div class="page-header">
                <h1>Уведомления</h1>
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn btn-outline">Отметить все прочитанными</button>
                </form>
            </div>
            <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <p>У вас нет уведомлений</p>
            </div>
            <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
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
            <?php endif; ?>
        </div>
    </div>
    <?php 
    if ($_SESSION['user_role'] === 'worker') {
        include '../includes/worker-footer.php';
    } else {
        include '../includes/organizer-footer.php';
    }
    ?>
</body>
</html>