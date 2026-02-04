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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css">
</head>
<body>
<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🛠️</div>
            <h1>SecondPlan</h1>
            <div class="role-badge">Admin</div>
        </div>
        <nav class="nav">
            <a class="nav-item active" href="dashboard.php">
                <span>📊</span> <span>Dashboard</span>
            </a>
            <a class="nav-item" href="users.php">
                <span>👥</span> <span>Users</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item" href="tasks.php">
                <span>✅</span> <span>Tasks</span>
            </a>
            <a class="nav-item" href="expenses.php">
                <span>💰</span> <span>Expenses</span>
            </a>
            <a class="nav-item" href="reports.php">
                <span>📄</span> <span>Reports</span>
            </a>
            <a class="nav-item" href="profile.php">
                <span>👤</span> <span>Profile</span>
            </a>
            <a class="nav-item" href="../auth/logout.php">
                <span>🚪</span> <span>Logout</span>
            </a>
        </nav>
            
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <h2>Welcome back, <?= e(get_current_user()['name'] ?? 'Admin'); ?>!</h2>
            <div class="header-actions">
                <button class="notification-btn" onclick="toggleNotifications()">
                    🔔
                    <span class="notification-badge" id="notificationBadge"></span>
                </button>
                <div class="user-avatar"><?= strtoupper(substr(get_current_user()['name'] ?? 'A', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">👥</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= $stats['total_users']; ?></div>
                        <div class="stat-subtext">All members</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">🎤</div>
                    <div class="stat-info">
                        <div class="stat-label">Upcoming Events</div>
                        <div class="stat-value"><?= $stats['upcoming_events']; ?></div>
                        <div class="stat-subtext">Next 30 days</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">✅</div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Tasks</div>
                        <div class="stat-value"><?= $stats['pending_tasks']; ?></div>
                        <div class="stat-subtext">Needs attention</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Expenses</div>
                        <div class="stat-value"><?= $stats['pending_expenses']; ?></div>
                        <div class="stat-subtext">Awaiting approval</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Cards -->
            <div class="section">
                <h3>Quick Actions</h3>
                <div class="quick-actions-grid">
                    <a href="users.php" class="card-link">
                        <div class="card-icon">👥</div>
                        <div class="card-title">Manage Users</div>
                    </a>
                    <a href="events.php" class="card-link">
                        <div class="card-icon">🎤</div>
                        <div class="card-title">Manage Events</div>
                    </a>
                    <a href="tasks.php" class="card-link">
                        <div class="card-icon">✅</div>
                        <div class="card-title">Manage Tasks</div>
                    </a>
                    <a href="expenses.php" class="card-link">
                        <div class="card-icon">💰</div>
                        <div class="card-title">Manage Expenses</div>
                    </a>
                    <a href="reports.php" class="card-link">
                        <div class="card-icon">📄</div>
                        <div class="card-title">View Reports</div>
                    </a>
                </div>
            </div>

            <!-- Recent Tasks & Events -->
            <div class="grid-2">
                <!-- Recent Tasks -->
                <div class="section">
                    <div class="section-header">
                        <h3>Recent Tasks</h3>
                        <a href="tasks.php" class="link-btn">View All</a>
                    </div>
                    <?php if (empty($recent_tasks)): ?>
                        <div class="empty-state">No recent tasks</div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($recent_tasks as $task): ?>
                                <div class="task-item">
                                    <div class="task-header">
                                        <h4><?= e($task['title']); ?></h4>
                                        <span class="badge priority-<?= $task['priority']; ?>"><?= strtoupper($task['priority']); ?></span>
                                    </div>
                                    <div class="task-meta">
                                        <span>Assigned: <?= e($task['assigned_user']); ?></span>
                                        <span>Due: <?= format_date($task['due_date'], 'M d, Y'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Events -->
                <div class="section">
                    <div class="section-header">
                        <h3>Recent Events</h3>
                        <a href="events.php" class="link-btn">View All</a>
                    </div>
                    <?php if (empty($recent_events)): ?>
                        <div class="empty-state">No recent events</div>
                    <?php else: ?>
                        <div class="event-list">
                            <?php foreach ($recent_events as $event): ?>
                                <div class="event-item">
                                    <div class="event-date">
                                        <div class="month"><?= format_date($event['date'], 'M'); ?></div>
                                        <div class="day"><?= format_date($event['date'], 'd'); ?></div>
                                    </div>
                                    <div class="event-details">
                                        <h4><?= e($event['title']); ?></h4>
                                        <div class="event-meta">
                                            <span>📍 <?= e($event['venue']); ?></span>
                                            <span>⏰ <?= format_date($event['start_time'], 'H:i'); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge status-<?= $event['status']; ?>"><?= ucfirst($event['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar -->
            <div class="section">
                <h3>Admin Calendar</h3>
                <div id="calendar"></div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js"></script>
<script src="assets/js/dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        events: [
            { url: '../api/events.php', color: '#3b82f6' },
            { url: '../api/tasks.php', color: '#a855f7' }
        ]
    });
    calendar.render();
});
</script>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
