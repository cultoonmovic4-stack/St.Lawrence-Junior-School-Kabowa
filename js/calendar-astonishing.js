/* ============================================
   ACADEMIC CALENDAR - ASTONISHING DESIGN
   JavaScript Functionality with Database Integration
   ============================================ */

(function () {
    'use strict';

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    let eventsData = {};

    const monthNames = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];

    async function fetchEvents() {
        const datesGrid = document.getElementById('datesGridNew');
        if (!datesGrid) return;

        try {
            const response = await fetch('../backend/api/public/get_upcoming_events.php');
            if (!response.ok) {
                eventsData = {};
                renderCalendar();
                return;
            }
            const data = await response.json();
            
            if (data && data.success && Array.isArray(data.events)) {
                eventsData = {};
                data.events.forEach(event => {
                    const eventDate = new Date(event.event_date);
                    const dateKey = `${eventDate.getFullYear()}-${String(eventDate.getMonth() + 1).padStart(2, '0')}-${String(eventDate.getDate()).padStart(2, '0')}`;
                    
                    if (!eventsData[dateKey]) {
                        eventsData[dateKey] = [];
                    }
                    eventsData[dateKey].push({
                        title: event.title,
                        description: event.description,
                        date: eventDate
                    });
                });
                renderCalendar();
            } else {
                eventsData = {};
                renderCalendar();
            }
        } catch (error) {
            eventsData = {};
            renderCalendar();
        }
    }

    function initCalendar() {
        const datesGrid = document.getElementById('datesGridNew');
        if (!datesGrid) return;

        fetchEvents();
        
        const prevMonthBtn = document.getElementById('prevMonthNew');
        const nextMonthBtn = document.getElementById('nextMonthNew');

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', () => {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar();
            });
        }
        
        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', () => {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar();
            });
        }
    }

    function renderCalendar() {
        const datesGrid = document.getElementById('datesGridNew');
        const currentMonthDisplay = document.getElementById('currentMonthNew');
        const currentYearDisplay = document.getElementById('currentYearNew');

        if (!datesGrid) return;

        if (currentMonthDisplay) {
            currentMonthDisplay.textContent = monthNames[currentMonth];
        }
        if (currentYearDisplay) {
            currentYearDisplay.textContent = currentYear;
        }
        
        datesGrid.innerHTML = '';
        
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
        
        const today = new Date();
        const isCurrentMonth = today.getMonth() === currentMonth && today.getFullYear() === currentYear;
        const todayDate = today.getDate();
        
        for (let i = firstDay - 1; i >= 0; i--) {
            const dateCell = createDateCell(daysInPrevMonth - i, 'other-month');
            datesGrid.appendChild(dateCell);
        }
        
        for (let day = 1; day <= daysInMonth; day++) {
            const dateString = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const classes = [];
            
            if (isCurrentMonth && day === todayDate) {
                classes.push('today');
            }
            
            if (eventsData[dateString] && eventsData[dateString].length > 0) {
                classes.push('has-event');
            }
            
            const dateCell = createDateCell(day, classes.join(' '));
            
            if (eventsData[dateString]) {
                const eventTitles = eventsData[dateString].map(e => e.title).join(', ');
                dateCell.title = eventTitles;
            }
            
            datesGrid.appendChild(dateCell);
        }
        
        const totalCells = datesGrid.children.length;
        const remainingCells = 42 - totalCells;
        for (let day = 1; day <= remainingCells; day++) {
            const dateCell = createDateCell(day, 'other-month');
            datesGrid.appendChild(dateCell);
        }
        
        renderEvents();
    }

    function createDateCell(day, className = '') {
        const cell = document.createElement('div');
        cell.className = `date-cell ${className}`;
        cell.textContent = day;
        return cell;
    }

    function renderEvents() {
        const eventsList = document.getElementById('eventsListNew');
        const eventsCount = document.getElementById('eventsCount');

        if (!eventsList) return;
        
        const monthEvents = [];
        
        Object.keys(eventsData).forEach(dateString => {
            eventsData[dateString].forEach(event => {
                if (event.date.getMonth() === currentMonth && event.date.getFullYear() === currentYear) {
                    monthEvents.push(event);
                }
            });
        });
        
        monthEvents.sort((a, b) => a.date - b.date);
        
        if (eventsCount) {
            eventsCount.textContent = `${monthEvents.length} Event${monthEvents.length !== 1 ? 's' : ''}`;
        }
        
        if (monthEvents.length === 0) {
            eventsList.innerHTML = `
                <div class="no-events-message">
                    <i class="fas fa-calendar-times"></i>
                    <p>No events scheduled this month</p>
                </div>
            `;
        } else {
            eventsList.innerHTML = monthEvents.map(event => `
                <div class="event-item-new">
                    <div class="event-date-new">${monthNames[event.date.getMonth()]} ${event.date.getDate()}, ${event.date.getFullYear()}</div>
                    <div class="event-title-new">${event.title}</div>
                </div>
            `).join('');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendar);
    } else {
        initCalendar();
    }
})();
