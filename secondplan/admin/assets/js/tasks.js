var tasks = [];
var selectedTaskId = null;
var searchQuery = '';

document.addEventListener('DOMContentLoaded', function() {
    loadTasks();
    loadStats();
    loadAssignees();
    setupSearch();
});

function loadTasks() {
    fetch('tasks.php?api=list')
        .then(function(res) {
            if (!res.ok) throw new Error('Server error');
            return res.json();
        })
        .then(function(json) {
            if (json.success && json.data) {
                tasks = json.data.map(function(t) {
                    return {
                        id: t.task_id,
                        title: t.title,
                        description: t.description || '',
                        assignee: t.assigned_to_name || 'Unassigned',
                        assigneeId: t.assigned_to,
                        dueDate: t.due_date || '',
                        dueTime: t.due_time || '',
                        priority: t.priority,
                        status: t.status,
                        eventTitle: t.event_title || ''
                    };
                });
            }
            renderTasks();
        })
        .catch(function() {
            showToast('Failed to load tasks', 'error');
            renderTasks();
        });
}

function loadStats() {
    fetch('tasks.php?api=stats')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success && json.data) {
                document.getElementById('totalTasks').textContent = json.data.total_tasks;
                document.getElementById('todoTasks').textContent = json.data.todo_count;
                document.getElementById('inProgressTasks').textContent = json.data.in_progress_count;
                document.getElementById('completedTasks').textContent = json.data.completed_count;
            }
        })
        .catch(function() {});
}

function loadAssignees() {
    fetch('tasks.php?api=users')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success && json.data) {
                var select = document.getElementById('assigneeSelect');
                json.data.forEach(function(u) {
                    var opt = document.createElement('option');
                    opt.value = u.user_id;
                    opt.textContent = u.name + (u.roles ? ' (' + u.roles + ')' : '');
                    select.appendChild(opt);
                });
            }
        })
        .catch(function() {});
}

function renderTasks() {
    var filtered = tasks.slice();
    if (searchQuery) {
        filtered = filtered.filter(function(t) {
            return t.title.toLowerCase().indexOf(searchQuery) !== -1 ||
                t.description.toLowerCase().indexOf(searchQuery) !== -1 ||
                t.assignee.toLowerCase().indexOf(searchQuery) !== -1;
        });
    }

    var todoTasks = filtered.filter(function(t) { return t.status === 'todo'; });
    var progressTasks = filtered.filter(function(t) { return t.status === 'in_progress'; });
    var completedTasks = filtered.filter(function(t) { return t.status === 'completed'; });

    document.getElementById('todoCount').textContent = todoTasks.length;
    document.getElementById('progressCount').textContent = progressTasks.length;
    document.getElementById('completedCount').textContent = completedTasks.length;

    document.getElementById('todoList').innerHTML = renderColumn(todoTasks);
    document.getElementById('progressList').innerHTML = renderColumn(progressTasks);
    document.getElementById('completedList').innerHTML = renderColumn(completedTasks);
}

