// Toggle notifications
function toggleNotifications() {
    document.getElementById('notificationDropdown')?.classList.toggle('active');
}

// Open Task Modal
function viewTaskDetails(taskId) {
    const modal = document.getElementById('taskModal');
    const details = document.getElementById('taskDetails');
    
    // Fetch task details
    fetch(`../api/tasks.php?action=view&id=${taskId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const task = data.data;
                details.innerHTML = `
                    <h4>${task.title}</h4>
                    <p>${task.description || ''}</p>
                    <p>Due: ${task.due_date}</p>
                    <p>Priority: ${task.priority}</p>
                `;
                modal.style.display = 'flex';
            }
        });
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

// Mark Task Complete
function markTaskComplete() {
    const taskId = document.getElementById('taskDetails').dataset.taskId;
    fetch(`../api/tasks.php?action=complete&id=${taskId}`, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Task marked as complete!');
                closeTaskModal();
                location.reload();
            }
        });
}

// Update Task Status
function updateTaskStatus(taskId, status) {
    fetch(`../api/tasks.php?action=update_status&id=${taskId}&status=${status}`, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}
