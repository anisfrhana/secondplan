<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$flash = getFlash();

$stmt = $pdo->prepare("
    SELECT t.*, e.title as event_title
    FROM tasks t
    LEFT JOIN events e ON t.event_id = e.event_id
    WHERE t.assigned_to = ?
    ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.due_date ASC
");
$stmt->execute([getUserId()]);
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h2>My Tasks</h2>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">No tasks assigned to you.</div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <h4><?= e($task['title']) ?></h4>
                                <span class="badge priority-<?= $task['priority'] ?>"><?= strtoupper($task['priority']) ?></span>
                            </div>
                            <?php if ($task['description']): ?>
                                <p class="task-description"><?= e($task['description']) ?></p>
                            <?php endif; ?>
                            <div class="task-meta">
                                <?php if (!empty($task['event_title'])): ?>
                                    <span>Event: <?= e($task['event_title']) ?></span>
                                <?php endif; ?>
                                <?php if ($task['due_date']): ?>
                                    <span>Due: <?= formatDate($task['due_date'], 'M d, Y') ?></span>
                                <?php endif; ?>
                                <span>Status: <?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                            </div>
                            <div class="task-actions">
                                <button class="btn-small primary" onclick="viewTaskDetails(<?= $task['task_id'] ?>)"><i class="bi bi-eye btn-icon"></i> Details</button>
                                <form method="POST" action="update_task_status.php" style="display:inline">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                    <?php if ($task['status'] === 'todo'): ?>
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" class="btn-small success"><i class="bi bi-play-circle btn-icon"></i> Start</button>
                                    <?php elseif ($task['status'] === 'in_progress'): ?>
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn-small success"><i class="bi bi-check-circle btn-icon"></i> Complete</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script src="assets/js/band.js"></script>
</body>
</html>
