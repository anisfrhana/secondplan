<?php
session_start();
include("../config/db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Admin") {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$data = [];

// Fetch counts safely using prepared statements
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM events");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $data['events']);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM event_booking");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $data['bookings']);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM tasks");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $data['tasks']);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM event_expenses");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $data['expenses']);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success'=>true, 'data'=>$data]);

exits;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-icon">⚡</div>
                <h1>SecondPlan</h1>
            </div>
            <nav class="nav">
                <a class="nav-item active" href="dashboard.html">
                    <span>📊</span> <span>Dashboard</span>
                </a>
                <a class="nav-item" href="bookings.html">
                    <span>📅</span> <span>Bookings</span>
                </a>
                <a class="nav-item" href="events.html">
                    <span>🎤</span> <span>Events</span>
                </a>
                <a class="nav-item" href="tasks.html">
                    <span>✓</span> <span>Tasks</span>
                </a>
                <a class="nav-item" href="expenses.html">
                    <span>💰</span> <span>Expenses</span>
                </a>
                <a class="nav-item" href="merchandise.html">
                    <span>📦</span> <span>Merchandise</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <input type="text" placeholder="Search..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn" onclick="toggleNotifications()">
                        🔔
                        <span class="notification-badge" id="notificationBadge"></span>
                    </button>
                    <div class="user-avatar" onclick="toggleUserMenu()">👤</div>
                </div>
            </header>

            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h4>Notifications</h4>
                    <button onclick="markAllRead()">Mark all read</button>
                </div>
                <div id="notificationList"></div>
            </div>

            <!-- Content -->
            <main class="content">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon green">💰</div>
                        <div class="stat-info">
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value" id="totalRevenue">RM 0</div>
                            <div class="stat-change" id="revenueChange">↑ 0%</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">📅</div>
                        <div class="stat-info">
                            <div class="stat-label">Active Bookings</div>
                            <div class="stat-value" id="activeBookings">0</div>
                            <div class="stat-change" id="bookingsChange">↑ 0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">✓</div>
                        <div class="stat-info">
                            <div class="stat-label">Pending Tasks</div>
                            <div class="stat-value" id="pendingTasks">0</div>
                            <div class="stat-change" id="tasksChange">↓ 0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">📈</div>
                        <div class="stat-info">
                            <div class="stat-label">Monthly Expenses</div>
                            <div class="stat-value" id="monthlyExpenses">RM 0</div>
                            <div class="stat-change" id="expensesChange">↑ 0%</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid-2">
                    <div class="section">
                        <div class="section-header">
                            <h3>Revenue Overview</h3>
                            <select id="revenueFilter" onchange="loadRevenueChart()">
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                            </select>
                        </div>
                        <div class="chart" id="revenueChart"></div>
                    </div>

                    <div class="section">
                        <h3>Expense Breakdown</h3>
                        <div id="expenseBreakdown"></div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid-2">
                    <div class="section">
                        <div class="section-header">
                            <h3>Recent Bookings</h3>
                            <a href="bookings.html" class="link-btn">View All</a>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentBookings"></tbody>
                        </table>
                    </div>

                    <div class="section">
                        <div class="section-header">
                            <h3>Upcoming Events</h3>
                            <a href="events.html" class="link-btn">View Calendar</a>
                        </div>
                        <div id="upcomingEvents"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>