<?php
/**
 * ADMIN - Tasks Management System
 * Complete CRUD operations for tasks
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['admin']);

$isApi = isset($_GET['api']) || 
         ($_SERVER['REQUEST_METHOD'] === 'POST' &&
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

if ($isApi) {
    header('Content-Type: application/json');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? null;

}

// LIST TASKS
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
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
        ");
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// GET USERS FOR ASSIGNMENT
if (isset($_GET['api']) && $_GET['api'] === 'users') {
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

// GET STATS
if (isset($_GET['api']) && $_GET['api'] === 'stats') {
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN status = 'todo' THEN 1 END) as todo_count,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_count
            FROM tasks
        ");
        
        echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// CREATE TASK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $assigned_to = !empty($input['assigned_to']) ? (int)$input['assigned_to'] : null;
        $event_id = !empty($input['event_id']) ? (int)$input['event_id'] : null;
        $priority = $input['priority'] ?? 'medium';
        $status = $input['status'] ?? 'todo';
        $due_date = $input['due_date'] ?? null;
        $due_time = $input['due_time'] ?? null;
        
        if (empty($title)) {
            throw new Exception('Task title is required');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, assigned_to, assigned_by, event_id,
                             priority, status, due_date, due_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $title, $description, $assigned_to, $_SESSION['user_id'], $event_id,
            $priority, $status, $due_date, $due_time
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Task created', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// UPDATE TASK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        
        if ($id <= 0) throw new Exception('Invalid task ID');
        
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, assigned_to = ?, event_id = ?,
                priority = ?, status = ?, due_date = ?, due_time = ?
            WHERE task_id = ?
        ");
        
        $stmt->execute([
            $input['title'], $input['description'], $input['assigned_to'], 
            $input['event_id'], $input['priority'], $input['status'],
            $input['due_date'], $input['due_time'], $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Task updated']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($id <= 0) throw new Exception('Invalid task ID');
        
        $completedAt = ($status === 'completed') ? 'NOW()' : 'NULL';
        
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET status = ?, completed_at = $completedAt
            WHERE task_id = ?
        ");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// DELETE TASK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid task ID');
        
        $pdo->prepare("DELETE FROM tasks WHERE task_id = ?")->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Task deleted']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// http_response_code(400);
// echo json_encode(['success' => false, 'message' => 'Invalid request']);
// exit;

?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">

<style>
/* ==========================
   Kanban Board Styles
   ========================== */

/* Kanban Columns */
.tasks-board {
    display: flex;
    gap: 16px;
    margin-top: 20px;
}

.task-column {
    flex: 1;
    background: #1e1e2f;
    padding: 12px;
    border-radius: 8px;
    min-height: 300px;
}

.column-header {
    font-weight: bold;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    color: #f0f0f0;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ==========================
   Task Card Styles
   ========================== */

.task-card {
    background: #2a2a3f;
    padding: 14px;
    border-radius: 8px;
    color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    cursor: pointer;
    transition: transform 0.1s, box-shadow 0.2s;
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}

.task-card h5 {
    margin: 0 0 6px 0;
    font-size: 16px;
}

.task-card p.muted {
    font-size: 13px;
    color: #b0b0b0;
    margin: 4px 0;
}

/* Task Meta */
.task-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-top: 6px;
    color: #aaa;
}

/* Task Footer */
.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

/* ==========================
   Badges (Priority / Status)
   ========================== */

.badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    color: #fff; /* ensures text is visible */
    text-shadow: 0 1px 2px rgba(0,0,0,0.4); /* subtle shadow for readability */
    display: inline-block;
}

/* Badge Colors */
.badge.danger { 
    background: #e74c3c; /* High / Danger */
    color: #f0f0f0;
}
.badge.warning { 
    background: #f39c12; /* Medium / Warning */
    color: #f0f0f0;
}
.badge.info { 
    background: #3498db; /* Low / Info */
    color: #f0f0f0;
}
.badge.success { 
    background: #2ecc71; /* Completed / Success */
    color: #f0f0f0;
}

/* ==========================
   Task Action Buttons
   ========================== */

.actions button {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    color: #fff;
    transition: opacity 0.2s;
}

.actions button:hover { 
    opacity: 0.9; 
}

.actions button:first-child { 
    background: #3498db; 
}

.actions button.danger { 
    background: #e74c3c; 
}

/* ==========================
   Empty State
   ========================== */

.empty-state {
    color: #888;
    font-style: italic;
    text-align: center;
}

/* ==========================
   Modal Overrides
   ========================== */

.modal-content {
    background: #2a2a3f;
    color: #fff;
}

.modal-content input,
.modal-content select,
.modal-content textarea {
    background: #1e1e2f;
    color: #fff;
    border: 1px solid #555;
    border-radius: 4px;
    padding: 6px;
}

.modal-content label { 
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
}
</style>


</head>
<body>
    <div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">⚡</div>
            <h1>SecondPlan</h1>
            <div class="role-badge">Admin</div>
        </div>
        <nav class="nav">
            <a class="nav-item" href="dashboard.php">
                <span>📊</span> <span>Dashboard</span>
            </a>
            <a class="nav-item " href="users.php">
                <span>👥</span> <span>Users</span>
            </a>
            <a class="nav-item" href="bookings.php">
                <span>📅</span> <span>Bookings</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item active" href="tasks.php">
                <span>✓</span> <span>Tasks</span>
            </a>
            <a class="nav-item" href="expenses.php">
                <span>💰</span> <span>Expenses</span>
            </a>
            <a class="nav-item" href="merchandise.php">
                <span>📦</span> <span>Merchandise</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

        <div class="main-content">
            <header class="header">
                <input type="text" placeholder="Search tasks..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn">🔔</button>
                    <div class="user-avatar">👤</div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Task Management</h2>
                        <p class="subtitle">Assign and track tasks for team members</p>
                    </div>
                    <button class="btn-primary" onclick="openAddTaskModal()">
                        + Assign Task
                    </button>
                </div>

                <!-- Task Stats -->
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

                <!-- Kanban Board -->
                <div class="tasks-board">
                    <div class="task-column">
                        <div class="column-header">
                            <h4>📋 To Do</h4>
                            <span class="count" id="todoCount">0</span>
                        </div>
                        <div class="task-list" id="todoList">
                            <div class="loading">Loading...</div>
                        </div>
                    </div>

                    <div class="task-column">
                        <div class="column-header">
                            <h4>🔄 In Progress</h4>
                            <span class="count" id="progressCount">0</span>
                        </div>
                        <div class="task-list" id="progressList">
                            <div class="loading">Loading...</div>
                        </div>
                    </div>

                    <div class="task-column">
                        <div class="column-header">
                            <h4>✅ Completed</h4>
                            <span class="count" id="completedCount">0</span>
                        </div>
                        <div class="task-list" id="completedList">
                            <div class="loading">Loading...</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal" id="addTaskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Task</h3>
                <button class="close-btn" onclick="closeAddTaskModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assignee" required>
                            <option value="">Select member</option>
                            <option value="Ahmad Rahman">Ahmad Rahman</option>
                            <option value="Sarah Lee">Sarah Lee</option>
                            <option value="John Tan">John Tan</option>
                            <option value="Zul Hisham">Zul Hisham</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeAddTaskModal()">Cancel</button>
                <button class="btn-primary" onclick="saveTask()">Create Task</button>
            </div>
        </div>
    </div>

    <script src="assets/js/tasks.js"></script>
</body>
</html>