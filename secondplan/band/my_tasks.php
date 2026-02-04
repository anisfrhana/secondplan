<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

$flash = getFlash();

// Fetch tasks from DB (example)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to = ?");
$stmt->execute([getUserId()]);
$tasks = $stmt->fetchAll();
?>

<h1>My Tasks</h1>
<?php if ($flash): ?>
<p><?= e($flash['message']) ?></p>
<?php endif; ?>

<table border="1">
    <thead>
        <tr>
            <th>Task</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tasks as $task): ?>
        <tr>
            <td><?= e($task['title']) ?></td>
            <td><?= e($task['status']) ?></td>
            <td>
                <form method="POST" action="update_task_status.php">
                    <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="task_id" value="<?= e($task['id']) ?>">
                    <select name="status">
                        <option value="pending" <?= $task['status']=='pending'?'selected':'' ?>>Pending</option>
                        <option value="in_progress" <?= $task['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                        <option value="completed" <?= $task['status']=='completed'?'selected':'' ?>>Completed</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
