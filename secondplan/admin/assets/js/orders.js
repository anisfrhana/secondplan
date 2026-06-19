var orders = [];
var currentFilter = 'all';
var searchQuery = '';

document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    setupSearch();
});

function loadOrders() {
    fetch('orders.php?api=list')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                orders = data.data;
            }
            renderOrders();
            updateStats();
        })
        .catch(function() {
            showToast('Failed to load orders', 'error');
        });
}

function renderOrders() {
    var tbody = document.getElementById('ordersTable');
    var filtered = orders.slice();

    if (currentFilter !== 'all') {
        filtered = filtered.filter(function(o) { return o.status === currentFilter; });
    }

    if (searchQuery) {
        filtered = filtered.filter(function(o) {
            return (o.order_number || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (o.customer_name || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (o.customer_email || '').toLowerCase().indexOf(searchQuery) !== -1;
        });
    }

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No orders found</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(function(o) {
        var statusOptions = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        var statusSelect = '<select onchange="updateStatus(' + o.order_id + ', this.value)" style="padding:4px 8px;border-radius:6px;border:1px solid #d1d5db;font-size:12px;">';
        statusOptions.forEach(function(s) {
            statusSelect += '<option value="' + s + '"' + (o.status === s ? ' selected' : '') + '>' + s.charAt(0).toUpperCase() + s.slice(1) + '</option>';
        });
        statusSelect += '</select>';

        var paymentHtml = '';
        if (o.payment_status === 'paid') {
            paymentHtml = '<span class="badge status-approved">PAID</span>';
        } else if (o.payment_status === 'refunded') {
            paymentHtml = '<span class="badge status-rejected">REFUNDED</span>';
        } else {
            paymentHtml = '<span class="badge status-pending">UNPAID</span>';
        }

        var actionsHtml = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
        if (o.payment_status === 'unpaid') {
            actionsHtml += '<button onclick="event.stopPropagation();markPaid(' + o.order_id + ')" class="btn-success btn-small" title="Mark Paid">' + icon('check') + '</button>';
        }
        actionsHtml += '<button onclick="event.stopPropagation();viewOrder(' + o.order_id + ')" class="btn-primary btn-small" title="View Details">' + icon('eye') + '</button>';
        actionsHtml += '<button onclick="event.stopPropagation();deleteOrder(' + o.order_id + ')" class="btn-danger btn-small" title="Delete">' + icon('trash') + '</button>';
        actionsHtml += '</div>';

        var dateStr = o.created_at ? o.created_at.split(' ')[0] : '-';

        return '<tr>' +
            '<td><strong>' + esc(o.order_number) + '</strong></td>' +
            '<td>' + esc(o.customer_name) + '<div style="font-size:11px;color:#6b7280;">' + esc(o.customer_email) + '</div></td>' +
            '<td>' + dateStr + '</td>' +
            '<td>' + (o.item_count || 0) + '</td>' +
            '<td><strong>RM ' + Number(o.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</strong></td>' +
            '<td>' + paymentHtml + '</td>' +
            '<td>' + statusSelect + '</td>' +
            '<td>' + actionsHtml + '</td>' +
            '</tr>';
    }).join('');
}

function updateStats() {
    document.getElementById('totalOrders').textContent = orders.length;
    document.getElementById('pendingOrders').textContent = orders.filter(function(o) { return o.status === 'pending'; }).length;
    document.getElementById('processingOrders').textContent = orders.filter(function(o) { return o.status === 'processing'; }).length;
    document.getElementById('unpaidOrders').textContent = orders.filter(function(o) { return o.payment_status === 'unpaid'; }).length;
    var revenue = orders.filter(function(o) { return o.payment_status === 'paid'; }).reduce(function(s, o) { return s + Number(o.total_amount || 0); }, 0);
    document.getElementById('totalRevenue').textContent = 'RM ' + revenue.toLocaleString(undefined, {minimumFractionDigits: 2});
}

function filterOrders(status) {
    currentFilter = status;
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    if (event && event.target) event.target.classList.add('active');
    renderOrders();
}

function viewOrder(id) {
    var modal = document.getElementById('orderDetailModal');
    var body = document.getElementById('modalBody');
    body.innerHTML = '<div class="loading">Loading order details...</div>';
    modal.style.display = 'flex';
    modal.classList.add('active');

    fetch('orders.php?api=get&id=' + id)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                body.innerHTML = '<p>Failed to load order details.</p>';
                return;
            }
            var o = data.order;
            var items = data.items || [];

            var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">';
            html += '<div><strong>Order Number</strong><div>' + esc(o.order_number) + '</div></div>';
            html += '<div><strong>Date</strong><div>' + (o.created_at || '-') + '</div></div>';
            html += '<div><strong>Customer</strong><div>' + esc(o.customer_name) + '</div><div style="font-size:12px;color:#6b7280;">' + esc(o.customer_email) + '</div></div>';
            html += '<div><strong>Status</strong><div><span class="badge status-' + (o.status === 'delivered' ? 'approved' : o.status === 'cancelled' ? 'rejected' : 'pending') + '">' + (o.status || '').toUpperCase() + '</span></div></div>';
            html += '<div><strong>Payment</strong><div><span class="badge status-' + (o.payment_status === 'paid' ? 'approved' : o.payment_status === 'refunded' ? 'rejected' : 'pending') + '">' + (o.payment_status || '').toUpperCase() + '</span></div></div>';
            html += '<div><strong>Payment Method</strong><div>' + esc(o.payment_method) + '</div></div>';
            if (o.shipping_address) {
                html += '<div style="grid-column:1/3;"><strong>Shipping Address</strong><div>' + esc(o.shipping_address) + '</div></div>';
            }
            if (o.notes) {
                html += '<div style="grid-column:1/3;"><strong>Notes</strong><div>' + esc(o.notes) + '</div></div>';
            }
            html += '</div>';

            if (items.length) {
                html += '<h4 style="margin-bottom:12px;">Order Items</h4>';
                html += '<table style="width:100%;"><thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>';
                items.forEach(function(item) {
                    html += '<tr>';
                    html += '<td style="display:flex;align-items:center;gap:8px;">';
                    if (item.merch_image) {
                        var imgSrc = (item.merch_image.indexOf('assets/') === 0) ? '../' + item.merch_image : '../uploads/' + item.merch_image;
                        html += '<img src="' + imgSrc + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" alt="">';
                    }
                    html += esc(item.merch_name) + '</td>';
                    html += '<td>RM ' + Number(item.price || 0).toFixed(2) + '</td>';
                    html += '<td>' + item.quantity + '</td>';
                    html += '<td><strong>RM ' + Number(item.subtotal || 0).toFixed(2) + '</strong></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }

            html += '<div style="text-align:right;margin-top:16px;padding-top:16px;border-top:1px solid #e5e7eb;">';
            html += '<strong style="font-size:18px;">Total: RM ' + Number(o.total_amount || 0).toFixed(2) + '</strong>';
            html += '</div>';

            document.getElementById('modalTitle').textContent = 'Order #' + (o.order_number || o.order_id);
            body.innerHTML = html;
        })
        .catch(function() {
            body.innerHTML = '<p>Failed to load order details.</p>';
        });
}

function closeModal() {
    var modal = document.getElementById('orderDetailModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function updateStatus(id, status) {
    var fd = new FormData();
    fd.append('api', 'update_status');
    fd.append('id', id);
    fd.append('status', status);
    fetch('orders.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Status updated to ' + status, 'success');
                loadOrders();
            } else {
                showToast(data.message || 'Failed to update status', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function markPaid(id) {
    if (!confirm('Mark this order as paid?')) return;
    var fd = new FormData();
    fd.append('api', 'update_payment');
    fd.append('id', id);
    fd.append('payment_status', 'paid');
    fetch('orders.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment marked as paid', 'success');
                loadOrders();
            } else {
                showToast(data.message || 'Failed to update payment', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function deleteOrder(id) {
    if (!confirm('Delete this order permanently? Stock will be restored.')) return;
    var fd = new FormData();
    fd.append('api', 'delete');
    fd.append('id', id);
    fetch('orders.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Order deleted', 'success');
                loadOrders();
            } else {
                showToast(data.message || 'Failed to delete order', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            searchQuery = e.target.value.toLowerCase();
            renderOrders();
        });
    }
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '-';
    return d.innerHTML;
}

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('orderDetailModal')) closeModal();
});
