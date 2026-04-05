<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$eventObj = new Event();
$userId = $_SESSION['user_id'];
$eventId = $_GET['id'] ?? 0;
$event = $eventObj->getById($eventId);
if (!$event || $event['organizer_id'] != $userId) {
    redirect('/organizer/dashboard.php');
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'event_date' => $_POST['event_date'] ?? '',
        'start_time' => $_POST['start_time'] ?? '',
        'end_time' => $_POST['end_time'] ?? '',
        'location' => $_POST['location'] ?? '',
        'latitude' => $_POST['latitude'] ?? null,
        'longitude' => $_POST['longitude'] ?? null,
        'technical_task' => $_POST['technical_task'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    if (empty($data['title']) || empty($data['event_date']) || empty($data['start_time']) || empty($data['location'])) {
        $error = 'Заполните все обязательные поля';
    } else {
        $result = $eventObj->update($eventId, $data);
        if ($result['success']) {
            $success = 'Мероприятие обновлено!';
            $event = $eventObj->getById($eventId);
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать мероприятие - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/organizer-header.php'; ?>
    <div class="edit-event-page">
        <div class="container">
            <h1>Редактировать мероприятие</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="event-form">
                <div class="form-section">
                    <h2>Основная информация</h2>
                    <div class="form-group">
                        <label for="title">Название мероприятия *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo escape($event['title']); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Дата *</label>
                            <input type="date" id="event_date" name="event_date" class="form-control" 
                                   value="<?php echo $event['event_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Время начала *</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" 
                                   value="<?php echo $event['start_time']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Время окончания</label>
                            <input type="time" id="end_time" name="end_time" class="form-control" 
                                   value="<?php echo $event['end_time']; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo $event['status'] === 'active' ? 'selected' : ''; ?>>Активное</option>
                            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Завершённое</option>
                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                        </select>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Место проведения</h2>
                    <div class="form-group">
                        <label for="location">Адрес *</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?php echo escape($event['location']); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Широта</label>
                            <input type="text" id="latitude" name="latitude" class="form-control" 
                                   value="<?php echo $event['latitude']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Долгота</label>
                            <input type="text" id="longitude" name="longitude" class="form-control" 
                                   value="<?php echo $event['longitude']; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Техническое задание</h2>
                    <div class="form-group">
                        <label for="technical_task">Описание</label>
                        <textarea id="technical_task" name="technical_task" class="form-control" rows="6"><?php echo escape($event['technical_task']); ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <a href="/organizer/event.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
    <?php include '../includes/organizer-footer.php'; ?>
</body>
</html>