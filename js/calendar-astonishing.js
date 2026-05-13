/* ============================================
   ACADEMIC CALENDAR - ASTONISHING DESIGN
   JavaScript Functionality with Database Integration
   ============================================ */

// Calendar Elements
const prevMonthBtn = document.getElementById('prevMonthNew');
const nextMonthBtn = document.getElementById('nextMonthNew');
const currentMonthDisplay = document.getElementById('currentMonthNew');
const currentYearDisplay = document.getElementById('currentYearNew');
const datesGrid = document.getElementById('datesGridNew');
const eventsList = document.getElementById('eventsListNew');
const eventsCount = document.getElementById('eventsCount');

// Calendar State
let currentDate = new Date();
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();
let eventsData = {}; // Store events from database

// Month Names
const monthNames = [
    'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
    'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
];

// Fetch Events from Database
async function fetchEvents() {
    try {
        const response = await fetch('../backend/api/public/get_upcoming_events.php');
        const data = await response.json();
        
        if (data.success && Array.isArray(data.events)) {
            // Convert events array to object with date keys
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
            console.error('Failed to fetch events:', data.message || 'Unknown error');
            eventsData = {};
            renderCalendar();
        }
    } catch (error) {
        console.error('Error fetching events:', error);
        eventsData = {};
        renderCalendar();
    }
}

// Initialize Calendar
function initCalendar() {
    fetchEvents();
    
    // Event Listeners
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

// Render Calendar
function renderCalendar() {
    // Update month and year display
    if (currentMonthDisplay) {
        currentMonthDisplay.textContent = monthNames[currentMonth];
    }
    if (currentYearDisplay) {
        currentYearDisplay.textContent = currentYear;
    }
    
    // Clear dates grid
    if (datesGrid) {
        datesGrid.innerHTML = '';
    }
    
    // Get calendar data
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
    
    // Get today's date
    const today = new Date();
    const isCurrentMonth = today.getMonth() === currentMonth && today.getFullYear() === currentYear;
    const todayDate = today.getDate();
    
    // Add previous month's trailing days
    for (let i = firstDay - 1; i >= 0; i--) {
        const dateCell = createDateCell(daysInPrevMonth - i, 'other-month');
        datesGrid.appendChild(dateCell);
    }
    
    // Add current month's days
    for (let day = 1; day <= daysInMonth; day++) {
        const dateString = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const classes = [];
        
        // Check if today
        if (isCurrentMonth && day === todayDate) {
            classes.push('today');
        }
        
        // Check if has event from database
        if (eventsData[dateString] && eventsData[dateString].length > 0) {
            classes.push('has-event');
        }
        
        const dateCell = createDateCell(day, classes.join(' '));
        
        // Add tooltip with event titles
        if (eventsData[dateString]) {
            const eventTitles = eventsData[dateString].map(e => e.title).join(', ');
            dateCell.title = eventTitles;
        }
        
        datesGrid.appendChild(dateCell);
    }
    
    // Add next month's leading days
    const totalCells = datesGrid.children.length;
    const remainingCells = 42 - totalCells; // 6 rows × 7 days
    for (let day = 1; day <= remainingCells; day++) {
        const dateCell = createDateCell(day, 'other-month');
        datesGrid.appendChild(dateCell);
    }
    
    // Render events
    renderEvents();
}

// Create Date Cell
function createDateCell(day, className = '') {
    const cell = document.createElement('div');
    cell.className = `date-cell ${className}`;
    cell.textContent = day;
    return cell;
}

// Render Events
function renderEvents() {
    if (!eventsList) return;
    
    const monthEvents = [];
    
    // Find events in current month from database
    Object.keys(eventsData).forEach(dateString => {
        eventsData[dateString].forEach(event => {
            if (event.date.getMonth() === currentMonth && event.date.getFullYear() === currentYear) {
                monthEvents.push(event);
            }
        });
    });
    
    // Sort by date
    monthEvents.sort((a, b) => a.date - b.date);
    
    // Update events count
    if (eventsCount) {
        eventsCount.textContent = `${monthEvents.length} Event${monthEvents.length !== 1 ? 's' : ''}`;
    }
    
    // Render events list
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', initCalendar);
