var bookings = [];
var currentFilter = 'all';
var searchQuery = '';
var approvingBookingId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadBookings();
    setupSearch();
});

function loadBookings() {
    fetch('bookings.php?api=list')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                bookings = data.data;
            }
            renderBookings();
            updateStats();
        })
        .catch(function() {
            showToast('Failed to load bookings', 'error');
        });
}

function renderBookings() {
    var tbody = document.getElementById('bookingsTable');
    var filtered = bookings.slice();

    if (currentFilter !== 'all') {
        filtered = filtered.filter(function(b) { return b.status === currentFilter; });
    }

    if (searchQuery) {
        filtered = filtered.filter(function(b) {
            return (b.company_name || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (b.event_name || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (b.location || '').toLowerCase().indexOf(searchQuery) !== -1;
        });
    }

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No bookings found</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(function(b) {
        var actionsHtml = '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
        if (b.status === 'pending') {
            actionsHtml += '<button onclick="event.stopPropagation();approveBooking(' + b.id + ')" class="btn-success btn-small">' + icon('check') + ' Approve</button>';
            actionsHtml += '<button onclick="event.stopPropagation();rejectBooking(' + b.id + ')" class="btn-danger btn-small">' + icon('x') + ' Reject</button>';
        }
        if (b.invoice_number) {
            actionsHtml += '<a href="invoice.php?id=' + b.id + '" target="_blank" class="btn-primary btn-small" style="text-decoration:none;">' + icon('receipt') + ' Invoice</a>';
        }
        if (b.status === 'approved' && b.payment_status === 'unpaid') {
            actionsHtml += '<button onclick="event.stopPropagation();markPaid(' + b.id + ')" class="btn-success btn-small">' + icon('check') + ' Mark Paid</button>';
        }
        actionsHtml += '<button onclick="event.stopPropagation();deleteBooking(' + b.id + ')" class="btn-danger btn-small">' + icon('trash') + '</button>';
        actionsHtml += '</div>';

        var paymentHtml = '-';
        if (b.invoice_number) {
            if (b.payment_status === 'paid') {
                paymentHtml = '<span class="badge status-approved">PAID</span>';
                if (b.paid_at) {
                    paymentHtml += '<div style="font-size:11px;color:#6b7280;margin-top:2px;">' + b.paid_at.split(' ')[0] + '</div>';
                }
            } else {
                var isOverdue = b.payment_due_date && new Date(b.payment_due_date) < new Date();
                paymentHtml = '<span class="badge status-pending">UNPAID</span>';
                if (b.payment_due_date) {
                    paymentHtml += '<div style="font-size:11px;color:' + (isOverdue ? '#ef4444' : '#6b7280') + ';margin-top:2px;">Due: ' + b.payment_due_date + '</div>';
                }
            }
        }

        return '<tr>' +
            '<td>#' + b.id + '</td>' +
            '<td>' + esc(b.company_name) + '</td>' +
            '<td>' + esc(b.event_name) + '</td>' +
            '<td>' + (b.event_date || '-') + '</td>' +
            '<td>' + esc(b.location) + '</td>' +
            '<td><strong>' + (b.price ? 'RM ' + Number(b.price).toLocaleString() : '-') + '</strong></td>' +
            '<td><span class="badge status-' + b.status + '">' + (b.status || '').toUpperCase() + '</span></td>' +
            '<td>' + paymentHtml + '</td>' +
            '<td>' + actionsHtml + '</td>' +
            '</tr>';
    }).join('');
}

function updateStats() {
    document.getElementById('totalBookings').textContent = bookings.length;
    document.getElementById('pendingBookings').textContent = bookings.filter(function(b) { return b.status === 'pending'; }).length;
    document.getElementById('approvedBookings').textContent = bookings.filter(function(b) { return b.status === 'approved'; }).length;
    document.getElementById('unpaidBookings').textContent = bookings.filter(function(b) { return b.payment_status === 'unpaid' && b.invoice_number; }).length;
    var revenue = bookings.filter(function(b) { return b.status === 'approved'; }).reduce(function(s, b) { return s + Number(b.price || 0); }, 0);
    document.getElementById('totalRevenue').textContent = 'RM ' + revenue.toLocaleString();
}

function filterBookings(status) {
    currentFilter = status;
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    if (event && event.target) event.target.classList.add('active');
    renderBookings();
}

function approveBooking(id) {
    approvingBookingId = id;
    document.getElementById('approvePrice').value = '';

    var booking = bookings.find(function(b) { return b.id == id; });
    var budgetHint = document.getElementById('budgetHint');
    if (!budgetHint) {
        budgetHint = document.createElement('div');
        budgetHint.id = 'budgetHint';
        budgetHint.style.cssText = 'font-size:13px;color:#f59e0b;margin-bottom:8px;padding:8px 12px;background:rgba(245,158,11,0.1);border-radius:8px;';
        var formGroup = document.getElementById('approvePrice').parentElement;
        formGroup.insertBefore(budgetHint, document.getElementById('approvePrice'));
    }

    if (booking && booking.quotation_price && Number(booking.quotation_price) > 0) {
        budgetHint.innerHTML = '<i class="bi bi-info-circle"></i> Customer\'s budget: <strong>RM ' + Number(booking.quotation_price).toFixed(2) + '/day</strong>';
        budgetHint.style.display = 'block';
        document.getElementById('approvePrice').value = booking.quotation_price;
    } else {
        budgetHint.style.display = 'none';
    }

    var modal = document.getElementById('priceModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.getElementById('approvePrice').focus();
}

function closePriceModal() {
    var modal = document.getElementById('priceModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    approvingBookingId = null;
}

function confirmApprove() {
    if (!approvingBookingId) return;
    var price = document.getElementById('approvePrice').value;

    var fd = new FormData();
    fd.append('api', 'approve');
    fd.append('id', approvingBookingId);
    if (price) fd.append('price', price);

    fetch('bookings.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                closePriceModal();
                showToast('Booking approved. Invoice: ' + (data.invoice_number || ''), 'success');
                loadBookings();
            } else {
                showToast(data.message || 'Failed to approve', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function rejectBooking(id) {
    if (!confirm('Reject this booking?')) return;
    var fd = new FormData();
    fd.append('api', 'reject');
    fd.append('id', id);
    fetch('bookings.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Booking rejected', 'success');
                loadBookings();
            } else {
                showToast(data.message || 'Failed to reject', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function markPaid(id) {
    if (!confirm('Mark this booking as paid?')) return;
    var fd = new FormData();
    fd.append('api', 'mark_paid');
    fd.append('id', id);
    fetch('bookings.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment confirmed', 'success');
                loadBookings();
            } else {
                showToast(data.message || 'Failed to update payment', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function deleteBooking(id) {
    if (!confirm('Delete this booking permanently?')) return;
    var fd = new FormData();
    fd.append('api', 'delete');
    fd.append('id', id);
    fetch('bookings.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Booking deleted', 'success');
                loadBookings();
            } else {
                showToast(data.message || 'Failed to delete', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            searchQuery = e.target.value.toLowerCase();
            renderBookings();
        });
    }
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '-';
    return d.innerHTML;
}

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('priceModal')) closePriceModal();
});
