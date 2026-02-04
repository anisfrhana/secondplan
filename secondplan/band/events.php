<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

// Fetch upcoming events
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt->fetchAll();
?>

<h1>Upcoming Events</h1>
<ul>
<?php foreach ($events as $event): ?>
    <li><?= e($event['title']) ?> - <?= e($event['event_date']) ?> @ <?= e($event['location']) ?></li>
<?php endforeach; ?>
</ul>
