<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf'] ?? null)) {
        setFlash('error', 'CSRF token validation failed.');
        redirect('my_tasks.php');
    }

    $taskId = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';

    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$status, $taskId, getUserId()]);

    setFlash('success', 'Task updated successfully.');
    redirect('my_tasks.php');
}
