<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
requireRole([ROLE_ADMIN]);

$user = getUserData(); // Get logged-in user info

// Fetch stats from DB
$data = [
    'events'    => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'bookings'  => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'tasks'     => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'expenses'  => $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn(),
];

// Get admin stats
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM events WHERE date >= CURDATE()) as upcoming_events,
        (SELECT COUNT(*) FROM tasks WHERE status != 'completed') as pending_tasks,
        (SELECT COUNT(*) FROM expenses WHERE status = 'pending') as pending_expenses
");
$stmt->execute();
$stats = $stmt->fetch();

// Get recent events
$stmt = $pdo->prepare("
    SELECT *
    FROM events
    ORDER BY date DESC
    LIMIT 5
");
$stmt->execute();
$recent_events = $stmt->fetchAll();

// Get recent tasks
$stmt = $pdo->prepare("
    SELECT t.*, u.name as assigned_user
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.user_id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_tasks = $stmt->fetchAll();

// include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – SecondPlan</title>
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
            <a class="nav-item active" href="dashboard.php">
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

    <!-- MAIN -->
    <div class="main-content">

        <!-- HEADER -->
        <div class="header">
            <div>
                <h2>Dashboard</h2>
                <div class="subtitle">Overview of system performance</div>
            </div>

            <div class="header-actions">
                <input type="text" id="searchBox" class="search-box" placeholder="Search…">
                <button class="notification-btn" onclick="toggleNotifications()">
                    🔔
                    <span id="notificationBadge" class="notification-badge"></span>
                </button>
                <div class="user-avatar">👤</div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Revenue</div>
                        <div id="totalRevenue" class="stat-value">RM 0</div>
                        <div class="stat-subtext">This month</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">📅</div>
                    <div class="stat-info">
                        <div class="stat-label">Bookings</div>
                        <div id="totalBookings" class="stat-value">0</div>
                        <div class="stat-subtext">Active bookings</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">📝</div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Tasks</div>
                        <div id="pendingTasks" class="stat-value">0</div>
                        <div class="stat-subtext">Require action</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">💸</div>
                    <div class="stat-info">
                        <div class="stat-label">Expenses</div>
                        <div id="monthlyExpenses" class="stat-value">RM 0</div>
                        <div class="stat-subtext">This month</div>
                    </div>
                </div>
            </div>

            <!-- GRID -->
            <div class="grid-2">

                <!-- RECENT BOOKINGS -->
                <div class="section">
                    <div class="section-header">
                        <h3>Recent Bookings</h3>
                        <a href="bookings.php" class="link-btn">View all</a>
                    </div>

                    <table>
                        <thead>
                        <tr>
                            <th>Company</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="recentBookings">
                        <tr>
                            <td colspan="5" class="empty-state">Loading…</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- UPCOMING EVENTS -->
                <div class="section">
                    <h3>Upcoming Events</h3>
                    <div id="upcomingEvents" class="empty-state">Loading…</div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>

