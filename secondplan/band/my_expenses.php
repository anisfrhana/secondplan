<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

// Fetch user expenses
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([getUserId()]);
$expenses = $stmt->fetchAll();
?>

<h1>My Expenses</h1>
<ul>
<?php foreach ($expenses as $e): ?>
    <li><?= e($e['description']) ?> - RM <?= number_format($e['amount'],2) ?> (<?= e($e['created_at']) ?>)</li>
<?php endforeach; ?>
</ul>
