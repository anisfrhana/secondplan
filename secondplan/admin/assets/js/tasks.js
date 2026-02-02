// Sample tasks data
let tasks = [
    { id: 1, title: 'Setup sound system', description: 'Install and test all audio equipment', assignee: 'Ahmad Rahman', dueDate: '2025-02-08', priority: 'high', status: 'todo' },
    { id: 2, title: 'Rehearsal for Jazz Night', description: 'Full band rehearsal with setlist', assignee: 'Sarah Lee', dueDate: '2025-02-11', priority: 'medium', status: 'in-progress' },
    { id: 3, title: 'Equipment inventory check', description: 'Verify all equipment is working', assignee: 'John Tan', dueDate: '2025-02-07', priority: 'urgent', status: 'todo' },
    { id: 4, title: 'Load equipment into van', description: 'Pack and secure all gear for transport', assignee: 'Ahmad Rahman', dueDate: '2025-02-08', priority: 'high', status: 'in-progress' },
    { id: 5, title: 'Promote event on social media', description: 'Create posts for FB, IG, Twitter', assignee: 'Sarah Lee', dueDate: '2025-02-06', priority: 'medium', status: 'completed' }
];

let selectedTaskId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
    updateStats();
    setupSearch();
});

// Load tasks from PHP backend
async function loadTasksFromDB() {
    try {
        const response = await fetch('tasks.php?api=list');
        const data = await response.json();
        if (data.success && data.data) {
            tasks = data.data.map(t => ({
                id: t.id,
                title: t.title || t.task_name,
                description: t.description || '',
                assignee: t.assigned_to || t.assignee,
                dueDate: t.due_date || t.deadline,
                priority: t.priority || 'medium',
                status: t.status || 'todo'
            }));
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}

// Load and display tasks
function loadTasks() {
    const todoList = document.getElementById('todoList');
    const progressList = document.getElementById('progressList');
    const completedList = document.getElementById('completedList');
    
    const todoTasks = tasks.filter(t => t.status === 'todo');
    const progressTasks = tasks.filter(t => t.status === 'in-progress');
    const completedTasks = tasks.filter(t => t.status === 'completed');
    
    // Update counts
    document.getElementById('todoCount').textContent = todoTasks.length;
    document.getElementById('progressCount').textContent = progressTasks.length;
    document.getElementById('completedCount').textContent = completedTasks.length;
    
    // Render todo tasks
    todoList.innerHTML = todoTasks.length === 0 
        ? '<div class="empty-state">No tasks</div>'
        : todoTasks.map(task => renderTaskCard(task)).join('');
    
    // Render in-progress tasks
    progressList.innerHTML = progressTasks.length === 0
        ? '<div class="empty-state">No tasks</div>'
        : progressTasks.map(task => renderTaskCard(task)).join('');
    
    // Render completed tasks
    completedList.innerHTML = completedTasks.length === 0
        ? '<div class="empty-state">No tasks</div>'
        : completedTasks.map(task => renderTaskCard(task)).join('');
}

// Render task card
function renderTaskCard(task) {
    return `
        <div class="task-card" onclick="viewTask(${task.id})">
            <h5>${task.title}</h5>
            <p style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">${task.description}</p>
            <div class="task-meta">
                <span>👤 ${task.assignee}</span>
                <span>📅 Due: ${task.dueDate}</span>
            </div>
            <div class="task-footer">
                <span class="badge ${getPriorityClass(task.priority)}">${task.priority.toUpperCase()}</span>
                <div style="display: flex; gap: 8px;">
                    ${task.status !== 'completed' ? `
                        <button onclick="event.stopPropagation(); moveTask(${task.id}, 'next')" style="padding: 4px 8px; background: var(--blue); border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 11px;">
                            ${task.status === 'todo' ? 'Start' : 'Complete'}
                        </button>
                    ` : ''}
                    <button onclick="event.stopPropagation(); deleteTask(${task.id})" style="padding: 4px 8px; background: var(--red); border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 11px;">Delete</button>
                </div>
            </div>
        </div>
    `;
}

// Move task to next status
function moveTask(id, direction) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;
    
    const statusFlow = ['todo', 'in-progress', 'completed'];
    const currentIndex = statusFlow.indexOf(task.status);
    
    if (direction === 'next' && currentIndex < statusFlow.length - 1) {
        task.status = statusFlow[currentIndex + 1];
    } else if (direction === 'prev' && currentIndex > 0) {
        task.status = statusFlow[currentIndex - 1];
    }
    
    loadTasks();
    updateStats();
}

// Update statistics
function updateStats() {
    const total = tasks.length;
    const todo = tasks.filter(t => t.status === 'todo').length;
    const inProgress = tasks.filter(t => t.status === 'in-progress').length;
    const completed = tasks.filter(t => t.status === 'completed').length;
    
    document.getElementById('totalTasks').textContent = total;
    document.getElementById('todoTasks').textContent = todo;
    document.getElementById('inProgressTasks').textContent = inProgress;
    document.getElementById('completedTasks').textContent = completed;
}

// View task details
function viewTask(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;
    
    alert(`Task: ${task.title}\n\nDescription: ${task.description}\nAssigned to: ${task.assignee}\nDue: ${task.dueDate}\nPriority: ${task.priority}\nStatus: ${task.status}`);
}

// Open add task modal
function openAddTaskModal() {
    document.getElementById('addTaskModal').classList.add('active');
}

// Close add task modal
function closeAddTaskModal() {
    document.getElementById('addTaskModal').classList.remove('active');
    document.getElementById('addTaskForm').reset();
}

// Save task
async function saveTask() {
    const form = document.getElementById('addTaskForm');
    const formData = new FormData(form);
    
    const newTask = {
        id: tasks.length + 1,
        title: formData.get('title'),
        description: formData.get('description'),
        assignee: formData.get('assignee'),
        dueDate: formData.get('due_date'),
        priority: formData.get('priority'),
        status: 'todo'
    };
    
    try {
        const response = await fetch('add_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newTask)
        });
        
        const data = await response.json();
        
        if (data.success) {
            tasks.push({ ...newTask, id: data.id || newTask.id });
            loadTasks();
            updateStats();
            closeAddTaskModal();
            alert('Task created successfully!');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        // Fallback for demo
        tasks.push(newTask);
        loadTasks();
        updateStats();
        closeAddTaskModal();
        alert('Task created!');
    }
}

// Delete task
function deleteTask(id) {
    if (!confirm('Are you sure you want to delete this task?')) return;
    
    tasks = tasks.filter(t => t.id !== id);
    loadTasks();
    updateStats();
}

// Helper function
function getPriorityClass(priority) {
    const priorityMap = {
        'urgent': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'success'
    };
    return priorityMap[priority] || 'info';
}

// Search functionality
function setupSearch() {
    const searchBox = document.getElementById('searchBox');
    searchBox?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = tasks.filter(t => 
            t.title.toLowerCase().includes(query) ||
            t.description.toLowerCase().includes(query) ||
            t.assignee.toLowerCase().includes(query)
        );
        
        // Filter and display
        const todoList = document.getElementById('todoList');
        const progressList = document.getElementById('progressList');
        const completedList = document.getElementById('completedList');
        
        const todoTasks = filtered.filter(t => t.status === 'todo');
        const progressTasks = filtered.filter(t => t.status === 'in-progress');
        const completedTasks = filtered.filter(t => t.status === 'completed');
        
        todoList.innerHTML = todoTasks.length === 0 
            ? '<div class="empty-state">No tasks</div>'
            : todoTasks.map(task => renderTaskCard(task)).join('');
        
        progressList.innerHTML = progressTasks.length === 0
            ? '<div class="empty-state">No tasks</div>'
            : progressTasks.map(task => renderTaskCard(task)).join('');
        
        completedList.innerHTML = completedTasks.length === 0
            ? '<div class="empty-state">No tasks</div>'
            : completedTasks.map(task => renderTaskCard(task)).join('');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addTaskModal');
    if (event.target === modal) {
        closeAddTaskModal();
    }
}