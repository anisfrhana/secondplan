function toggleNotifications() {
    var dropdown = document.getElementById('notificationDropdown');
    if (dropdown) dropdown.classList.toggle('active');
}

function viewTaskDetails(taskId) {
    var modal = document.getElementById('taskModal');
    var details = document.getElementById('taskDetails');

    fetch('../api/tasks.php?action=view&id=' + taskId)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var t = data.data;
                details.dataset.taskId = t.task_id;
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
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Assigned To</div><div style="font-size:14px;margin-top:4px;font-weight:600;">' + esc(t.assigned_to_name || 'Unassigned') + '</div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Priority</div><div style="margin-top:4px;"><span class="badge priority-' + t.priority + '">' + (t.priority || '').toUpperCase() + '</span></div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="font-size:14px;margin-top:4px;">' + statusLabel + '</div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Due Date</div><div style="font-size:14px;margin-top:4px;">' + (t.due_date || 'Not set') + (t.due_time ? ' ' + t.due_time : '') + '</div></div>' +
                '</div>';
                if (t.event_title) {
                    html += '<div>' +
                        '<div style="color:var(--text-secondary);font-size:13px;">Event</div>' +
                        '<div style="font-size:14px;margin-top:4px;">' + esc(t.event_title) + '</div>' +
                    '</div>';
                }
                html += '</div>';
                details.innerHTML = html;
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
        })
        .catch(function() { showToast('Failed to load task details', 'error'); });
}

function closeTaskModal() {
    var modal = document.getElementById('taskModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function markTaskComplete() {
    var taskId = document.getElementById('taskDetails').dataset.taskId;
    if (!taskId) return;

    var fd = new FormData();
    fd.append('action', 'complete');
    fd.append('id', taskId);
    fd.append('csrf', getCSRF());

    fetch('../api/tasks.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Task completed', 'success');
                closeTaskModal();
                location.reload();
            } else {
                showToast(data.message || 'Failed to complete task', 'error');
            }
        })
        .catch(function() { showToast('Failed to complete task', 'error'); });
}

function updateTaskStatus(taskId, status) {
    var fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', taskId);
    fd.append('status', status);
    fd.append('csrf', getCSRF());

    fetch('../api/tasks.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Status updated', 'success');
                location.reload();
            } else {
                showToast(data.message || 'Failed to update status', 'error');
            }
        })
        .catch(function() { showToast('Failed to update status', 'error'); });
}

function loadNotificationCount() {
    fetch('../api/notifications.php?action=count')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data > 0) {
                var badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.textContent = data.data;
                    badge.classList.add('active');
                }
            }
        })
        .catch(function() {});
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    loadNotificationCount();
});

window.addEventListener('click', function(e) {
    var modal = document.getElementById('taskModal');
    if (e.target === modal) closeTaskModal();
});
