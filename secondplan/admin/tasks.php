<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
requireRole([ROLE_ADMIN]);

$method = $_SERVER['REQUEST_METHOD'];
$api = $_GET['api'] ?? null;
$postData = [];

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $postData = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $postData = $_POST;
    }
}

$action = $api ?: ($postData['action'] ?? null);

if ($method === 'GET' && $action === 'list') {
    header('Content-Type: application/json');
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $total = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();

        $stmt = $pdo->query("
            SELECT
                t.*,
                u1.name as assigned_to_name,
                u2.name as assigned_by_name,
                e.title as event_title
            FROM tasks t
            LEFT JOIN users u1 ON t.assigned_to = u1.user_id
            LEFT JOIN users u2 ON t.assigned_by = u2.user_id
            LEFT JOIN events e ON t.event_id = e.event_id
            ORDER BY
                FIELD(t.status, 'todo', 'in_progress', 'completed', 'cancelled'),
                FIELD(t.priority, 'urgent', 'high', 'medium', 'low'),
                t.due_date ASC
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ");

        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'total_pages' => (int)ceil($total / $perPage)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'users') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT u.user_id, u.name, u.email, GROUP_CONCAT(r.role_name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.status = 'active'
            GROUP BY u.user_id
            ORDER BY u.name
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'stats') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN status = 'todo' THEN 1 END) as todo_count,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
            FROM tasks
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && (!$action || $action === 'create')) {
    header('Content-Type: application/json');
    try {
        $title = trim($postData['title'] ?? '');
        $description = trim($postData['description'] ?? '');
        $assigned_to = !empty($postData['assigned_to']) ? (int)$postData['assigned_to'] : null;
        $event_id = !empty($postData['event_id']) ? (int)$postData['event_id'] : null;
        $priority = $postData['priority'] ?? 'medium';
        $status = $postData['status'] ?? 'todo';
        $due_date = $postData['due_date'] ?? null;
        $due_time = $postData['due_time'] ?? null;

        if (empty($title)) {
            throw new Exception('Task title is required');
        }

        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, assigned_to, assigned_by, event_id, priority, status, due_date, due_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $assigned_to, $_SESSION['user_id'], $event_id, $priority, $status, $due_date, $due_time]);

        if ($assigned_to) {
            createNotification($assigned_to, 'task', 'New Task Assigned', 'You have been assigned: ' . $title, '/band/my_tasks.php');

            $assignee = $pdo->prepare("SELECT email, name FROM users WHERE user_id = ?");
            $assignee->execute([$assigned_to]);
            $assigneeData = $assignee->fetch();
            if ($assigneeData && $assigneeData['email']) {
                $dueDateFormatted = $due_date ? date('d M Y', strtotime($due_date)) . ($due_time ? ' ' . $due_time : '') : 'Not set';
                sendTaskAssignedEmail($assigneeData['email'], $assigneeData['name'], $title, $description, $priority, $dueDateFormatted);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Task created', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update') {
    header('Content-Type: application/json');
    try {
        $id = (int)($postData['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid task ID');

        $stmt = $pdo->prepare("
            UPDATE tasks
            SET title = ?, description = ?, assigned_to = ?, event_id = ?,
                priority = ?, status = ?, due_date = ?, due_time = ?
            WHERE task_id = ?
        ");
        $stmt->execute([
            $postData['title'], $postData['description'], $postData['assigned_to'],
            $postData['event_id'], $postData['priority'], $postData['status'],
            $postData['due_date'], $postData['due_time'], $id
        ]);

        echo json_encode(['success' => true, 'message' => 'Task updated']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update_status') {
    header('Content-Type: application/json');
    try {
        $id = (int)($postData['id'] ?? 0);
        $status = $postData['status'] ?? '';
        if ($id <= 0) throw new Exception('Invalid task ID');

        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ? WHERE task_id = ?");
        $stmt->execute([$status, $completedAt, $id]);

        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = (int)($postData['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid task ID');

        $pdo->prepare("DELETE FROM tasks WHERE task_id = ?")->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Task deleted']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search tasks..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Task Management</h2>
                        <p class="subtitle">Assign and track tasks for team members</p>
                    </div>
                    <button class="btn-primary" onclick="openAddTaskModal()">
                        <i class="bi bi-plus-circle btn-icon"></i> Assign Task
                    </button>
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalTasks">0</div>
                        <div class="mini-stat-label">Total Tasks</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="todoTasks">0</div>
                        <div class="mini-stat-label">To Do</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="inProgressTasks">0</div>
                        <div class="mini-stat-label">In Progress</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="completedTasks">0</div>
                        <div class="mini-stat-label">Completed</div>
                    </div>
                </div>

                <div class="tasks-board">
                    <div class="task-column">
                        <div class="column-header">
                            <h4>To Do</h4>
                            <span id="todoCount">0</span>
                        </div>
                        <div class="task-list" id="todoList">
                            <div class="empty-state">Loading...</div>
                        </div>
                    </div>
                    <div class="task-column">
                        <div class="column-header">
                            <h4>In Progress</h4>
                            <span id="progressCount">0</span>
                        </div>
                        <div class="task-list" id="progressList">
                            <div class="empty-state">Loading...</div>
                        </div>
                    </div>
                    <div class="task-column">
                        <div class="column-header">
                            <h4>Completed</h4>
                            <span id="completedCount">0</span>
                        </div>
                        <div class="task-list" id="completedList">
                            <div class="empty-state">Loading...</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="addTaskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Task</h3>
                <button class="close-btn" onclick="closeAddTaskModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm" class="form-grid">
                    <div class="form-group full-width">
                        <label>Task Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to" id="assigneeSelect" required>
                            <option value="">Select member</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label>Due Time</label>
                        <input type="time" name="due_time">
                    </div>
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Task details..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeAddTaskModal()">Cancel</button>
                <button class="btn-primary" onclick="saveTask()"><i class="bi bi-floppy btn-icon"></i> Create Task</button>
            </div>
        </div>
    </div>

    <div class="modal" id="taskDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Task Details</h3>
                <button class="close-btn" onclick="closeTaskDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="taskDetailBody"></div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeTaskDetailModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/tasks.js"></script>
</body>
</html>
