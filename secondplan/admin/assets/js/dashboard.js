// API Base URL
const API_BASE = '../api/';

// State
let notifications = [];
let stats = {
    revenue: 0,
    bookings: 0,
    tasks: 0,
    expenses: 0
};

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    loadNotifications();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Search functionality
    document.getElementById('searchBox')?.addEventListener('input', handleSearch);
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notification-btn') && !e.target.closest('.notification-dropdown')) {
            document.getElementById('notificationDropdown')?.classList.remove('active');
        }
    });
}

// Load Dashboard Data
async function loadDashboardData() {
    try {
        showLoading();
        
        // Load stats
        await Promise.all([
            loadStats(),
            loadRecentBookings(),
            loadUpcomingEvents(),
            loadRevenueChart(),
            loadExpenseBreakdown()
        ]);
        
        hideLoading();
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showError('Failed to load dashboard data');
    }
}

// Load Stats
async function loadStats() {
    try {
        // You can replace this with actual API call
        // const response = await fetch(API_BASE + 'dashboard_stats.php');
        // const data = await response.json();
        
        // Demo data
        const data = {
            totalRevenue: 45250,
            revenueChange: 12.5,
            activeBookings: 8,
            bookingsChange: 3,
            pendingTasks: 12,
            tasksChange: -2,
            monthlyExpenses: 18750,
            expensesChange: 8.3
        };
        
        // Update UI
        document.getElementById('totalRevenue').textContent = `RM ${data.totalRevenue.toLocaleString()}`;
        document.getElementById('revenueChange').textContent = `↑ ${data.revenueChange}%`;
        document.getElementById('revenueChange').className = data.revenueChange > 0 ? 'stat-change' : 'stat-change negative';
        
        document.getElementById('activeBookings').textContent = data.activeBookings;
        document.getElementById('bookingsChange').textContent = `↑ ${data.bookingsChange}`;
        
        document.getElementById('pendingTasks').textContent = data.pendingTasks;
        document.getElementById('tasksChange').textContent = `${data.tasksChange > 0 ? '↑' : '↓'} ${Math.abs(data.tasksChange)}`;
        document.getElementById('tasksChange').className = data.tasksChange > 0 ? 'stat-change' : 'stat-change negative';
        
        document.getElementById('monthlyExpenses').textContent = `RM ${data.monthlyExpenses.toLocaleString()}`;
        document.getElementById('expensesChange').textContent = `↑ ${data.expensesChange}%`;
        
        stats = data;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load Recent Bookings
async function loadRecentBookings() {
    try {
        const response = await fetch('../admin/bookings.php?api=list&limit=5');
        const data = await response.json();
        
        const tbody = document.getElementById('recentBookings');
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No bookings yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.data.map(booking => `
            <tr>
                <td>${booking.company_name || 'N/A'}</td>
                <td>${booking.event_name || 'N/A'}</td>
                <td>${booking.event_date || 'N/A'}</td>
                <td><strong>RM ${parseFloat(booking.price || 0).toLocaleString()}</strong></td>
                <td><span class="badge ${getStatusClass(booking.status)}">${booking.status?.toUpperCase() || 'PENDING'}</span></td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading bookings:', error);
        document.getElementById('recentBookings').innerHTML = '<tr><td colspan="5" class="empty-state">Failed to load bookings</td></tr>';
    }
}

// Load Upcoming Events
async function loadUpcomingEvents() {
    try {
        const response = await fetch('../api/events.php?action=upcoming&limit=5');
        const data = await response.json();
        
        const container = document.getElementById('upcomingEvents');
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<div class="empty-state">No upcoming events</div>';
            return;
        }
        
        container.innerHTML = data.data.map(event => `
            <div class="event-item">
                <div class="event-icon">📅</div>
                <div class="event-details">
                    <h4>${event.name || 'Untitled Event'}</h4>
                    <div class="event-meta">
                        📍 ${event.location || 'TBA'} • 
                        ⏰ ${event.date || 'TBA'} ${event.time || ''}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('upcomingEvents').innerHTML = '<div class="empty-state">Failed to load events</div>';
    }
}

// Load Revenue Chart
async function loadRevenueChart() {
    const days = parseInt(document.getElementById('revenueFilter')?.value || 7);
    
    try {
        // Demo data - replace with API call
        const data = Array.from({length: days}, (_, i) => ({
            day: i + 1,
            value: Math.floor(Math.random() * 5000) + 2000
        }));
        
        const chart = document.getElementById('revenueChart');
        const maxValue = Math.max(...data.map(d => d.value));
        
        chart.innerHTML = data.map(d => {
            const height = (d.value / maxValue * 100);
            return `
                <div class="chart-bar" 
                     style="height: ${height}%" 
                     data-value="RM ${d.value.toLocaleString()}"
                     title="Day ${d.day}: RM ${d.value.toLocaleString()}">
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading revenue chart:', error);
    }
}

// Load Expense Breakdown
async function loadExpenseBreakdown() {
    try {
        // Demo data
        const categories = [
            { name: 'Equipment', amount: 8500, percentage: 45, color: '#3b82f6' },
            { name: 'Marketing', amount: 4200, percentage: 22, color: '#22c55e' },
            { name: 'Transportation', amount: 3150, percentage: 17, color: '#eab308' },
            { name: 'Miscellaneous', amount: 2900, percentage: 16, color: '#a855f7' }
        ];
        
        const container = document.getElementById('expenseBreakdown');
        container.innerHTML = categories.map(cat => `
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #cbd5e1; font-size: 14px;">${cat.name}</span>
                    <span style="font-weight: 600;">RM ${cat.amount.toLocaleString()}</span>
                </div>
                <div style="background: rgba(51, 65, 85, 0.3); border-radius: 999px; height: 8px; overflow: hidden;">
                    <div style="background: ${cat.color}; height: 100%; width: ${cat.percentage}%; transition: width 0.5s;"></div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading expense breakdown:', error);
    }
}

// Load Notifications
async function loadNotifications() {
    try {
        const response = await fetch('../api/notifications.php');
        const data = await response.json();
        
        notifications = data.data || [];
        updateNotificationUI();
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Update Notification UI
function updateNotificationUI() {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    
    const unreadCount = notifications.filter(n => !n.read).length;
    
    if (unreadCount > 0) {
        badge.classList.add('active');
    } else {
        badge.classList.remove('active');
    }
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="empty-state">No notifications</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.read ? '' : 'unread'}">
            <div class="notification-icon">🔔</div  >
            <div class="notification-content">
                <h4>${notif.title}</h4>
                <p>${notif.message}</p>
                <small>${new Date(notif.timestamp).toLocaleString()}</small>
            </div>
        </div>
    `).join('');

    async function loadStats() {
    const response = await fetch('../api/dashboard_stats.php');
    const result = await response.json();
    if(result.success){
        const data = result.data;
        document.getElementById('totalRevenue').textContent = `RM ${data.events}`; // example
        document.getElementById('activeBookings').textContent = data.bookings;
        document.getElementById('pendingTasks').textContent = data.tasks;
        document.getElementById('monthlyExpenses').textContent = `RM ${data.expenses}`;
    }
}

}
