// Sample events data
let events = [
    { id: 1, name: 'Corporate Launch Event', venue: 'KLCC Convention Centre', date: '2025-02-08', time: '19:00', attendees: 250, status: 'upcoming' },
    { id: 2, name: 'Jazz Night Performance', venue: 'The Bee Publika', date: '2025-02-12', time: '20:30', attendees: 80, status: 'upcoming' },
    { id: 3, name: 'Private Birthday Party', venue: 'Desa ParkCity', date: '2025-02-14', time: '18:00', attendees: 50, status: 'upcoming' },
    { id: 4, name: 'Wedding Reception', venue: 'Mandarin Oriental', date: '2025-02-18', time: '17:00', attendees: 150, status: 'upcoming' },
    { id: 5, name: 'University Festival', venue: 'UM Campus', date: '2025-01-15', time: '14:00', attendees: 500, status: 'past' }
];

let currentFilter = 'all';
let selectedEventId = null;
let viewMode = 'grid'; // 'grid' or 'calendar'
let currentMonth = new Date();

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    setupSearch();
});

// Load events from PHP backend
async function loadEventsFromDB() {
    try {
        const response = await fetch('events.php?api=list');
        const data = await response.json();
        if (data.success && data.data) {
            events = data.data.map(e => ({
    id: e.event_id,
    name: e.title,
    venue: e.venue,
    date: e.date,
    time: e.start_time,
    attendees: 0,
    status: e.status
}));

        }
    } catch (error) {
        console.error('Error loading events:', error);
    }
}

