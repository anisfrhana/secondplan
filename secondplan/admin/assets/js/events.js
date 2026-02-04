// Events Management System
let events = [];
let currentFilter = 'all';
let currentEventId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    loadStats();
    setupSearch();
    setMinDate();
});

// Set minimum date to today
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').min = today;
}

// Load Events
async function loadEvents() {
    try {
        const response = await fetch(`events.php?api=list&filter=${currentFilter}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load events');
        }
        
        events = data.data || [];
        renderTable();
        
    } catch (error) {
        showToast('Error loading events: ' + error.message, 'error');
        document.getElementById('eventsTable').innerHTML = `
            <tr><td colspan="7" class="empty-state">Failed to load events</td></tr>
        `;
    }
}

// Load Stats
async function loadStats() {
    try {
        const response = await fetch('events.php?api=stats');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            document.getElementById('totalEvents').textContent = stats.total_events || 0;
            document.getElementById('upcomingEvents').textContent = stats.upcoming || 0;
            document.getElementById('pastEvents').textContent = stats.past || 0;
            document.getElementById('totalCapacity').textContent = stats.total_capacity || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Render Table
function renderTable() {
    const tbody = document.getElementById('eventsTable');
    
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No events found</td></tr>';
        return;
    }
    
    tbody.innerHTML = events.map(event => `
        <tr onclick="viewEvent(${event.event_id})">
            <td>
                <strong>${escapeHtml(event.title)}</strong>
                ${event.description ? `<br><small style="color: #94a3b8">${escapeHtml(event.description.substring(0, 50))}${event.description.length > 50 ? '...' : ''}</small>` : ''}
            </td>
            <td>
                ${formatDate(event.date)}<br>
                <small>${event.start_time} - ${event.end_time}</small>
            </td>
            <td>
                ${escapeHtml(event.venue)}<br>
                ${event.location ? `<small style="color: #94a3b8">${escapeHtml(event.location)}</small>` : ''}
            </td>
            <td>
                ${event.capacity ? `${event.available_seats || 0} / ${event.capacity}` : 'Unlimited'}
            </td>
            <td>
                ${event.price ? `RM ${parseFloat(event.price).toFixed(2)}` : 'Free'}
            </td>
            <td>
                ${getStatusBadge(event.status)}
            </td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button onclick="event.stopPropagation(); editEvent(${event.event_id})" class="btn-small btn-primary">Edit</button>
                    ${event.status !== 'cancelled' ? `
                        <button onclick="event.stopPropagation(); cancelEvent(${event.event_id})" class="btn-small btn-warning">Cancel</button>
                    ` : ''}
                    <button onclick="event.stopPropagation(); deleteEvent(${event.event_id})" class="btn-small btn-danger">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Open Add Modal
function openAddModal() {
    currentEventId = null;
    document.getElementById('modalTitle').textContent = 'Add Event';
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventModal').classList.add('active');
}

// Edit Event
async function editEvent(id) {
    try {
        const response = await fetch(`events.php?api=get&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load event');
        }
        
        const event = data.data;
        currentEventId = id;
        
        document.getElementById('modalTitle').textContent = 'Edit Event';
        document.getElementById('eventId').value = event.event_id;
        document.getElementById('title').value = event.title;
        document.getElementById('description').value = event.description || '';
        document.getElementById('date').value = event.date;
        document.getElementById('start_time').value = event.start_time;
        document.getElementById('end_time').value = event.end_time;
        document.getElementById('venue').value = event.venue;
        document.getElementById('location').value = event.location || '';
        document.getElementById('capacity').value = event.capacity || '';
        document.getElementById('price').value = event.price || '';
        document.getElementById('status').value = event.status;
        
        document.getElementById('eventModal').classList.add('active');
        
    } catch (error) {
        showToast('Error loading event: ' + error.message, 'error');
    }
}

// Save Event
async function saveEvent() {
    try {
        const id = document.getElementById('eventId').value;
        const isEdit = id !== '';
        
        const eventData = {
            id: id || undefined,
            title: document.getElementById('title').value.trim(),
            description: document.getElementById('description').value.trim(),
            date: document.getElementById('date').value,
            start_time: document.getElementById('start_time').value,
            end_time: document.getElementById('end_time').value,
            venue: document.getElementById('venue').value.trim(),
            location: document.getElementById('location').value.trim(),
            capacity: document.getElementById('capacity').value || null,
            price: document.getElementById('price').value || null,
            status: document.getElementById('status').value
        };
        
        // Validation
        if (!eventData.title || !eventData.date || !eventData.start_time || !eventData.end_time || !eventData.venue) {
            showToast('Please fill in all required fields', 'error');
            return;
        }
        
        const url = isEdit ? 'events.php' : 'events.php';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(isEdit ? { ...eventData, action: 'update' } : eventData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to save event');
        }
        
        showToast(data.message || (isEdit ? 'Event updated' : 'Event created'), 'success');
        closeModal();
        loadEvents();
        loadStats();
        
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

// Cancel Event
async function cancelEvent(id) {
    if (!confirm('Are you sure you want to cancel this event?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('id', id);
        
        const response = await fetch('events.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to cancel event');
        }
        
        showToast('Event cancelled successfully', 'success');
        loadEvents();
        loadStats();
        
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

// Delete Event
async function deleteEvent(id) {
    if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        const response = await fetch('events.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to delete event');
        }
        
        showToast('Event deleted successfully', 'success');
        loadEvents();
        loadStats();
        
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

// View Event
async function viewEvent(id) {
    try {
        const response = await fetch(`events.php?api=get&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load event');
        }
        
        const event = data.data;
        currentEventId = id;
        
        document.getElementById('eventDetails').innerHTML = `
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Event Title</label>
                    <p>${escapeHtml(event.title)}</p>
                </div>
                <div class="detail-item">
                    <label>Date</label>
                    <p>${formatDate(event.date)}</p>
                </div>
                <div class="detail-item">
                    <label>Time</label>
                    <p>${event.start_time} - ${event.end_time}</p>
                </div>
                <div class="detail-item">
                    <label>Venue</label>
                    <p>${escapeHtml(event.venue)}</p>
                </div>
                ${event.location ? `
                    <div class="detail-item">
                        <label>Location</label>
                        <p>${escapeHtml(event.location)}</p>
                    </div>
                ` : ''}
                <div class="detail-item">
                    <label>Capacity</label>
                    <p>${event.capacity ? `${event.available_seats || 0} available / ${event.capacity} total` : 'Unlimited'}</p>
                </div>
                <div class="detail-item">
                    <label>Price</label>
                    <p>${event.price ? `RM ${parseFloat(event.price).toFixed(2)}` : 'Free'}</p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p>${getStatusBadge(event.status)}</p>
                </div>
                ${event.description ? `
                    <div class="detail-item full-width">
                        <label>Description</label>
                        <p>${escapeHtml(event.description)}</p>
                    </div>
                ` : ''}
                ${event.created_by_name ? `
                    <div class="detail-item">
                        <label>Created By</label>
                        <p>${escapeHtml(event.created_by_name)}</p>
                    </div>
                ` : ''}
                <div class="detail-item">
                    <label>Created At</label>
                    <p>${formatDateTime(event.created_at)}</p>
                </div>
            </div>
        `;
        
        document.getElementById('viewModal').classList.add('active');
        
    } catch (error) {
        showToast('Error loading event: ' + error.message, 'error');
    }
}

// Edit from View
function editFromView() {
    closeViewModal();
    if (currentEventId) {
        editEvent(currentEventId);
    }
}

// Filter Events
function filterEvents(filter) {
    currentFilter = filter;
    
    // Update active tab
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    loadEvents();
}

// Search Events
function setupSearch() {
    const searchBox = document.getElementById('searchBox');
    let searchTimeout;
    
    searchBox.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = e.target.value.toLowerCase();
            
            if (query === '') {
                renderTable();
                return;
            }
            
            const filtered = events.filter(event => 
                event.title.toLowerCase().includes(query) ||
                (event.description && event.description.toLowerCase().includes(query)) ||
                event.venue.toLowerCase().includes(query) ||
                (event.location && event.location.toLowerCase().includes(query))
            );
            
            const tbody = document.getElementById('eventsTable');
            
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No events found</td></tr>';
                return;
            }
            
            events = filtered;
            renderTable();
        }, 300);
    });
}

// Close Modal
function closeModal() {
    document.getElementById('eventModal').classList.remove('active');
    document.getElementById('eventForm').reset();
    currentEventId = null;
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
    currentEventId = null;
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusBadge(status) {
    const statusMap = {
        'scheduled': 'success',
        'completed': 'info',
        'cancelled': 'danger',
        'postponed': 'warning'
    };
    
    const badgeClass = statusMap[status] || 'info';
    return `<span class="badge badge-${badgeClass}">${status.toUpperCase()}</span>`;
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast toast-${type} active`;
    
    setTimeout(() => {
        toast.classList.remove('active');
    }, 3000);
}

// Close modals on outside click
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});