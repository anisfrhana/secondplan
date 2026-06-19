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
                merch_id AS id,
                name,
                sku,
                description,
                price,
                cost,
                stock,
                low_stock_threshold,
                category,
                image,
                status,
                created_at,
                CASE
                    WHEN stock = 0 THEN 'out_of_stock'
                    WHEN stock <= low_stock_threshold THEN 'low_stock'
                    ELSE 'in_stock'
                END AS stock_status
            FROM merchandise
            ORDER BY merch_id DESC
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
        $stmt = $pdo->prepare("SELECT * FROM merchandise WHERE merch_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item not found']);
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
                COUNT(*) AS total_items,
                COALESCE(SUM(stock * price), 0) AS total_value,
                COUNT(CASE WHEN stock <= low_stock_threshold AND stock > 0 THEN 1 END) AS low_stock,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_items
            FROM merchandise
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
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $lowThreshold = (int)($_POST['low_stock_threshold'] ?? 10);
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (!$name || !$price) {
            throw new Exception('Name and price are required');
        }

        $imageFile = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $imageFile = $upload['filename'];
            } else {
                throw new Exception($upload['error']);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO merchandise (name, sku, description, price, cost, stock, low_stock_threshold, category, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $sku ?: null, $description, $price, $cost, $stock, $lowThreshold, $category, $imageFile, $status]);

        echo json_encode(['success' => true, 'message' => 'Item created', 'id' => $pdo->lastInsertId()]);
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
        if (!$id) throw new Exception('Invalid item ID');

        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $lowThreshold = (int)($_POST['low_stock_threshold'] ?? 10);
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (!$name || !$price) {
            throw new Exception('Name and price are required');
        }

        $imageFile = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $imageFile = $upload['filename'];
                $old = $pdo->prepare("SELECT image FROM merchandise WHERE merch_id = ?");
                $old->execute([$id]);
                $oldFile = $old->fetchColumn();
                if ($oldFile && file_exists(UPLOAD_PATH . '/' . $oldFile)) {
                    unlink(UPLOAD_PATH . '/' . $oldFile);
                }
            } else {
                throw new Exception($upload['error']);
            }
        }

        $sql = "UPDATE merchandise SET name=?, sku=?, description=?, price=?, cost=?, stock=?, low_stock_threshold=?, category=?, status=?";
        $params = [$name, $sku ?: null, $description, $price, $cost, $stock, $lowThreshold, $category, $status];

        if ($imageFile) {
            $sql .= ", image=?";
            $params[] = $imageFile;
        }

        $sql .= " WHERE merch_id=?";
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);

        echo json_encode(['success' => true, 'message' => 'Item updated']);
    } catch (Exception $ex) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit;
    }

    try {
        $old = $pdo->prepare("SELECT image FROM merchandise WHERE merch_id = ?");
        $old->execute([$id]);
        $oldFile = $old->fetchColumn();

        $pdo->prepare("DELETE FROM merchandise WHERE merch_id = ?")->execute([$id]);

        if ($oldFile && file_exists(UPLOAD_PATH . '/' . $oldFile)) {
            unlink(UPLOAD_PATH . '/' . $oldFile);
        }

        echo json_encode(['success' => true, 'message' => 'Item deleted']);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), '1451') !== false || strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $pdo->prepare("UPDATE merchandise SET status = 'inactive' WHERE merch_id = ?")->execute([$id]);
            echo json_encode(['success' => false, 'message' => 'Cannot delete this item because it has existing orders. It has been marked as inactive instead.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete item.']);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchandise - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search merchandise..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Merchandise</h2>
                        <p class="subtitle">Manage inventory and products</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn-secondary" onclick="exportInventory()"><i class="bi bi-download btn-icon"></i> Export CSV</button>
                        <button class="btn-primary" onclick="openAddModal()"><i class="bi bi-plus-circle btn-icon"></i> Add Item</button>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalItems">0</div>
                        <div class="mini-stat-label">Total Items</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalValue">RM 0</div>
                        <div class="mini-stat-label">Inventory Value</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="lowStock">0</div>
                        <div class="mini-stat-label">Low Stock</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="activeItems">0</div>
                        <div class="mini-stat-label">Active Items</div>
                    </div>
                </div>

                <div class="merch-grid" id="merchGrid">
                    <div class="loading">Loading merchandise...</div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewModalTitle">Item Details</h3>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeViewModal()">Close</button>
                <button class="btn-primary" id="viewEditBtn" onclick="editFromView()"><i class="bi bi-pencil-square btn-icon"></i> Edit</button>
            </div>
        </div>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">Add Item</h3>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="merchForm" class="form-grid">
                    <input type="hidden" id="merchId" value="">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" id="merchName" required>
                    </div>
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" id="merchSku" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" id="merchPrice" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Cost (RM)</label>
                        <input type="number" id="merchCost" step="0.01" min="0" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" id="merchStock" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Low Stock Threshold</label>
                        <input type="number" id="merchThreshold" min="0" value="10">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" id="merchCategory" placeholder="e.g. Apparel, Accessories">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="merchStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Image</label>
                        <input type="file" id="merchImage" accept="image/*">
                    </div>
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea id="merchDesc" rows="3" placeholder="Product description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button class="btn-primary" onclick="saveMerchandise()"><i class="bi bi-floppy btn-icon"></i> Save</button>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/merchandise.js"></script>
</body>
</html>
