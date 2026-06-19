<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

$stmt = $pdo->prepare("
    SELECT c.cart_id, c.quantity, m.merch_id, m.name, m.price, m.stock, m.image
    FROM cart c
    JOIN merchandise m ON c.merch_id = m.merch_id
    WHERE c.user_id = ? AND m.status = 'active'
    ORDER BY c.added_at DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = 0;
foreach ($cartItems as &$item) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title>Cart - SecondPlan</title>
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
                <h2>Shopping Cart</h2>
                <div class="subtitle"><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <a href="merchandise.php" class="btn-secondary">
                    <i class="bi bi-tag btn-icon"></i>
                    Continue Shopping
                </a>
            </div>
        </header>

        <main class="content">
            <?php if (empty($cartItems)): ?>
                <div class="section">
                    <div class="empty-state">
                        <svg style="width:48px;height:48px;margin:0 auto 16px;display:block;opacity:0.3;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 1h2l1.5 8h8L14 4H4.5"/><circle cx="6" cy="13" r="1"/><circle cx="12" cy="13" r="1"/></svg>
                        Your cart is empty. <a href="merchandise.php">Browse merchandise</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="section">
                    <h3>Cart Items</h3>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>">
                            <?php if ($item['image']): ?>
                                <?php $imgSrc = (strpos($item['image'], 'assets/') === 0) ? APP_URL . '/' . $item['image'] : APP_URL . '/uploads/' . $item['image']; ?>
                                <img class="cart-img" src="<?= e($imgSrc) ?>" alt="<?= e($item['name']) ?>">
                            <?php else: ?>
                                <div class="cart-img" style="display:flex;align-items:center;justify-content:center;">
                                    <svg style="width:24px;height:24px;opacity:0.3;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="12" height="12" rx="2"/></svg>
                                </div>
                            <?php endif; ?>
                            <div class="cart-details">
                                <div class="cart-name"><?= e($item['name']) ?></div>
                                <div class="cart-price"><?= formatMoney($item['price']) ?> each</div>
                            </div>
                            <div class="cart-qty">
                                <input type="number" min="1" max="<?= $item['stock'] ?>" value="<?= $item['quantity'] ?>"
                                       onchange="updateQty(<?= $item['cart_id'] ?>, this.value)">
                            </div>
                            <div class="cart-subtotal"><?= formatMoney($item['subtotal']) ?></div>
                            <button class="btn-remove" onclick="removeItem(<?= $item['cart_id'] ?>)" title="Remove">
                                <svg style="width:14px;height:14px;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4l8 8M12 4l-8 8"/></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-total">
                        <span>Total</span>
                        <span id="cartTotal"><?= formatMoney($total) ?></span>
                    </div>
                </div>

                <div class="section" style="max-width:600px;">
                    <h3>Checkout</h3>
                    <div class="checkout-form" style="margin-top:12px;">
                        <label>Shipping Address</label>
                        <textarea id="shippingAddress" rows="3" placeholder="Full shipping address"></textarea>

                        <label>Notes (optional)</label>
                        <input type="text" id="orderNotes" placeholder="Any special instructions">

                        <button class="btn-primary" onclick="checkout()" style="margin-top:4px;">
                            <i class="bi bi-check-circle btn-icon"></i>
                            Place Order - <?= formatMoney($total) ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
var API = '<?= APP_URL ?>/api/cart.php';

async function postCart(data) {
    var form = new FormData();
    for (var k in data) form.append(k, data[k]);
    form.append('csrf', getCSRF());
    var res = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin' });
    return await res.json();
}

async function updateQty(cartId, qty) {
    var json = await postCart({ action: 'update', cart_id: cartId, quantity: qty });
    if (json.success) location.reload();
    else showToast(json.message || 'Failed to update', 'error');
}

async function removeItem(cartId) {
    var json = await postCart({ action: 'remove', cart_id: cartId });
    if (json.success) {
        showToast('Item removed', 'success');
        location.reload();
    } else {
        showToast(json.message || 'Failed to remove', 'error');
    }
}

async function checkout() {
    var address = document.getElementById('shippingAddress').value.trim();
    if (!address) {
        showToast('Shipping address is required', 'error');
        return;
    }

    var json = await postCart({
        action: 'checkout',
        shipping_address: address,
        notes: document.getElementById('orderNotes').value
    });

    if (json.success) {
        showToast('Order placed! Order: ' + json.data.order_number, 'success');
        setTimeout(function() { location.href = 'dashboard.php'; }, 1500);
    } else {
        showToast(json.message || 'Checkout failed', 'error');
    }
}
</script>
</body>
</html>
