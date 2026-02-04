<?php
require_once __DIR__ . '/../config/bootstrap.php';

$isApi = isset($_GET['api']) || 
         ($_SERVER['REQUEST_METHOD'] === 'POST' &&
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

if ($isApi) {
    header('Content-Type: application/json');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? null;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['api'] ?? $_POST['api'] ?? null;

/*
|--------------------------------------------------------------------------
| LIST BOOKINGS
|--------------------------------------------------------------------------
*/
if ($method === 'GET' && $action === 'list') {

  $stmt = $pdo->query("
    SELECT 
      booking_id AS id,
      company_name,
      event_name,
      event_date,
      location,
      price,
      status
    FROM bookings
    ORDER BY booking_id DESC
  ");

  $rows = $stmt->fetchAll();

  echo json_encode([
    'success' => true,
    'data' => $rows
  ]);
  exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE STATUS (APPROVE / REJECT)
|--------------------------------------------------------------------------
*/
if ($method === 'POST' && in_array($action, ['approve','reject'])) {

  $id = (int)($_POST['id'] ?? 0);
  if (!$id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid booking ID']);
    exit;
  }

  $status = $action === 'approve' ? 'approved' : 'rejected';

  $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
  $stmt->execute([$status, $id]);

  echo json_encode([
    'success' => true,
    'message' => "Booking {$status}"
  ]);
  exit;
}

/*
|--------------------------------------------------------------------------
| DELETE BOOKING
|--------------------------------------------------------------------------
*/
if ($method === 'POST' && $action === 'delete') {

  $id = (int)($_POST['id'] ?? 0);
  if (!$id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid booking ID']);
    exit;
  }

  $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
  $stmt->execute([$id]);

  echo json_encode([
    'success' => true,
    'message' => 'Booking deleted'
  ]);
  exit;
}

/*
|--------------------------------------------------------------------------
| FALLBACK
|--------------------------------------------------------------------------
*/
// http_response_code(400);
// echo json_encode([
//   'success' => false,
//   'message' => 'Invalid request'
// ]);

// exit;   
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - SecondPlan</title>

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
            <a class="nav-item active" href="bookings.php">
                <span>📅</span> <span>Bookings</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item " href="tasks.php">
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

        <div class="main-content">
            <header class="header">
                <input type="text" placeholder="Search bookings..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn">🔔</button>
                    <div class="user-avatar">👤</div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Event Bookings</h2>
                        <p class="subtitle">Manage all event booking requests</p>
                    </div>
                    <!-- <button class="btn-primary" onclick="window.location.href='add_booking.php'">
                        + New Booking
                    </button> -->
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button class="tab active" onclick="filterBookings('all')">All</button>
                    <button class="tab" onclick="filterBookings('pending')">Pending</button>
                    <button class="tab" onclick="filterBookings('approved')">Approved</button>
                    <button class="tab" onclick="filterBookings('rejected')">Rejected</button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalBookings">0</div>
                        <div class="mini-stat-label">Total Bookings</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="pendingBookings">0</div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="approvedBookings">0</div>
                        <div class="mini-stat-label">Approved</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalRevenue">RM 0</div>
                        <div class="mini-stat-label">Total Value</div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTable">
                            <tr>
                                <td colspan="8" class="loading">Loading bookings...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Booking Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="bookingDetails"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn-success" onclick="approveBooking()">Approve</button>
                <button class="btn-danger" onclick="rejectBooking()">Reject</button>
            </div>
        </div>
    </div>

    <script src="assets/js/bookings.js"></script>
</body>
</html>
