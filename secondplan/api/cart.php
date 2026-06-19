<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.cart_id, c.quantity, m.merch_id, m.name, m.price, m.stock, m.image
        FROM cart c
        JOIN merchandise m ON c.merch_id = m.merch_id
        WHERE c.user_id = ? AND m.status = 'active'
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $total = 0;
    foreach ($items as &$item) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total += $item['subtotal'];
    }

    jsonSuccess('OK', ['items' => $items, 'total' => $total]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf'] ?? '')) {
        jsonError('Invalid request', 403);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $merchId = (int)($_POST['merch_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $product = $pdo->prepare("SELECT merch_id, stock FROM merchandise WHERE merch_id = ? AND status = 'active'");
        $product->execute([$merchId]);
        $item = $product->fetch();

        if (!$item) {
            jsonError('Product not found');
        }
        if ($item['stock'] < $quantity) {
            jsonError('Insufficient stock');
        }

        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, merch_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$userId, $merchId, $quantity]);

        jsonSuccess('Added to cart');
    }

    if ($action === 'update') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?")
            ->execute([$quantity, $cartId, $userId]);

        jsonSuccess('Cart updated');
    }

    if ($action === 'remove') {
        $cartId = (int)($_POST['cart_id'] ?? 0);

        $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?")
            ->execute([$cartId, $userId]);

        jsonSuccess('Removed from cart');
    }

    if ($action === 'checkout') {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                SELECT c.*, m.price, m.stock, m.name
                FROM cart c
                JOIN merchandise m ON c.merch_id = m.merch_id
                WHERE c.user_id = ? AND m.status = 'active'
            ");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll();

            if (empty($cartItems)) {
                $pdo->rollBack();
                jsonError('Cart is empty');
            }

            $total = 0;
            foreach ($cartItems as $ci) {
                if ($ci['stock'] < $ci['quantity']) {
                    $pdo->rollBack();
                    jsonError('Insufficient stock for: ' . $ci['name']);
                }
                $total += $ci['price'] * $ci['quantity'];
            }

            $orderNumber = generateOrderNumber();
            $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, shipping_address, notes)
                VALUES (?, ?, ?, 'pending', 'unpaid', ?, ?)
            ")->execute([$userId, $orderNumber, $total, $shippingAddress, $notes]);

            $orderId = $pdo->lastInsertId();

            $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, merch_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $updateStock = $pdo->prepare("UPDATE merchandise SET stock = stock - ? WHERE merch_id = ?");

            foreach ($cartItems as $ci) {
                $subtotal = $ci['price'] * $ci['quantity'];
                $insertItem->execute([$orderId, $ci['merch_id'], $ci['quantity'], $ci['price'], $subtotal]);
                $updateStock->execute([$ci['quantity'], $ci['merch_id']]);
            }

            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

            $pdo->commit();

            createNotification(
                $userId,
                'order_placed',
                'Order Confirmed',
                'Your order ' . $orderNumber . ' has been placed. Total: ' . formatMoney($total),
                '/user/orders.php'
            );

            logActivity($userId, 'order_placed', ['order_number' => $orderNumber, 'total' => $total]);

            $userData = getUserData();
            $emailItems = array_map(function($ci) {
                return [
                    'name' => $ci['name'],
                    'quantity' => $ci['quantity'],
                    'subtotal' => $ci['price'] * $ci['quantity']
                ];
            }, $cartItems);
            sendOrderConfirmedEmail($userData['email'], $userData['name'], $orderNumber, $emailItems, $total);

            jsonSuccess('Order placed', ['order_number' => $orderNumber, 'total' => $total]);

        } catch (\Exception $ex) {
            $pdo->rollBack();
            error_log("Checkout error: " . $ex->getMessage());
            jsonError('Checkout failed. Please try again.', 500);
        }
    }

    jsonError('Invalid action');
}
