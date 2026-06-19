<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');

$sql = "SELECT * FROM merchandise WHERE status = 'active'";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM merchandise WHERE status = 'active' AND category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title>Merchandise - SecondPlan</title>
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
                <h2>Merchandise</h2>
                <div class="subtitle">Browse and purchase band merchandise</div>
            </div>
            <div class="header-actions">
                <form method="GET" style="margin:0;">
                    <input class="search-box" type="text" name="search" placeholder="Search products..." value="<?= e($search) ?>">
                </form>
                <a href="cart.php" class="btn-secondary" style="white-space:nowrap;">
                    <i class="bi bi-cart btn-icon"></i>
                    View Cart
                </a>
            </div>
        </header>

        <main class="content">
            <?php if (!empty($categories)): ?>
            <div class="filters">
                <a class="filter-btn <?= empty($category) ? 'active' : '' ?>" href="merchandise.php">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a class="filter-btn <?= $category === $cat ? 'active' : '' ?>" href="merchandise.php?category=<?= urlencode($cat) ?>"><?= e(ucfirst($cat)) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="section">
                    <div class="empty-state">No merchandise available.</div>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <?php $imgSrc = (strpos($product['image'], 'assets/') === 0) ? APP_URL . '/' . $product['image'] : APP_URL . '/uploads/' . $product['image']; ?>
                                    <img src="<?= e($imgSrc) ?>" alt="<?= e($product['name']) ?>">
                                <?php else: ?>
                                    <svg style="width:48px;height:48px;opacity:0.3;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="12" height="12" rx="2"/><circle cx="5.5" cy="5.5" r="1.5"/><path d="M14 11l-3-3-4 4-2-2-3 3"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?= e($product['name']) ?></div>
                                <div class="product-price"><?= formatMoney($product['price']) ?></div>
                                <div class="product-stock"><?= $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock' ?></div>
                                <form method="POST" action="" data-action="<?= APP_URL ?>/api/cart.php" class="add-cart-form">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="merch_id" value="<?= $product['merch_id'] ?>">
                                    <button type="submit" class="btn-add-cart" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-plus btn-icon"></i>
                                        <?= $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
document.querySelectorAll('.add-cart-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('.btn-add-cart');
        var data = new FormData(form);
        data.append('csrf', getCSRF());
        fetch(form.dataset.action, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function(res) {
                if (!res.ok) {
                    showToast('Session expired. Please login again.', 'error');
                    window.location.href = '../auth/login.php';
                    throw new Error('redirect');
                }
                return res.json();
            })
            .then(function(json) {
                if (json.success) {
                    btn.classList.add('added');
                    btn.innerHTML = '<i class="bi bi-check-circle btn-icon"></i> Added';
                    showToast('Added to cart', 'success');
                    setTimeout(function() {
                        btn.classList.remove('added');
                        btn.innerHTML = '<i class="bi bi-cart-plus btn-icon"></i> Add to Cart';
                    }, 1500);
                } else {
                    showToast(json.message || 'Failed to add to cart', 'error');
                }
            })
            .catch(function(err) {
                if (err.message !== 'redirect') {
                    showToast('Failed to add to cart', 'error');
                }
            });
    });
});
</script>
</body>
</html>
