<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$eventObj = new Event();
$chatObj = new Chat();
$paymentObj = new Payment();
$userId = $_SESSION['user_id'];
$eventId = $_GET['id'] ?? 0;
$event = $eventObj->getById($eventId);
if (!$event || $event['organizer_id'] != $userId) {
    redirect('/organizer/dashboard.php');
}
$staff = $eventObj->getEventStaff($eventId);
$chat = $chatObj->getEventChat($eventId);
$payments = $paymentObj->getByEvent($eventId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'remove_worker') {
        $workerId = $_POST['worker_id'] ?? 0;
        $eventObj->removeWorker($eventId, $workerId, $userId);
        redirect("/organizer/event.php?id=$eventId");
    } elseif ($action === 'approve_payment') {
        $paymentId = $_POST['payment_id'] ?? 0;
        $paymentObj->updateStatus($paymentId, 'approved');
        redirect("/organizer/event.php?id=$eventId");
    } elseif ($action === 'mark_paid') {
        $paymentId = $_POST['payment_id'] ?? 0;
        $paymentObj->updateStatus($paymentId, 'paid');
        redirect("/organizer/event.php?id=$eventId");
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
    <?php include '../includes/organizer-header.php'; ?>
    <div class="event-management-page">
        <div class="container">
            <div class="event-header">
                <div>
                    <h1><?php echo escape($event['title']); ?></h1>
                    <p>
                        📅 <?php echo date('d.m.Y', strtotime($event['event_date'])); ?> 
                        ⏰ <?php echo date('H:i', strtotime($event['start_time'])); ?>
                        <?php if ($event['end_time']): ?>
                            - <?php echo date('H:i', strtotime($event['end_time'])); ?>
                        <?php endif; ?>
                    </p>
                    <p>📍 <?php echo escape($event['location']); ?></p>
                </div>
                <div class="header-actions">
                    <a href="/organizer/edit-event.php?id=<?php echo $eventId; ?>" class="btn btn-outline">Редактировать</a>
                    <?php if ($chat): ?>
                    <a href="/chat.php?id=<?php echo $chat['id']; ?>" class="btn btn-primary">💬 Чат команды</a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Команда -->
            <div class="team-section">
                <div class="section-header">
                    <h2>Команда (<?php echo count($staff); ?>)</h2>
                    <a href="/organizer/add-staff.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary">+ Добавить сотрудника</a>
                </div>
                <?php if (empty($staff)): ?>
                <div class="empty-state">
                    <p>В команде пока нет сотрудников</p>
                </div>
                <?php else: ?>
                <div class="team-grid">
                    <?php foreach ($staff as $worker): ?>
                    <div class="team-member-card">
                        <div class="member-header">
                            <div>
                                <h3><?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?></h3>
                                <span class="badge"><?php echo escape($worker['specialty']); ?></span>
                            </div>
                            <span class="badge badge-<?php echo $worker['status']; ?>">
                                <?php 
                                $statuses = [
                                    'pending' => 'Ожидает',
                                    'confirmed' => 'Подтвердил',
                                    'on_location' => 'На месте',
                                    'completed' => 'Завершил'
                                ];
                                echo $statuses[$worker['status']] ?? $worker['status'];
                                ?>
                            </span>
                        </div>
                        <div class="member-body">
                            <p><strong>Ставка:</strong> <?php echo number_format($worker['hourly_rate'], 0); ?> ₽/час</p>
                            <?php if ($worker['confirmed_at']): ?>
                            <p><small>Подтвердил: <?php echo date('d.m.Y H:i', strtotime($worker['confirmed_at'])); ?></small></p>
                            <?php endif; ?>
                            <?php if ($worker['arrived_at']): ?>
                            <p><small>Прибыл: <?php echo date('d.m.Y H:i', strtotime($worker['arrived_at'])); ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div class="member-actions">
                            <?php
                            $workerChatResult = $chatObj->getOrCreateOrganizerChat($worker['id'], $userId);
                            ?>
                            <?php if ($workerChatResult['success']): ?>
                            <a href="/chat.php?id=<?php echo $workerChatResult['chat_id']; ?>" class="btn btn-sm btn-secondary">
                                💬 Чат
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="remove_worker">
                                <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline" 
                                        onclick="return confirm('Удалить сотрудника из команды?')">
                                    Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <!-- Платежи -->
            <div class="payments-section">
                <h2>Оплата (<?php echo count($payments); ?>)</h2>
                <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <p>Нет данных об оплате</p>
                </div>
                <?php else: ?>
                <?php
                $totalAmount = 0;
                foreach ($payments as $payment) {
                    $totalAmount += $payment['total_amount'];
                }
                ?>
                <div class="payment-total">
                    <strong>Общая сумма к выплате:</strong> <?php echo number_format($totalAmount, 0, ',', ' '); ?> ₽
                </div>
                <div class="payments-list">
                    <?php foreach ($payments as $payment): ?>
                    <div class="payment-card">
                        <div class="payment-header">
                            <div>
                                <strong><?php echo escape($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
                                <span class="badge"><?php echo escape($payment['specialty']); ?></span>
                            </div>
                            <span class="badge badge-<?php echo $payment['status']; ?>">
                                <?php 
                                $paymentStatuses = [
                                    'pending' => 'Ожидает',
                                    'approved' => 'Подтверждено',
                                    'paid' => 'Оплачено'
                                ];
                                echo $paymentStatuses[$payment['status']];
                                ?>
                            </span>
                        </div>
                        <div class="payment-details">
                            <div class="payment-item">
                                <span>Часы работы:</span>
                                <span><?php echo $payment['hours_worked']; ?> ч × <?php echo number_format($payment['hourly_rate'], 0); ?> ₽</span>
                            </div>
                            <div class="payment-item">
                                <span>Оплата за работу:</span>
                                <strong><?php echo number_format($payment['work_payment'], 0, ',', ' '); ?> ₽</strong>
                            </div>
                            <?php if ($payment['travel_cost'] > 0): ?>
                            <div class="payment-item">
                                <span>Проезд (<?php echo $payment['travel_type'] === 'own_car' ? 'авто' : 'такси'; ?>):</span>
                                <strong><?php echo number_format($payment['travel_cost'], 0, ',', ' '); ?> ₽</strong>
                            </div>
                            <?php if ($payment['travel_receipt']): ?>
                            <div class="payment-item">
                                <a href="/<?php echo escape($payment['travel_receipt']); ?>" target="_blank" class="btn btn-sm">Чек</a>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            <div class="payment-item payment-total">
                                <span>Итого:</span>
                                <strong><?php echo number_format($payment['total_amount'], 0, ',', ' '); ?> ₽</strong>
                            </div>
                        </div>
                        <div class="payment-actions">
                            <?php if ($payment['status'] === 'pending'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="approve_payment">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">Подтвердить</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($payment['status'] === 'approved'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="mark_paid">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Отметить оплаченным</button>
                            </form>
                            <?php endif; ?>
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