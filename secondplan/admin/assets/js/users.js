// User Management System - Complete JavaScript
let users = [];
let filteredUsers = [];
let allRoles = [];
let currentUserId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    loadRoles();
    setupSearch();
});

// Load users from API
async function loadUsers() {
    try {
        const response = await fetch('users.php?api=list');
        const data = await response.json();
        
        if (data.success) {
            users = data.data;
            filteredUsers = users;
            renderUsers();
            updateStats();
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showToast('Failed to load users', 'error');
    }
}

// Load available roles
async function loadRoles() {
    try {
        const response = await fetch('users.php?api=roles');
        const data = await response.json();
        
        if (data.success) {
            allRoles = data.data;
        }
    } catch (error) {
        console.error('Error loading roles:', error);
    }
}

// Render users table
function renderUsers() {
    const tbody = document.getElementById('usersTable');
    
    if (filteredUsers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredUsers.map(user => `
        <tr>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div style="font-weight: 500;">${escapeHtml(user.name)}</div>
                        ${user.email_verified ? '<span style="color: #22c55e; font-size: 12px;">✓ Verified</span>' : '<span style="color: #f59e0b; font-size: 12px;">⚠ Not Verified</span>'}
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>${user.phone || '-'}</td>
            <td>
                ${user.roles ? user.roles.split(',').map(role => 
                    `<span class="badge info">${role}</span>`
                ).join(' ') : '-'}
            </td>
            <td>
                <span class="badge status-${user.status}">${user.status.toUpperCase()}</span>
            </td>
            <td>${user.last_login ? formatDateTime(user.last_login) : 'Never'}</td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button onclick="editUser(${user.user_id})" class="btn-small btn-primary">Edit</button>
                    <button onclick="resetPassword(${user.user_id})" class="btn-small btn-warning">Reset</button>
                    <button onclick="deleteUser(${user.user_id})" class="btn-small btn-danger">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Update statistics
function updateStats() {
    document.getElementById('totalUsers').textContent = users.length;
    document.getElementById('activeUsers').textContent = users.filter(u => u.status === 'active').length;
    document.getElementById('adminCount').textContent = users.filter(u => u.roles && u.roles.includes('admin')).length;
    document.getElementById('memberCount').textContent = users.filter(u => u.roles && u.roles.includes('band_member')).length;
}

// Filter users
function filterUsers() {
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredUsers = users.filter(user => {
        const matchesRole = !roleFilter || (user.roles && user.roles.includes(roleFilter));
        const matchesStatus = !statusFilter || user.status === statusFilter;
        return matchesRole && matchesStatus;
    });
    
    renderUsers();
}

// Setup search
function setupSearch() {
    const searchBox = document.getElementById('searchBox');
    searchBox.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        filteredUsers = users.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            (user.phone && user.phone.includes(query))
        );
        renderUsers();
    });
}

// Open add user modal
function openAddUserModal() {
    currentUserId = null;
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    
    renderRolesCheckboxes();
    
    document.getElementById('userModal').classList.add('active');
}

// Edit user
async function editUser(id) {
    currentUserId = id;
    
    try {
        const response = await fetch(`users.php?api=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.user_id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPhone').value = user.phone || '';
            document.getElementById('userStatus').value = user.status;
            document.getElementById('passwordGroup').style.display = 'none';
            
            const userRoles = user.roles ? user.roles.split(',') : [];
            renderRolesCheckboxes(userRoles);
            
            document.getElementById('userModal').classList.add('active');
        }
    } catch (error) {
        showToast('Failed to load user data', 'error');
    }
}

// Render roles checkboxes
function renderRolesCheckboxes(selectedRoles = []) {
    const container = document.getElementById('rolesContainer');
    
    if (allRoles.length === 0) {
        container.innerHTML = '<p>Loading roles...</p>';
        return;
    }
    
    container.innerHTML = allRoles.map(role => `
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="role" value="${role.role_name}" 
                   ${selectedRoles.includes(role.role_name) ? 'checked' : ''}>
            <span>${role.role_name}</span>
            ${role.description ? `<span style="color: var(--text-secondary); font-size: 12px;">- ${role.description}</span>` : ''}
        </label>
    `).join('');
}

// Save user
async function saveUser() {
    const form = document.getElementById('userForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const userId = document.getElementById('userId').value;
    const isEdit = !!userId;
    
    const selectedRoles = Array.from(document.querySelectorAll('input[name="role"]:checked'))
        .map(cb => cb.value);
    
    const userData = {
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        phone: document.getElementById('userPhone').value,
        status: document.getElementById('userStatus').value,
        roles: selectedRoles
    };
    
    if (isEdit) {
        userData.id = userId;
        userData.action = 'update';
    } else {
        const password = document.getElementById('userPassword').value;
        if (!password || password.length < 8) {
            showToast('Password must be at least 8 characters', 'error');
            return;
        }
        userData.password = password;
    }
    
    try {
        const response = await fetch('users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeUserModal();
            loadUsers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to save user', 'error');
    }
}

// Delete user
async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        const response = await fetch('users.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadUsers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to delete user', 'error');
    }
}

// Reset password
async function resetPassword(id) {
    const password = prompt('Enter new password (min 8 characters):');
    
    if (!password) return;
    
    if (password.length < 8) {
        showToast('Password must be at least 8 characters', 'error');
        return;
    }
    
    try {
        const response = await fetch('users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reset_password',
                id: id,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to reset password', 'error');
    }
}

// Close user modal
function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('userForm').reset();
    currentUserId = null;
}

// Utilities
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(datetime) {
    if (!datetime) return '-';
    const date = new Date(datetime);
    return date.toLocaleString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showToast(message, type = 'info') {
    let toast = document.querySelector('.toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.style.borderLeft = `4px solid ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'}`;
    toast.classList.add('show');
    
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
}
