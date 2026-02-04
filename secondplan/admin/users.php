<?php
/**
 * ADMIN - User Management System
 * Complete CRUD operations for users with role management
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_login();
requireRole([ROLE_ADMIN]);


$isApi = isset($_GET['api']) || isset($_POST['action']);

if ($isApi) {
    header('Content-Type: application/json');
}


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
}


// ============================================
// API HANDLERS
// ============================================

// LIST USERS
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT 
                u.user_id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.email_verified,
                u.last_login,
                u.created_at,
                GROUP_CONCAT(r.role_name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            GROUP BY u.user_id
            ORDER BY u.created_at DESC
        ");
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// GET SINGLE USER
if (isset($_GET['api']) && $_GET['api'] === 'get' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                GROUP_CONCAT(r.role_name) as roles,
                GROUP_CONCAT(r.role_id) as role_ids
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.user_id = ?
            GROUP BY u.user_id
        ");
        $stmt->execute([$id]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// CREATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';
        $status = $input['status'] ?? 'active';
        $roles = $input['roles'] ?? [];
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Name, email and password are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, password_hash, status, email_verified)
            VALUES (?, ?, ?, ?, ?, TRUE)
        ");
        $stmt->execute([
            $name,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $status
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Assign roles
        if (!empty($roles)) {
            $roleStmt = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT ?, role_id FROM roles WHERE role_name = ?
            ");
            
            foreach ($roles as $role) {
                $roleStmt->execute([$userId, $role]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'id' => $userId
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// UPDATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $status = $input['status'] ?? 'active';
        $roles = $input['roles'] ?? [];
        
        if ($id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        if (empty($name) || empty($email)) {
            throw new Exception('Name and email are required');
        }
        
        // Check if email exists (excluding current user)
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Update user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone = ?, status = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$name, $email, $phone, $status, $id]);
        
        // Update roles
        $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$id]);
        
        if (!empty($roles)) {
            $roleStmt = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT ?, role_id FROM roles WHERE role_name = ?
            ");
            
            foreach ($roles as $role) {
                $roleStmt->execute([$id, $role]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// DELETE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        // Prevent deleting yourself
        if ($id === $_SESSION['user_id']) {
            throw new Exception('You cannot delete your own account');
        }
        
        // Delete user (cascades to user_roles)
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// RESET PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($input['id'] ?? 0);
        $password = $input['password'] ?? '';
        
        if ($id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// GET ROLES
if (isset($_GET['api']) && $_GET['api'] === 'roles') {
    try {
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $roles
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================
// DEFAULT RESPONSE
// ============================================
// http_response_code(400);
// echo json_encode([
//     'success' => false,
//     'message' => 'Invalid request'
// ]);
// exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SecondPlan Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
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
            <a class="nav-item active" href="users.php">
                <span>👥</span> <span>Users</span>
            </a>
            <a class="nav-item" href="bookings.php">
                <span>📅</span> <span>Bookings</span>
            </a>
            <a class="nav-item" href="events.php">
                <span>🎤</span> <span>Events</span>
            </a>
            <a class="nav-item" href="tasks.php">
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

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <input type="text" class="search-box" id="searchBox" placeholder="Search users...">
            <div class="header-actions">
                <button class="notification-btn">🔔
                    <span class="notification-badge" id="notificationBadge"></span>
                </button>
                <div class="user-avatar">👤</div>
            </div>
        </header>

        <main class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>User Management</h2>
                    <p class="subtitle">Manage system users and permissions</p>
                </div>
                <button class="btn-primary" onclick="openAddUserModal()">
                    + Add User
                </button>
            </div>

            <!-- User Stats -->
            <div class="stats-row">
                <div class="mini-stat">
                    <div class="mini-stat-value" id="totalUsers">0</div>
                    <div class="mini-stat-label">Total Users</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="activeUsers">0</div>
                    <div class="mini-stat-label">Active Users</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="adminCount">0</div>
                    <div class="mini-stat-label">Admins</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value" id="memberCount">0</div>
                    <div class="mini-stat-label">Members</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="section">
                <div class="filter-row">
                    <select id="roleFilter" onchange="filterUsers()">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="band_member">Band Member</option>
                        <option value="customer">Customer</option>
                        <option value="client">Client</option>
                    </select>
                    <select id="statusFilter" onchange="filterUsers()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr>
                            <td colspan="7" class="loading">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New User</h3>
            <button class="close-btn" onclick="closeUserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm" class="form">
                <input type="hidden" id="userId">
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="userName" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="userEmail" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" id="userPhone">
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label>Password *</label>
                    <input type="password" id="userPassword">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select id="userStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <div class="form-group full-width">
                    <label>Roles</label>
                    <div id="rolesContainer" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Roles will be loaded dynamically -->
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeUserModal()">Cancel</button>
            <button class="btn-primary" onclick="saveUser()">Save User</button>
        </div>
    </div>
</div>

<script src="assets/js/users.js"></script>
</body>
</html>
