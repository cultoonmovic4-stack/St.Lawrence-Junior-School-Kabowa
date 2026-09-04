/**
 * Academic Calendar Section - Editorial & Elegant JS
 * St. Lawrence Junior School, Kabowa
 * 100% Strictly Dynamic Database-Driven Implementation
 */

(function () {
    const today = new Date();
    let currentYear = today.getFullYear();
    let currentMonth = today.getMonth(); // 0-indexed
    let selectedDateStr = null;
    let dbEvents = [];
    let isFilterByDate = false;

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const monthShorts = [
        'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
        'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
    ];

    const dayNames = [
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
    ];

    // Fetch dynamic events strictly from the database / admin dashboard
    async function fetchCalendarData() {
        const endpoints = [
            '../backend/api/public/get_upcoming_events.php?all=1',
            '../backend/api/public/get_calendar.php'
        ];

        let loaded = false;

        for (const url of endpoints) {
            try {
                const res = await fetch(url);
                if (!res.ok) continue;
                const json = await res.json();
                
                let rawList = [];
                if (json.data) {
                    if (Array.isArray(json.data)) {
                        rawList = json.data;
                    } else if (Array.isArray(json.data.all_events)) {
                        rawList = json.data.all_events;
                    } else if (Array.isArray(json.data.important_days)) {
                        rawList = json.data.important_days;
                    }
                } else if (Array.isArray(json.events)) {
                    rawList = json.events;
                }

                if (rawList && rawList.length > 0) {
                    dbEvents = rawList.map(item => {
                        const dateVal = item.event_date || item.start_date || item.date;
                        if (!dateVal) return null;
                        
                        // Parse clean date components
                        const dParts = dateVal.split('T')[0].split('-');
                        const y = parseInt(dParts[0], 10);
                        const m = parseInt(dParts[1], 10) - 1;
                        const d = parseInt(dParts[2], 10);
                        const dateObj = new Date(y, m, d);

                        const dayName = dayNames[dateObj.getDay()];
                        const mName = monthNames[m];
                        const fullStr = `${dayName}, ${d} ${mName} ${y}`;

                        return {
                            date: `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`,
                            title: item.event_title || item.title || 'School Event',
                            subtitle: item.description ? item.description.replace(/^\n*Time:\s*/, 'Time: ') : (item.event_type || item.category || 'Academic Activity'),
                            fulldate: fullStr,
                            source: item.source || 'admin'
                        };
                    }).filter(Boolean);

                    loaded = true;
                    break;
                }
            } catch (err) {
                console.warn('Target Calendar: Fetch error from', url, err);
            }
        }

        if (loaded && dbEvents.length > 0) {
            // Find next upcoming event from database to align the calendar month
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
            const upcoming = dbEvents.filter(e => e.date >= todayStr).sort((a, b) => a.date.localeCompare(b.date));
            
            const targetEvent = upcoming.length > 0 ? upcoming[0] : dbEvents[0];
            const [tY, tM] = targetEvent.date.split('-');
            currentYear = parseInt(tY, 10);
            currentMonth = parseInt(tM, 10) - 1;
            selectedDateStr = targetEvent.date;
        }

        renderCalendar();
        renderTimeline();
    }

    function renderCalendar() {
        const monthTitle = document.getElementById('calMonthTitle');
        const daysGrid = document.getElementById('calDaysGrid');
        if (!daysGrid || !monthTitle) return;

        monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        daysGrid.innerHTML = '';

        const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay(); // 0 = Sun
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

        // 1. Previous month overflow days
        for (let i = firstDayIndex - 1; i >= 0; i--) {
            const dayNum = daysInPrevMonth - i;
            const cell = document.createElement('div');
            cell.className = 'cal-day-cell cal-other-month';
            cell.textContent = dayNum;
            daysGrid.appendChild(cell);
        }

        // 2. Current month active days
        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day-cell';
            cell.textContent = day;

            const mStr = String(currentMonth + 1).padStart(2, '0');
            const dStr = String(day).padStart(2, '0');
            const dateStr = `${currentYear}-${mStr}-${dStr}`;

            if (dateStr === selectedDateStr) {
                cell.classList.add('cal-selected');
            }

            // Check if any database event falls on this date
            const eventsOnDay = dbEvents.filter(e => e.date === dateStr);
            if (eventsOnDay.length > 0) {
                const dot = document.createElement('span');
                dot.className = 'cal-event-dot';
                dot.title = eventsOnDay.map(e => e.title).join(' | ');
                cell.appendChild(dot);
            }

            // Click interaction: select date and filter/highlight timeline
            cell.addEventListener('click', () => {
                if (selectedDateStr === dateStr && isFilterByDate) {
                    isFilterByDate = false;
                } else {
                    selectedDateStr = dateStr;
                    isFilterByDate = true;
                }
                renderCalendar();
                renderTimeline();
            });

            daysGrid.appendChild(cell);
        }

        // 3. Next month overflow days (fill up to 42 cells total for uniform 6 rows)
        const totalRendered = daysGrid.children.length;
        const remaining = 42 - totalRendered;
        for (let day = 1; day <= remaining; day++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day-cell cal-other-month';
            cell.textContent = day;
            daysGrid.appendChild(cell);
        }
    }

    function renderTimeline() {
        const timelineList = document.getElementById('calTimelineList');
        const calloutText = document.querySelector('.cal-callout-text');
        if (!timelineList) return;

        // Get events for the currently displayed month from DB
        const monthEvents = dbEvents.filter(e => {
            const [y, m] = e.date.split('-');
            return parseInt(y, 10) === currentYear && parseInt(m, 10) === (currentMonth + 1);
        });

        // Filter if user clicked a specific date
        let displayedEvents = monthEvents;
        if (isFilterByDate && selectedDateStr) {
            const dayEvents = monthEvents.filter(e => e.date === selectedDateStr);
            if (dayEvents.length > 0) {
                displayedEvents = dayEvents;
            }
        }

        // Sort ascending by date
        displayedEvents.sort((a, b) => a.date.localeCompare(b.date));

        // Update callout text dynamically
        if (calloutText) {
            if (isFilterByDate && selectedDateStr) {
                const parts = selectedDateStr.split('-');
                const dFormatted = `${parseInt(parts[2], 10)} ${monthNames[parseInt(parts[1], 10) - 1]} ${parts[0]}`;
                if (displayedEvents.length > 0) {
                    calloutText.innerHTML = `Showing events for <strong>${dFormatted}</strong>.<br><a href="javascript:void(0)" id="calShowAllBtn" style="color:#003399;font-weight:600;text-decoration:underline;">Show all for ${monthNames[currentMonth]}</a>`;
                    const resetBtn = document.getElementById('calShowAllBtn');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            isFilterByDate = false;
                            renderCalendar();
                            renderTimeline();
                        });
                    }
                } else {
                    calloutText.innerHTML = `No events scheduled on <strong>${dFormatted}</strong>.<br>Showing all events for ${monthNames[currentMonth]}.`;
                }
            } else {
                calloutText.innerHTML = `Click on a date in the calendar<br>to see events for that day.`;
            }
        }

        if (displayedEvents.length === 0) {
            timelineList.innerHTML = `
                <div style="padding: 36px 16px; text-align: center; color: #64748b;">
                    <i class="far fa-calendar-times" style="font-size: 26px; margin-bottom: 8px; display: block; color: #94a3b8;"></i>
                    <p style="margin: 0; font-size: 14px;">No events scheduled for ${monthNames[currentMonth]} ${currentYear}.</p>
                </div>
            `;
            return;
        }

        timelineList.innerHTML = displayedEvents.map(event => {
            const parts = event.date.split('-');
            const dayNum = parts[2];
            const mIdx = parseInt(parts[1], 10) - 1;
            const mText = monthShorts[mIdx] || 'SEP';
            const isSelected = event.date === selectedDateStr;

            return `
                <div class="cal-timeline-item" style="${isSelected ? 'background: #f8fafc; border-radius: 8px; padding: 6px 8px; margin-left: -8px;' : ''}">
                    <div class="cal-item-date-badge">
                        <div class="cal-date-number">${dayNum}</div>
                        <div class="cal-date-month">${mText}</div>
                    </div>
                    <div class="cal-item-node" style="${isSelected ? 'transform: scale(1.25); box-shadow: 0 0 0 4px rgba(211,47,47,0.25);' : ''}"></div>
                    <div class="cal-item-content">
                        <h4 class="cal-item-title">${escapeHtml(event.title)}</h4>
                        <div class="cal-item-subtitle">${escapeHtml(event.subtitle)}</div>
                        <div class="cal-item-fulldate">${event.fulldate}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initListeners() {
        const prevBtn = document.getElementById('calPrevBtn');
        const nextBtn = document.getElementById('calNextBtn');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                isFilterByDate = false;
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar();
                renderTimeline();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                isFilterByDate = false;
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar();
                renderTimeline();
            });
        }
    }

    // Expose dynamic reload function
    window.refreshTargetCalendar = fetchCalendarData;

    document.addEventListener('DOMContentLoaded', () => {
        initListeners();
        fetchCalendarData();
    });
})();
