<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
requireRole([ROLE_ADMIN]);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['api'] ?? $_POST['api'] ?? null;

if ($method === 'GET' && $action === 'list') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT
                booking_id AS id,
                company_name,
                event_name,
                event_date,
                location,
                price,
                status,
                quotation_number,
                invoice_number,
                payment_status,
                payment_due_date,
                paid_at,
                quotation_price,
                user_id
            FROM bookings
            ORDER BY booking_id DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'approve') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $price = isset($_POST['price']) ? (float)$_POST['price'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    $invoiceNumber = generateInvoiceNumber();

    $stmt = $pdo->prepare("
        UPDATE bookings
        SET status = 'approved',
            price = ?,
            invoice_number = ?,
            payment_status = 'unpaid',
            payment_due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY),
            approved_by = ?,
            approved_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$price, $invoiceNumber, $_SESSION['user_id'], $id]);

    $booking = $pdo->prepare("
        SELECT b.user_id, b.event_name, b.event_date, b.invoice_number, b.price, b.payment_due_date,
               u.email, u.name
        FROM bookings b
        LEFT JOIN users u ON u.user_id = b.user_id
        WHERE b.booking_id = ?
    ");
    $booking->execute([$id]);
    $row = $booking->fetch();
    if ($row && $row['user_id']) {
        createNotification(
            $row['user_id'],
            'booking_approved',
            'Booking Approved',
            'Your booking for "' . $row['event_name'] . '" has been approved.' . ($price ? ' Price: RM ' . number_format($price, 2) : ''),
            '/user/my_bookings.php'
        );
        if ($row['email']) {
            $dueDate = date('d M Y', strtotime('+14 days'));
            sendBookingApprovedEmail($row['email'], $row['name'], $invoiceNumber, $row['event_name'], $row['event_date'], $price, $dueDate);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Booking approved', 'invoice_number' => $invoiceNumber]);
    exit;
}

if ($method === 'POST' && $action === 'reject') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE booking_id = ?");
    $stmt->execute([$id]);

    $booking = $pdo->prepare("
        SELECT b.user_id, b.event_name, b.event_date, b.quotation_number, u.email, u.name
        FROM bookings b
        LEFT JOIN users u ON u.user_id = b.user_id
        WHERE b.booking_id = ?
    ");
    $booking->execute([$id]);
    $row = $booking->fetch();
    if ($row && $row['user_id']) {
        createNotification(
            $row['user_id'],
            'booking_rejected',
            'Booking Rejected',
            'Your booking for "' . $row['event_name'] . '" has been rejected.',
            '/user/my_bookings.php'
        );
        if ($row['email']) {
            sendBookingRejectedEmail($row['email'], $row['name'], $row['quotation_number'], $row['event_name'], $row['event_date']);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Booking rejected']);
    exit;
}

if ($method === 'POST' && $action === 'mark_paid') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid', paid_at = NOW() WHERE booking_id = ?");
    $stmt->execute([$id]);

    $booking = $pdo->prepare("
        SELECT b.user_id, b.event_name, b.event_date, b.invoice_number, b.price, b.paid_at,
               u.email, u.name
        FROM bookings b
        LEFT JOIN users u ON u.user_id = b.user_id
        WHERE b.booking_id = ?
    ");
    $booking->execute([$id]);
    $row = $booking->fetch();
    if ($row && $row['user_id']) {
        createNotification(
            $row['user_id'],
            'payment_confirmed',
            'Payment Confirmed',
            'Payment for your booking "' . $row['event_name'] . '" has been confirmed.',
            '/user/my_bookings.php'
        );
        if ($row['email']) {
            $paidDate = date('d M Y');
            sendPaymentConfirmedEmail($row['email'], $row['name'], $row['invoice_number'], $row['event_name'], $row['event_date'], $row['price'], $paidDate);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Payment marked as paid']);
    exit;
}

if ($method === 'POST' && $action === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?")->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Booking deleted']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search bookings..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Event Bookings</h2>
                        <p class="subtitle">Manage all event booking requests</p>
                    </div>
                </div>

                <div class="filter-tabs">
                    <button class="tab active" onclick="filterBookings('all')">All</button>
                    <button class="tab" onclick="filterBookings('pending')">Pending</button>
                    <button class="tab" onclick="filterBookings('approved')">Approved</button>
                    <button class="tab" onclick="filterBookings('rejected')">Rejected</button>
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalBookings">0</div>
                        <div class="mini-stat-label">Total Bookings</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="pendingBookings">0</div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="approvedBookings">0</div>
                        <div class="mini-stat-label">Approved</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="unpaidBookings">0</div>
                        <div class="mini-stat-label">Unpaid</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalRevenue">RM 0</div>
                        <div class="mini-stat-label">Total Value</div>
                    </div>
                </div>

                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTable">
                            <tr>
                                <td colspan="9" class="loading">Loading bookings...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="priceModal">
        <div class="modal-content" style="max-width:400px;">
            <div class="modal-header">
                <h3>Set Booking Price</h3>
                <button class="close-btn" onclick="closePriceModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Price (RM)</label>
                    <input type="number" id="approvePrice" step="0.01" min="0" placeholder="Enter price for this booking">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closePriceModal()">Cancel</button>
                <button class="btn-success" onclick="confirmApprove()">Approve & Generate Invoice</button>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/bookings.js"></script>
</body>
</html>
