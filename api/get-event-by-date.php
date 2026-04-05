<?php
header('Content-Type: application/json');
require_once '../config/config.php';
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$date = $_GET['date'] ?? '';
$workerId = $_SESSION['user_id'];
if (empty($date)) {
    echo json_encode(['success' => false, 'error' => 'Date required']);
    exit;
}
$eventObj = new Event();
if ($_SESSION['user_role'] === 'worker') {
    $events = $eventObj->getByWorker($workerId);
    foreach ($events as $event) {
        if ($event['event_date'] === $date) {
            echo json_encode([
                'success' => true,
                'event' => $event
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'No event found']);
} else {
    echo json_encode(['success' => false]);
}