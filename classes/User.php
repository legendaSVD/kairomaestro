<?php
class User {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function register($data) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$data['phone']]);
            if ($stmt->fetch()) {
                throw new Exception("Этот номер телефона уже зарегистрирован");
            }
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("
                INSERT INTO users (phone, password, first_name, last_name, middle_name, role) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['phone'],
                $passwordHash,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['role']
            ]);
            $userId = $this->db->lastInsertId();
            if ($data['role'] === 'worker') {
                $stmt = $this->db->prepare("
                    INSERT INTO worker_profiles 
                    (user_id, specialty, has_car, car_brand, car_number, additional_info, hourly_rate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $data['specialty'],
                    $data['has_car'] ?? 0,
                    $data['car_brand'] ?? null,
                    $data['car_number'] ?? null,
                    $data['additional_info'] ?? null,
                    $data['hourly_rate'] ?? 0
                ]);
                $stmt = $this->db->prepare("INSERT INTO worker_statistics (worker_id) VALUES (?)");
                $stmt->execute([$userId]);
            }
            $this->db->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function login($phone, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'error' => 'Неверный номер телефона или пароль'];
    }
    public function logout() {
        session_destroy();
        return true;
    }
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function getWorkerProfile($userId) {
        $stmt = $this->db->prepare("
            SELECT u.*, wp.* 
            FROM users u 
            LEFT JOIN worker_profiles wp ON u.id = wp.user_id 
            WHERE u.id = ? AND u.role = 'worker'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    public function getAllWorkers($filters = []) {
        $sql = "
            SELECT u.*, wp.specialty, wp.has_car, wp.car_brand, wp.hourly_rate 
            FROM users u 
            LEFT JOIN worker_profiles wp ON u.id = wp.user_id 
            WHERE u.role = 'worker'
        ";
        $params = [];
        if (!empty($filters['specialty'])) {
            $sql .= " AND wp.specialty = ?";
            $params[] = $filters['specialty'];
        }
        $sql .= " ORDER BY u.first_name, u.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function isWorkerAvailable($workerId, $date) {
        $stmt = $this->db->prepare("
            SELECT id FROM worker_availability 
            WHERE worker_id = ? AND unavailable_date = ?
        ");
        $stmt->execute([$workerId, $date]);
        if ($stmt->fetch()) {
            return false;
        }
        $stmt = $this->db->prepare("
            SELECT e.id FROM events e 
            JOIN event_staff es ON e.id = es.event_id 
            WHERE es.worker_id = ? AND e.event_date = ? AND e.status != 'cancelled'
        ");
        $stmt->execute([$workerId, $date]);
        if ($stmt->fetch()) {
            return false;
        }
        return true;
    }
    public function updateProfile($userId, $data) {
        try {
            $this->db->beginTransaction();
            $updateFields = [];
            $params = [];
            if (isset($data['first_name'])) {
                $updateFields[] = "first_name = ?";
                $params[] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $updateFields[] = "last_name = ?";
                $params[] = $data['last_name'];
            }
            if (isset($data['middle_name'])) {
                $updateFields[] = "middle_name = ?";
                $params[] = $data['middle_name'];
            }
            if (isset($data['avatar'])) {
                $updateFields[] = "avatar = ?";
                $params[] = $data['avatar'];
            }
            if (!empty($updateFields)) {
                $params[] = $userId;
                $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            if (isset($data['specialty'])) {
                $stmt = $this->db->prepare("
                    UPDATE worker_profiles 
                    SET specialty = ?, has_car = ?, car_brand = ?, car_number = ?, 
                        additional_info = ?, hourly_rate = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $data['specialty'],
                    $data['has_car'] ?? 0,
                    $data['car_brand'] ?? null,
                    $data['car_number'] ?? null,
                    $data['additional_info'] ?? null,
                    $data['hourly_rate'] ?? 0,
                    $userId
                ]);
            }
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function uploadAvatar($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Файл не был загружен'];
        }
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; 
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Недопустимый тип файла. Разрешены: JPG, PNG, WebP'];
        }
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Файл слишком большой. Максимум 5MB'];
        }
        $avatarDir = UPLOAD_DIR . 'avatars/';
        if (!file_exists($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $filePath = $avatarDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Ошибка при загрузке файла'];
        }
        $user = $this->getUserById($_SESSION['user_id']);
        if (!empty($user['avatar']) && file_exists(UPLOAD_DIR . 'avatars/' . $user['avatar'])) {
            unlink(UPLOAD_DIR . 'avatars/' . $user['avatar']);
        }
        return ['success' => true, 'filename' => $fileName];
    }
}