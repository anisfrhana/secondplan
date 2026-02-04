<?php
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

    // ===============================
    // LIST EVENTS
    // ===============================
    if (($_GET['api'] ?? '') === 'list') {
        try {
            $stmt = $pdo->query("
                SELECT e.*, u.name AS created_by_name,
                       COALESCE(e.capacity - e.seats_booked, NULL) AS available_seats
                FROM events e
                LEFT JOIN users u ON u.user_id = e.created_by
                ORDER BY e.date DESC, e.start_time DESC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ===============================
    // GET EVENT
    // ===============================
    if (($_GET['api'] ?? '') === 'get' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([(int)$_GET['id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
        exit;
    }

    // ===============================
    // STATS
    // ===============================
    if (($_GET['api'] ?? '') === 'stats') {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) total_events,
                SUM(date >= CURDATE()) upcoming_events,
                SUM(date < CURDATE()) past_events,
                SUM(COALESCE(capacity,0)) total_capacity
            FROM events
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
        exit;
    }

    // ===============================
    // CREATE
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
        $stmt = $pdo->prepare("
            INSERT INTO events
            (title, description, date, start_time, end_time, venue, location, capacity, price, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['title'],
            $input['description'] ?? '',
            $input['date'],
            $input['start_time'],
            $input['end_time'],
            $input['venue'],
            $input['location'] ?? '',
            $input['capacity'] ?: null,
            $input['price'] ?: null,
            $input['status'] ?? 'scheduled',
            $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Event created']);
        exit;
    }

    // ===============================
    // UPDATE
    // ===============================
    if ($action === 'update') {
        $stmt = $pdo->prepare("
            UPDATE events SET
                title=?, description=?, date=?, start_time=?, end_time=?,
                venue=?, location=?, capacity=?, price=?, status=?
            WHERE event_id=?
        ");
        $stmt->execute([
            $input['title'],
            $input['description'],
            $input['date'],
            $input['start_time'],
            $input['end_time'],
            $input['venue'],
            $input['location'],
            $input['capacity'] ?: null,
            $input['price'] ?: null,
            $input['status'],
            (int)$input['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Event updated']);
        exit;
    }

    // ===============================
    // CANCEL
    // ===============================
    if ($action === 'cancel') {
        $pdo->prepare("UPDATE events SET status='cancelled' WHERE event_id=?")
            ->execute([(int)$input['id']]);

        echo json_encode(['success' => true, 'message' => 'Event cancelled']);
        exit;
    }

    // ===============================
    // DELETE
    // ===============================
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM events WHERE event_id=?")
            ->execute([(int)$input['id']]);

        echo json_encode(['success' => true, 'message' => 'Event deleted']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - SecondPlan Admin</title>
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
            <a class="nav-item" href="users.php">
                <span>👥</span> <span>Users</span>
            </a>
            <a class="nav-item" href="bookings.php">
                <span>📅</span> <span>Bookings</span>
            </a>
            <a class="nav-item active" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item" href="tasks.php">
                <span>✓</span> <span>Tasks</span>
            </a>
            <a class="nav-item" href="expenses.php">
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

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <input type="text" id="searchBox" placeholder="Search events..." class="search-box">
            <div class="header-actions">
                <button class="notification-btn">🔔</button>
                <div class="user-avatar">👤</div>
            </div>
        </header>

        <main class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>Events Management</h2>
                    <p class="subtitle">Schedule and manage all events</p>
                </div>
                <button class="btn-primary" onclick="window.location.href='add_event.php'">
                    + Add Event
                </button>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab active" onclick="filterEvents('all')">All Events</button>
                <button class="tab" onclick="filterEvents('upcoming')">Upcoming</button>
                <button class="tab" onclick="filterEvents('past')">Past</button>
                <button class="tab" onclick="filterEvents('cancelled')">Cancelled</button>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="mini-stat">
                    <div class="mini-stat-value" id="totalEvents">0</div>
                    <div class="mini-stat-label">Total Events</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="upcomingEvents">0</div>
                    <div class="mini-stat-label">Upcoming</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="pastEvents">0</div>
                    <div class="mini-stat-label">Past Events</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="totalCapacity">0</div>
                    <div class="mini-stat-label">Total Capacity</div>
                </div>
            </div>

            <!-- Events Table -->
            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Capacity</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTable">
                        <tr>
                            <td colspan="7" class="loading">Loading events...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add/Edit Event Modal -->
<div class="modal" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Event</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="eventForm" class="form-grid">
                <input type="hidden" id="eventId">
                
                <div class="form-group full-width">
                    <label>Event Title *</label>
                    <input type="text" id="title" required placeholder="e.g., Jazz Night Performance">
                </div>

                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" id="date" required>
                </div>

                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" id="start_time" required>
                </div>

                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" id="end_time" required>
                </div>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="capacity" min="0" placeholder="Leave empty for unlimited">
                </div>

                <div class="form-group">
                    <label>Venue *</label>
                    <input type="text" id="venue" required placeholder="e.g., KLCC Convention Centre">
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="location" placeholder="e.g., Kuala Lumpur">
                </div>

                <div class="form-group">
                    <label>Price (RM)</label>
                    <input type="number" id="price" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="status">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="postponed">Postponed</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea id="description" rows="4" placeholder="Event details..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn-primary" onclick="saveEvent()">Save Event</button>
        </div>
    </div>
</div>

<!-- View Event Modal -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Event Details</h3>
            <button class="close-btn" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="eventDetails"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeViewModal()">Close</button>
            <button class="btn-primary" onclick="editFromView()">Edit</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>



<script src="assets/js/events.js"></script>
</body>
</html>