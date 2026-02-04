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

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Collect POST data
$item_name = trim($_POST['itemName'] ?? '');
$sku       = trim($_POST['sku'] ?? '');
$price     = floatval($_POST['price'] ?? 0);
$stock     = intval($_POST['stock'] ?? 0);
$description = trim($_POST['description'] ?? '');
$imageFile = $_FILES['image'] ?? null;
$imagePath = null;

// Validate required fields
if (!$item_name || !$sku || $price < 0 || $stock < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide all required fields with valid values.']);
    exit;
}

// Handle image upload
if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/merch/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png'];
    if (!in_array(strtolower($ext), $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG/PNG allowed.']);
        exit;
    }

    if ($imageFile['size'] > 5 * 1024 * 1024) { // 5MB max
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Image exceeds 5MB limit.']);
        exit;
    }

    $filename = uniqid('merch_') . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    if (!move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
        exit;
    }

    $imagePath = 'uploads/merch/' . $filename; // relative path
}

// Insert into DB
try {
    $stmt = $pdo->prepare("INSERT INTO merchandise (item_name, sku, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$item_name, $sku, $price, $stock, $description, $imagePath]);

    echo json_encode([
        'success' => true,
        'message' => 'Merchandise item added successfully',
        'id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
?>  

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Merchandise - SecondPlan</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    /* Form layout */
    .form { display:grid; grid-template-columns:repeat(2,1fr); gap:20px; }
    .field { display:flex; flex-direction:column; }
    .field.full { grid-column:1/-1; }
    .field label { font-size:13px; margin-bottom:6px; color:var(--text-secondary); }
    .field input, .field textarea, .field select { padding:10px 12px; border-radius:8px; border:1px solid var(--border); background:var(--panel); color:var(--text-primary); }
    .actions { grid-column:1/-1; display:flex; justify-content:flex-end; gap:12px; margin-top:10px; }

    /* Image upload */
    .image-upload { border:2px dashed var(--border); border-radius:12px; padding:40px 20px; text-align:center; cursor:pointer; transition:all .3s; background:rgba(30,41,59,.3); }
    .image-upload:hover { border-color:var(--blue); background: rgba(59,130,246,.05); }
    .image-upload.has-image { padding:0; border-style:solid; }
    .image-preview { display:none; position:relative; }
    .image-preview.show { display:block; }
    .image-preview img { width:100%; max-height:300px; object-fit:contain; border-radius:10px; }
    .image-preview .remove-image { position:absolute; top:12px; right:12px; padding:8px 12px; background:var(--red); color:white; border:none; border-radius:6px; cursor:pointer; font-size:13px; box-shadow:0 2px 8px rgba(0,0,0,.3); }

    /* Stock indicator */
    .stock-indicator { grid-column:1/-1; padding:16px; border-radius:8px; margin-top:12px; display:none; }
    .stock-indicator.show { display:block; }
    .stock-indicator.low { background: linear-gradient(135deg, rgba(234,179,8,.1), rgba(234,179,8,.05)); border:1px solid rgba(234,179,8,.3); }
    .stock-indicator.good { background: linear-gradient(135deg, rgba(34,197,94,.1), rgba(34,197,94,.05)); border:1px solid rgba(34,197,94,.3); }
    .stock-indicator .label { font-size:13px; color:var(--text-secondary); margin-bottom:4px; }
    .stock-indicator .value { font-size:20px; font-weight:600; }
    .stock-indicator.low .value { color:var(--yellow); }
    .stock-indicator.good .value { color:var(--green); }

    .validation-error { color:var(--red); font-size:13px; margin-top:4px; display:none; }
    .field.error input, .field.error textarea, .field.error select { border-color:var(--red); }
    .field.error .validation-error { display:block; }
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
      <a class="nav-item" href="expenses.html">💰 <span>Expenses</span></a>
      <a class="nav-item active" href="merchandise.html">📦 <span>Merchandise</span></a>
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
          <h2>Add Merchandise Item</h2>
          <p class="subtitle">Add a new product to inventory</p>
        </div>
        <a href="merchandise.html" class="btn-secondary">← Back</a>
      </div>

      <div class="section">
        <form id="merchForm" class="form" enctype="multipart/form-data">
          <!-- Product Image -->
          <div class="field full">
            <label>Product Image (Optional)</label>
            <div class="image-upload" id="image-upload-area" onclick="document.getElementById('image').click()">
              <div id="upload-prompt" class="upload-prompt">
                <div class="upload-icon">📦</div>
                <div>Click to upload image</div>
                <small style="color: var(--text-secondary);">JPG, PNG (Max 5MB)</small>
              </div>
              <div id="image-preview" class="image-preview">
                <img id="preview-img" alt="Product image">
                <button type="button" class="remove-image" onclick="event.stopPropagation(); removeImage()">Remove</button>
              </div>
            </div>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" style="display:none;">
          </div>

          <div class="field">
            <label>Item Name *</label>
            <input id="itemName" required placeholder="e.g., Band T-Shirt">
          </div>

          <div class="field">
            <label>SKU *</label>
            <input id="sku" required placeholder="e.g., TS-001">
          </div>

          <div class="field">
            <label>Price (RM) *</label>
            <input id="price" type="number" step="0.01" min="0" required placeholder="0.00">
          </div>

          <div class="field">
            <label>Stock Quantity *</label>
            <input id="stock" type="number" min="0" required placeholder="0">
          </div>

          <div class="field full">
            <label>Description (Optional)</label>
            <textarea id="description" rows="3" placeholder="Product description, size, color, etc."></textarea>
          </div>

          <div id="stock-indicator" class="stock-indicator">
            <div class="label">Stock Status</div>
            <div class="value" id="stock-status"></div>
          </div>

          <div class="actions">
            <a href="merchandise.html" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Add Item</button>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<script src="assets/js/add_merchandise.js"></script>

</body>
</html>

