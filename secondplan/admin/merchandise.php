<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();  // make sure user is logged in
require_role(['admin']); // restrict to admin
header('Content-Type: application/json');

// Use PDO for database
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// ------------- Handle GET API -------------
if (isset($_GET['api'])) {
    // -------- LIST MERCHANDISE ----------
    if ($_GET['api'] === 'list') {
        $stmt = $pdo->query("SELECT * FROM merchandise ORDER BY id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $items]);
        exit;

    // -------- SAVE MERCHANDISE ----------
    } elseif ($_GET['api'] === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $name = trim($_POST['name'] ?? '');
            $sku = trim($_POST['sku'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? null);

            if (!$name || !$sku || $price <= 0 || $stock < 0) {
                throw new Exception('Invalid input data');
            }

            $stmt = $pdo->prepare("INSERT INTO merchandise (name, sku, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $sku, $price, $stock, $description, $image]);

            echo json_encode(['success' => true, 'message' => 'Merchandise added']);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

// ------------- Handle DELETE (POST) -------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM merchandise WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Item deleted']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// ------------- Default: show simple HTML table -------------
$stmt = $pdo->query("SELECT * FROM merchandise ORDER BY id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Merchandise Inventory</h1>";
echo "<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>ID</th><th>Name</th><th>SKU</th><th>Price</th><th>Stock</th><th>Description</th></tr>";

foreach ($items as $m) {
    echo "<tr>
        <td>{$m['id']}</td>
        <td>{$m['name']}</td>
        <td>{$m['sku']}</td>
        <td>RM {$m['price']}</td>
        <td>{$m['stock']}</td>
        <td>{$m['description']}</td>
    </tr>";
}
echo "</table>";


exits;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchandise - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        
/* GRID FIX */
.merch-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

/* CARD */
.merch-card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: transform .2s ease, box-shadow .2s ease;
}

.merch-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0,0,0,.2);
}

/* IMAGE */
.merch-image {
  height: 150px;
  background: rgba(30,41,59,.5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  position: relative;
}

.merch-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* LOW STOCK BADGE */
.low-stock-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  background: #facc15;
  color: #000;
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 600;
}

/* DETAILS */
.merch-details {
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-grow: 1;
}

.merch-details h4 {
  margin: 0;
  font-size: 16px;
}

.merch-sku {
  font-size: 12px;
  color: var(--text-secondary);
}

/* STATS */
.merch-stats {
  display: flex;
  justify-content: space-between;
}

.merch-stat-label {
  font-size: 12px;
  color: var(--text-secondary);
}

.merch-stat-value {
  font-size: 14px;
  font-weight: 600;
}

/* STOCK BAR */
.stock-bar {
  height: 6px;
  background: rgba(51,65,85,.3);
  border-radius: 6px;
  overflow: hidden;
}

.stock-fill {
  height: 100%;
  background: var(--green);
}

.stock-fill.low {
  background: var(--yellow);
}

/* ACTION BUTTONS */
.merch-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.merch-actions button {
  flex: 1;
  padding: 8px;
  font-size: 13px;
  border-radius: 8px;
  cursor: pointer;
}

    </style>
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
                <a class="nav-item" href="expenses.html">💰 <span>Expenses</span></a>
                <a class="nav-item active" href="merchandise.html">📦 <span>Merchandise</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="header">
                <input type="text" placeholder="Search merchandise..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn">🔔</button>
                    <div class="user-avatar">👤</div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Merchandise Inventory</h2>
                        <p class="subtitle">Manage band merchandise and stock levels</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn-secondary" onclick="exportInventory()">
                            📥 Export
                        </button>
                        <button class="btn-primary" onclick="window.location.href='add_merchandise.html'">
                            + Add Item
                        </button>
                    </div>
                </div>

                <!-- Inventory Summary -->
                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalItems">0</div>
                        <div class="mini-stat-label">Total Items</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalValue">RM 0</div>
                        <div class="mini-stat-label">Total Value</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="lowStock">0</div>
                        <div class="mini-stat-label">Low Stock Items</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalSales">RM 0</div>
                        <div class="mini-stat-label">Total Sales</div>
                    </div>
                </div>

                <!-- Merchandise Grid -->
                <div class="merch-grid" id="merchGrid">
                    <div class="loading-card">Loading merchandise...</div>
                </div>
            </main>
        </div>
    </div>

    <!-- Merchandise Modal -->
    <div class="modal" id="merchModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Item Details</h3>
                <button class="close-btn" onclick="closeMerchModal()">&times;</button>
            </div>
            <div class="modal-body" id="merchDetails"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeMerchModal()">Close</button>
                <button class="btn-primary" onclick="editMerchandise()">Edit</button>
                <button class="btn-danger" onclick="deleteMerchandise()">Delete</button>
            </div>
        </div>
    </div>

    <script src="assets/js/merchandise.js"></script>
</body>
</html>