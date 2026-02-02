<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();  // Ensure user is logged in
require_role(['admin', 'manager']); // Restrict access if needed
header('Content-Type: application/json');

// Connect to database
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// ------------------ GET API ------------------
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $tasks]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ------------------ POST ------------------
$input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? $input['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------- CREATE TASK ----------
    if (!$action && $input) {
        try {
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $assignee = trim($input['assignee'] ?? '');
            $dueDate = $input['dueDate'] ?? null;
            $priority = $input['priority'] ?? 'medium';
            $status = 'todo';

            if (!$title || !$assignee || !$dueDate) {
                throw new Exception('Missing required fields');
            }

            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assignee, due_date, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $assignee, $dueDate, $priority, $status]);

            echo json_encode(['success' => true, 'message' => 'Task created', 'id' => $pdo->lastInsertId()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // -------- DELETE TASK ----------
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Task deleted']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// ------------------ Default HTML Table for Testing ------------------
try {
    $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Task List</h1>";
    echo "<table border='1' cellpadding='6' cellspacing='0'>
            <tr><th>ID</th><th>Title</th><th>Description</th><th>Assignee</th><th>Due Date</th><th>Priority</th><th>Status</th></tr>";
    foreach ($tasks as $t) {
        echo "<tr>
            <td>{$t['id']}</td>
            <td>{$t['title']}</td>
            <td>{$t['description']}</td>
            <td>{$t['assignee']}</td>
            <td>{$t['due_date']}</td>
            <td>{$t['priority']}</td>
            <td>{$t['status']}</td>
        </tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error loading tasks: " . $e->getMessage();
}

exits;
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon">⚡</div>
                <h1>SecondPlan</h1>
            </div>
            <nav class="nav">
                <a class="nav-item" href="dashboard.html">📊 <span>Dashboard</span></a>
                <a class="nav-item" href="bookings.html">📅 <span>Bookings</span></a>
                <a class="nav-item" href="events.html">🎤 <span>Events</span></a>
                <a class="nav-item active" href="tasks.html">✓ <span>Tasks</span></a>
                <a class="nav-item" href="expenses.html">💰 <span>Expenses</span></a>
                <a class="nav-item" href="merchandise.html">📦 <span>Merchandise</span></a>
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