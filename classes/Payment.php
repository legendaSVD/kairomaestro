<?php
class Payment {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function create($data) {
        try {
            $stmt = $this->db->prepare("SELECT hourly_rate FROM worker_profiles WHERE user_id = ?");
            $stmt->execute([$data['worker_id']]);
            $profile = $stmt->fetch();
            $hourlyRate = $data['hourly_rate'] ?? $profile['hourly_rate'] ?? 0;
            $hoursWorked = $data['hours_worked'] ?? 0;
            $workPayment = $hoursWorked * $hourlyRate;
            $travelCost = 0;
            if ($data['travel_type'] === 'own_car') {
                $travelCost = ($data['travel_distance'] ?? 0) * 10;
            } elseif ($data['travel_type'] === 'taxi') {
                $travelCost = $data['travel_cost'] ?? 0;
            }
            $totalAmount = $workPayment + $travelCost;
            $stmt = $this->db->prepare("
                INSERT INTO payments 
                (event_id, worker_id, hours_worked, hourly_rate, work_payment, 
                 travel_type, travel_distance, travel_cost, travel_receipt, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $data['event_id'],
                $data['worker_id'],
                $hoursWorked,
                $hourlyRate,
                $workPayment,
                $data['travel_type'] ?? 'none',
                $data['travel_distance'] ?? null,
                $travelCost,
                $data['travel_receipt'] ?? null,
                $totalAmount
            ]);
            $paymentId = $this->db->lastInsertId();
            $this->updateWorkerStatistics($data['worker_id']);
            return ['success' => true, 'payment_id' => $paymentId, 'total_amount' => $totalAmount];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function update($paymentId, $data) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            if (!$payment) {
                throw new Exception("Платёж не найден");
            }
            $hoursWorked = $data['hours_worked'] ?? $payment['hours_worked'];
            $hourlyRate = $data['hourly_rate'] ?? $payment['hourly_rate'];
            $workPayment = $hoursWorked * $hourlyRate;
            $travelType = $data['travel_type'] ?? $payment['travel_type'];
            $travelCost = 0;
            if ($travelType === 'own_car') {
                $travelDistance = $data['travel_distance'] ?? $payment['travel_distance'];
                $travelCost = $travelDistance * 10;
            } elseif ($travelType === 'taxi') {
                $travelCost = $data['travel_cost'] ?? $payment['travel_cost'];
            }
            $totalAmount = $workPayment + $travelCost;
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET hours_worked = ?, hourly_rate = ?, work_payment = ?, 
                    travel_type = ?, travel_distance = ?, travel_cost = ?, 
                    travel_receipt = ?, total_amount = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $hoursWorked,
                $hourlyRate,
                $workPayment,
                $travelType,
                $data['travel_distance'] ?? $payment['travel_distance'],
                $travelCost,
                $data['travel_receipt'] ?? $payment['travel_receipt'],
                $totalAmount,
                $paymentId
            ]);
            $this->updateWorkerStatistics($payment['worker_id']);
            return ['success' => true, 'total_amount' => $totalAmount];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function getByEvent($eventId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.first_name, u.last_name, wp.specialty 
            FROM payments p 
            JOIN users u ON p.worker_id = u.id 
            LEFT JOIN worker_profiles wp ON u.id = wp.user_id 
            WHERE p.event_id = ?
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }
    public function getByWorker($workerId, $filters = []) {
        $sql = "
            SELECT p.*, e.title as event_title, e.event_date 
            FROM payments p 
            JOIN events e ON p.event_id = e.id 
            WHERE p.worker_id = ?
        ";
        $params = [$workerId];
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY e.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function updateStatus($paymentId, $status) {
        $stmt = $this->db->prepare("
            UPDATE payments 
            SET status = ?, 
                paid_at = CASE WHEN ? = 'paid' THEN NOW() ELSE paid_at END 
            WHERE id = ?
        ");
        $stmt->execute([$status, $status, $paymentId]);
        return ['success' => true];
    }
    private function updateWorkerStatistics($workerId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT p.event_id) as total_events,
                COALESCE(SUM(p.total_amount), 0) as total_earnings,
                COALESCE(SUM(p.hours_worked), 0) as total_hours
            FROM payments p
            WHERE p.worker_id = ? AND p.status IN ('approved', 'paid')
        ");
        $stmt->execute([$workerId]);
        $stats = $stmt->fetch();
        $stmt = $this->db->prepare("
            INSERT INTO worker_statistics (worker_id, total_events, total_earnings, total_hours) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                total_events = VALUES(total_events),
                total_earnings = VALUES(total_earnings),
                total_hours = VALUES(total_hours)
        ");
        $stmt->execute([
            $workerId,
            $stats['total_events'],
            $stats['total_earnings'],
            $stats['total_hours']
        ]);
    }
    public function getWorkerStatistics($workerId) {
        $stmt = $this->db->prepare("SELECT * FROM worker_statistics WHERE worker_id = ?");
        $stmt->execute([$workerId]);
        return $stmt->fetch();
    }
    public function uploadReceipt($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Неподдерживаемый тип файла'];
        }
        if ($file['size'] > 5 * 1024 * 1024) { 
            return ['success' => false, 'error' => 'Файл слишком большой (максимум 5MB)'];
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = UPLOAD_DIR . 'receipts/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true, 
                'file_path' => 'uploads/receipts/' . $filename
            ];
        }
        return ['success' => false, 'error' => 'Ошибка загрузки файла'];
    }
}