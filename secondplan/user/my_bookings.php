<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['api'] ?? '') === 'upload_receipt') {
    header('Content-Type: application/json');
    verify_csrf();

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if (!$bookingId) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    // Verify ownership
    $stmt = $pdo->prepare("SELECT booking_id, invoice_number, payment_status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    if (empty($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a file to upload']);
        exit;
    }

    $upload = uploadFile($_FILES['receipt']);
    if (!$upload['success']) {
        echo json_encode(['success' => false, 'message' => $upload['error']]);
        exit;
    }

    $pdo->prepare("UPDATE bookings SET payment_receipt = ? WHERE booking_id = ?")->execute([$upload['filename'], $bookingId]);

    // Notify admin
    $admins = $pdo->query("
        SELECT u.user_id FROM users u
        JOIN user_roles ur ON ur.user_id = u.user_id
        JOIN roles r ON r.role_id = ur.role_id
        WHERE r.role_name = 'admin'
    ")->fetchAll();
    foreach ($admins as $admin) {
        createNotification(
            $admin['user_id'],
            'receipt_uploaded',
            'Payment Receipt Uploaded',
            getUserData()['name'] . ' uploaded a payment receipt for booking #' . $bookingId,
            '/admin/bookings.php'
        );
    }

    echo json_encode(['success' => true, 'message' => 'Receipt uploaded successfully']);
    exit;
}

$bookings = $pdo->prepare("
    SELECT * FROM bookings
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$bookings->execute([$userId]);
$bookings = $bookings->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - SecondPlan</title>
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
                <h2>My Bookings</h2>
                <div class="subtitle">Track your event booking requests</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'U', 0, 1)) ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="section">
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <p>No bookings yet.</p>
                        <a href="booking.php" class="btn-primary" style="margin-top:16px;display:inline-flex;">Submit a Booking</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Quotation No.</th>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Quotation</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td style="font-family:monospace;font-size:13px;"><?= e($b['quotation_number'] ?? '-') ?></td>
                                <td><?= e($b['event_name']) ?></td>
                                <td><?= formatDate($b['event_date']) ?></td>
                                <td><?= e($b['location'] ?? '-') ?></td>
                                <td><?= $b['quotation_price'] ? 'RM ' . number_format($b['quotation_price'], 2) . '/day' : '-' ?></td>
                                <td><?= $b['price'] ? formatMoney($b['price']) : '-' ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'secondary';
                                    if ($b['status'] === 'approved') $statusClass = 'success';
                                    elseif ($b['status'] === 'pending') $statusClass = 'warning';
                                    elseif ($b['status'] === 'rejected') $statusClass = 'danger';
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= strtoupper($b['status']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($b['invoice_number'])): ?>
                                        <?php if ($b['payment_status'] === 'paid'): ?>
                                            <span class="badge success">PAID</span>
                                        <?php else: ?>
                                            <span class="badge warning">UNPAID</span>
                                            <?php if (!empty($b['payment_due_date'])): ?>
                                                <?php $isOverdue = strtotime($b['payment_due_date']) < time(); ?>
                                                <div style="font-size:11px;color:<?= $isOverdue ? '#ef4444' : '#6b7280' ?>;margin-top:2px;">Due: <?= formatDate($b['payment_due_date']) ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:13px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <?php if (!empty($b['invoice_number'])): ?>
                                            <a href="invoice.php?id=<?= $b['booking_id'] ?>" class="btn-primary btn-small" target="_blank" style="text-decoration:none;">
                                                <i class="bi bi-receipt btn-icon"></i> Invoice
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($b['invoice_number']) && $b['payment_status'] !== 'paid' && empty($b['payment_receipt'])): ?>
                                            <button class="btn-secondary btn-small" onclick="openReceiptModal(<?= $b['booking_id'] ?>)">
                                                <i class="bi bi-upload btn-icon"></i> Upload Receipt
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($b['payment_receipt'])): ?>
                                            <span class="badge success" style="font-size:11px;"><i class="bi bi-check-circle"></i> Receipt Uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Receipt Upload Modal -->
<div class="modal" id="receiptModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:24px;max-width:420px;width:90%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;">Upload Payment Receipt</h3>
            <button onclick="closeReceiptModal()" style="background:none;border:none;color:var(--text);font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form id="receiptForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="api" value="upload_receipt">
            <input type="hidden" name="booking_id" id="receiptBookingId" value="">
            <div class="form-group">
                <label>Receipt File (JPG/PNG/PDF)</label>
                <input type="file" name="receipt" id="receiptFile" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <button type="button" class="btn-secondary" onclick="closeReceiptModal()">Cancel</button>
                <button type="submit" class="btn-primary"><i class="bi bi-upload btn-icon"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
function openReceiptModal(bookingId) {
    document.getElementById('receiptBookingId').value = bookingId;
    document.getElementById('receiptFile').value = '';
    var modal = document.getElementById('receiptModal');
    modal.style.display = 'flex';
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

document.getElementById('receiptForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('my_bookings.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                closeReceiptModal();
                location.reload();
            } else {
                alert(data.message || 'Upload failed');
            }
        })
        .catch(function() { alert('Upload failed'); });
});

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('receiptModal')) closeReceiptModal();
});
</script>
</body>
</html>
