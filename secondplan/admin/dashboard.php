<?php
require_once __DIR__ . '/../config/config.php';
requireRole([ROLE_ADMIN]);

// Fetch stats
$data = [
    'events' => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'tasks' => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'expenses' => $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - <?= APP_NAME ?></title>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
.sidebar { width:200px; background:#1e293b; height:100vh; float:left; color:#fff; padding:20px; }
.main-content { margin-left:200px; padding:20px; }
.stat-card { display:inline-block; width:200px; background:#3b82f6; color:#fff; padding:20px; margin:10px; border-radius:10px; text-align:center; }
.stat-card h3 { margin-bottom:10px; }
a { color:#3b82f6; text-decoration:none; }
</style>
</head>
<body>
<div class="sidebar">
    <h2><?= APP_NAME ?></h2>
    <p>Admin Panel</p>
    <hr>
    <a href="dashboard.php">📊 Dashboard</a><br>
    <a href="bookings.php">📅 Bookings</a><br>
    <a href="events.php">🎤 Events</a><br>
    <a href="tasks.php">✓ Tasks</a><br>
    <a href="expenses.php">💰 Expenses</a><br>
    <a href="../auth/logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <h1>Welcome, <?= e(getUserName()) ?></h1>
    <p>Overview of your system</p>
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Events</h3>
            <p><?= $data['events'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Bookings</h3>
            <p><?= $data['bookings'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Tasks</h3>
            <p><?= $data['tasks'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Expenses</h3>
            <p><?= $data['expenses'] ?></p>
        </div>
    </div>

    <h2>Quick Actions</h2>
    <ul>
        <li><a href="events.php">Manage Events</a></li>
        <li><a href="bookings.php">View Bookings</a></li>
        <li><a href="tasks.php">Manage Tasks</a></li>
        <li><a href="expenses.php">Manage Expenses</a></li>
    </ul>
</div>
</body>
</html>
