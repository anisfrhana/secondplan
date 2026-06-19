<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

$stmt = $pdo->prepare("
    SELECT t.*, e.title as event_title
    FROM tasks t
    LEFT JOIN events e ON t.event_id = e.event_id
    WHERE t.assigned_to = ?
    ORDER BY FIELD(t.status, 'in_progress', 'todo', 'completed', 'cancelled'), t.due_date ASC
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll();

$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$inProgressTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>My Tasks</h2>
                <div class="subtitle"><?= $totalTasks ?> total, <?= $completedTasks ?> completed</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'U', 0, 1)) ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($inProgressTasks > 0 || ($totalTasks - $completedTasks) > 0): ?>
            <div class="stats-grid" style="margin-bottom:20px;">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="2" width="10" height="12" rx="1"/><path d="M6 6h4M6 9h2"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Tasks</div>
                        <div class="stat-value"><?= $totalTasks ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 2"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">In Progress</div>
                        <div class="stat-value"><?= $inProgressTasks ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8.5l3.5 3.5 6.5-7"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                        <div class="stat-value"><?= $completedTasks ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($tasks)): ?>
                <div class="section">
                    <div class="empty-state">
                        <svg style="width:48px;height:48px;margin:0 auto 16px;display:block;opacity:0.3;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="2" width="10" height="12" rx="1"/><path d="M6 6h4M6 9h2"/></svg>
                        No tasks assigned to you.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <span class="task-title"><?= e($task['title']) ?></span>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <span class="badge status-<?= $task['status'] ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                                <span class="badge priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                            </div>
                        </div>
                        <?php if ($task['description']): ?>
                            <p class="task-description"><?= e($task['description']) ?></p>
                        <?php endif; ?>
                        <div class="task-meta">
                            <?php if ($task['due_date']): ?>
                                <span>
                                    <svg style="width:12px;height:12px;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v3M11 1v3M2 7h12"/></svg>
                                    Due: <?= formatDate($task['due_date'], 'M d, Y') ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($task['event_title'])): ?>
                                <span>
                                    <svg style="width:12px;height:12px;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 5v3h3"/></svg>
                                    Event: <?= e($task['event_title']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
