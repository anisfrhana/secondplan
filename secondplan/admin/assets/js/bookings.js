// Sample bookings data
let bookings = [
    { id: 1, company: 'TechCorp Malaysia', event: 'Annual Gala Night', date: '2025-02-15', location: 'KLCC', amount: 8500, status: 'approved' },
    { id: 2, company: 'Music Festival Sdn Bhd', event: 'Summer Rock Festival', date: '2025-02-20', location: 'Stadium Bukit Jalil', amount: 15000, status: 'pending' },
    { id: 3, company: 'Wedding Planners Pro', event: 'Garden Wedding', date: '2025-02-10', location: 'Putrajaya', amount: 6500, status: 'approved' },
    { id: 4, company: 'Corporate Events Co', event: 'Product Launch', date: '2025-02-25', location: 'Pavilion KL', amount: 12000, status: 'pending' },
    { id: 5, company: 'University Students Union', event: 'Graduation Party', date: '2025-03-05', location: 'UM Campus', amount: 4500, status: 'rejected' }
];

let currentFilter = 'all';
let selectedBookingId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadBookings();
    updateStats();
    setupSearch();
});

// Load bookings from PHP backend
async function loadBookingsFromDB() {
    try {
        const response = await fetch('bookings.php?api=list');
        const data = await response.json();
        if (data.success && data.data) {
            bookings = data.data.map(b => ({
                id: b.id,
                company: b.company_name,
                event: b.event_name,
                date: b.event_date,
                location: b.location,
                amount: parseFloat(b.price),
                status: b.status
            }));
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
    }
}

// Load and display bookings
function loadBookings() {
    const tbody = document.getElementById('bookingsTable');
    const filtered = filterBookingsByStatus(currentFilter);
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No bookings found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map(booking => `
        <tr onclick="viewBooking(${booking.id})">
            <td>#${booking.id}</td>
            <td>${booking.company}</td>
            <td>${booking.event}</td>
            <td>${booking.date}</td>
            <td>${booking.location}</td>
            <td><strong>RM ${booking.amount.toLocaleString()}</strong></td>
            <td><span class="badge ${getStatusClass(booking.status)}">${booking.status.toUpperCase()}</span></td>
            <td>
                <div style="display: flex; gap: 8px;">
                    ${booking.status === 'pending' ? `
                        <button onclick="event.stopPropagation(); approveBooking(${booking.id})" class="btn-success" style="padding: 6px 12px; font-size: 12px;">Approve</button>
                        <button onclick="event.stopPropagation(); rejectBooking(${booking.id})" class="btn-danger" style="padding: 6px 12px; font-size: 12px;">Reject</button>
                    ` : ''}
                    <button onclick="event.stopPropagation(); deleteBooking(${booking.id})" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}