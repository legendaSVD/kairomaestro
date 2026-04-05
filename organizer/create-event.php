<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'organizer') {
    redirect('/login.php');
}
$userObj = new User();
$eventObj = new Event();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$workers = $userObj->getAllWorkers();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'organizer_id' => $userId,
        'title' => $_POST['title'] ?? '',
        'event_date' => $_POST['event_date'] ?? '',
        'start_time' => $_POST['start_time'] ?? '',
        'end_time' => $_POST['end_time'] ?? '',
        'location' => $_POST['location'] ?? '',
        'latitude' => $_POST['latitude'] ?? null,
        'longitude' => $_POST['longitude'] ?? null,
        'technical_task' => $_POST['technical_task'] ?? '',
        'status' => 'active'
    ];
    if (empty($data['title']) || empty($data['event_date']) || empty($data['start_time']) || empty($data['location'])) {
        $error = 'Заполните все обязательные поля';
    } else {
        $result = $eventObj->create($data);
        if ($result['success']) {
            $eventId = $result['event_id'];
            if (!empty($_POST['workers'])) {
                foreach ($_POST['workers'] as $workerId) {
                    $eventObj->assignWorker($eventId, $workerId, $userId);
                }
            }
            $success = 'Мероприятие успешно создано!';
            header("refresh:2;url=/organizer/event.php?id=$eventId");
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
    <title>Создать мероприятие - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/organizer-header.php'; ?>
    <div class="create-event-page">
        <div class="container">
            <h1>Создать мероприятие</h1>
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
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Дата *</label>
                            <input type="date" id="event_date" name="event_date" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Время начала *</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Время окончания</label>
                            <input type="time" id="end_time" name="end_time" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Место проведения</h2>
                    <div class="form-group">
                        <label for="location">Адрес *</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               placeholder="Например: Москва, ул. Тверская, д. 1" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Широта</label>
                            <input type="text" id="latitude" name="latitude" class="form-control" 
                                   placeholder="55.7558">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Долгота</label>
                            <input type="text" id="longitude" name="longitude" class="form-control" 
                                   placeholder="37.6173">
                        </div>
                    </div>
                    <small>Координаты можно получить на <a href="https://yandex.ru/maps/" target="_blank">Яндекс.Картах</a></small>
                </div>
                <div class="form-section">
                    <h2>Техническое задание</h2>
                    <div class="form-group">
                        <label for="technical_task">Описание</label>
                        <textarea id="technical_task" name="technical_task" class="form-control" rows="6" 
                                  placeholder="Детали мероприятия, особые указания для сотрудников..."></textarea>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Подбор сотрудников</h2>
                    <div class="form-group">
                        <label for="specialty_filter">Фильтр по специальности</label>
                        <select id="specialty_filter" class="form-control" onchange="filterWorkers()">
                            <option value="">Все специальности</option>
                            <option value="Флорист">Флорист</option>
                            <option value="Хелпер">Хелпер</option>
                            <option value="Монтажник">Монтажник</option>
                            <option value="Декоратор">Декоратор</option>
                            <option value="Координатор">Координатор</option>
                            <option value="Фотограф">Фотограф</option>
                            <option value="Видеограф">Видеограф</option>
                        </select>
                    </div>
                    <div class="workers-list" id="workersList">
                        <?php foreach ($workers as $worker): ?>
                        <div class="worker-checkbox" data-specialty="<?php echo escape($worker['specialty']); ?>">
                            <label class="checkbox-label">
                                <input type="checkbox" name="workers[]" value="<?php echo $worker['id']; ?>">
                                <div class="worker-info">
                                    <strong><?php echo escape($worker['first_name'] . ' ' . $worker['last_name']); ?></strong>
                                    <span class="badge"><?php echo escape($worker['specialty']); ?></span>
                                    <?php if ($worker['has_car']): ?>
                                    <span class="icon" title="Есть автомобиль">🚗</span>
                                    <?php endif; ?>
                                    <small><?php echo number_format($worker['hourly_rate'], 0); ?> ₽/час</small>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="help-text">Можно добавить сотрудников позже</p>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">Создать мероприятие</button>
                    <a href="/organizer/dashboard.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
    <?php include '../includes/organizer-footer.php'; ?>
    <script>
        function filterWorkers() {
            const filter = document.getElementById('specialty_filter').value;
            const workers = document.querySelectorAll('.worker-checkbox');
            workers.forEach(worker => {
                const specialty = worker.dataset.specialty;
                if (filter === '' || specialty === filter) {
                    worker.style.display = 'block';
                } else {
                    worker.style.display = 'none';
                }
            });
        }
        document.getElementById('event_date').addEventListener('change', function() {
            const date = this.value;
            if (!date) return;
            fetch(`/api/check-availability.php?date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Object.keys(data.availability).forEach(workerId => {
                            const checkbox = document.querySelector(`input[value="${workerId}"]`);
                            if (checkbox) {
                                const available = data.availability[workerId];
                                const parent = checkbox.closest('.worker-checkbox');
                                if (!available) {
                                    parent.classList.add('unavailable');
                                    checkbox.disabled = true;
                                } else {
                                    parent.classList.remove('unavailable');
                                    checkbox.disabled = false;
                                }
                            }
                        });
                    }
                });
        });
    </script>
</body>
</html>