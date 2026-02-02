<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); // Optional: ensure user is logged in
header('Content-Type: application/json');

// Connect to database
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Handle GET: list bookings (optional)
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY id DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle POST: add booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $company = trim($input['company_name'] ?? '');
    $event = trim($input['event_name'] ?? '');
    $date = $input['event_date'] ?? null;
    $time = $input['event_time'] ?? null;
    $location = trim($input['location'] ?? '');
    $amount = $input['estimated_amount'] ?? null;
    $status = $input['status'] ?? 'pending';
    $notes = trim($input['notes'] ?? '');

    if (!$company || !$event || !$date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Company, Event, and Date are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bookings 
            (company_name, event_name, event_date, event_time, location, estimated_amount, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company, $event, $date, $time, $location, $amount, $status, $notes]);

        echo json_encode(['success' => true, 'message' => 'Booking added successfully', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Default response for other requests
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
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
            <a class="nav-item active" href="bookings.html">📅 <span>Bookings</span></a>
            <a class="nav-item" href="events.html">🎤 <span>Events</span></a>
            <a class="nav-item" href="tasks.html">✓ <span>Tasks</span></a>
            <a class="nav-item" href="expenses.html">💰 <span>Expenses</span></a>
            <a class="nav-item" href="merchandise.html">📦 <span>Merchandise</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <input type="text" placeholder="Search..." class="search-box">
            <div class="header-actions">
                <button class="notification-btn">🔔</button>
                <div class="user-avatar">👤</div>
            </div>
        </header>

        <main class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>Add New Booking</h2>
                    <p class="subtitle">Create a new event booking request</p>
                </div>
                <button class="btn-secondary" onclick="window.location.href='bookings.html'">
                    ← Back
                </button>
            </div>

            <!-- Form Section -->
            <div class="section">
                <form class="form-grid" id="addBookingForm">

                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" placeholder="e.g. ABC Corporation" required>
                    </div>

                    <div class="form-group">
                        <label>Event Name</label>
                        <input type="text" placeholder="e.g. Annual Dinner" required>
                    </div>

                    <div class="form-group">
                        <label>Event Date</label>
                        <input type="date" required>
                    </div>

                    <div class="form-group">
                        <label>Event Time</label>
                        <input type="time">
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" placeholder="e.g. Kuala Lumpur Convention Centre">
                    </div>

                    <div class="form-group">
                        <label>Estimated Amount (RM)</label>
                        <input type="number" placeholder="e.g. 15000">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Notes / Special Requests</label>
                        <textarea rows="4" placeholder="Additional details..."></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions full-width">
                        <button type="button" class="btn-secondary"
                                onclick="window.location.href='bookings.html'">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            Save Booking
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/add_booking.js"></script>

</body>
</html>
