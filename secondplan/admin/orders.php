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
            SELECT o.*, u.name as customer_name, u.email as customer_email,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            ORDER BY o.order_id DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'get') {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $items = $pdo->prepare("
            SELECT oi.*, m.name as merch_name, m.image as merch_image
            FROM order_items oi
            LEFT JOIN merchandise m ON oi.merch_id = m.merch_id
            WHERE oi.order_id = ?
        ");
        $items->execute([$id]);

        echo json_encode(['success' => true, 'order' => $order, 'items' => $items->fetchAll()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update_status') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    if (!in_array($status, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $id]);

        $order = $pdo->prepare("SELECT user_id, order_number FROM orders WHERE order_id = ?");
        $order->execute([$id]);
        $row = $order->fetch();
        if ($row && $row['user_id']) {
            createNotification(
                $row['user_id'],
                'order_status_updated',
                'Order Status Updated',
                'Your order #' . $row['order_number'] . ' status has been updated to ' . ucfirst($status) . '.',
                '/user/orders.php'
            );
        }

        echo json_encode(['success' => true, 'message' => 'Order status updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update_payment') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $paymentStatus = $_POST['payment_status'] ?? '';
    $allowed = ['unpaid', 'paid', 'refunded'];

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    if (!in_array($paymentStatus, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment status']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?");
        $stmt->execute([$paymentStatus, $id]);

        $order = $pdo->prepare("SELECT user_id, order_number FROM orders WHERE order_id = ?");
        $order->execute([$id]);
        $row = $order->fetch();
        if ($row && $row['user_id']) {
            createNotification(
                $row['user_id'],
                'payment_updated',
                'Payment Updated',
                'Payment for your order #' . $row['order_number'] . ' has been marked as ' . ucfirst($paymentStatus) . '.',
                '/user/orders.php'
            );
        }

        echo json_encode(['success' => true, 'message' => 'Payment status updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    try {
        // Restore stock for each item before deleting
        $items = $pdo->prepare("SELECT merch_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$id]);
        $orderItems = $items->fetchAll();

        foreach ($orderItems as $item) {
            $pdo->prepare("UPDATE merchandise SET stock = stock + ? WHERE merch_id = ?")->execute([$item['quantity'], $item['merch_id']]);
        }

        $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Order deleted and stock restored']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search orders..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Order Management</h2>
                        <p class="subtitle">Manage merchandise orders</p>
                    </div>
                </div>

                <div class="filter-tabs">
                    <button class="tab active" onclick="filterOrders('all')">All</button>
                    <button class="tab" onclick="filterOrders('pending')">Pending</button>
                    <button class="tab" onclick="filterOrders('processing')">Processing</button>
                    <button class="tab" onclick="filterOrders('shipped')">Shipped</button>
                    <button class="tab" onclick="filterOrders('delivered')">Delivered</button>
                    <button class="tab" onclick="filterOrders('cancelled')">Cancelled</button>
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalOrders">0</div>
                        <div class="mini-stat-label">Total Orders</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="pendingOrders">0</div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="processingOrders">0</div>
                        <div class="mini-stat-label">Processing</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalRevenue">RM 0</div>
                        <div class="mini-stat-label">Total Revenue</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="unpaidOrders">0</div>
                        <div class="mini-stat-label">Unpaid</div>
                    </div>
                </div>

                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTable">
                            <tr>
                                <td colspan="8" class="loading">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="orderDetailModal">
        <div class="modal-content" style="max-width:700px;">
            <div class="modal-header">
                <h3 id="modalTitle">Order Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/orders.js"></script>
</body>
</html>
