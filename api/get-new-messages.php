<?php
header('Content-Type: application/json');
require_once '../config/config.php';
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$chatId = $_GET['chat_id'] ?? 0;
$lastId = $_GET['last_id'] ?? 0;
if (!$chatId) {
    echo json_encode(['success' => false, 'error' => 'Chat ID required']);
    exit;
}
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT m.*, u.first_name, u.last_name 
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.chat_id = ? AND m.id > ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$chatId, $lastId]);
$messages = $stmt->fetchAll();
echo json_encode([
    'success' => true,
    'messages' => $messages
]);