<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireRole([ROLE_ADMIN]);

$user = getUserData();

$range = $_GET['range'] ?? 'this_month';
$startDate = '';
$endDate = date('Y-m-d');

switch ($range) {
    case 'today':
        $startDate = date('Y-m-d');
        break;
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        break;
    case 'this_month':
        $startDate = date('Y-m-01');
        break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        break;
    case 'all_time':
        $startDate = '2000-01-01';
        break;
    case 'custom':
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-01');
}

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN price ELSE 0 END), 0) as paid_revenue,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN price ELSE 0 END), 0) as total_approved,
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN payment_status = 'unpaid' AND status = 'approved' THEN 1 ELSE 0 END) as unpaid_count
    FROM bookings
    WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$startDate, $endDate]);
$bookingStats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_expenses
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$expenseStats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT category, SUM(amount) as total
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute([$startDate, $endDate]);
$expensesByCategory = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(o.total_amount), 0) as total_sales,
        COUNT(DISTINCT o.order_id) as order_count,
        COALESCE(SUM(oi.quantity), 0) as items_sold
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    AND o.payment_status = 'paid'
");
$stmt->execute([$startDate, $endDate]);
$merchStats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT m.name, m.sku, SUM(oi.quantity) as qty_sold, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    JOIN merchandise m ON m.merch_id = oi.merch_id
    WHERE o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    AND o.payment_status = 'paid'
    GROUP BY m.merch_id
    ORDER BY qty_sold DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll();

$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $monthlyData[$m] = ['month' => date('M Y', strtotime($m . '-01')), 'revenue' => 0, 'expenses' => 0];
}

$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(price) as total
    FROM bookings WHERE payment_status = 'paid'
    GROUP BY month
");
foreach ($stmt->fetchAll() as $row) {
    if (isset($monthlyData[$row['month']])) {
        $monthlyData[$row['month']]['revenue'] = (float)$row['total'];
    }
}

