<?php
/**
 * ADMIN - Merchandise Management System
 * Complete CRUD operations for merchandise with image handling
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

// LIST MERCHANDISE
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT 
                m.*,
                CASE 
                    WHEN m.stock = 0 THEN 'out_of_stock'
                    WHEN m.stock <= m.low_stock_threshold THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status,
                (m.stock * m.price) as inventory_value
            FROM merchandise m
            ORDER BY m.name ASC
        ");
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// GET SINGLE ITEM
if (isset($_GET['api']) && $_GET['api'] === 'get' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM merchandise WHERE merch_id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            echo json_encode(['success' => true, 'data' => $item]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// GET STATS
if (isset($_GET['api']) && $_GET['api'] === 'stats') {
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_items,
                SUM(stock * price) as total_value,
                COUNT(CASE WHEN stock <= low_stock_threshold THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_items
            FROM merchandise
        ");
        
        echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// CREATE MERCHANDISE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 10);
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name) || $price <= 0) {
            throw new Exception('Name and price are required');
        }
        
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/merchandise/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Invalid image type');
            }
            
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Image exceeds 5MB');
            }
            
            $filename = 'merch_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $imagePath = 'uploads/merchandise/' . $filename;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO merchandise (name, sku, description, price, cost, stock, 
                                   low_stock_threshold, category, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $sku, $description, $price, $cost, $stock, 
                       $low_stock_threshold, $category, $imagePath, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Item created', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// UPDATE MERCHANDISE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        
        if ($id <= 0) throw new Exception('Invalid ID');
        
        $stmt = $pdo->prepare("
            UPDATE merchandise 
            SET name = ?, sku = ?, description = ?, price = ?, cost = ?, 
                stock = ?, low_stock_threshold = ?, category = ?, status = ?
            WHERE merch_id = ?
        ");
        
        $stmt->execute([
            $input['name'], $input['sku'], $input['description'], 
            $input['price'], $input['cost'], $input['stock'],
            $input['low_stock_threshold'], $input['category'], 
            $input['status'], $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Item updated']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// DELETE MERCHANDISE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');
        
        // Get image path
        $stmt = $pdo->prepare("SELECT image FROM merchandise WHERE merch_id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();
        
        // Delete record
        $pdo->prepare("DELETE FROM merchandise WHERE merch_id = ?")->execute([$id]);
        
        // Delete image file
        if ($image && file_exists(__DIR__ . '/../' . $image)) {
            unlink(__DIR__ . '/../' . $image);
        }
        
        echo json_encode(['success' => true, 'message' => 'Item deleted']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// http_response_code(400);
// echo json_encode(['success' => false, 'message' => 'Invalid request']);
// exit;

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
            <a class="nav-item " href="expenses.php">
                <span>💰</span> <span>Expenses</span>
            </a>
            <a class="nav-item active" href="merchandise.php">
                <span>📦</span> <span>Merchandise</span>
            </a>
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