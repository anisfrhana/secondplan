// Dashboard JS – Admin Style
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadRecentBookings();
    loadUpcomingEvents();
});

// =====================
// LOAD STATS
// =====================
async function loadStats() {
    try {
        const res = await fetch('../api/dashboard_stats.php');
        const data = await res.json();

        if (!data.success) return;

        const d = data.data;
        document.getElementById('totalRevenue').textContent = `RM ${d.revenue.toLocaleString()}`;
        document.getElementById('totalBookings').textContent = d.bookings;
        document.getElementById('pendingTasks').textContent = d.tasks;
        document.getElementById('monthlyExpenses').textContent = `RM ${d.expenses.toLocaleString()}`;

    } catch (e) {
        console.error('Stats error', e);
    }
}

// =====================
// RECENT BOOKINGS
// =====================
async function loadRecentBookings() {
    try {
        const res = await fetch('../admin/bookings.php?api=list&limit=5');
        const data = await res.json();

        const tbody = document.getElementById('recentBookings');

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="empty-state">No bookings</td></tr>`;
            return;
        }

        tbody.innerHTML = data.data.map(b => `
            <tr>
                <td>${escapeHtml(b.company_name || '-')}</td>
                <td>${escapeHtml(b.event_name || '-')}</td>
                <td>${b.event_date || '-'}</td>
                <td><strong>RM ${Number(b.price || 0).toLocaleString()}</strong></td>
                <td>
                    <span class="badge status-${b.status}">
                        ${b.status.toUpperCase()}
                    </span>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        console.error(e);
    }
}

// =====================
// UPCOMING EVENTS
// =====================
async function loadUpcomingEvents() {
    try {
        const res = await fetch('../api/events.php?action=upcoming&limit=5');
        const data = await res.json();

        const container = document.getElementById('upcomingEvents');

        if (!data.data || data.data.length === 0) {
            container.innerHTML = `<div class="empty-state">No upcoming events</div>`;
            return;
        }

        container.innerHTML = data.data.map(ev => `
            <div class="event-item">
                <div class="event-icon">📅</div>
                <div>
                    <h4>${escapeHtml(ev.name)}</h4>
                    <div class="event-meta">
                        ${ev.date} • ${ev.location || 'TBA'}
                    </div>
                </div>
            </div>
        `).join('');

    } catch (e) {
        console.error(e);
    }
}

// =====================
// UTIL
// =====================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
