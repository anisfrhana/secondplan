<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); // Optional: ensure user is logged in
header('Content-Type: application/json');

// Connect to DB
try {
    $pdo = $pdo ?? new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Collect POST data
$title      = trim($_POST['title'] ?? '');
$assignee   = trim($_POST['assignee'] ?? '');
$due_date   = trim($_POST['due_date'] ?? '');
$priority   = trim($_POST['priority'] ?? 'medium');
$description = trim($_POST['description'] ?? '');

// Validation
$errors = [];

if (!$title) {
    $errors[] = 'Task title is required';
}

if (!$assignee) {
    $errors[] = 'Assignee is required';
}

if (!$due_date) {
    $errors[] = 'Due date is required';
} else {
    $selected = strtotime($due_date);
    $today = strtotime(date('Y-m-d'));
    if ($selected < $today) {
        $errors[] = 'Due date cannot be in the past';
    }
}

$valid_priorities = ['low','medium','high','urgent'];
if (!in_array($priority, $valid_priorities)) {
    $priority = 'medium';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errors]);
    exit;
}

// Insert into DB
try {
    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, assignee, due_date, priority, description)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $assignee, $due_date, $priority, $description]);

    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
?>  

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Task - SecondPlan</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .priority-selector {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-top: 8px;
    }
    .priority-option {
      padding: 12px;
      border: 2px solid var(--border);
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: rgba(30, 41, 59, 0.3);
    }
    .priority-option:hover {
      border-color: var(--blue);
      background: rgba(59, 130, 246, 0.1);
    }
    .priority-option.selected {
      border-width: 2px;
    }
    .priority-option.low.selected {
      border-color: var(--green);
      background: rgba(34, 197, 94, 0.1);
    }
    .priority-option.medium.selected {
      border-color: var(--blue);
      background: rgba(59, 130, 246, 0.1);
    }
    .priority-option.high.selected {
      border-color: var(--yellow);
      background: rgba(234, 179, 8, 0.1);
    }
    .priority-option.urgent.selected {
      border-color: var(--red);
      background: rgba(239, 68, 68, 0.1);
    }
    .priority-icon {
      font-size: 24px;
      margin-bottom: 4px;
    }
    .priority-label {
      font-size: 13px;
      font-weight: 500;
    }
    .assignee-suggestions {
      margin-top: 8px;
      display: none;
      flex-wrap: wrap;
      gap: 6px;
    }
    .assignee-suggestions.show {
      display: flex;
    }
    .assignee-chip {
      padding: 6px 12px;
      background: rgba(59, 130, 246, 0.1);
      border: 1px solid rgba(59, 130, 246, 0.3);
      border-radius: 999px;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .assignee-chip:hover {
      background: rgba(59, 130, 246, 0.2);
      border-color: rgba(59, 130, 246, 0.5);
    }
    .validation-error {
      color: var(--red);
      font-size: 13px;
      margin-top: 4px;
      display: none;
    }
    .field.error input,
    .field.error textarea,
    .field.error select {
      border-color: var(--red);
    }
    .field.error .validation-error {
      display: block;
    }
    .task-summary {
      grid-column: 1/-1;
      padding: 20px;
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.5));
      border: 1px solid var(--border);
      border-radius: 12px;
      margin-top: 12px;
      display: none;
    }
    .task-summary.show {
      display: block;
    }
    .task-summary h4 {
      margin-bottom: 12px;
      color: var(--text-primary);
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid rgba(51, 65, 85, 0.3);
    }
    .summary-row:last-child {
      border-bottom: none;
    }
    .summary-label {
      color: var(--text-secondary);
      font-size: 14px;
    }
    .summary-value {
      font-weight: 500;
      color: var(--text-primary);
    }
  </style>
</head>
<body data-page="add_task">
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
  
  <main class="content">
    <div class="page-header">
      <div>
        <h2>Assign New Task</h2>
        <p class="subtitle">Create and assign a task to a team member</p>
      </div>
    </div>
    
    <form id="form" class="form" novalidate>
      <div class="field" style="grid-column:1/-1">
        <label>Task Title *</label>
        <input id="title" name="title" required placeholder="e.g., Setup sound system for event">
        <div class="validation-error">Task title is required</div>
      </div>
      
      <div class="field">
        <label>Assign To *</label>
        <input id="assignee" name="assignee" required placeholder="Enter name">
        <div class="validation-error">Assignee is required</div>
        <div id="assignee-suggestions" class="assignee-suggestions">
          <div class="assignee-chip" onclick="selectAssignee('Ahmad Rahman')">Ahmad Rahman</div>
          <div class="assignee-chip" onclick="selectAssignee('Sarah Lee')">Sarah Lee</div>
          <div class="assignee-chip" onclick="selectAssignee('John Tan')">John Tan</div>
          <div class="assignee-chip" onclick="selectAssignee('Maria Chen')">Maria Chen</div>
        </div>
      </div>
      
      <div class="field">
        <label>Due Date *</label>
        <input id="due_date" name="due_date" type="date" required>
        <div class="validation-error">Due date is required</div>
      </div>
      
      <div class="field" style="grid-column:1/-1">
        <label>Priority *</label>
        <input id="priority" name="priority" type="hidden" value="medium">
        <div class="priority-selector">
          <div class="priority-option low" onclick="selectPriority('low')">
            <div class="priority-icon">🟢</div>
            <div class="priority-label">Low</div>
          </div>
          <div class="priority-option medium selected" onclick="selectPriority('medium')">
            <div class="priority-icon">🔵</div>
            <div class="priority-label">Medium</div>
          </div>
          <div class="priority-option high" onclick="selectPriority('high')">
            <div class="priority-icon">🟡</div>
            <div class="priority-label">High</div>
          </div>
          <div class="priority-option urgent" onclick="selectPriority('urgent')">
            <div class="priority-icon">🔴</div>
            <div class="priority-label">Urgent</div>
          </div>
        </div>
      </div>
      
      <div class="field" style="grid-column:1/-1">
        <label>Description (Optional)</label>
        <textarea id="description" name="description" rows="4" placeholder="Task details, requirements, notes..."></textarea>
      </div>
      
      <div id="task-summary" class="task-summary">
        <h4>Task Summary</h4>
        <div class="summary-row">
          <span class="summary-label">Title:</span>
          <span class="summary-value" id="summary-title">—</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Assigned to:</span>
          <span class="summary-value" id="summary-assignee">—</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Due date:</span>
          <span class="summary-value" id="summary-date">—</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Priority:</span>
          <span class="summary-value" id="summary-priority">Medium</span>
        </div>
      </div>
      
      <div class="actions">
        <a class="btn-secondary" href="tasks.html">Cancel</a>
        <button id="submit-btn" class="btn-primary" type="submit">
          <span id="submit-text">Create Task</span>
        </button>
      </div>
    </form>
  </main>
  
  <script src="assets/js/add_task.js"></script>
  
  
  <div class="toast"></div>
</body>
</html>
