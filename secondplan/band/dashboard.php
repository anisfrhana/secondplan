<?php
$title = 'Band Dashboard · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'completed') as pending_tasks,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE)) as completed_this_month,
        (SELECT COUNT(*) FROM events WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as upcoming_events,
        (SELECT COUNT(*) FROM expenses WHERE submitted_by = ? AND status = 'pending') as pending_expenses
");
$stmt->execute([$user_id, $user_id, $user_id]);
$stats = $stmt->fetch();

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

$stmt = $pdo->prepare("
    SELECT *
    FROM events
    WHERE date >= CURDATE()
    ORDER BY date ASC, start_time ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_events = $stmt->fetchAll();

$nextGig = null;
if (!empty($upcoming_events)) {
    $nextGig = $upcoming_events[0];
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
$stmtTotal->execute([$user_id]);
$totalTasks = (int)$stmtTotal->fetchColumn();
$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
$stmtDone->execute([$user_id]);
$completedTasks = (int)$stmtDone->fetchColumn();
$taskPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$stmtExp = $pdo->prepare("SELECT
    COALESCE(SUM(amount), 0) as total_submitted,
    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_amount
    FROM expenses WHERE submitted_by = ?");
$stmtExp->execute([$user_id]);
$expenseSummary = $stmtExp->fetch();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Band Dashboard - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h2>Welcome back, <?= e(getUserData()['name'] ?? 'Member'); ?>!</h2>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($nextGig): ?>
            <div class="countdown-card">
                <h3>Next Gig Countdown</h3>
                <div class="countdown-numbers" id="gigCountdown">
                    <div class="cd-unit">
                        <div class="cd-value" id="gig-days">00</div>
                        <div class="cd-label">Days</div>
                    </div>
                    <div class="cd-unit">
                        <div class="cd-value" id="gig-hours">00</div>
                        <div class="cd-label">Hours</div>
                    </div>
                    <div class="cd-unit">
                        <div class="cd-value" id="gig-mins">00</div>
                        <div class="cd-label">Minutes</div>
                    </div>
                </div>
                <div class="cd-event-name"><?= e($nextGig['title']) ?></div>
                <div class="cd-event-detail"><?= e($nextGig['venue'] ?? 'TBA') ?> &bull; <?= formatDate($nextGig['date'], 'd M Y') ?></div>
            </div>
            <script>
            var gigDate = new Date("<?= $nextGig['date'] ?>T<?= $nextGig['start_time'] ?? '00:00:00' ?>").getTime();
            function updateGigCountdown() {
                var now = new Date().getTime();
                var diff = gigDate - now;
                if (diff <= 0) { return; }
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                document.getElementById('gig-days').textContent = String(d).padStart(2, '0');
                document.getElementById('gig-hours').textContent = String(h).padStart(2, '0');
                document.getElementById('gig-mins').textContent = String(m).padStart(2, '0');
            }
            updateGigCountdown();
            setInterval(updateGigCountdown, 60000);
            </script>
            <?php endif; ?>

            <div class="progress-bar-section">
                <h3>Task Progress</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $taskPercent ?>%"></div>
                    </div>
                    <div class="progress-text"><?= $taskPercent ?>%</div>
                </div>
                <div style="font-size:13px;color:var(--text-secondary);margin-top:8px;"><?= $completedTasks ?> of <?= $totalTasks ?> tasks completed</div>
            </div>

            <div class="expense-summary-row">
                <div class="expense-mini-card">
                    <div class="mini-label">Total Submitted</div>
                    <div class="mini-value">RM <?= number_format($expenseSummary['total_submitted']) ?></div>
                </div>
                <div class="expense-mini-card">
                    <div class="mini-label">Pending</div>
                    <div class="mini-value pending">RM <?= number_format($expenseSummary['pending_amount']) ?></div>
                </div>
                <div class="expense-mini-card">
                    <div class="mini-label">Approved</div>
                    <div class="mini-value approved">RM <?= number_format($expenseSummary['approved_amount']) ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-list-task" style="font-size:24px;color:#a855f7;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Tasks</div>
                        <div class="stat-value"><?= $stats['pending_tasks']; ?></div>
                        <div class="stat-subtext">Need attention</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-check2-circle" style="font-size:24px;color:#22c55e;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Completed This Month</div>
                        <div class="stat-value"><?= $stats['completed_this_month']; ?></div>
                        <div class="stat-subtext">Great work!</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-calendar-event" style="font-size:24px;color:#38bdf8;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Upcoming Events</div>
                        <div class="stat-value"><?= $stats['upcoming_events']; ?></div>
                        <div class="stat-subtext">Next 30 days</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-receipt" style="font-size:24px;color:#f59e0b;"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Expenses</div>
                        <div class="stat-value"><?= $stats['pending_expenses']; ?></div>
                        <div class="stat-subtext">Awaiting approval</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Quick Actions</h3>
                <ul class="quick-actions">
                    <li><a href="events.php" class="link-btn">Manage Events</a></li>
                    <li><a href="my_tasks.php" class="link-btn">View Tasks</a></li>
                    <li><a href="expenses.php" class="link-btn">Submit Expense</a></li>
                    <li><a href="my_expenses.php" class="link-btn">View My Expenses</a></li>
                </ul>
            </div>

            <div class="grid-2">
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
                                            <span><?= e($task['event_title']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($task['due_date']): ?>
                                            <span>Due: <?= formatDate($task['due_date'], 'M d, Y'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-small success" onclick="updateTaskStatus(<?= $task['task_id']; ?>, 'in_progress')"><i class="bi bi-play-circle btn-icon"></i> Start</button>
                                        <button class="btn-small primary" onclick="viewTaskDetails(<?= $task['task_id']; ?>)"><i class="bi bi-eye btn-icon"></i> Details</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

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
                                        <div class="month"><?= formatDate($event['date'], 'M'); ?></div>
                                        <div class="day"><?= formatDate($event['date'], 'd'); ?></div>
                                    </div>
                                    <div class="event-details">
                                        <h4><?= e($event['title']); ?></h4>
                                        <div class="event-meta">
                                            <span><?= e($event['venue']); ?></span>
                                            <span><?= formatDate($event['start_time'], 'H:i'); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge status-<?= $event['status']; ?>"><?= ucfirst($event['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h3>My Schedule</h3>
                <div id="calendar"></div>
            </div>
        </main>
    </div>
</div>

<div class="modal" id="taskModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Task Details</h3>
            <button class="close-btn" onclick="closeTaskModal()">&times;</button>
        </div>
        <div class="modal-body" id="taskDetails"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeTaskModal()">Close</button>
            <button class="btn-success" onclick="markTaskComplete()"><i class="bi bi-check-circle btn-icon"></i> Mark Complete</button>
        </div>
    </div>
</div>

<div class="modal" id="eventDetailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Event Details</h3>
            <button class="close-btn" onclick="closeEventDetailModal()">&times;</button>
        </div>
        <div class="modal-body" id="eventDetailBody"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeEventDetailModal()">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script src="assets/js/band.js"></script>
<script>
    var USER_ID = <?= $user_id ?>;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            eventSources: [
                {
                    url: '../api/events.php',
                    color: '#DC2626'
                },
                {
                    url: '../api/tasks.php?assigned_to=' + USER_ID
                }
            ],
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                var evt = info.event;
                var props = evt.extendedProps || {};
                if (props.type === 'task') {
                    viewTaskDetails(evt.id);
                } else {
                    showEventDetail(evt, props);
                }
            }
        });
        calendar.render();
    });

    function showEventDetail(evt, props) {
        var timeStr = evt.start ? evt.start.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '-';
        if (evt.end) timeStr += ' - ' + evt.end.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        var body = document.getElementById('eventDetailBody');
        body.innerHTML =
            '<div style="display:flex;flex-direction:column;">' +
                '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                    '<div style="color:var(--text-secondary);font-size:13px;">Event</div>' +
                    '<div style="font-size:18px;font-weight:600;margin-top:4px;">' + esc(evt.title) + '</div>' +
                '</div>' +
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;' + (props.venue ? 'border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;' : '') + '">' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Date</div><div style="font-size:14px;margin-top:4px;">' + (evt.start ? evt.start.toLocaleDateString() : '-') + '</div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Time</div><div style="font-size:14px;margin-top:4px;">' + timeStr + '</div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="margin-top:4px;"><span class="badge status-' + (props.status || '') + '">' + (props.status || '').toUpperCase() + '</span></div></div>' +
                '</div>' +
                (props.venue ? '<div><div style="color:var(--text-secondary);font-size:13px;">Venue</div><div style="font-size:14px;margin-top:4px;">' + esc(props.venue) + '</div></div>' : '') +
            '</div>';
        var modal = document.getElementById('eventDetailModal');
        modal.style.display = 'flex';
        modal.classList.add('active');
    }

    function closeEventDetailModal() {
        var modal = document.getElementById('eventDetailModal');
        modal.style.display = 'none';
        modal.classList.remove('active');
    }

    window.addEventListener('click', function(e) {
        if (e.target === document.getElementById('eventDetailModal')) closeEventDetailModal();
    });
</script>
</body>
</html>
