<?php
require_once 'config/config.php';
if (!isLoggedIn()) {
    redirect('/login.php');
}
$chatObj = new Chat();
$userId = $_SESSION['user_id'];
$chats = $chatObj->getUserChats($userId);
$chatId = $_GET['id'] ?? null;
$currentChat = null;
$messages = [];
if ($chatId) {
    foreach ($chats as $chat) {
        if ($chat['id'] == $chatId) {
            $currentChat = $chat;
            break;
        }
    }
    if ($currentChat) {
        $messages = $chatObj->getMessages($chatId, $userId);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = $_POST['message'] ?? '';
    $chatId = $_POST['chat_id'] ?? 0;
    if (!empty($message) && $chatId) {
        $filePath = null;
        $fileType = null;
        if (!empty($_FILES['file']['name'])) {
            $uploadResult = $chatObj->uploadFile($_FILES['file']);
            if ($uploadResult['success']) {
                $filePath = $uploadResult['file_path'];
                $fileType = $uploadResult['file_type'];
            }
        }
        $result = $chatObj->sendMessage($chatId, $userId, $message, $filePath, $fileType);
        if ($result['success']) {
            redirect("/chat.php?id=$chatId");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чаты - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php 
    if ($_SESSION['user_role'] === 'worker') {
        include 'includes/worker-header.php';
    } else {
        include 'includes/organizer-header.php';
    }
    ?>
    <div class="chat-page">
        <div class="chat-overlay" id="chatOverlay" onclick="document.getElementById('chatSidebar').classList.remove('show'); document.getElementById('chatOverlay').classList.remove('show');"></div>
        <div class="chat-container">
            <!-- Список чатов -->
            <div class="chat-sidebar" id="chatSidebar">
                <div class="chat-sidebar-header">
                    <h2>Чаты</h2>
                    <button class="chat-sidebar-close" onclick="toggleChatSidebar()" title="Закрыть">✕</button>
                </div>
                <div class="chat-list">
                    <?php if (empty($chats)): ?>
                    <div class="empty-state">
                        <p>У вас пока нет чатов</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($chats as $chat): ?>
                        <a href="/chat.php?id=<?php echo $chat['id']; ?>" 
                           class="chat-item <?php echo $chatId == $chat['id'] ? 'active' : ''; ?>"
                           onclick="if(window.innerWidth <= 767) { toggleChatSidebar(); }">
                            <div class="chat-item-info">
                                <strong>
                                    <?php 
                                    if ($chat['type'] === 'personal' && isset($chat['chat_name'])) {
                                        echo '👤 ' . escape($chat['chat_name']);
                                    } elseif ($chat['type'] === 'event_group' && $chat['event_title']) {
                                        echo '💬 ' . escape($chat['event_title']) . ' (команда)';
                                    } elseif (isset($chat['chat_name'])) {
                                        echo escape($chat['chat_name']);
                                    } elseif ($chat['event_title']) {
                                        echo escape($chat['event_title']);
                                    } else {
                                        echo 'Чат';
                                    }
                                    ?>
                                </strong>
                                <?php if ($chat['last_message']): ?>
                                <p><?php echo escape(substr($chat['last_message'], 0, 50)) . (strlen($chat['last_message']) > 50 ? '...' : ''); ?></p>
                                <small><?php echo formatRussianDateTime($chat['last_message_time']); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if ($chat['unread_count'] > 0): ?>
                            <span class="badge"><?php echo $chat['unread_count']; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Окно чата -->
            <div class="chat-main">
                <?php if ($currentChat): ?>
                <div class="chat-header">
                    <button class="chat-menu-toggle" onclick="toggleChatSidebar()" title="Список чатов">☰</button>
                    <div class="chat-header-info">
                        <h2>
                            <?php 
                            if ($currentChat['type'] === 'personal' && isset($currentChat['chat_name'])) {
                                echo '👤 ' . escape($currentChat['chat_name']);
                            } elseif ($currentChat['type'] === 'event_group' && $currentChat['event_title']) {
                                echo '💬 ' . escape($currentChat['event_title']) . ' (команда)';
                            } elseif (isset($currentChat['chat_name'])) {
                                echo escape($currentChat['chat_name']);
                            } elseif ($currentChat['event_title']) {
                                echo escape($currentChat['event_title']);
                            } else {
                                echo 'Чат';
                            }
                            ?>
                        </h2>
                        <?php if ($currentChat['event_date']): ?>
                        <small><?php echo formatRussianDate($currentChat['event_date'], 'date_only'); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['user_id'] == $userId ? 'message-own' : 'message-other'; ?>">
                        <div class="message-header">
                            <strong><?php echo escape($msg['first_name'] . ' ' . $msg['last_name']); ?></strong>
                            <small><?php echo formatRussianDateTime($msg['created_at']); ?></small>
                        </div>
                        <div class="message-body">
                            <?php echo nl2br(escape($msg['message'])); ?>
                            <?php if ($msg['file_path']): ?>
                            <div class="message-file">
                                <?php if (strpos($msg['file_type'], 'image') !== false): ?>
                                <a href="/<?php echo escape($msg['file_path']); ?>" target="_blank">
                                    <img src="/<?php echo escape($msg['file_path']); ?>" alt="Изображение" loading="lazy">
                                </a>
                                <?php else: ?>
                                <a href="/<?php echo escape($msg['file_path']); ?>" target="_blank" class="file-link">
                                    📎 Прикрепленный файл
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-input">
                    <form method="POST" action="" enctype="multipart/form-data" id="messageForm">
                        <input type="hidden" name="chat_id" value="<?php echo $chatId; ?>">
                        <input type="hidden" name="send_message" value="1">
                        <div class="chat-input-group">
                            <label for="file" class="file-btn" title="Прикрепить файл">
                                📎
                                <input type="file" id="file" name="file" accept="image/*,video/*,.pdf" style="display:none;">
                            </label>
                            <textarea name="message" id="messageInput" class="message-input" 
                                      placeholder="Введите сообщение..." rows="1" required></textarea>
                            <button type="submit" class="send-btn">📤</button>
                        </div>
                        <div id="filePreview" class="file-preview"></div>
                    </form>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <p>Выберите чат из списка</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php 
    if ($_SESSION['user_role'] === 'worker') {
        include 'includes/worker-footer.php';
    } else {
        include 'includes/organizer-footer.php';
    }
    ?>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
        const fileInput = document.getElementById('file');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const preview = document.getElementById('filePreview');
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    const fileType = this.files[0].type;
                    if (fileType.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `<img src="${e.target.result}" alt="Preview"><button type="button" onclick="clearFile()">✕</button>`;
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        preview.innerHTML = `<span>📎 ${fileName}</span><button type="button" onclick="clearFile()">✕</button>`;
                    }
                }
            });
        }
        function clearFile() {
            document.getElementById('file').value = '';
            document.getElementById('filePreview').innerHTML = '';
        }
        function toggleChatSidebar() {
            const sidebar = document.getElementById('chatSidebar');
            const overlay = document.getElementById('chatOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
        <?php if ($chatId): ?>
        setInterval(function() {
            const lastMessageId = document.querySelector('.message:last-child')?.dataset?.messageId || 0;
            fetch(`/api/get-new-messages.php?chat_id=<?php echo $chatId; ?>&last_id=${lastMessageId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        location.reload();
                    }
                });
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>