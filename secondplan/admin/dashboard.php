<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireRole([ROLE_ADMIN]);

$user = getUserData();

$data = [
    'events'    => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'bookings'  => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'tasks'     => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'expenses'  => $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn(),
];

$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM events WHERE date >= CURDATE()) as upcoming_events,
        (SELECT COUNT(*) FROM tasks WHERE status != 'completed') as pending_tasks,
        (SELECT COUNT(*) FROM expenses WHERE status = 'pending') as pending_expenses
");
$stmt->execute();
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT * FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 5
");
$stmt->execute();
$upcoming_events_list = $stmt->fetchAll();

$revenue = (float)$pdo->query("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE status = 'approved'")->fetchColumn();
$monthly_expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())")->fetchColumn();

$recent_bookings = $pdo->query("SELECT company_name, event_name, event_date, price, status FROM bookings ORDER BY booking_id DESC LIMIT 5")->fetchAll();

$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE status = 'approved' AND DATE_FORMAT(event_date, '%Y-%m') = ?");
    $stmt->execute([$m]);
    $monthlyRevenue[] = [
        'month' => date('M', strtotime($m . '-01')),
        'amount' => (float)$stmt->fetchColumn()
    ];
}

$bookingStatuses = $pdo->query("
    SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$activityLog = $pdo->query("
    SELECT a.*, u.name as user_name
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>

<div class="app">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>Dashboard</h2>
                <div class="subtitle">Overview of system performance</div>
            </div>

            <div class="header-actions">
                <input type="text" id="searchBox" class="search-box" placeholder="Search...">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <div class="quick-actions-row">
                <a href="events.php" class="quick-action-btn">
                    <i class="bi bi-calendar-event"></i>
                    Events
                </a>
                <a href="tasks.php" class="quick-action-btn">
                    <i class="bi bi-check2-circle"></i>
                    Tasks
                </a>
                <a href="expenses.php" class="quick-action-btn">
                    <i class="bi bi-wallet2"></i>
                    Expenses
                </a>
                <a href="bookings.php" class="quick-action-btn">
                    <i class="bi bi-journal-check"></i>
                    Bookings
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-wallet2" style="font-size:24px;color:#38bdf8;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">RM <?= number_format($revenue) ?></div>
                        <div class="stat-subtext">From approved bookings</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-journal-check" style="font-size:24px;color:#22c55e;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Bookings</div>
                        <div class="stat-value"><?= $data['bookings'] ?></div>
                        <div class="stat-subtext">Active bookings</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-list-task" style="font-size:24px;color:#DC2626;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Tasks</div>
                        <div class="stat-value"><?= $stats['pending_tasks'] ?></div>
                        <div class="stat-subtext">Require action</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-cash-stack" style="font-size:24px;color:#ef4444;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Expenses</div>
                        <div class="stat-value">RM <?= number_format($monthly_expenses) ?></div>
                        <div class="stat-subtext">This month</div>
                    </div>
                </div>
            </div>

            <div class="chart-section">
                <div class="section">
                    <h3>Monthly Revenue</h3>
                    <div class="bar-chart">
                        <?php
                        $maxRev = max(array_column($monthlyRevenue, 'amount')) ?: 1;
                        foreach ($monthlyRevenue as $mr):
                            $pct = ($mr['amount'] / $maxRev) * 100;
                        ?>
                        <div class="chart-bar-wrapper">
                            <div class="chart-bar-value">RM <?= number_format($mr['amount'] / 1000, 1) ?>k</div>
                            <div class="chart-bar" style="height: <?= max($pct, 3) ?>%"></div>
                            <div class="chart-bar-label"><?= $mr['month'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section">
                    <h3>Booking Status</h3>
                    <div class="donut-chart-wrapper">
                        <?php
                        $pending = (int)($bookingStatuses['pending'] ?? 0);
                        $approved = (int)($bookingStatuses['approved'] ?? 0);
                        $rejected = (int)($bookingStatuses['rejected'] ?? 0);
                        $total = $pending + $approved + $rejected ?: 1;
                        $pPending = ($pending / $total) * 360;
                        $pApproved = ($approved / $total) * 360;
                        $pRejected = ($rejected / $total) * 360;
                        ?>
                        <div class="donut-chart" style="background: conic-gradient(#f59e0b 0deg <?= $pPending ?>deg, #22c55e <?= $pPending ?>deg <?= $pPending + $pApproved ?>deg, #ef4444 <?= $pPending + $pApproved ?>deg 360deg)"></div>
                        <div class="donut-legend">
                            <div class="donut-legend-item">
                                <div class="donut-legend-dot" style="background:#f59e0b"></div>
                                Pending
                                <span class="donut-legend-count"><?= $pending ?></span>
                            </div>
                            <div class="donut-legend-item">
                                <div class="donut-legend-dot" style="background:#22c55e"></div>
                                Approved
                                <span class="donut-legend-count"><?= $approved ?></span>
                            </div>
                            <div class="donut-legend-item">
                                <div class="donut-legend-dot" style="background:#ef4444"></div>
                                Rejected
                                <span class="donut-legend-count"><?= $rejected ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-2">

                <div class="section">
                    <div class="section-header">
                        <h3>Recent Bookings</h3>
                        <a href="bookings.php" class="link-btn">View all</a>
                    </div>

                    <table>
                        <thead>
                        <tr>
                            <th>Company</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recent_bookings)): ?>
                            <tr><td colspan="5" class="empty-state">No bookings yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $b): ?>
                                <tr>
                                    <td><?= e($b['company_name'] ?? '-') ?></td>
                                    <td><?= e($b['event_name'] ?? '-') ?></td>
                                    <td><?= $b['event_date'] ?? '-' ?></td>
                                    <td><strong>RM <?= number_format((float)($b['price'] ?? 0)) ?></strong></td>
                                    <td><span class="badge status-<?= $b['status'] ?>"><?= strtoupper($b['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h3>Upcoming Events</h3>
                    <?php if (empty($upcoming_events_list)): ?>
                        <div class="empty-state">No upcoming events</div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($upcoming_events_list as $evt): ?>
                                <div style="display:flex;gap:12px;padding:12px;background:#ffffff;border:1px solid var(--border);border-radius:8px;">
                                    <div style="min-width:48px;text-align:center;">
                                        <div style="font-size:12px;color:var(--text-secondary);"><?= formatDate($evt['date'], 'M') ?></div>
                                        <div style="font-size:20px;font-weight:700;"><?= formatDate($evt['date'], 'd') ?></div>
                                    </div>
                                    <div>
                                        <h4 style="font-size:14px;font-weight:600;"><?= e($evt['title']) ?></h4>
                                        <div style="font-size:13px;color:var(--text-secondary);"><?= e($evt['venue'] ?? 'TBA') ?> &bull; <?= $evt['start_time'] ? formatDate($evt['start_time'], 'H:i') : 'TBA' ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="section">
                <h3>Activity Feed</h3>
                <?php if (empty($activityLog)): ?>
                    <div class="empty-state">No recent activity</div>
                <?php else: ?>
                    <div class="activity-feed">
                        <?php foreach ($activityLog as $log):
                            $dotClass = '';
                            if (strpos($log['action'] ?? '', 'approved') !== false) $dotClass = 'green';
                            elseif (strpos($log['action'] ?? '', 'rejected') !== false || strpos($log['action'] ?? '', 'delete') !== false) $dotClass = 'red';
                            elseif (strpos($log['action'] ?? '', 'pending') !== false || strpos($log['action'] ?? '', 'login') !== false) $dotClass = 'orange';
                        ?>
                            <div class="activity-item">
                                <div class="activity-dot <?= $dotClass ?>"></div>
                                <div class="activity-content">
                                    <div class="activity-text"><strong><?= e($log['user_name'] ?? 'System') ?></strong> <?= e($log['action'] ?? '') ?></div>
                                    <div class="activity-time"><?= $log['created_at'] ? formatDateTime($log['created_at'], 'd M Y, h:i A') : '' ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
