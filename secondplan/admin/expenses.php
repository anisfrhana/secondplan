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
                e.expense_id AS id,
                e.category,
                e.amount,
                e.expense_date,
                e.vendor,
                e.reference,
                e.description,
                e.notes,
                e.receipt,
                e.status,
                e.event_id,
                e.created_at,
                u1.name AS submitted_by_name,
                u2.name AS approved_by_name,
                ev.title AS event_title
            FROM expenses e
            LEFT JOIN users u1 ON e.submitted_by = u1.user_id
            LEFT JOIN users u2 ON e.approved_by = u2.user_id
            LEFT JOIN events ev ON e.event_id = ev.event_id
            ORDER BY e.expense_id DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $ex) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'get') {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("
            SELECT
                e.*,
                u1.name AS submitted_by_name,
                u2.name AS approved_by_name,
                ev.title AS event_title
            FROM expenses e
            LEFT JOIN users u1 ON e.submitted_by = u1.user_id
            LEFT JOIN users u2 ON e.approved_by = u2.user_id
            LEFT JOIN events ev ON e.event_id = ev.event_id
            WHERE e.expense_id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Expense not found']);
        }
    } catch (Exception $ex) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'stats') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) AS approved,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount END), 0) AS total_approved
            FROM expenses
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
    } catch (Exception $ex) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'create') {
    header('Content-Type: application/json');
    try {
        $category = trim($_POST['category'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $expenseDate = $_POST['expense_date'] ?? '';
        $vendor = trim($_POST['vendor'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

        if (!$category || !$amount || !$expenseDate) {
            throw new Exception('Category, amount and date are required');
        }

        $receiptFile = null;
        if (!empty($_FILES['receipt']['name'])) {
            $upload = uploadFile($_FILES['receipt']);
            if ($upload['success']) {
                $receiptFile = $upload['filename'];
            } else {
                throw new Exception($upload['error']);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO expenses (category, amount, expense_date, vendor, reference, description, notes, receipt, event_id, submitted_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$category, $amount, $expenseDate, $vendor, $reference, $description, $notes, $receiptFile, $eventId, $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Expense created', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $ex) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update') {
    header('Content-Type: application/json');
    try {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Invalid expense ID');

        $category = trim($_POST['category'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $expenseDate = $_POST['expense_date'] ?? '';
        $vendor = trim($_POST['vendor'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

        if (!$category || !$amount || !$expenseDate) {
            throw new Exception('Category, amount and date are required');
        }

        $receiptFile = null;
        if (!empty($_FILES['receipt']['name'])) {
            $upload = uploadFile($_FILES['receipt']);
            if ($upload['success']) {
                $receiptFile = $upload['filename'];
                $old = $pdo->prepare("SELECT receipt FROM expenses WHERE expense_id = ?");
                $old->execute([$id]);
                $oldFile = $old->fetchColumn();
                if ($oldFile && file_exists(UPLOAD_PATH . '/' . $oldFile)) {
                    unlink(UPLOAD_PATH . '/' . $oldFile);
                }
            } else {
                throw new Exception($upload['error']);
            }
        }

        $sql = "UPDATE expenses SET category=?, amount=?, expense_date=?, vendor=?, reference=?, description=?, notes=?, event_id=?";
        $params = [$category, $amount, $expenseDate, $vendor, $reference, $description, $notes, $eventId];

        if ($receiptFile) {
            $sql .= ", receipt=?";
            $params[] = $receiptFile;
        }

        $sql .= " WHERE expense_id=?";
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);

        echo json_encode(['success' => true, 'message' => 'Expense updated']);
    } catch (Exception $ex) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'approve') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        exit;
    }

    $pdo->prepare("UPDATE expenses SET status='approved', approved_by=?, approved_at=NOW() WHERE expense_id=?")->execute([$_SESSION['user_id'], $id]);

    $row = $pdo->prepare("SELECT submitted_by, category, amount FROM expenses WHERE expense_id=?");
    $row->execute([$id]);
    $exp = $row->fetch();
    if ($exp && $exp['submitted_by']) {
        createNotification(
            $exp['submitted_by'],
            'expense_approved',
            'Expense Approved',
            'Your ' . $exp['category'] . ' expense of RM ' . number_format($exp['amount'], 2) . ' has been approved.',
            '/band/my_expenses.php'
        );
    }

    echo json_encode(['success' => true, 'message' => 'Expense approved']);
    exit;
}

if ($method === 'POST' && $action === 'reject') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        exit;
    }

    $pdo->prepare("UPDATE expenses SET status='rejected' WHERE expense_id=?")->execute([$id]);

    $row = $pdo->prepare("SELECT submitted_by, category, amount FROM expenses WHERE expense_id=?");
    $row->execute([$id]);
    $exp = $row->fetch();
    if ($exp && $exp['submitted_by']) {
        createNotification(
            $exp['submitted_by'],
            'expense_rejected',
            'Expense Rejected',
            'Your ' . $exp['category'] . ' expense of RM ' . number_format($exp['amount'], 2) . ' has been rejected.',
            '/band/my_expenses.php'
        );
    }

    echo json_encode(['success' => true, 'message' => 'Expense rejected']);
    exit;
}

if ($method === 'POST' && $action === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        exit;
    }

    $old = $pdo->prepare("SELECT receipt FROM expenses WHERE expense_id=?");
    $old->execute([$id]);
    $oldFile = $old->fetchColumn();
    if ($oldFile && file_exists(UPLOAD_PATH . '/' . $oldFile)) {
        unlink(UPLOAD_PATH . '/' . $oldFile);
    }

    $pdo->prepare("DELETE FROM expenses WHERE expense_id=?")->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Expense deleted']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search expenses..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Expense Management</h2>
                        <p class="subtitle">Track and manage all expenses</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn-secondary" onclick="exportExpenses()"><i class="bi bi-download btn-icon"></i> Export CSV</button>
                        <button class="btn-primary" onclick="openExpenseModal()"><i class="bi bi-plus-circle btn-icon"></i> Add Expense</button>
                    </div>
                </div>

                <div class="filter-row">
                    <select id="filterCategory" onchange="renderExpenses()">
                        <option value="">All Categories</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Food">Food</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Rental">Rental</option>
                        <option value="Transport">Transport</option>
                        <option value="Venue">Venue</option>
                        <option value="Other">Other</option>
                    </select>
                    <select id="filterStatus" onchange="renderExpenses()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <input type="date" id="filterDateFrom" onchange="renderExpenses()" placeholder="From">
                    <input type="date" id="filterDateTo" onchange="renderExpenses()" placeholder="To">
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalExpenses">0</div>
                        <div class="mini-stat-label">Total Expenses</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="pendingExpenses">0</div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="approvedExpenses">0</div>
                        <div class="mini-stat-label">Approved</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalAmount">RM 0</div>
                        <div class="mini-stat-label">Approved Amount</div>
                    </div>
                </div>

                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Description</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expensesTable">
                            <tr>
                                <td colspan="9" class="loading">Loading expenses...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="expenseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="expenseModalTitle">Add Expense</h3>
                <button class="close-btn" onclick="closeExpenseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" class="form-grid">
                    <input type="hidden" id="expenseId" value="">
                    <div class="form-group">
                        <label>Category</label>
                        <select id="expCategory" required>
                            <option value="">Select category</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Food">Food</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Rental">Rental</option>
                            <option value="Transport">Transport</option>
                            <option value="Venue">Venue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (RM)</label>
                        <input type="number" id="expAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="expDate" required>
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <input type="text" id="expVendor" placeholder="Vendor name">
                    </div>
                    <div class="form-group">
                        <label>Reference</label>
                        <input type="text" id="expReference" placeholder="Invoice/receipt ref">
                    </div>
                    <div class="form-group">
                        <label>Receipt</label>
                        <input type="file" id="expReceipt" accept="image/*,.pdf">
                    </div>
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea id="expDescription" rows="3" placeholder="What was this expense for?"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeExpenseModal()">Cancel</button>
                <button class="btn-primary" onclick="saveExpense()"><i class="bi bi-floppy btn-icon"></i> Save Expense</button>
            </div>
        </div>
    </div>

    <div class="modal" id="receiptModal">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h3>Receipt</h3>
                <button class="close-btn" onclick="closeReceiptModal()">&times;</button>
            </div>
            <div class="modal-body" id="receiptModalBody" style="text-align:center;"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/expenses.js"></script>
</body>
</html>
