<?php
class Availability {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function markUnavailable($workerId, $date, $reason = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO worker_availability (worker_id, unavailable_date, reason) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE reason = VALUES(reason)
            ");
            $stmt->execute([$workerId, $date, $reason]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function markAvailable($workerId, $date) {
        $stmt = $this->db->prepare("
            DELETE FROM worker_availability 
            WHERE worker_id = ? AND unavailable_date = ?
        ");
        $stmt->execute([$workerId, $date]);
        return ['success' => true];
    }
    public function getUnavailableDays($workerId, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT * FROM worker_availability WHERE worker_id = ?";
        $params = [$workerId];
        if ($dateFrom) {
            $sql .= " AND unavailable_date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND unavailable_date <= ?";
            $params[] = $dateTo;
        }
        $sql .= " ORDER BY unavailable_date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function isAvailable($workerId, $date) {
        $stmt = $this->db->prepare("
            SELECT id FROM worker_availability 
            WHERE worker_id = ? AND unavailable_date = ?
        ");
        $stmt->execute([$workerId, $date]);
        if ($stmt->fetch()) {
            return ['available' => false, 'reason' => 'personal'];
        }
        $stmt = $this->db->prepare("
            SELECT e.id, e.title FROM events e 
            JOIN event_staff es ON e.id = es.event_id 
            WHERE es.worker_id = ? AND e.event_date = ? AND e.status != 'cancelled'
        ");
        $stmt->execute([$workerId, $date]);
        $event = $stmt->fetch();
        if ($event) {
            return [
                'available' => false, 
                'reason' => 'busy', 
                'event_id' => $event['id'],
                'event_title' => $event['title']
            ];
        }
        return ['available' => true];
    }
}