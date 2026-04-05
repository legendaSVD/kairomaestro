<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'worker') {
    redirect('/login.php');
}
$eventObj = new Event();
$chatObj = new Chat();
$paymentObj = new Payment();
$userId = $_SESSION['user_id'];
$eventId = $_GET['id'] ?? 0;
$event = $eventObj->getById($eventId);
if (!$event) {
    redirect('/worker/dashboard.php');
}
$staff = $eventObj->getEventStaff($eventId);
$isAssigned = false;
$workerStatus = '';
foreach ($staff as $worker) {
    if ($worker['id'] == $userId) {
        $isAssigned = true;
        $workerStatus = $worker['status'];
        break;
    }
}
if (!$isAssigned) {
    redirect('/worker/dashboard.php');
}
$chat = $chatObj->getEventChat($eventId);
$organizerChat = $chatObj->getOrCreateOrganizerChat($userId, $event['organizer_id']);
$payments = $paymentObj->getByEvent($eventId);
$myPayment = null;
foreach ($payments as $payment) {
    if ($payment['worker_id'] == $userId) {
        $myPayment = $payment;
        break;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'confirm') {
        $eventObj->updateWorkerStatus($eventId, $userId, 'confirmed');
        redirect("/worker/event.php?id=$eventId");
    } elseif ($action === 'arrive') {
        $eventObj->updateWorkerStatus($eventId, $userId, 'on_location');
        redirect("/worker/event.php?id=$eventId");
    } elseif ($action === 'complete') {
        $eventObj->updateWorkerStatus($eventId, $userId, 'completed');
        redirect("/worker/event.php?id=$eventId");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($event['title']); ?> - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/worker-header.php'; ?>
    <div class="event-page">
        <div class="container">
            <div class="event-header-card">
                <div class="event-header-content">
                    <h1><?php echo escape($event['title']); ?></h1>
                    <span class="status-badge badge-<?php echo $workerStatus; ?>">
                        <?php 
                        $statuses = [
                            'pending' => '⏳ Ожидает подтверждения',
                            'confirmed' => '✅ Подтверждено',
                            'on_location' => '📍 Вы на месте',
                            'completed' => '✓ Завершено'
                        ];
                        echo $statuses[$workerStatus] ?? $workerStatus;
                        ?>
                    </span>
                </div>
            </div>
            <div class="event-content-grid">
                <div class="info-card">
                    <h2>📋 Информация о мероприятии</h2>
                    <div class="info-list">
                        <div class="info-row">
                            <div class="info-icon">📅</div>
                            <div class="info-content">
                                <div class="info-label">Дата</div>
                                <div class="info-value"><?php echo formatRussianDate($event['event_date'], 'full'); ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">⏰</div>
                            <div class="info-content">
                                <div class="info-label">Время</div>
                                <div class="info-value">
                                    <?php echo formatTime($event['start_time']); ?>
                                    <?php if ($event['end_time']): ?>
                                        - <?php echo formatTime($event['end_time']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">📍</div>
                            <div class="info-content">
                                <div class="info-label">Место</div>
                                <div class="info-value"><?php echo escape($event['location']); ?></div>
                                <?php if ($event['latitude'] && $event['longitude']): ?>
                                <button onclick="openMap(<?php echo $event['latitude']; ?>, <?php echo $event['longitude']; ?>)" class="btn btn-sm btn-outline" style="margin-top: 0.5rem;">
                                    📍 Открыть на карте
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">👔</div>
                            <div class="info-content">
                                <div class="info-label">Организатор</div>
                                <div class="info-value">
                                    <?php echo escape($event['organizer_first_name'] . ' ' . $event['organizer_last_name']); ?>
                                    <br>
                                    <a href="tel:<?php echo escape($event['organizer_phone']); ?>" class="phone-link">
                                        <?php echo escape($event['organizer_phone']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php if ($event['technical_task']): ?>
                        <div class="info-row">
                            <div class="info-icon">📋</div>
                            <div class="info-content">
                                <div class="info-label">Техническое задание</div>
                                <div class="info-value"><?php echo nl2br(escape($event['technical_task'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-card">
                    <h2>👥 Команда (<?php echo count($staff); ?>)</h2>
                    <div class="team-list">
                        <?php foreach ($staff as $worker): ?>
                        <div class="team-member-item">
                            <div class="team-member-avatar">
                                <?php if ($worker['avatar']): ?>
                                    <img src="/uploads/avatars/<?php echo escape($worker['avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="team-avatar-placeholder">
                                        <?php echo mb_substr($worker['first_name'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="team-member-info">
                                <div class="team-member-name">
                                    <?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                </div>
                                <div class="team-member-specialty">
                                    <?php echo escape($worker['specialty']); ?>
                                </div>
                            </div>
                            <span class="team-member-status badge-<?php echo $worker['status']; ?>">
                                <?php echo $statuses[$worker['status']] ?? $worker['status']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="actions-card">
                <h2>⚡ Действия</h2>
                <div class="actions-grid">
                    <?php if ($workerStatus === 'pending'): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            ✓ Подтвердить участие
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($workerStatus === 'confirmed' && strtotime($event['event_date']) <= strtotime(date('Y-m-d'))): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="arrive">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            📍 Я на месте
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($workerStatus === 'on_location'): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            ✓ Завершить работу
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($chat): ?>
                    <a href="/chat.php?id=<?php echo $chat['id']; ?>" class="btn btn-primary btn-lg btn-block">
                        💬 Чат с командой
                    </a>
                    <?php endif; ?>
                    <?php if ($organizerChat['success'] && $organizerChat['chat_id']): ?>
                    <a href="/chat.php?id=<?php echo $organizerChat['chat_id']; ?>" class="btn btn-secondary btn-lg btn-block">
                        👔 Чат с организатором
                    </a>
                    <?php endif; ?>
                    <?php if ($workerStatus === 'completed'): ?>
                    <a href="/worker/payment.php?event_id=<?php echo $eventId; ?>" class="btn btn-secondary btn-lg btn-block">
                        💰 <?php echo $myPayment ? 'Просмотреть' : 'Добавить'; ?> оплату
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($myPayment): ?>
            <div class="payment-info-card">
                <h2>💰 Оплата</h2>
                <div class="payment-details-grid">
                    <div class="payment-detail-item">
                        <div class="payment-label">Часы работы</div>
                        <div class="payment-value"><?php echo $myPayment['hours_worked']; ?> ч</div>
                    </div>
                    <div class="payment-detail-item">
                        <div class="payment-label">Оплата за работу</div>
                        <div class="payment-value"><?php echo number_format($myPayment['work_payment'], 0, ',', ' '); ?> ₽</div>
                    </div>
                    <?php if ($myPayment['travel_cost'] > 0): ?>
                    <div class="payment-detail-item">
                        <div class="payment-label">Компенсация проезда</div>
                        <div class="payment-value"><?php echo number_format($myPayment['travel_cost'], 0, ',', ' '); ?> ₽</div>
                    </div>
                    <?php endif; ?>
                    <div class="payment-detail-item payment-total-item">
                        <div class="payment-label">Итого</div>
                        <div class="payment-value-total"><?php echo number_format($myPayment['total_amount'], 0, ',', ' '); ?> ₽</div>
                    </div>
                </div>
                <div class="payment-status-badge">
                    <span class="badge badge-<?php echo $myPayment['status']; ?>">
                        <?php 
                        $paymentStatuses = [
                            'pending' => 'Ожидает подтверждения',
                            'approved' => 'Подтверждено',
                            'paid' => 'Оплачено'
                        ];
                        echo $paymentStatuses[$myPayment['status']] ?? $myPayment['status'];
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../includes/worker-footer.php'; ?>
    <script>
        function openMap(lat, lng) {
            window.open(`https://yandex.ru/maps/?pt=${lng},${lat}&z=16&l=map`, '_blank');
        }
    </script>
</body>
</html>