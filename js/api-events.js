// Events API Integration

// Load upcoming events for homepage
async function loadUpcomingEvents() {
    const container = document.getElementById('eventsContainer');
    if (!container) return;

    try {
        const response = await API.get('/events/list.php?upcoming=true');

        if (response.success && response.data && response.data.length > 0) {
            // Filter only upcoming events with status 'Upcoming'
            const upcomingEvents = response.data.filter(event => 
                event.status === 'Upcoming' && new Date(event.event_date) >= new Date()
            );
            
            if (upcomingEvents.length > 0) {
                displayEvents(upcomingEvents.slice(0, 3)); // Show only first 3 events
            } else {
                showNoEvents();
            }
        } else {
            showNoEvents();
        }
    } catch (error) {
        console.error('Error loading events:', error);
        showNoEvents();
    }
}

// Display events matching the static design
function displayEvents(events) {
    const container = document.getElementById('eventsContainer');
    if (!container) return;

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.event_date);
        const day = eventDate.getDate();
        const month = eventDate.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        
        return `
            <div class="event-item">
                <div class="event-date">
                    <span class="event-month">${month}</span>
                    <span class="event-day">${day}</span>
                </div>
                <div class="event-info">
                    <h4>${event.title}</h4>
                    ${event.location ? `<p style="margin: 0; font-size: 14px; opacity: 0.9;"><i class="fas fa-map-marker-alt"></i> ${event.location}</p>` : ''}
                </div>
                <a href="Contact-redesign.html" class="event-btn">Register</a>
            </div>
        `;
    }).join('');
}

// Show message when no events
function showNoEvents() {
    const container = document.getElementById('eventsContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div style="text-align: center; padding: 40px; width: 100%; color: #666;">
            <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
            <p style="font-size: 18px; margin: 0;">No upcoming events at the moment</p>
            <p style="font-size: 14px; margin-top: 10px; opacity: 0.7;">Check back soon for new events!</p>
        </div>
    `;
}

// Initialize events on homepage
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('upcomingEventsList') || 
        document.querySelector('.events-list') || 
        window.location.pathname === '/' || 
        window.location.pathname.endsWith('/index.html') ||
        window.location.pathname.endsWith('/')) {
        loadUpcomingEvents();
    }
});