$stmt = $pdo->query("
    SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total
    FROM expenses
    GROUP BY month
");
foreach ($stmt->fetchAll() as $row) {
    if (isset($monthlyData[$row['month']])) {
        $monthlyData[$row['month']]['expenses'] = (float)$row['total'];
    }
}

$monthlyData = array_values($monthlyData);

$netProfit = $bookingStats['paid_revenue'] + $merchStats['total_sales'] - $expenseStats['total_expenses'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .reports-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .date-filters { display: flex; gap: 8px; flex-wrap: wrap; }
        .date-filters a { padding: 8px 16px; background: var(--panel); border: 1px solid var(--border); border-radius: 8px; color: var(--text); text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .date-filters a:hover, .date-filters a.active { background: var(--accent); border-color: var(--accent); color: white; }
        .custom-range { display: flex; gap: 8px; align-items: center; }
        .custom-range input { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; }
        .custom-range button { padding: 8px 16px; background: var(--accent); border: none; border-radius: 8px; color: white; cursor: pointer; font-size: 13px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
        .stat-card.accent { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); border: none; }
        .stat-card.accent * { color: white !important; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--bg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .stat-card.accent .stat-icon { background: rgba(255,255,255,0.2); }
        .stat-card .stat-icon i { font-size: 24px; color: var(--accent); }
        .stat-card.accent .stat-icon i { color: white; }
        .stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--text); }
        .stat-card .stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }
        .report-section { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .report-section h3 { font-size: 16px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .report-section h3 i { color: var(--accent); }
        .chart-container { height: 300px; position: relative; }
        .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 250px; padding: 20px 0; border-bottom: 1px solid var(--border); gap: 8px; }
        .bar-group { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .bar-wrapper { display: flex; gap: 4px; align-items: flex-end; height: 200px; }
        .bar { width: 20px; border-radius: 4px 4px 0 0; transition: height 0.3s; min-height: 4px; }
        .bar.revenue { background: var(--accent); }
        .bar.expenses { background: var(--warning); }
        .bar-label { font-size: 11px; color: var(--text-secondary); text-align: center; }
        .chart-legend { display: flex; gap: 20px; justify-content: center; margin-top: 16px; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; }
        .legend-dot.revenue { background: var(--accent); }
        .legend-dot.expenses { background: var(--warning); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; font-size: 12px; text-transform: uppercase; color: var(--text-secondary); border-bottom: 1px solid var(--border); }
        .data-table td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-table tr:last-child td { border-bottom: none; }
        .progress-bar { height: 8px; background: var(--bg); border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .donut-chart { width: 200px; height: 200px; border-radius: 50%; margin: 0 auto 20px; position: relative; }
        .donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; background: var(--panel); width: 100px; height: 100px; border-radius: 50%; display: flex; flex-direction: column; justify-content: center; }
        .donut-center .value { font-size: 24px; font-weight: 700; color: var(--text); }
        .donut-center .label { font-size: 11px; color: var(--text-secondary); }
        .status-list { display: flex; flex-direction: column; gap: 12px; }
        .status-item { display: flex; justify-content: space-between; align-items: center; }
        .status-item .name { display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .status-item .dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-item .count { font-weight: 600; }
        .export-btn { padding: 10px 20px; background: var(--panel); border: 1px solid var(--border); border-radius: 8px; color: var(--text); cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
        .export-btn:hover { border-color: var(--accent); color: var(--accent); }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h1>Reports & Analytics</h1>
            <div class="header-right">
                <button class="export-btn" onclick="exportData()"><i class="bi bi-download"></i> Export CSV</button>
                <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
            </div>
        </header>

        <div class="content">
            <div class="reports-header">
                <div class="date-filters">
                    <a href="?range=today" class="<?= $range === 'today' ? 'active' : '' ?>">Today</a>
                    <a href="?range=this_week" class="<?= $range === 'this_week' ? 'active' : '' ?>">This Week</a>
                    <a href="?range=this_month" class="<?= $range === 'this_month' ? 'active' : '' ?>">This Month</a>
                    <a href="?range=last_month" class="<?= $range === 'last_month' ? 'active' : '' ?>">Last Month</a>
                    <a href="?range=this_year" class="<?= $range === 'this_year' ? 'active' : '' ?>">This Year</a>
                    <a href="?range=all_time" class="<?= $range === 'all_time' ? 'active' : '' ?>">All Time</a>
                </div>
                <form class="custom-range" method="GET">
                    <input type="hidden" name="range" value="custom">
                    <input type="date" name="start" value="<?= e($startDate) ?>">
                    <span>to</span>
                    <input type="date" name="end" value="<?= e($endDate) ?>">
                    <button type="submit">Apply</button>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card accent">
                    <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-value"><?= formatMoney($bookingStats['paid_revenue']) ?></div>
                    <div class="stat-label">Booking Revenue (Paid)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                    <div class="stat-value"><?= formatMoney($merchStats['total_sales']) ?></div>
                    <div class="stat-label">Merchandise Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                    <div class="stat-value"><?= formatMoney($expenseStats['total_expenses']) ?></div>
                    <div class="stat-label">Total Expenses</div>
                </div>
                <div class="stat-card <?= $netProfit >= 0 ? '' : 'accent' ?>">
                    <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-value"><?= formatMoney($netProfit) ?></div>
                    <div class="stat-label">Net Profit</div>
                </div>
            </div>

            <div class="report-section">
                <h3><i class="bi bi-bar-chart"></i> Revenue vs Expenses (Last 12 Months)</h3>
                <div class="chart-container">
                    <div class="bar-chart" id="monthlyChart">
                        <?php
                        $maxVal = max(array_merge(array_column($monthlyData, 'revenue'), array_column($monthlyData, 'expenses'), [1]));
                        foreach ($monthlyData as $m):
                            $revHeight = $maxVal > 0 ? ($m['revenue'] / $maxVal) * 180 : 0;
                            $expHeight = $maxVal > 0 ? ($m['expenses'] / $maxVal) * 180 : 0;
                        ?>
                        <div class="bar-group">
                            <div class="bar-wrapper">
                                <div class="bar revenue" style="height: <?= $revHeight ?>px" title="Revenue: <?= formatMoney($m['revenue']) ?>"></div>
                                <div class="bar expenses" style="height: <?= $expHeight ?>px" title="Expenses: <?= formatMoney($m['expenses']) ?>"></div>
                            </div>
                            <div class="bar-label"><?= substr($m['month'], 0, 3) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item"><div class="legend-dot revenue"></div> Revenue</div>
                        <div class="legend-item"><div class="legend-dot expenses"></div> Expenses</div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="report-section">
                    <h3><i class="bi bi-calendar-check"></i> Booking Status</h3>
                    <?php
                    $total = $bookingStats['total_bookings'] ?: 1;
                    $approved = $bookingStats['approved_count'] ?? 0;
                    $pending = $bookingStats['pending_count'] ?? 0;
                    $rejected = $bookingStats['rejected_count'] ?? 0;
                    $completed = $bookingStats['completed_count'] ?? 0;

                    $colors = ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6'];
                    $segments = [$approved, $pending, $rejected, $completed];
                    $gradient = '';
                    $currentAngle = 0;
                    foreach ($segments as $i => $val) {
                        $angle = ($val / $total) * 360;
                        $nextAngle = $currentAngle + $angle;
                        $gradient .= $colors[$i] . ' ' . $currentAngle . 'deg ' . $nextAngle . 'deg, ';
                        $currentAngle = $nextAngle;
                    }
                    $gradient = rtrim($gradient, ', ');
                    ?>
                    <div class="donut-chart" style="background: conic-gradient(<?= $gradient ?>);">
                        <div class="donut-center">
                            <div class="value"><?= $bookingStats['total_bookings'] ?></div>
                            <div class="label">Total</div>
                        </div>
                    </div>
                    <div class="status-list">
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#22c55e"></div> Approved</div>
                            <div class="count"><?= $approved ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#f59e0b"></div> Pending</div>
                            <div class="count"><?= $pending ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#ef4444"></div> Rejected</div>
                            <div class="count"><?= $rejected ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#3b82f6"></div> Completed</div>
                            <div class="count"><?= $completed ?></div>
                        </div>
                    </div>
                </div>

                <div class="report-section">
                    <h3><i class="bi bi-wallet2"></i> Expenses by Category</h3>
                    <?php if (empty($expensesByCategory)): ?>
                        <p style="color:var(--text-secondary);text-align:center;padding:40px 0;">No expenses in this period</p>
                    <?php else: ?>
                        <?php $maxExp = $expensesByCategory[0]['total'] ?? 1; ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th style="width:40%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expensesByCategory as $exp): ?>
                                <tr>
                                    <td><?= e($exp['category']) ?></td>
                                    <td><?= formatMoney($exp['total']) ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width:<?= ($exp['total'] / $maxExp) * 100 ?>%;background:var(--warning);"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="report-section">
                <h3><i class="bi bi-bag"></i> Top Selling Products</h3>
                <?php if (empty($topProducts)): ?>
                    <p style="color:var(--text-secondary);text-align:center;padding:40px 0;">No merchandise sales in this period</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Qty Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $prod): ?>
                            <tr>
                                <td><strong><?= e($prod['name']) ?></strong></td>
                                <td><?= e($prod['sku']) ?></td>
                                <td><?= $prod['qty_sold'] ?></td>
                                <td><?= formatMoney($prod['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <div class="report-section">
                    <h3><i class="bi bi-credit-card"></i> Payment Summary</h3>
                    <div class="status-list" style="gap:16px;">
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#22c55e"></div> Paid Bookings</div>
                            <div class="count"><?= $bookingStats['paid_count'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#ef4444"></div> Unpaid Bookings</div>
                            <div class="count"><?= $bookingStats['unpaid_count'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#3b82f6"></div> Outstanding Amount</div>
                            <div class="count"><?= formatMoney($bookingStats['total_approved'] - $bookingStats['paid_revenue']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="report-section">
                    <h3><i class="bi bi-box-seam"></i> Merchandise Summary</h3>
                    <div class="status-list" style="gap:16px;">
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:var(--accent)"></div> Total Orders</div>
                            <div class="count"><?= $merchStats['order_count'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#22c55e"></div> Items Sold</div>
                            <div class="count"><?= $merchStats['items_sold'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="name"><div class="dot" style="background:#3b82f6"></div> Avg Order Value</div>
                            <div class="count"><?= $merchStats['order_count'] > 0 ? formatMoney($merchStats['total_sales'] / $merchStats['order_count']) : 'RM 0.00' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/common.js"></script>
    <script>
    function exportData() {
        var data = [
            ['SecondPlan Reports'],
            ['Period: <?= e($startDate) ?> to <?= e($endDate) ?>'],
            [''],
            ['Financial Summary'],
            ['Metric', 'Amount'],
            ['Booking Revenue (Paid)', '<?= $bookingStats['paid_revenue'] ?>'],
            ['Merchandise Sales', '<?= $merchStats['total_sales'] ?>'],
            ['Total Expenses', '<?= $expenseStats['total_expenses'] ?>'],
            ['Net Profit', '<?= $netProfit ?>'],
            [''],
            ['Booking Statistics'],
            ['Status', 'Count'],
            ['Total Bookings', '<?= $bookingStats['total_bookings'] ?>'],
            ['Approved', '<?= $bookingStats['approved_count'] ?>'],
            ['Pending', '<?= $bookingStats['pending_count'] ?>'],
            ['Rejected', '<?= $bookingStats['rejected_count'] ?>'],
            ['Completed', '<?= $bookingStats['completed_count'] ?>'],
            [''],
            ['Payment Status'],
            ['Paid Bookings', '<?= $bookingStats['paid_count'] ?>'],
            ['Unpaid Bookings', '<?= $bookingStats['unpaid_count'] ?>'],
            [''],
            ['Merchandise'],
            ['Total Orders', '<?= $merchStats['order_count'] ?>'],
            ['Items Sold', '<?= $merchStats['items_sold'] ?>'],
            ['Total Sales', '<?= $merchStats['total_sales'] ?>']
        ];

        var csv = data.map(function(row) { return row.join(','); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'SecondPlan_Report_<?= date('Y-m-d') ?>.csv';
        a.click();
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
