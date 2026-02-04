<?php
/**
 * ADMIN - Expenses Management System
 * Complete CRUD operations for expenses with receipt handling
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['admin']);

$isApi = isset($_GET['api']) || 
         ($_SERVER['REQUEST_METHOD'] === 'POST' &&
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

if ($isApi) {
    header('Content-Type: application/json');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? null;
}

// ============================================
// API HANDLERS
// ============================================

// LIST EXPENSES
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT 
                e.*,
                u1.name as submitted_by_name,
                u2.name as approved_by_name,
                ev.title as event_title
            FROM expenses e
            LEFT JOIN users u1 ON e.submitted_by = u1.user_id
            LEFT JOIN users u2 ON e.approved_by = u2.user_id
            LEFT JOIN events ev ON e.event_id = ev.event_id
            ORDER BY e.expense_date DESC, e.created_at DESC
        ");
        
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $expenses
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// GET SINGLE EXPENSE
if (isset($_GET['api']) && $_GET['api'] === 'get' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   u1.name as submitted_by_name,
                   u2.name as approved_by_name,
                   ev.title as event_title
            FROM expenses e
            LEFT JOIN users u1 ON e.submitted_by = u1.user_id
            LEFT JOIN users u2 ON e.approved_by = u2.user_id
            LEFT JOIN events ev ON e.event_id = ev.event_id
            WHERE e.expense_id = ?
        ");
        $stmt->execute([$id]);
        
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($expense) {
            echo json_encode([
                'success' => true,
                'data' => $expense
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Expense not found'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// GET STATS
if (isset($_GET['api']) && $_GET['api'] === 'stats') {
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_expenses,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
            FROM expenses
            WHERE YEAR(expense_date) = YEAR(CURDATE())
        ");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get monthly breakdown
        $monthlyStmt = $pdo->query("
            SELECT 
                DATE_FORMAT(expense_date, '%Y-%m') as month,
                SUM(amount) as amount,
                category
            FROM expenses
            WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, category
            ORDER BY month DESC
        ");
        
        $monthly = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'monthly' => $monthly
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// CREATE EXPENSE (with file upload support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        // Handle both JSON and form data
        if (isset($_POST['category'])) {
            // Form data (with file upload)
            $category = trim($_POST['category'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $expense_date = $_POST['expense_date'] ?? '';
            $vendor = trim($_POST['vendor'] ?? '');
            $reference = trim($_POST['reference'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
            $status = $_POST['status'] ?? 'pending';
        } else {
            // JSON data
            $input = json_decode(file_get_contents('php://input'), true);
            $category = trim($input['category'] ?? '');
            $amount = (float)($input['amount'] ?? 0);
            $expense_date = $input['expense_date'] ?? '';
            $vendor = trim($input['vendor'] ?? '');
            $reference = trim($input['reference'] ?? '');
            $description = trim($input['description'] ?? '');
            $notes = trim($input['notes'] ?? '');
            $event_id = !empty($input['event_id']) ? (int)$input['event_id'] : null;
            $status = $input['status'] ?? 'pending';
        }
        
        // Validation
        if (empty($category) || $amount <= 0 || empty($expense_date)) {
            throw new Exception('Category, amount, and date are required');
        }
        
        if (!strtotime($expense_date)) {
            throw new Exception('Invalid date format');
        }
        
        // Handle file upload
        $receiptPath = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array(strtolower($ext), $allowed)) {
                throw new Exception('Invalid file type. Only JPG, PNG, PDF allowed.');
            }
            
            if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size exceeds 5MB limit');
            }
            
            $filename = 'receipt_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $targetPath)) {
                throw new Exception('Failed to upload file');
            }
            
            $receiptPath = 'uploads/receipts/' . $filename;
        }
        
        // Insert expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (
                category, amount, expense_date, vendor, reference,
                description, notes, receipt, status, submitted_by, event_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $category,
            $amount,
            $expense_date,
            $vendor,
            $reference,
            $description,
            $notes,
            $receiptPath,
            $status,
            $_SESSION['user_id'],
            $event_id
        ]);
        
        $expenseId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense created successfully',
            'id' => $expenseId
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// UPDATE EXPENSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($input['id'] ?? 0);
        $category = trim($input['category'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $expense_date = $input['expense_date'] ?? '';
        $vendor = trim($input['vendor'] ?? '');
        $reference = trim($input['reference'] ?? '');
        $description = trim($input['description'] ?? '');
        $notes = trim($input['notes'] ?? '');
        $status = $input['status'] ?? 'pending';
        $event_id = !empty($input['event_id']) ? (int)$input['event_id'] : null;
        
        if ($id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        if (empty($category) || $amount <= 0 || empty($expense_date)) {
            throw new Exception('Category, amount, and date are required');
        }
        
        // Update expense
        $stmt = $pdo->prepare("
            UPDATE expenses 
            SET category = ?, amount = ?, expense_date = ?, vendor = ?, 
                reference = ?, description = ?, notes = ?, status = ?, event_id = ?
            WHERE expense_id = ?
        ");
        
        $stmt->execute([
            $category,
            $amount,
            $expense_date,
            $vendor,
            $reference,
            $description,
            $notes,
            $status,
            $event_id,
            $id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// APPROVE EXPENSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        $stmt = $pdo->prepare("
            UPDATE expenses 
            SET status = 'approved', 
                approved_by = ?, 
                approved_at = NOW()
            WHERE expense_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense approved successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// REJECT EXPENSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        $stmt = $pdo->prepare("
            UPDATE expenses 
            SET status = 'rejected'
            WHERE expense_id = ?
        ");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense rejected successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// DELETE EXPENSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception('Invalid expense ID');
        }
        
        // Get receipt path before deleting
        $stmt = $pdo->prepare("SELECT receipt FROM expenses WHERE expense_id = ?");
        $stmt->execute([$id]);
        $receipt = $stmt->fetchColumn();
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = ?");
        $stmt->execute([$id]);
        
        // Delete receipt file if exists
        if ($receipt && file_exists(__DIR__ . '/../' . $receipt)) {
            unlink(__DIR__ . '/../' . $receipt);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================
// DEFAULT RESPONSE
// ============================================
// http_response_code(400);
// echo json_encode([
//     'success' => false,
//     'message' => 'Invalid request'
// ]);
// exit;
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
         <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">⚡</div>
            <h1>SecondPlan</h1>
            <div class="role-badge">Admin</div>
        </div>
        <nav class="nav">
            <a class="nav-item" href="dashboard.php">
                <span>📊</span> <span>Dashboard</span>
            </a>
            <a class="nav-item " href="users.php">
                <span>👥</span> <span>Users</span>
            </a>
            <a class="nav-item" href="bookings.php">
                <span>📅</span> <span>Bookings</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item " href="tasks.php">
                <span>✓</span> <span>Tasks</span>
            </a>
            <a class="nav-item active" href="expenses.php">
                <span>💰</span> <span>Expenses</span>
            </a>
            <a class="nav-item" href="merchandise.php">
                <span>📦</span> <span>Merchandise</span>
            </a>
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
                        <button class="btn-primary" onclick="window.location.href='add_expense.php'">
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