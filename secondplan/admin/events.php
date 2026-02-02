<?php
$title = 'Events · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['admin']);
verify_csrf();

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    // -------- LIST EVENTS ----------
    if ($_GET['api'] === 'list') {
        $events = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $events]);
        exit;

    // -------- SAVE EVENT ----------
    } elseif ($_GET['api'] === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $title = trim($_POST['title'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime = trim($_POST['end_time'] ?? '');
            $venue = trim($_POST['venue'] ?? '');

            if (empty($title) || empty($date) || empty($startTime) || empty($endTime) || empty($venue)) {
                throw new Exception('All fields are required');
            }

            if (!strtotime($date)) throw new Exception('Invalid date');
            if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                throw new Exception('Invalid time format');
            }
            if (strtotime($startTime) >= strtotime($endTime)) throw new Exception('Start time must be before end time');

            $stmt = $pdo->prepare("INSERT INTO events (title, date, start_time, end_time, venue, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$title, $date, $startTime, $endTime, $venue]);

            echo json_encode(['success' => true, 'message' => 'Event added']);
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
            $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
}

// Handle regular POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');

    if (empty($title) || empty($date) || empty($startTime) || empty($endTime) || empty($venue)) {
        die('All fields are required');
    }

    if (!strtotime($date)) die('Invalid date');
    if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        die('Invalid time format');
    }
    if (strtotime($startTime) >= strtotime($endTime)) die('Start time must be before end time');

    $stmt = $pdo->prepare("INSERT INTO events (title, date, start_time, end_time, venue, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
    $stmt->execute([$title, $date, $startTime, $endTime, $venue]);

    header('Location: /admin/events.php?ok=1');
    exit;
}

// Load events for admin table
$events = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1>Events</h1>
<?php if (!empty($_GET['ok'])): ?><p style="color:green">Event added.</p><?php endif; ?>

<h3>Add Event</h3>
<form method="post">
  <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
  <label>Title <input name="title" required></label><br><br>
  <label>Date <input type="date" name="date" required></label><br><br>
  <label>Start <input type="time" name="start_time" required></label><br><br>
  <label>End <input type="time" name="end_time" required></label><br><br>
  <label>Venue <input name="venue" required></label><br><br>
  <button type="submit">Add</button>
</form>

<h3>All Events</h3>
<table border="1" cellpadding="6" cellspacing="0">
  <tr><th>ID</th><th>Title</th><th>Date</th><th>Start</th><th>End</th><th>Venue</th><th>Status</th></tr>
  <?php foreach ($events as $ev): ?>
  <tr>
    <td><?php echo (int)$ev['event_id']; ?></td>
    <td><?php echo e($ev['title']); ?></td>
    <td><?php echo e($ev['date']); ?></td>
    <td><?php echo e($ev['start_time']); ?></td>
    <td><?php echo e($ev['end_time']); ?></td>
    <td><?php echo e($ev['venue']); ?></td>
    <td><?php echo e($ev['status']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - SecondPlan</title>
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
                <a class="nav-item active" href="events.html">🎤 <span>Events</span></a>
                <a class="nav-item" href="tasks.html">✓ <span>Tasks</span></a>
                <a class="nav-item" href="expenses.html">💰 <span>Expenses</span></a>
                <a class="nav-item" href="merchandise.html">📦 <span>Merchandise</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="header">
                <input type="text" placeholder="Search events..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn">🔔</button>
                    <div class="user-avatar">👤</div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Events & Schedule</h2>
                        <p class="subtitle">Manage upcoming performances and events</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn-secondary" onclick="toggleView()">
                            📅 Calendar View
                        </button>
                        <button class="btn-primary" onclick="window.location.href='add_event.html'">
                            + New Event
                        </button>
                    </div>
                </div>

                <!-- View Toggle -->
                <div class="filter-tabs">
                    <button class="tab active" onclick="filterEvents('all')">All Events</button>
                    <button class="tab" onclick="filterEvents('upcoming')">Upcoming</button>
                    <button class="tab" onclick="filterEvents('past')">Past</button>
                    <button class="tab" onclick="filterEvents('cancelled')">Cancelled</button>
                </div>

                <!-- Events Grid -->
                <div class="events-grid" id="eventsGrid">
                    <div class="loading-card">Loading events...</div>
                </div>

                <!-- Calendar View (Hidden by default) -->
                <div class="calendar-view" id="calendarView" style="display: none;">
                    <div class="section">
                        <div class="calendar-header">
                            <button onclick="previousMonth()">◀</button>
                            <h3 id="calendarMonth">January 2025</h3>
                            <button onclick="nextMonth()">▶</button>
                        </div>
                        <div class="calendar-grid" id="calendarGrid"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Event Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="eventDetails"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn-primary" onclick="editEvent()">Edit</button>
                <button class="btn-danger" onclick="deleteEvent()">Delete</button>
            </div>
        </div>
    </div>

    <script src="assets/js/events.js"></script>
</body>
</html>
