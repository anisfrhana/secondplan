<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'view' && $id > 0) {
        $stmt = $pdo->prepare("
            SELECT t.*, e.title as event_title, u.name as assigned_to_name
            FROM tasks t
            LEFT JOIN events e ON t.event_id = e.event_id
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE t.task_id = ? AND t.assigned_to = ?
        ");
        $stmt->execute([$id, $userId]);
        $task = $stmt->fetch();

        if ($task) {
            jsonSuccess('OK', $task);
        } else {
            jsonError('Task not found');
        }
        exit;
    }

    $assignedTo = $userId;
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    $sql = "SELECT task_id, title, description, due_date, due_time, priority, status FROM tasks WHERE assigned_to = ? AND status != 'cancelled'";
    $params = [$assignedTo];

    if ($start) {
        $sql .= " AND due_date >= ?";
        $params[] = $start;
    }
    if ($end) {
        $sql .= " AND due_date <= ?";
        $params[] = $end;
    }

    $sql .= " ORDER BY due_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    $format = $_GET['format'] ?? 'calendar';

    if ($format === 'list') {
        jsonSuccess('OK', $tasks);
    }

    $calendarTasks = [];
    foreach ($tasks as $task) {
        $colors = [
            'urgent' => '#ef4444',
            'high' => '#f97316',
            'medium' => '#3b82f6',
            'low' => '#6b7280',
        ];

        $calendarTasks[] = [
            'id' => $task['task_id'],
            'title' => $task['title'],
            'start' => $task['due_date'] . ($task['due_time'] ? 'T' . $task['due_time'] : ''),
            'color' => $colors[$task['priority']] ?? '#3b82f6',
            'groupId' => 'task',
            'extendedProps' => [
                'type' => 'task',
                'priority' => $task['priority'],
                'status' => $task['status'],
                'description' => $task['description'],
            ],
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($calendarTasks);
    exit;
}

if ($method === 'POST') {
    header('Content-Type: application/json');

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $postData = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $postData = $_POST;
    }

    $csrfToken = $postData['csrf'] ?? $_POST['csrf'] ?? '';
    if (!verifyCSRF($csrfToken)) {
        jsonError('Invalid request', 403);
    }

    $action = $postData['action'] ?? $_GET['action'] ?? null;
    $id = (int)($postData['id'] ?? $_GET['id'] ?? 0);

    if ($action === 'update_status' && $id > 0) {
        $status = $postData['status'] ?? $_GET['status'] ?? '';
        $allowed = ['todo', 'in_progress', 'completed'];

        if (!in_array($status, $allowed)) {
            jsonError('Invalid status');
            exit;
        }

        $stmt = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id = ? AND assigned_to = ?");
        $stmt->execute([$id, $userId]);
        if (!$stmt->fetch()) {
            jsonError('Task not found or not assigned to you');
            exit;
        }

        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ? WHERE task_id = ?");
        $stmt->execute([$status, $completedAt, $id]);

        jsonSuccess('Status updated');
        exit;
    }

    if ($action === 'complete' && $id > 0) {
        $stmt = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id = ? AND assigned_to = ?");
        $stmt->execute([$id, $userId]);
        if (!$stmt->fetch()) {
            jsonError('Task not found or not assigned to you');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE task_id = ?");
        $stmt->execute([$id]);

        jsonSuccess('Task completed');
        exit;
    }

    jsonError('Invalid action');
    exit;
}
