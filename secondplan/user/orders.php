<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

$stmt = $pdo->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>My Orders</h2>
                <div class="subtitle">Track your merchandise orders</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <a href="merchandise.php" class="btn-primary">
                    <i class="bi bi-tag btn-icon"></i>
                    Browse Shop
                </a>
            </div>
        </header>

        <main class="content">
            <?php if (empty($orders)): ?>
                <div class="section">
                    <div class="empty-state">
                        No orders yet. <a href="merchandise.php">Browse merchandise</a> to place your first order.
                    </div>
                </div>
            <?php else: ?>
                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr onclick="viewOrder(<?= $o['order_id'] ?>)" style="cursor:pointer;">
                                <td><strong><?= e($o['order_number']) ?></strong></td>
                                <td><?= formatDate($o['created_at']) ?></td>
                                <td><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                <td><strong><?= formatMoney($o['total_amount']) ?></strong></td>
                                <td><span class="badge status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                                <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<div class="modal" id="orderModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Details</h3>
            <button class="close-btn" onclick="closeOrderModal()">&times;</button>
        </div>
        <div class="modal-body" id="orderDetails"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeOrderModal()">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
async function viewOrder(id) {
    try {
        var res = await fetch('<?= APP_URL ?>/api/orders.php?action=get&id=' + id, { credentials: 'same-origin' });
        var json = await res.json();
        if (json.success && json.data) {
            var o = json.data;
            var itemsHtml = (o.items || []).map(function(item) {
                return '<tr><td>' + esc(item.name) + '</td><td>' + item.quantity + '</td><td>RM ' + Number(item.price).toFixed(2) + '</td><td>RM ' + Number(item.subtotal).toFixed(2) + '</td></tr>';
            }).join('');

            document.getElementById('orderDetails').innerHTML =
                '<div style="display:flex;flex-direction:column;gap:16px;">' +
                    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">' +
                        '<div><label style="color:var(--text-secondary);font-size:13px;">Order Number</label><p style="font-weight:600;margin-top:4px;">' + esc(o.order_number) + '</p></div>' +
                        '<div><label style="color:var(--text-secondary);font-size:13px;">Date</label><p style="margin-top:4px;">' + (o.created_at || '-') + '</p></div>' +
                        '<div><label style="color:var(--text-secondary);font-size:13px;">Status</label><p style="margin-top:4px;"><span class="badge status-' + o.status + '">' + (o.status || '').toUpperCase() + '</span></p></div>' +
                        '<div><label style="color:var(--text-secondary);font-size:13px;">Payment</label><p style="margin-top:4px;"><span class="badge status-' + o.payment_status + '">' + (o.payment_status || '').toUpperCase() + '</span></p></div>' +
                    '</div>' +
                    (o.shipping_address ? '<div><label style="color:var(--text-secondary);font-size:13px;">Shipping Address</label><p style="margin-top:4px;">' + esc(o.shipping_address) + '</p></div>' : '') +
                    '<div><label style="color:var(--text-secondary);font-size:13px;">Items</label>' +
                        '<table style="margin-top:8px;"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>' + (itemsHtml || '<tr><td colspan="4">No items</td></tr>') + '</tbody></table>' +
                    '</div>' +
                    '<div style="text-align:right;font-size:18px;font-weight:700;">Total: RM ' + Number(o.total_amount).toFixed(2) + '</div>' +
                '</div>';

            var modal = document.getElementById('orderModal');
            modal.style.display = 'flex';
            modal.classList.add('active');
        }
    } catch (e) {
        showToast('Failed to load order details', 'error');
    }
}

function closeOrderModal() {
    var modal = document.getElementById('orderModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
}

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('orderModal')) closeOrderModal();
});
</script>
</body>
</html>
