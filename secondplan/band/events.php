<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$stmt = $pdo->prepare("
    SELECT * FROM events
    WHERE date >= CURDATE()
    ORDER BY date ASC, start_time ASC
");
$stmt->execute();
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h2>Upcoming Events</h2>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if (empty($events)): ?>
                <div class="empty-state">No upcoming events.</div>
            <?php else: ?>
                <div class="event-list">
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <div class="month"><?= formatDate($event['date'], 'M') ?></div>
                                <div class="day"><?= formatDate($event['date'], 'd') ?></div>
                            </div>
                            <div class="event-details">
                                <h4><?= e($event['title']) ?></h4>
                                <div class="event-meta">
                                    <span>Venue: <?= e($event['venue']) ?></span>
                                    <span>Time: <?= date('H:i', strtotime($event['start_time'])) ?> - <?= date('H:i', strtotime($event['end_time'])) ?></span>
                                    <?php if ($event['location']): ?>
                                        <span>Location: <?= e($event['location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge status-<?= $event['status'] ?>"><?= ucfirst($event['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
