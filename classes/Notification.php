<?php
class Notification {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function create($userId, $type, $title, $message, $link = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $type, $title, $message, $link]);
            return ['success' => true, 'notification_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function getByUser($userId, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    public function getUnread($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    public function markAsRead($notificationId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notificationId]);
        return ['success' => true];
    }
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        return ['success' => true];
    }
    public function delete($notificationId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        return ['success' => true];
    }
    public function sendEventReminders() {
        $stmt = $this->db->prepare("
            SELECT e.*, es.worker_id 
            FROM events e 
            JOIN event_staff es ON e.id = es.event_id 
            WHERE e.event_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) 
            AND e.status = 'active' 
            AND es.status IN ('pending', 'confirmed')
        ");
        $stmt->execute();
        $events = $stmt->fetchAll();
        foreach ($events as $event) {
            $this->create(
                $event['worker_id'],
                'event_reminder',
                'Напоминание о мероприятии',
                "Завтра {$event['event_date']} в {$event['start_time']} - {$event['title']}",
                "/worker/event.php?id={$event['id']}"
            );
        }
    }
}