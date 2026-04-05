<?php
class Event {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO events 
                (organizer_id, title, event_date, start_time, end_time, location, latitude, longitude, technical_task, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['organizer_id'],
                $data['title'],
                $data['event_date'],
                $data['start_time'],
                $data['end_time'] ?? null,
                $data['location'],
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['technical_task'] ?? null,
                $data['status'] ?? 'active'
            ]);
            $eventId = $this->db->lastInsertId();
            $this->logAction($data['organizer_id'], 'event_created', 
                "Создано мероприятие: {$data['title']}", 'event', $eventId);
            $this->createEventChat($eventId, $data['organizer_id']);
            return ['success' => true, 'event_id' => $eventId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    private function createEventChat($eventId, $organizerId) {
        $stmt = $this->db->prepare("INSERT INTO chats (event_id, type) VALUES (?, 'event_group')");
        $stmt->execute([$eventId]);
        $chatId = $this->db->lastInsertId();
        $stmt = $this->db->prepare("INSERT INTO chat_participants (chat_id, user_id) VALUES (?, ?)");
        $stmt->execute([$chatId, $organizerId]);
        return $chatId;
    }
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.first_name as organizer_first_name, u.last_name as organizer_last_name, u.phone as organizer_phone 
            FROM events e 
            LEFT JOIN users u ON e.organizer_id = u.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function getByOrganizer($organizerId, $filters = []) {
        $sql = "SELECT * FROM events WHERE organizer_id = ?";
        $params = [$organizerId];
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND event_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND event_date <= ?";
            $params[] = $filters['date_to'];
        }
        $sql .= " ORDER BY event_date DESC, start_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function getByWorker($workerId, $filters = []) {
        $sql = "
            SELECT e.*, es.status as worker_status, es.confirmed_at, es.arrived_at 
            FROM events e 
            JOIN event_staff es ON e.id = es.event_id 
            WHERE es.worker_id = ?
        ";
        $params = [$workerId];
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY e.event_date DESC, e.start_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function assignWorker($eventId, $workerId, $organizerId) {
        try {
            $this->db->beginTransaction();
            $event = $this->getById($eventId);
            $userObj = new User();
            if (!$userObj->isWorkerAvailable($workerId, $event['event_date'])) {
                throw new Exception("Этот сотрудник уже работает в этот день");
            }
            $stmt = $this->db->prepare("
                INSERT INTO event_staff (event_id, worker_id, status) 
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$eventId, $workerId]);
            $stmt = $this->db->prepare("SELECT id FROM chats WHERE event_id = ? AND type = 'event_group'");
            $stmt->execute([$eventId]);
            $chat = $stmt->fetch();
            if ($chat) {
                $stmt = $this->db->prepare("INSERT INTO chat_participants (chat_id, user_id) VALUES (?, ?)");
                $stmt->execute([$chat['id'], $workerId]);
            }
            $this->createNotification($workerId, 'event_assigned', 
                'Новое мероприятие', 
                "Вы назначены на мероприятие {$event['title']}", 
                "/worker/event.php?id={$eventId}");
            $worker = $userObj->getUserById($workerId);
            $this->logAction($organizerId, 'worker_assigned', 
                "Работник {$worker['first_name']} {$worker['last_name']} назначен на мероприятие", 
                'event', $eventId);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function removeWorker($eventId, $workerId, $organizerId) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM event_staff WHERE event_id = ? AND worker_id = ?");
            $stmt->execute([$eventId, $workerId]);
            $stmt = $this->db->prepare("
                DELETE cp FROM chat_participants cp 
                JOIN chats c ON cp.chat_id = c.id 
                WHERE c.event_id = ? AND cp.user_id = ?
            ");
            $stmt->execute([$eventId, $workerId]);
            $event = $this->getById($eventId);
            $this->createNotification($workerId, 'event_removed', 
                'Изменение в расписании', 
                "Вы удалены из мероприятия {$event['title']}", null);
            $userObj = new User();
            $worker = $userObj->getUserById($workerId);
            $this->logAction($organizerId, 'worker_removed', 
                "Работник {$worker['first_name']} {$worker['last_name']} удалён из мероприятия", 
                'event', $eventId);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function getEventStaff($eventId) {
        $stmt = $this->db->prepare("
            SELECT u.*, wp.specialty, wp.hourly_rate, es.status, es.confirmed_at, es.arrived_at 
            FROM event_staff es 
            JOIN users u ON es.worker_id = u.id 
            LEFT JOIN worker_profiles wp ON u.id = wp.user_id 
            WHERE es.event_id = ?
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }
    public function updateWorkerStatus($eventId, $workerId, $status) {
        $stmt = $this->db->prepare("
            UPDATE event_staff 
            SET status = ?, 
                confirmed_at = CASE WHEN ? = 'confirmed' THEN NOW() ELSE confirmed_at END,
                arrived_at = CASE WHEN ? = 'on_location' THEN NOW() ELSE arrived_at END
            WHERE event_id = ? AND worker_id = ?
        ");
        $stmt->execute([$status, $status, $status, $eventId, $workerId]);
        return ['success' => true];
    }
    public function update($eventId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE events 
                SET title = ?, event_date = ?, start_time = ?, end_time = ?, 
                    location = ?, latitude = ?, longitude = ?, technical_task = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['event_date'],
                $data['start_time'],
                $data['end_time'] ?? null,
                $data['location'],
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['technical_task'] ?? null,
                $data['status'] ?? 'active',
                $eventId
            ]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    private function createNotification($userId, $type, $title, $message, $link) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $title, $message, $link]);
    }
    private function logAction($userId, $actionType, $description, $entityType, $entityId) {
        $stmt = $this->db->prepare("
            INSERT INTO action_logs (user_id, action_type, description, entity_type, entity_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $actionType, $description, $entityType, $entityId]);
    }
    public function getWorkerCalendar($workerId, $year, $month) {
        $stmt = $this->db->prepare("
            SELECT e.event_date, e.id, e.title, es.status 
            FROM events e 
            JOIN event_staff es ON e.id = es.event_id 
            WHERE es.worker_id = ? 
            AND YEAR(e.event_date) = ? 
            AND MONTH(e.event_date) = ? 
            AND e.status != 'cancelled'
        ");
        $stmt->execute([$workerId, $year, $month]);
        $workDays = $stmt->fetchAll(PDO::FETCH_GROUP);
        $stmt = $this->db->prepare("
            SELECT unavailable_date 
            FROM worker_availability 
            WHERE worker_id = ? 
            AND YEAR(unavailable_date) = ? 
            AND MONTH(unavailable_date) = ?
        ");
        $stmt->execute([$workerId, $year, $month]);
        $unavailableDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return [
            'work_days' => $workDays,
            'unavailable_days' => $unavailableDays
        ];
    }
}