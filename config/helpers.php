<?php
function formatRussianDate($date, $format = 'full') {
    $timestamp = strtotime($date);
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
    ];
    $monthsShort = [
        1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр',
        5 => 'май', 6 => 'июн', 7 => 'июл', 8 => 'авг',
        9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'
    ];
    $days = [
        'Monday' => 'Понедельник',
        'Tuesday' => 'Вторник',
        'Wednesday' => 'Среда',
        'Thursday' => 'Четверг',
        'Friday' => 'Пятница',
        'Saturday' => 'Суббота',
        'Sunday' => 'Воскресенье'
    ];
    $daysShort = [
        'Monday' => 'Пн',
        'Tuesday' => 'Вт',
        'Wednesday' => 'Ср',
        'Thursday' => 'Чт',
        'Friday' => 'Пт',
        'Saturday' => 'Сб',
        'Sunday' => 'Вс'
    ];
    $day = date('j', $timestamp);
    $month = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);
    $dayName = date('l', $timestamp);
    switch ($format) {
        case 'full':
            return $day . ' ' . $months[$month] . ' ' . $year . ' (' . $days[$dayName] . ')';
        case 'short':
            return $day . ' ' . $monthsShort[$month];
        case 'month':
            return $months[$month];
        case 'month_short':
            return $monthsShort[$month];
        case 'day':
            return $days[$dayName];
        case 'day_short':
            return $daysShort[$dayName];
        case 'date_only':
            return $day . ' ' . $months[$month] . ' ' . $year;
        default:
            return date('d.m.Y', $timestamp);
    }
}
function formatTime($time) {
    return date('H:i', strtotime($time));
}
function formatRussianDateTime($datetime) {
    $date = formatRussianDate($datetime, 'date_only');
    $time = formatTime($datetime);
    return $date . ' в ' . $time;
}