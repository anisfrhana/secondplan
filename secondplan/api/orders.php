<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

header('Content-Type: application/json');

$userId = getUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'get' && isset($_GET['id'])) {
    try {
        $orderId = (int)$_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT oi.*, m.name
            FROM order_items oi
            JOIN merchandise m ON oi.merch_id = m.merch_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $order]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load order']);
    }
    exit;
}

if ($action === 'list') {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load orders']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
