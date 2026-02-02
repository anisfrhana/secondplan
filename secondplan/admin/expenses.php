<?php
$title = 'Expenses · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['admin']);
verify_csrf();

header('Content-Type: application/json');

// Handle API requests
if (isset($_GET['api'])) {
    // -------- LIST EXPENSES ----------
    if ($_GET['api'] === 'list') {
        $stmt = $pdo->query("SELECT * FROM event_expenses ORDER BY date DESC");
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $expenses]);
        exit;

    // -------- SAVE EXPENSE ----------
    } elseif ($_GET['api'] === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $date = trim($_POST['date'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $vendor = trim($_POST['vendor'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $status = trim($_POST['status'] ?? 'pending');

            if (!$date || !$category || !$description || !$vendor || $amount <= 0) {
                throw new Exception('All fields are required and amount must be greater than 0.');
            }

            $stmt = $pdo->prepare("INSERT INTO event_expenses (date, category, description, vendor, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$date, $category, $description, $vendor, $amount, $status]);

            echo json_encode(['success' => true, 'message' => 'Expense added']);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid API request']);
        exit;
    }
}

// Handle JSON POST for DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!empty($input['action']) && $input['action'] === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM event_expenses WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
}

// Fallback: if accessed directly in browser, show basic HTML table
$stmt = $pdo->query("SELECT * FROM event_expenses ORDER BY date DESC");
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Expenses</h1>";
echo "<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>ID</th><th>Date</th><th>Category</th><th>Description</th><th>Vendor</th><th>Amount</th><th>Status</th></tr>";

foreach ($expenses as $exp) {
    echo "<tr>
        <td>{$exp['id']}</td>
        <td>{$exp['date']}</td>
        <td>{$exp['category']}</td>
        <td>{$exp['description']}</td>
        <td>{$exp['vendor']}</td>
        <td>RM {$exp['amount']}</td>
        <td>{$exp['status']}</td>
    </tr>";
}
echo "</table>";

exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon">⚡</div>
                <h1>SecondPlan</h1>
            </div>
            <nav class="nav">
                <a class="nav-item" href="dashboard.html">📊 <span>Dashboard</span></a>
                <a class="nav-item" href="bookings.html">📅 <span>Bookings</span></a>
                <a class="nav-item" href="events.html">🎤 <span>Events</span></a>
                <a class="nav-item" href="tasks.html">✓ <span>Tasks</span></a>
                <a class="nav-item active" href="expenses.html">💰 <span>Expenses</span></a>
                <a class="nav-item" href="merchandise.html">📦 <span>Merchandise</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="header">
                <input type="text" placeholder="Search expenses..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn">🔔</button>
                    <div class="user-avatar">👤</div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Expense Tracking</h2>
                        <p class="subtitle">Monitor and manage all expenses</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn-secondary" onclick="exportExpenses()">
                            📥 Export
                        </button>
                        <button class="btn-primary" onclick="window.location.href='add_expense.html'">
                            + Add Expense
                        </button>
                    </div>
                </div>

                <!-- Expense Summary -->
                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalExpenses">RM 0</div>
                        <div class="mini-stat-label">Total Expenses</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="equipmentExpenses">RM 0</div>
                        <div class="mini-stat-label">Equipment</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="marketingExpenses">RM 0</div>
                        <div class="mini-stat-label">Marketing</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="pendingPayments">RM 0</div>
                        <div class="mini-stat-label">Pending Payments</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-row">
                        <select id="categoryFilter" onchange="filterExpenses()">
                            <option value="">All Categories</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Venue">Venue</option>
                            <option value="Other">Other</option>
                        </select>
                        <select id="statusFilter" onchange="filterExpenses()">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" id="startDate" onchange="filterExpenses()">
                        <input type="date" id="endDate" onchange="filterExpenses()">
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Receipt</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expensesTable">
                            <tr>
                                <td colspan="8" class="loading">Loading expenses...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Upload Receipt -->
                <div class="section">
                    <h3>Quick Upload Receipt</h3>
                    <div class="upload-area" onclick="document.getElementById('receiptFile').click()">
                        <input type="file" id="receiptFile" accept="image/*,application/pdf" style="display: none;" onchange="handleFileUpload(event)">
                        <div class="upload-icon">📎</div>
                        <p>Click to upload or drag and drop</p>
                        <small>PDF, JPG, PNG (Max 5MB)</small>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/expenses.js"></script>
</body>
</html>