function renderColumn(list) {
    if (!list.length) return '<div class="empty-state">No tasks</div>';
    return list.map(function(t) {
        var advanceBtn = '';
        if (t.status === 'todo') {
            advanceBtn = '<button onclick="event.stopPropagation();advanceTask(' + t.id + ')" class="btn-primary btn-small">' + icon('play') + ' Start</button>';
        } else if (t.status === 'in_progress') {
            advanceBtn = '<button onclick="event.stopPropagation();advanceTask(' + t.id + ')" class="btn-success btn-small">' + icon('check') + ' Complete</button>';
        }

        return '<div class="task-card" onclick="viewTask(' + t.id + ')">' +
            '<h5>' + esc(t.title) + '</h5>' +
            '<p class="task-desc">' + esc(t.description) + '</p>' +
            '<div class="task-meta">' +
                '<span class="assignee-tag">' + icon('eye') + ' ' + esc(t.assignee) + '</span>' +
                '<span>' + (t.dueDate || '-') + '</span>' +
            '</div>' +
            '<div class="task-footer">' +
                '<span class="badge badge-' + priorityClass(t.priority) + '">' + (t.priority || '').toUpperCase() + '</span>' +
                '<div class="task-actions">' +
                    advanceBtn +
                    '<button onclick="event.stopPropagation();deleteTask(' + t.id + ')" class="btn-danger btn-small">' + icon('trash') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function viewTask(id) {
    var t = tasks.find(function(x) { return x.id == id; });
    if (!t) return;
    selectedTaskId = id;

    var statusLabel = (t.status || '').replace('_', ' ').toUpperCase();

    var html =
        '<div style="display:flex;flex-direction:column;">' +
            '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                '<div style="color:var(--text-secondary);font-size:13px;">Title</div>' +
                '<div style="font-size:18px;font-weight:600;margin-top:4px;">' + esc(t.title) + '</div>' +
            '</div>';
    if (t.description) {
        html += '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
            '<div style="color:var(--text-secondary);font-size:13px;">Description</div>' +
            '<div style="font-size:14px;margin-top:4px;line-height:1.6;">' + esc(t.description) + '</div>' +
        '</div>';
    }
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Assigned To</div><div style="font-size:14px;margin-top:4px;font-weight:600;">' + esc(t.assignee) + '</div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Priority</div><div style="margin-top:4px;"><span class="badge badge-' + priorityClass(t.priority) + '">' + (t.priority || '').toUpperCase() + '</span></div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="font-size:14px;margin-top:4px;">' + statusLabel + '</div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Due Date</div><div style="font-size:14px;margin-top:4px;">' + (t.dueDate || 'Not set') + (t.dueTime ? ' ' + t.dueTime : '') + '</div></div>' +
        '</div>';
    if (t.eventTitle) {
        html += '<div>' +
            '<div style="color:var(--text-secondary);font-size:13px;">Event</div>' +
            '<div style="font-size:14px;margin-top:4px;">' + esc(t.eventTitle) + '</div>' +
        '</div>';
    }
    html += '</div>';
    document.getElementById('taskDetailBody').innerHTML = html;

    var modal = document.getElementById('taskDetailModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeTaskDetailModal() {
    var modal = document.getElementById('taskDetailModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    selectedTaskId = null;
}

function advanceTask(id) {
    var task = tasks.find(function(t) { return t.id == id; });
    if (!task) return;

    var next = task.status === 'todo' ? 'in_progress' : task.status === 'in_progress' ? 'completed' : task.status;
    var fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', id);
    fd.append('status', next);

    fetch('tasks.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                task.status = next;
                showToast('Task updated', 'success');
                renderTasks();
                loadStats();
            } else {
                showToast(json.message || 'Failed to update', 'error');
            }
        })
        .catch(function() { showToast('Failed to update task', 'error'); });
}

function saveTask() {
    var form = document.getElementById('addTaskForm');
    var fd = new FormData(form);

    fetch('tasks.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                closeAddTaskModal();
                showToast('Task created', 'success');
                loadTasks();
                loadStats();
            } else {
                showToast(json.message || 'Failed to create task', 'error');
            }
        })
        .catch(function() { showToast('Failed to create task', 'error'); });
}

function deleteTask(id) {
    if (!confirm('Delete this task?')) return;

    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('tasks.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                tasks = tasks.filter(function(t) { return t.id != id; });
                showToast('Task deleted', 'success');
                renderTasks();
                loadStats();
            } else {
                showToast(json.message || 'Failed to delete', 'error');
            }
        })
        .catch(function() { showToast('Failed to delete task', 'error'); });
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(ev) {
            searchQuery = ev.target.value.toLowerCase();
            renderTasks();
        });
    }
}

function priorityClass(p) {
    var map = { urgent: 'danger', high: 'warning', medium: 'info', low: 'success' };
    return map[p] || 'info';
}

function openAddTaskModal() {
    var modal = document.getElementById('addTaskModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeAddTaskModal() {
    var modal = document.getElementById('addTaskModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    document.getElementById('addTaskForm').reset();
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
}

window.addEventListener('click', function(ev) {
    if (ev.target === document.getElementById('addTaskModal')) closeAddTaskModal();
    if (ev.target === document.getElementById('taskDetailModal')) closeTaskDetailModal();
});
