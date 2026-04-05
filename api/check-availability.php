<?php
header('Content-Type: application/json');
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$date = $_GET['date'] ?? '';
if (empty($date)) {
    echo json_encode(['success' => false, 'error' => 'Date required']);
    exit;
}
$userObj = new User();
$workers = $userObj->getAllWorkers();
$availability = [];
foreach ($workers as $worker) {
    $isAvailable = $userObj->isWorkerAvailable($worker['id'], $date);
    $availability[$worker['id']] = $isAvailable;
}
echo json_encode([
    'success' => true,
    'availability' => $availability
]);