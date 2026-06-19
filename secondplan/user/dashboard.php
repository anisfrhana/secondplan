<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();
$userData = getUserData();

$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM bookings WHERE user_id = ?) as total_bookings,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending') as pending_bookings,
        (SELECT COUNT(*) FROM orders WHERE user_id = ?) as total_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = ?) as total_spent
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$stats = $stmt->fetch();

$bookings = $pdo->prepare("
    SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
");
$bookings->execute([$userId]);
$recentBookings = $bookings->fetchAll();

$latestBooking = !empty($recentBookings) ? $recentBookings[0] : null;

$orders = $pdo->prepare("
    SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
");
$orders->execute([$userId]);
$recentOrders = $orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SecondPlan</title>
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
                <h2>Welcome back, <?= e($userData['name']) ?></h2>
                <div class="subtitle">Here's what's happening with your account</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr($userData['name'] ?? 'U', 0, 1)) ?></div>
            </div>
        </header>

        <main class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v3M11 1v3M2 7h12"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Bookings</div>
                        <div class="stat-value"><?= $stats['total_bookings'] ?></div>
                        <div class="stat-subtext">All time</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 2"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Bookings</div>
                        <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
                        <div class="stat-subtext">Awaiting approval</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1h2l1.5 8h8L14 4H4.5"/><circle cx="6" cy="13" r="1"/><circle cx="12" cy="13" r="1"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Orders</div>
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                        <div class="stat-subtext">Merchandise orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 1v5l3 2M2.5 8a5.5 5.5 0 1011 0 5.5 5.5 0 00-11 0z"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Spent</div>
                        <div class="stat-value">RM <?= number_format($stats['total_spent']) ?></div>
                        <div class="stat-subtext">All time</div>
                    </div>
                </div>
            </div>

            <?php if ($latestBooking): ?>
            <div class="booking-timeline">
                <?php
                $bStatus = $latestBooking['status'];
                $step1 = 'completed';
                $step2 = in_array($bStatus, ['pending']) ? 'active' : (in_array($bStatus, ['approved', 'rejected']) ? 'completed' : '');
                $step3class = '';
                if ($bStatus === 'approved') $step3class = 'completed';
                elseif ($bStatus === 'rejected') $step3class = 'active';
                $line1 = in_array($bStatus, ['approved', 'rejected']) ? 'completed' : '';
                $line2 = in_array($bStatus, ['approved', 'rejected']) ? 'completed' : '';
                ?>
                <div class="timeline-step <?= $step1 ?>">
                    <div class="timeline-dot"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 10.5V2M4.5 5.5L8 2l3.5 3.5M2.5 13h11"/></svg></div>
                    <div class="timeline-label">Submitted</div>
                </div>
                <div class="timeline-line <?= $line1 ?>"></div>
                <div class="timeline-step <?= $step2 ?>">
                    <div class="timeline-dot"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 2"/></svg></div>
                    <div class="timeline-label">Under Review</div>
                </div>
                <div class="timeline-line <?= $line2 ?>"></div>
                <div class="timeline-step <?= $step3class ?>">
                    <div class="timeline-dot">
                        <?php if ($bStatus === 'approved'): ?>
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8.5l3.5 3.5 6.5-7"/></svg>
                        <?php elseif ($bStatus === 'rejected'): ?>
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4l8 8M12 4l-8 8"/></svg>
                        <?php else: ?>
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8.5l3.5 3.5 6.5-7"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-label"><?= $bStatus === 'rejected' ? 'Rejected' : 'Approved' ?></div>
                </div>
            </div>
            <div style="text-align:center;margin-bottom:24px;font-size:13px;color:var(--text-secondary);">
                Latest booking: <strong><?= e($latestBooking['event_name']) ?></strong> &mdash;
                <span class="badge status-<?= $latestBooking['status'] ?>"><?= ucfirst($latestBooking['status']) ?></span>
            </div>
            <?php endif; ?>

            <div class="section">
                <h3>Quick Actions</h3>
                <ul class="quick-actions">
                    <li><a href="booking.php">
                        <i class="bi bi-plus-circle btn-icon"></i>
                        New Booking
                    </a></li>
                    <li><a href="merchandise.php">
                        <i class="bi bi-tag btn-icon"></i>
                        Browse Merchandise
                    </a></li>
                    <li><a href="orders.php">
                        <i class="bi bi-box-seam btn-icon"></i>
                        My Orders
                    </a></li>
                </ul>
            </div>

            <div class="grid-2">
                <div class="section">
                    <div class="section-header">
                        <h3>Recent Bookings</h3>
                        <a href="booking.php" class="link-btn">New Booking</a>
                    </div>
                    <?php if (empty($recentBookings)): ?>
                        <div class="empty-state">No bookings yet. <a href="booking.php">Create one</a></div>
                    <?php else: ?>
                        <table>
                            <thead><tr><th>Event</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td><?= e($b['event_name']) ?></td>
                                    <td><?= formatDate($b['event_date']) ?></td>
                                    <td><span class="badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h3>Recent Orders</h3>
                        <a href="merchandise.php" class="link-btn">Browse Shop</a>
                    </div>
                    <?php if (empty($recentOrders)): ?>
                        <div class="empty-state">No orders yet. <a href="merchandise.php">Browse merchandise</a></div>
                    <?php else: ?>
                        <table>
                            <thead><tr><th>Order</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><?= e($o['order_number']) ?></td>
                                    <td><?= formatMoney($o['total_amount']) ?></td>
                                    <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
