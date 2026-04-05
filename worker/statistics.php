<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'worker') {
    redirect('/login.php');
}
$paymentObj = new Payment();
$eventObj = new Event();
$userId = $_SESSION['user_id'];
$statistics = $paymentObj->getWorkerStatistics($userId);
$allPayments = $paymentObj->getByWorker($userId);
$pendingPayments = array_filter($allPayments, fn($p) => $p['status'] === 'pending');
$approvedPayments = array_filter($allPayments, fn($p) => $p['status'] === 'approved');
$paidPayments = array_filter($allPayments, fn($p) => $p['status'] === 'paid');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/worker-header.php'; ?>
    <div class="statistics-page">
        <div class="container">
            <h1>Моя статистика</h1>
            <!-- Общая статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $statistics['total_events'] ?? 0; ?></div>
                        <div class="stat-label">Завершённых мероприятий</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($statistics['total_earnings'] ?? 0, 0, ',', ' '); ?> ₽</div>
                        <div class="stat-label">Общий заработок</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($statistics['total_hours'] ?? 0, 1); ?> ч</div>
                        <div class="stat-label">Всего часов</div>
                    </div>
                </div>
            </div>
            <!-- История платежей -->
            <div class="payments-history">
                <h2>История платежей</h2>
                <!-- Ожидающие -->
                <?php if (!empty($pendingPayments)): ?>
                <div class="payments-section">
                    <h3>Ожидают подтверждения (<?php echo count($pendingPayments); ?>)</h3>
                    <div class="payments-list">
                        <?php foreach ($pendingPayments as $payment): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <strong><?php echo escape($payment['event_title']); ?></strong>
                                <span class="badge badge-pending">Ожидает</span>
                            </div>
                            <p><?php echo date('d.m.Y', strtotime($payment['event_date'])); ?></p>
                            <p>Часы: <?php echo $payment['hours_worked']; ?> ч</p>
                            <p><strong><?php echo number_format($payment['total_amount'], 0, ',', ' '); ?> ₽</strong></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Подтверждённые -->
                <?php if (!empty($approvedPayments)): ?>
                <div class="payments-section">
                    <h3>Подтверждены (<?php echo count($approvedPayments); ?>)</h3>
                    <div class="payments-list">
                        <?php foreach ($approvedPayments as $payment): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <strong><?php echo escape($payment['event_title']); ?></strong>
                                <span class="badge badge-approved">Подтверждено</span>
                            </div>
                            <p><?php echo date('d.m.Y', strtotime($payment['event_date'])); ?></p>
                            <p>Часы: <?php echo $payment['hours_worked']; ?> ч</p>
                            <p><strong><?php echo number_format($payment['total_amount'], 0, ',', ' '); ?> ₽</strong></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Оплаченные -->
                <?php if (!empty($paidPayments)): ?>
                <div class="payments-section">
                    <h3>Оплачены (<?php echo count($paidPayments); ?>)</h3>
                    <div class="payments-list">
                        <?php foreach ($paidPayments as $payment): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <strong><?php echo escape($payment['event_title']); ?></strong>
                                <span class="badge badge-paid">Оплачено</span>
                            </div>
                            <p><?php echo date('d.m.Y', strtotime($payment['event_date'])); ?></p>
                            <p>Часы: <?php echo $payment['hours_worked']; ?> ч</p>
                            <p><strong><?php echo number_format($payment['total_amount'], 0, ',', ' '); ?> ₽</strong></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (empty($allPayments)): ?>
                <div class="empty-state">
                    <p>История платежей пока пуста</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/worker-footer.php'; ?>
</body>
</html>