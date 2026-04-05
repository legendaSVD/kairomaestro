function renderMiniCalendar(containerId, data) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const year = data.year;
    const month = data.month;
    const workDays = data.workDays || [];
    const unavailableDays = data.unavailableDays || [];
    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    let html = '<div class="calendar-grid">';
    const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    weekdays.forEach(day => {
        html += `<div class="calendar-weekday">${day}</div>`;
    });
    let adjustedFirstDay = firstDay === 0 ? 6 : firstDay - 1;
    for (let i = 0; i < adjustedFirstDay; i++) {
        html += '<div class="calendar-day empty"></div>';
    }
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let className = 'calendar-day';
        if (workDays.includes(dateStr)) {
            className += ' work-day';
        } else if (unavailableDays.includes(dateStr)) {
            className += ' unavailable-day';
        }
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        if (dateStr === todayStr) {
            className += ' today';
        }
        html += `<div class="${className}"><span class="day-number">${day}</span></div>`;
    }
    html += '</div>';
    container.innerHTML = html;
}