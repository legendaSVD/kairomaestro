<?php
class Chat {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function getEventChat($eventId) {
        $stmt = $this->db->prepare("SELECT * FROM chats WHERE event_id = ? AND type = 'event_group'");
        $stmt->execute([$eventId]);
        return $stmt->fetch();
    }
    public function getOrCreateOrganizerChat($workerId, $organizerId) {
        $stmt = $this->db->prepare("
            SELECT c.id FROM chats c
            JOIN chat_participants cp1 ON c.id = cp1.chat_id AND cp1.user_id = ?
            JOIN chat_participants cp2 ON c.id = cp2.chat_id AND cp2.user_id = ?
            WHERE c.type = 'personal' AND c.event_id IS NULL
        ");
        $stmt->execute([$workerId, $organizerId]);
        $chat = $stmt->fetch();
        if ($chat) {
            return ['success' => true, 'chat_id' => $chat['id']];
        }
        return $this->createPersonalChat($workerId, $organizerId);
    }
    public function getUserChats($userId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   e.title as event_title, 
                   e.event_date,
                   (SELECT message FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                   (SELECT COUNT(*) FROM messages WHERE chat_id = c.id AND is_read = 0 AND user_id != ?) as unread_count
            FROM chats c
            JOIN chat_participants cp ON c.id = cp.chat_id
            LEFT JOIN events e ON c.event_id = e.id
            WHERE cp.user_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        $chats = $stmt->fetchAll();
        foreach ($chats as &$chat) {
            if ($chat['type'] === 'personal') {
                $stmt = $this->db->prepare("
                    SELECT u.id, u.first_name, u.last_name 
                    FROM users u
                    JOIN chat_participants cp ON u.id = cp.user_id
                    WHERE cp.chat_id = ? AND cp.user_id != ?
                ");
                $stmt->execute([$chat['id'], $userId]);
                $otherUser = $stmt->fetch();
                if ($otherUser) {
                    $chat['chat_name'] = $otherUser['first_name'] . ' ' . $otherUser['last_name'];
                    $chat['other_user_id'] = $otherUser['id'];
                }
            } elseif ($chat['type'] === 'event_group') {
                $chat['chat_name'] = ($chat['event_title'] ?? 'Групповой чат') . ' (команда)';
            }
        }
        usort($chats, function($a, $b) {
            $timeA = $a['last_message_time'] ? strtotime($a['last_message_time']) : 0;
            $timeB = $b['last_message_time'] ? strtotime($b['last_message_time']) : 0;
            return $timeB - $timeA;
        });
        return $chats;
    }
    public function getMessages($chatId, $userId, $limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE chat_id = ? AND user_id != ? AND is_read = 0
        ");
        $stmt->execute([$chatId, $userId]);
        $stmt = $this->db->prepare("
            SELECT m.*, u.first_name, u.last_name 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.chat_id = ? 
            ORDER BY m.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$chatId, $limit, $offset]);
        return array_reverse($stmt->fetchAll());
    }
    public function sendMessage($chatId, $userId, $message, $filePath = null, $fileType = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (chat_id, user_id, message, file_path, file_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$chatId, $userId, $message, $filePath, $fileType]);
            $messageId = $this->db->lastInsertId();
            $this->notifyChatParticipants($chatId, $userId);
            return ['success' => true, 'message_id' => $messageId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    private function notifyChatParticipants($chatId, $senderId) {
        $stmt = $this->db->prepare("
            SELECT cp.user_id, c.event_id 
            FROM chat_participants cp 
            JOIN chats c ON cp.chat_id = c.id 
            WHERE cp.chat_id = ? AND cp.user_id != ?
        ");
        $stmt->execute([$chatId, $senderId]);
        $participants = $stmt->fetchAll();
        $sender = (new User())->getUserById($senderId);
        foreach ($participants as $participant) {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (?, 'new_message', 'Новое сообщение', ?, ?)
            ");
            $stmt->execute([
                $participant['user_id'],
                "Новое сообщение от {$sender['first_name']} {$sender['last_name']}",
                "/chat.php?id={$chatId}"
            ]);
        }
    }
    public function createPersonalChat($user1Id, $user2Id) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("
                SELECT c.id FROM chats c
                JOIN chat_participants cp1 ON c.id = cp1.chat_id AND cp1.user_id = ?
                JOIN chat_participants cp2 ON c.id = cp2.chat_id AND cp2.user_id = ?
                WHERE c.type = 'personal'
            ");
            $stmt->execute([$user1Id, $user2Id]);
            $existingChat = $stmt->fetch();
            if ($existingChat) {
                $this->db->commit();
                return ['success' => true, 'chat_id' => $existingChat['id']];
            }
            $stmt = $this->db->prepare("INSERT INTO chats (type) VALUES ('personal')");
            $stmt->execute();
            $chatId = $this->db->lastInsertId();
            $stmt = $this->db->prepare("INSERT INTO chat_participants (chat_id, user_id) VALUES (?, ?)");
            $stmt->execute([$chatId, $user1Id]);
            $stmt->execute([$chatId, $user2Id]);
            $this->db->commit();
            return ['success' => true, 'chat_id' => $chatId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function uploadFile($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'video/mp4'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Неподдерживаемый тип файла'];
        }
        if ($file['size'] > 10 * 1024 * 1024) { 
            return ['success' => false, 'error' => 'Файл слишком большой (максимум 10MB)'];
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = UPLOAD_DIR . 'chat/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true, 
                'file_path' => 'uploads/chat/' . $filename,
                'file_type' => $file['type']
            ];
        }
        return ['success' => false, 'error' => 'Ошибка загрузки файла'];
    }
}