// Load and display events
function loadEvents() {
    const grid = document.getElementById('eventsGrid');
    const filtered = filterEventsByStatus(currentFilter);
    
    if (filtered.length === 0) {
        grid.innerHTML = '<div class="empty-state">No events found</div>';
        return;
    }
    
    grid.innerHTML = filtered.map(event => `
        <div class="event-card" onclick="viewEvent(${event.id})">
            <div class="event-image">
                🎤
                <div class="event-badge">${event.status}</div>
            </div>
            <div class="event-content">
                <h4>${event.name}</h4>
                <div class="event-meta">
                    <span>📍 ${event.venue}</span>
                    <span>📅 ${event.date}</span>
                    <span>⏰ ${event.time}</span>
                    <span>👥 ${event.attendees} attendees</span>
                </div>
                <div class="event-actions">
                    <button onclick="event.stopPropagation(); editEvent(${event.id})" class="btn-primary" style="background: rgba(59, 130, 246, 0.2); color: var(--blue);">
                        Edit
                    </button>
                    <button onclick="event.stopPropagation(); deleteEvent(${event.id})" class="btn-danger" style="background: rgba(239, 68, 68, 0.2); color: var(--red);">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Filter events
function filterEvents(status) {
    currentFilter = status;
    
    // Update active tab
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    loadEvents();
}

function filterEventsByStatus(status) {
    if (status === 'all') return events;
    if (status === 'upcoming') {
        const today = new Date().toISOString().split('T')[0];
        return events.filter(e => e.date >= today);
    }
    if (status === 'past') {
        const today = new Date().toISOString().split('T')[0];
        return events.filter(e => e.date < today);
    }
    return events.filter(e => e.status === status);
}

// Toggle view
function toggleView() {
    viewMode = viewMode === 'grid' ? 'calendar' : 'grid';
    
    const gridView = document.getElementById('eventsGrid');
    const calendarView = document.getElementById('calendarView');
    
    if (viewMode === 'calendar') {
        gridView.style.display = 'none';
        calendarView.style.display = 'block';
        loadCalendar();
    } else {
        gridView.style.display = 'grid';
        calendarView.style.display = 'none';
    }
}

// Load calendar
function loadCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const monthElement = document.getElementById('calendarMonth');
    monthElement.textContent = `${monthNames[currentMonth.getMonth()]} ${currentMonth.getFullYear()}`;
    
    const grid = document.getElementById('calendarGrid');
    const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDay = firstDay.getDay();
    
    let html = '';
    
    // Day headers
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
        html += `<div style="text-align: center; font-weight: 600; padding: 8px; color: var(--text-secondary);">${day}</div>`;
    });
    
    // Empty cells before first day
    for (let i = 0; i < startDay; i++) {
        html += '<div></div>';
    }
    
    // Calendar days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${currentMonth.getFullYear()}-${String(currentMonth.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEvents = events.filter(e => e.date === date);
        const hasEvent = dayEvents.length > 0;
        
        html += `
            <div class="calendar-day ${hasEvent ? 'has-event' : ''}" onclick="showDayEvents('${date}')">
                <div style="font-weight: 600; margin-bottom: 4px;">${day}</div>
                ${hasEvent ? `<div style="font-size: 10px; color: var(--purple);">${dayEvents.length} event${dayEvents.length > 1 ? 's' : ''}</div>` : ''}
            </div>
        `;
    }
    
    grid.innerHTML = html;
}

function previousMonth() {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1);
    loadCalendar();
}

function nextMonth() {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1);
    loadCalendar();
}

function showDayEvents(date) {
    const dayEvents = events.filter(e => e.date === date);
    if (dayEvents.length === 0) return;
    
    alert(`Events on ${date}:\n\n${dayEvents.map(e => `${e.time} - ${e.name} at ${e.venue}`).join('\n')}`);
}

// View event details
function viewEvent(id) {
    const event = events.find(e => e.id === id);
    if (!event) return;
    
    selectedEventId = id;
    
    const modal = document.getElementById('eventModal');
    const details = document.getElementById('eventDetails');
    
    details.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">Event Name</label>
                <p style="font-size: 18px; font-weight: 600; margin-top: 4px;">${event.name}</p>
            </div>
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">Venue</label>
                <p style="font-size: 16px; margin-top: 4px;">📍 ${event.venue}</p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Date</label>
                    <p style="font-size: 16px; margin-top: 4px;">📅 ${event.date}</p>
                </div>
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Time</label>
                    <p style="font-size: 16px; margin-top: 4px;">⏰ ${event.time}</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Attendees</label>
                    <p style="font-size: 20px; font-weight: 700; margin-top: 4px;">👥 ${event.attendees}</p>
                </div>
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Status</label>
                    <p style="margin-top: 4px;"><span class="badge info">${event.status.toUpperCase()}</span></p>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
}

// Edit event
function editEvent(id) {
    if (!id) id = selectedEventId;
    if (!id) return;
    
    // Redirect to edit page or open edit modal
    window.location.href = `add_event.html?id=${id}`;
}

// Delete event
async function deleteEvent(id) {
    if (!id) id = selectedEventId;
    if (!id) return;
    
    if (!confirm('Are you sure you want to delete this event?')) return;
    
    try {
        const response = await fetch('events.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            events = events.filter(e => e.id !== id);
            loadEvents();
            closeModal();
            alert('Event deleted successfully!');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        // Fallback for demo
        events = events.filter(e => e.id !== id);
        loadEvents();
        closeModal();
        alert('Event deleted!');
    }
}

// Close modal
function closeModal() {
    document.getElementById('eventModal').classList.remove('active');
    selectedEventId = null;
}

// Search functionality
function setupSearch() {
    const searchBox = document.getElementById('searchBox');
    searchBox?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = events.filter(ev => 
            ev.name.toLowerCase().includes(query) ||
            ev.venue.toLowerCase().includes(query)
        );
        
        const grid = document.getElementById('eventsGrid');
        if (filtered.length === 0) {
            grid.innerHTML = '<div class="empty-state">No events found</div>';
            return;
        }
        
        grid.innerHTML = filtered.map(event => `
            <div class="event-card" onclick="viewEvent(${event.id})">
                <div class="event-image">
                    🎤
                    <div class="event-badge">${event.status}</div>
                </div>
                <div class="event-content">
                    <h4>${event.name}</h4>
                    <div class="event-meta">
                        <span>📍 ${event.venue}</span>
                        <span>📅 ${event.date}</span>
                        <span>⏰ ${event.time}</span>
                        <span>👥 ${event.attendees} attendees</span>
                    </div>
                    <div class="event-actions">
                        <button onclick="event.stopPropagation(); editEvent(${event.id})" class="btn-primary" style="background: rgba(59, 130, 246, 0.2); color: var(--blue);">Edit</button>
                        <button onclick="event.stopPropagation(); deleteEvent(${event.id})" class="btn-danger" style="background: rgba(239, 68, 68, 0.2); color: var(--red);">Delete</button>
                    </div>
                </div>
            </div>
        `).join('');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('eventModal');
    if (event.target === modal) {
        closeModal();
    }
}