<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $taskId = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['todo', 'in_progress', 'completed'];
    if (!in_array($status, $allowed)) {
        setFlash('error', 'Invalid status.');
        redirect('/band/my_tasks.php');
    }

    $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("
        UPDATE tasks SET status = ?, completed_at = ?, updated_at = NOW()
        WHERE task_id = ? AND assigned_to = ?
    ");
    $stmt->execute([$status, $completedAt, $taskId, getUserId()]);

    if ($stmt->rowCount() > 0) {
        logActivity(getUserId(), 'task_status_update', [
            'task_id' => $taskId,
            'new_status' => $status
        ]);
        setFlash('success', 'Task updated successfully.');
    } else {
        setFlash('error', 'Task not found or not assigned to you.');
    }
}

redirect('/band/my_tasks.php');
