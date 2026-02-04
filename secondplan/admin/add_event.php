<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); // Optional: ensure user is logged in

$isApi = isset($_GET['api']) || 
         ($_SERVER['REQUEST_METHOD'] === 'POST' &&
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

if ($isApi) {
    header('Content-Type: application/json');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? null;
}

// Connect to database
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Handle GET: list events
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM events ORDER BY event_datetime DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $events]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle POST: add new event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $title = trim($input['title'] ?? '');
    $datetime = $input['date'] ?? null;
    $location = trim($input['location'] ?? '');
    $capacity = !empty($input['capacity']) ? intval($input['capacity']) : null;
    $description = trim($input['description'] ?? '');

    if (!$title || !$datetime || !$location) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title, Date/Time, and Location are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, event_datetime, location, capacity, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $datetime, $location, $capacity, $description]);

        echo json_encode([
            'success' => true,
            'message' => 'Event created successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Default response for invalid requests
// http_response_code(400);
// echo json_encode(['success' => false, 'message' => 'Invalid request']);

// exit;
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Event - SecondPlan</title>
  <link rel="stylesheet" href="assets/css/admin.css">

  <style>
    .form {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    .field {
      display: flex;
      flex-direction: column;
    }
    .field.full {
      grid-column: 1 / -1;
    }
    .field label {
      font-size: 13px;
      margin-bottom: 6px;
      color: var(--text-secondary);
    }
    .field input,
    .field textarea {
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text-primary);
    }
    .actions {
      grid-column: 1 / -1;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 10px;
    }

    /* Preview */
    .form-preview {
      background: linear-gradient(135deg, rgba(30,41,59,.5), rgba(15,23,42,.5));
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      margin-top: 24px;
    }
    .preview-item {
      padding: 10px 0;
      border-bottom: 1px solid rgba(51,65,85,.3);
    }
    .preview-item:last-child {
      border-bottom: none;
    }
    .preview-label {
      font-size: 12px;
      color: var(--text-secondary);
    }
    .preview-value {
      font-size: 15px;
      color: var(--text-primary);
      font-weight: 500;
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
            <a class="nav-item active" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item " href="tasks.php">
                <span>✓</span> <span>Tasks</span>
            </a>
            <a class="nav-item " href="expenses.php">
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
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h2>Add New Event</h2>
          <p class="subtitle">Schedule a new performance or event</p>
        </div>
        <a href="events.php" class="btn-secondary">← Back</a>
      </div>

      <!-- Form -->
      <div class="section">
        <form id="eventForm" class="form">

          <div class="field">
            <label>Event Title *</label>
            <input id="title" required placeholder="e.g. Jazz Night at Publika">
          </div>

          <div class="field">
            <label>Date & Time *</label>
            <input id="date" type="datetime-local" required>
          </div>

          <div class="field">
            <label>Location *</label>
            <input id="location" required placeholder="e.g. KL Convention Centre">
          </div>

          <div class="field">
            <label>Capacity (Optional)</label>
            <input id="capacity" type="number" min="1" placeholder="Max attendees">
          </div>

          <div class="field full">
            <label>Description (Optional)</label>
            <textarea id="description" rows="4"
              placeholder="Event details, notes, performer info..."></textarea>
          </div>

          <div class="actions">
            <a href="events.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Create Event</button>
          </div>
        </form>
      </div>

      <!-- Live Preview -->
      <div class="form-preview" id="preview" style="display:none">
        <h3>Event Preview</h3>

        <div class="preview-item">
          <div class="preview-label">Title</div>
          <div class="preview-value" id="p-title">—</div>
        </div>

        <div class="preview-item">
          <div class="preview-label">Date & Time</div>
          <div class="preview-value" id="p-date">—</div>
        </div>

        <div class="preview-item">
          <div class="preview-label">Location</div>
          <div class="preview-value" id="p-location">—</div>
        </div>

        <div class="preview-item" id="p-capacity-wrap" style="display:none">
          <div class="preview-label">Capacity</div>
          <div class="preview-value" id="p-capacity">—</div>
        </div>

        <div class="preview-item" id="p-desc-wrap" style="display:none">
          <div class="preview-label">Description</div>
          <div class="preview-value" id="p-desc">—</div>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="assets/js/add_event.js"></script>

</body>
</html>
