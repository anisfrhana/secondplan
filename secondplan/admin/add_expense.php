<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); // Optional: ensure user is logged in
header('Content-Type: application/json');

// Connect to DB
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Handle GET: list expenses
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC");
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $expenses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle POST: add new expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For file uploads, use $_FILES
    $category = trim($_POST['category'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? '';
    $reference = trim($_POST['reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $receiptFile = $_FILES['receipt'] ?? null;
    $receiptPath = null;

    // Validate required fields
    if (!$category || !$amount || !$date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category, Amount, and Date are required.']);
        exit;
    }

    // Handle file upload
    if ($receiptFile && $receiptFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/receipts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($receiptFile['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','pdf'];
        if (!in_array(strtolower($ext), $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
            exit;
        }

        $filename = uniqid('receipt_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($receiptFile['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            exit;
        }
        $receiptPath = 'uploads/receipts/' . $filename; // relative path
    }

    // Insert into DB
    try {
        $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, expense_date, reference, notes, receipt) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category, $amount, $date, $reference, $notes, $receiptPath]);

        echo json_encode([
            'success' => true,
            'message' => 'Expense recorded successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Default response
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Expense - SecondPlan</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .form {
      display: grid;
      grid-template-columns: repeat(2,1fr);
      gap: 20px;
    }
    .field {
      display: flex;
      flex-direction: column;
    }
    .field.full {
      grid-column:1/-1;
    }
    .field label {
      font-size:13px;
      margin-bottom:6px;
      color:var(--text-secondary);
    }
    .field input,
    .field textarea,
    .field select {
      padding:10px 12px;
      border-radius:8px;
      border:1px solid var(--border);
      background:var(--panel);
      color:var(--text-primary);
    }
    .actions {
      grid-column:1/-1;
      display:flex;
      justify-content:flex-end;
      gap:12px;
      margin-top:10px;
    }

    /* Upload preview */
    .upload-preview {
      margin-top:12px;
      display:none;
      align-items:center;
      gap:12px;
      padding:12px;
      background: rgba(30,41,59,0.5);
      border:1px solid var(--border);
      border-radius:8px;
    }
    .upload-preview.show { display:flex; }
    .upload-preview img {
      max-width:100px;
      max-height:100px;
      border-radius:6px;
      border:1px solid var(--border);
    }
    .upload-preview .file-info { flex:1; }
    .upload-preview .file-name { font-weight:500; margin-bottom:4px; }
    .upload-preview .file-size { font-size:12px; color:var(--text-secondary); }
    .upload-preview .remove-file {
      padding:6px 12px;
      background:var(--red);
      color:white;
      border:none;
      border-radius:6px;
      cursor:pointer;
      font-size:13px;
    }

    .total-amount {
      grid-column:1/-1;
      padding:16px;
      background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(34,197,94,0.05));
      border:1px solid rgba(34,197,94,0.3);
      border-radius:8px;
      text-align:center;
      margin-top:12px;
    }
    .total-amount .label { color:var(--text-secondary); font-size:13px; margin-bottom:4px; }
    .total-amount .value { font-size:32px; font-weight:700; color:var(--green); }
  </style>
</head>
<body>
<div class="app">

  <!-- Sidebar -->
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

  <!-- Main -->
  <div class="main-content">
    <header class="header">
      <input type="text" class="search-box" placeholder="Search...">
      <div class="header-actions">
        <button class="notification-btn">🔔</button>
        <div class="user-avatar">👤</div>
      </div>
    </header>

    <main class="content">
      <div class="page-header">
        <div>
          <h2>Add New Expense</h2>
          <p class="subtitle">Record a new expense with receipt</p>
        </div>
        <a href="expenses.html" class="btn-secondary">← Back</a>
      </div>

      <div class="section">
        <form id="expenseForm" class="form" enctype="multipart/form-data">

          <div class="field">
            <label>Category *</label>
            <select id="category" required>
              <option value="">Select category...</option>
              <option value="equipment">Equipment</option>
              <option value="venue">Venue</option>
              <option value="catering">Catering</option>
              <option value="logistics">Transportation</option>
              <option value="marketing">Marketing</option>
              <option value="misc">Miscellaneous</option>
            </select>
          </div>

          <div class="field">
            <label>Amount (RM) *</label>
            <input id="amount" type="number" step="0.01" min="0" required placeholder="0.00">
          </div>

          <div class="field">
            <label>Date *</label>
            <input id="date" type="date" required>
          </div>

          <div class="field">
            <label>Reference / Invoice #</label>
            <input id="reference" placeholder="Optional">
          </div>

          <div class="field full">
            <label>Notes</label>
            <textarea id="notes" rows="3" placeholder="Optional notes"></textarea>
          </div>

          <div class="field full">
            <label>Receipt (JPG, PNG, PDF)</label>
            <input id="receipt" type="file" accept=".jpg,.jpeg,.png,.pdf">
            <div id="upload-preview" class="upload-preview">
              <img id="preview-img" alt="Receipt preview">
              <div class="file-info">
                <div id="file-name" class="file-name"></div>
                <div id="file-size" class="file-size"></div>
              </div>
              <button type="button" class="remove-file" onclick="removeFile()">Remove</button>
            </div>
          </div>

          <div class="total-amount" id="total-display" style="display:none;">
            <div class="label">Total Expense</div>
            <div class="value" id="total-value">RM 0.00</div>
          </div>

          <div class="actions">
            <a href="expenses.html" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Expense</button>
          </div>

        </form>
      </div>
    </main>
  </div>
</div>

<script src="assets/js/add_expense.js"></script>

</body>
</html>
