let tasks = [];
let selectedTaskId = null;

// ===============================
// INIT
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
    loadStats();
    setupSearch();
});

// ===============================
// LOAD TASKS
// ===============================
async function loadTasks() {
    try {
        const res = await fetch('tasks.php?api=list');
        const json = await res.json();

        if (!json.success) throw new Error(json.message);

        tasks = json.data.map(t => ({
            id: t.task_id,
            title: t.title,
            description: t.description || '',
            assignee: t.assigned_to_name || 'Unassigned',
            dueDate: t.due_date || '',
            priority: t.priority,
            status: t.status
        }));

        renderTasks();
    } catch (err) {
        console.error(err);
    }
}

// ===============================
// LOAD STATS
// ===============================
async function loadStats() {
    try {
        const res = await fetch('tasks.php?api=stats');
        const json = await res.json();

        if (!json.success) return;

        document.getElementById('totalTasks').textContent = json.data.total_tasks;
        document.getElementById('todoTasks').textContent = json.data.todo_count;
        document.getElementById('inProgressTasks').textContent = json.data.in_progress_count;
        document.getElementById('completedTasks').textContent = json.data.completed_count;
    } catch (err) {
        console.error(err);
    }
}

// ===============================
// RENDER BOARD
// ===============================
function renderTasks() {
    const todo = document.getElementById('todoList');
    const progress = document.getElementById('progressList');
    const completed = document.getElementById('completedList');

    const todoTasks = tasks.filter(t => t.status === 'todo');
    const progressTasks = tasks.filter(t => t.status === 'in_progress');
    const completedTasks = tasks.filter(t => t.status === 'completed');

    document.getElementById('todoCount').textContent = todoTasks.length;
    document.getElementById('progressCount').textContent = progressTasks.length;
    document.getElementById('completedCount').textContent = completedTasks.length;

    todo.innerHTML = renderColumn(todoTasks);
    progress.innerHTML = renderColumn(progressTasks);
    completed.innerHTML = renderColumn(completedTasks);
}

function renderColumn(list) {
    if (!list.length) return `<div class="empty-state">No tasks</div>`;
    return list.map(renderTaskCard).join('');
}

// ===============================
// TASK CARD
// ===============================
function renderTaskCard(task) {
    return `
    <div class="task-card" onclick="viewTask(${task.id})">
        <h5>${task.title}</h5>
        <p class="muted">${task.description}</p>

        <div class="task-meta">
            <span>👤 ${task.assignee}</span>
            <span>📅 ${task.dueDate || '-'}</span>
        </div>

        <div class="task-footer">
            <span class="badge ${priorityClass(task.priority)}">
                ${task.priority.toUpperCase()}
            </span>

            <div class="actions">
                ${task.status !== 'completed' ? `
                <button onclick="event.stopPropagation(); advanceTask(${task.id})">
                    ${task.status === 'todo' ? 'Start' : 'Complete'}
                </button>` : ''}
                <button class="danger"
                        onclick="event.stopPropagation(); deleteTask(${task.id})">
                    Delete
                </button>
            </div>
        </div>
    </div>`;
}

// ===============================
// ADVANCE STATUS
// ===============================
async function advanceTask(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;

    const next =
        task.status === 'todo' ? 'in_progress' :
        task.status === 'in_progress' ? 'completed' :
        task.status;

    try {
        await fetch('tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_status',
                id,
                status: next
            })
        });

        task.status = next;
        renderTasks();
        loadStats();
    } catch (err) {
        console.error(err);
    }
}

// ===============================
// ADD TASK
// ===============================
async function saveTask() {
    const form = document.getElementById('addTaskForm');
    const fd = new FormData(form);

    const payload = {
        title: fd.get('title'),
        description: fd.get('description'),
        priority: fd.get('priority'),
        due_date: fd.get('due_date'),
        status: 'todo'
    };

    try {
        const res = await fetch('tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        closeAddTaskModal();
        loadTasks();
        loadStats();
    } catch (err) {
        alert(err.message);
    }
}

// ===============================
// DELETE
// ===============================
async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;

    try {
        await fetch('tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });

        tasks = tasks.filter(t => t.id !== id);
        renderTasks();
        loadStats();
    } catch (err) {
        console.error(err);
    }
}

// ===============================
// VIEW
// ===============================
function viewTask(id) {
    const t = tasks.find(x => x.id === id);
    if (!t) return;

    alert(
        `Task: ${t.title}\n\n` +
        `Assigned to: ${t.assignee}\n` +
        `Due: ${t.dueDate || '-'}\n` +
        `Priority: ${t.priority}\n` +
        `Status: ${t.status}`
    );
}

// ===============================
// SEARCH
// ===============================
function setupSearch() {
    document.getElementById('searchBox').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        const filtered = tasks.filter(t =>
            t.title.toLowerCase().includes(q) ||
            t.description.toLowerCase().includes(q) ||
            t.assignee.toLowerCase().includes(q)
        );

        renderFiltered(filtered);
    });
}

function renderFiltered(list) {
    const todo = list.filter(t => t.status === 'todo');
    const progress = list.filter(t => t.status === 'in_progress');
    const completed = list.filter(t => t.status === 'completed');

    document.getElementById('todoList').innerHTML = renderColumn(todo);
    document.getElementById('progressList').innerHTML = renderColumn(progress);
    document.getElementById('completedList').innerHTML = renderColumn(completed);
}

// ===============================
// HELPERS
// ===============================
function priorityClass(p) {
    return {
        urgent: 'danger',
        high: 'warning',
        medium: 'info',
        low: 'success'
    }[p] || 'info';
}

// ===============================
// MODAL
// ===============================
function openAddTaskModal() {
    document.getElementById('addTaskModal').classList.add('active');
}
function closeAddTaskModal() {
    document.getElementById('addTaskModal').classList.remove('active');
    document.getElementById('addTaskForm').reset();
}
