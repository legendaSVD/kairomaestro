<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'worker') {
    redirect('/login.php');
}
$eventObj = new Event();
$availabilityObj = new Availability();
$userId = $_SESSION['user_id'];
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$calendar = $eventObj->getWorkerCalendar($userId, $year, $month);
$unavailableDays = $availabilityObj->getUnavailableDays($userId, 
    "$year-$month-01", 
    date('Y-m-t', strtotime("$year-$month-01"))
);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $action = $_POST['action'] ?? '';
    $reason = $_POST['reason'] ?? '';
    if ($action === 'mark_unavailable') {
        $result = $availabilityObj->markUnavailable($userId, $date, $reason);
    } elseif ($action === 'mark_available') {
        $result = $availabilityObj->markAvailable($userId, $date);
    }
    redirect("/worker/calendar.php?year=$year&month=$month");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/worker-header.php'; ?>
    <div class="calendar-page">
        <div class="container">
            <div class="calendar-header">
                <a href="?year=<?php echo date('Y', strtotime("$year-$month-01 -1 month")); ?>&month=<?php echo date('m', strtotime("$year-$month-01 -1 month")); ?>" class="btn btn-icon">←</a>
                <h1><?php 
                    $months = [
                        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
                        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
                        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
                    ];
                    echo $months[(int)$month] . ' ' . $year;
                ?></h1>
                <a href="?year=<?php echo date('Y', strtotime("$year-$month-01 +1 month")); ?>&month=<?php echo date('m', strtotime("$year-$month-01 +1 month")); ?>" class="btn btn-icon">→</a>
            </div>
            <div class="calendar-legend">
                <div class="legend-item"><span class="color-box green"></span> Свободен</div>
                <div class="legend-item"><span class="color-box red"></span> Занят (работа)</div>
                <div class="legend-item"><span class="color-box gray"></span> Недоступен</div>
            </div>
            <div class="calendar-grid">
                <div class="calendar-weekday">Пн</div>
                <div class="calendar-weekday">Вт</div>
                <div class="calendar-weekday">Ср</div>
                <div class="calendar-weekday">Чт</div>
                <div class="calendar-weekday">Пт</div>
                <div class="calendar-weekday">Сб</div>
                <div class="calendar-weekday">Вс</div>
                <?php
                $firstDay = date('N', strtotime("$year-$month-01"));
                $daysInMonth = date('t', strtotime("$year-$month-01"));
                for ($i = 1; $i < $firstDay; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $dateStr = date('Y-m-d', strtotime($date));
                    $class = 'calendar-day';
                    $status = 'free';
                    if (isset($calendar['work_days'][$dateStr])) {
                        $class .= ' work-day';
                        $status = 'work';
                    } elseif (in_array($dateStr, $calendar['unavailable_days'])) {
                        $class .= ' unavailable-day';
                        $status = 'unavailable';
                    }
                    if ($dateStr === date('Y-m-d')) {
                        $class .= ' today';
                    }
                    echo '<div class="' . $class . '" data-date="' . $dateStr . '" data-status="' . $status . '" onclick="openDayModal(\'' . $dateStr . '\', \'' . $status . '\')">';
                    echo '<span class="day-number">' . $day . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- Модальное окно для дня -->
    <div id="dayModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeDayModal()">&times;</span>
            <h2 id="modalDate"></h2>
            <div id="modalContent"></div>
        </div>
    </div>
    <?php include '../includes/worker-footer.php'; ?>
    <script>
        function openDayModal(date, status) {
            const modal = document.getElementById('dayModal');
            const modalDate = document.getElementById('modalDate');
            const modalContent = document.getElementById('modalContent');
            modalDate.textContent = new Date(date).toLocaleDateString('ru-RU', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            if (status === 'work') {
                fetch(`/api/get-event-by-date.php?date=${date}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            modalContent.innerHTML = `
                                <div class="event-details">
                                    <h3>${data.event.title}</h3>
                                    <p><strong>Время:</strong> ${data.event.start_time} ${data.event.end_time ? '- ' + data.event.end_time : ''}</p>
                                    <p><strong>Место:</strong> ${data.event.location}</p>
                                    ${data.event.technical_task ? '<p><strong>Задание:</strong> ' + data.event.technical_task + '</p>' : ''}
                                    <a href="/worker/event.php?id=${data.event.id}" class="btn btn-primary">Подробнее</a>
                                </div>
                            `;
                        }
                    });
            } else if (status === 'unavailable') {
                modalContent.innerHTML = `
                    <p>Вы отметили этот день как недоступный</p>
                    <form method="POST" action="">
                        <input type="hidden" name="date" value="${date}">
                        <input type="hidden" name="action" value="mark_available">
                        <button type="submit" class="btn btn-primary">Отметить как доступный</button>
                    </form>
                `;
            } else {
                modalContent.innerHTML = `
                    <p>Вы свободны в этот день</p>
                    <form method="POST" action="">
                        <input type="hidden" name="date" value="${date}">
                        <input type="hidden" name="action" value="mark_unavailable">
                        <div class="form-group">
                            <label for="reason">Причина (необязательно)</label>
                            <input type="text" id="reason" name="reason" class="form-control" placeholder="Например: личные дела">
                        </div>
                        <button type="submit" class="btn btn-secondary">Отметить как недоступный</button>
                    </form>
                `;
            }
            modal.style.display = 'flex';
        }
        function closeDayModal() {
            document.getElementById('dayModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('dayModal');
            if (event.target === modal) {
                closeDayModal();
            }
        };
    </script>
</body>
</html>