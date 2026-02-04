<?php
$title = 'Band Dashboard · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role('band_member');

$user_id = $_SESSION['user_id'];

// Get band member stats
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'completed') as pending_tasks,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE)) as completed_this_month,
        (SELECT COUNT(*) FROM events WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as upcoming_events,
        (SELECT COUNT(*) FROM expenses WHERE submitted_by = ? AND status = 'pending') as pending_expenses
");
$stmt->execute([$user_id, $user_id, $user_id]);
$stats = $stmt->fetch();

// Get upcoming tasks
$stmt = $pdo->prepare("
    SELECT t.*, e.title as event_title
    FROM tasks t
    LEFT JOIN events e ON t.event_id = e.event_id
    WHERE t.assigned_to = ? AND t.status != 'completed'
    ORDER BY t.due_date ASC, t.priority DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$upcoming_tasks = $stmt->fetchAll();

// Get upcoming events
$stmt = $pdo->prepare("
    SELECT *
    FROM events
    WHERE date >= CURDATE()
    ORDER BY date ASC, start_time ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_events = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Band Dashboard - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css">
</head>
<body>
<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🎸</div>
            <h1>SecondPlan</h1>
            <div class="role-badge">Band Member</div>
        </div>
        <nav class="nav">
            <a class="nav-item active" href="dashboard.php">
                <span>📊</span> <span>Dashboard</span>
            </a>
            <a class="nav-item" href="my_tasks.php">
                <span>✓</span> <span>My Tasks</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item" href="expenses.php">
                <span>💰</span> <span>Submit Expense</span>
            </a>
            <a class="nav-item" href="my_expenses.php">
                <span>📄</span> <span>My Expenses</span>
            </a>
            <a class="nav-item" href="profile.php">
                <span>👤</span> <span>Profile</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <h2>Welcome back, <?= e(get_current_user()['name'] ?? 'Member'); ?>!</h2>
            <div class="header-actions">
                <button class="notification-btn" onclick="toggleNotifications()">
                    🔔
                    <span class="notification-badge" id="notificationBadge"></span>
                </button>
                <div class="user-avatar"><?= strtoupper(substr(get_current_user()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">📋</div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Tasks</div>
                        <div class="stat-value"><?= $stats['pending_tasks']; ?></div>
                        <div class="stat-subtext">Need attention</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="stat-label">Completed This Month</div>
                        <div class="stat-value"><?= $stats['completed_this_month']; ?></div>
                        <div class="stat-subtext">Great work!</div>
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
                    <div class="stat-icon orange">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Expenses</div>
                        <div class="stat-value"><?= $stats['pending_expenses']; ?></div>
                        <div class="stat-subtext">Awaiting approval</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="section">
                <h3>Quick Actions</h3>
                <ul class="quick-actions">
                    <li><a href="events.php" class="link-btn">Manage Events</a></li>
                    <li><a href="my_tasks.php" class="link-btn">View Tasks</a></li>
                    <li><a href="expenses.php" class="link-btn">Submit Expense</a></li>
                    <li><a href="my_expenses.php" class="link-btn">View My Expenses</a></li>
                </ul>
            </div>

            <!-- Grid: Tasks + Events -->
            <div class="grid-2">
                <!-- My Tasks -->
                <div class="section">
                    <div class="section-header">
                        <h3>My Tasks</h3>
                        <a href="my_tasks.php" class="link-btn">View All</a>
                    </div>
                    <?php if (empty($upcoming_tasks)): ?>
                        <div class="empty-state">No pending tasks</div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($upcoming_tasks as $task): ?>
                                <div class="task-item">
                                    <div class="task-header">
                                        <h4><?= e($task['title']); ?></h4>
                                        <span class="badge priority-<?= $task['priority']; ?>"><?= strtoupper($task['priority']); ?></span>
                                    </div>
                                    <?php if ($task['description']): ?>
                                        <p class="task-description"><?= e($task['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="task-meta">
                                        <?php if ($task['event_title']): ?>
                                            <span>📅 <?= e($task['event_title']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($task['due_date']): ?>
                                            <span>⏰ Due: <?= format_date($task['due_date'], 'M d, Y'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-small success" onclick="updateTaskStatus(<?= $task['task_id']; ?>, 'in_progress')">Start</button>
                                        <button class="btn-small primary" onclick="viewTaskDetails(<?= $task['task_id']; ?>)">Details</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Events -->
                <div class="section">
                    <div class="section-header">
                        <h3>Upcoming Events</h3>
                        <a href="events.php" class="link-btn">View All</a>
                    </div>
                    <?php if (empty($upcoming_events)): ?>
                        <div class="empty-state">No upcoming events</div>
                    <?php else: ?>
                        <div class="event-list">
                            <?php foreach ($upcoming_events as $event): ?>
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
                <h3>My Schedule</h3>
                <div id="calendar"></div>
            </div>
        </main>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal" id="taskModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Task Details</h3>
            <button class="close-btn" onclick="closeTaskModal()">&times;</button>
        </div>
        <div class="modal-body" id="taskDetails"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeTaskModal()">Close</button>
            <button class="btn-success" onclick="markTaskComplete()">Mark Complete</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js"></script>
<script src="assets/js/band.js"></script>
<script>
    const USER_ID = <?= $user_id ?>;

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
                {
                    url: '../api/events.php',
                    color: '#3b82f6'
                },
                {
                    url: '../api/tasks.php?user_id=' + USER_ID,
                    color: '#a855f7'
                }
            ],
            eventClick: function(info) {
                if (info.event.extendedProps.type === 'task') {
                    viewTaskDetails(info.event.id);
                }
            }
        });
        calendar.render();
    });
</script>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
