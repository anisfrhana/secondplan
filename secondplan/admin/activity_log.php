<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
requireRole([ROLE_ADMIN]);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['api'] ?? null;

if ($method === 'GET' && $action === 'list') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT al.*, u.name as user_name, u.email as user_email
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 500
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && $action === 'actions') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="app">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                <input type="text" placeholder="Search activity..." class="search-box" id="searchBox">
                <div class="header-actions">
                    <button class="notification-btn"></button>
                    <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'A', 0, 1)) ?></div>
                </div>
            </header>

            <main class="content">
                <div class="page-header">
                    <div>
                        <h2>Activity Log</h2>
                        <p class="subtitle">Monitor system activity and user actions</p>
                    </div>
                </div>

                <div class="filter-tabs" style="gap:12px;flex-wrap:wrap;">
                    <select id="actionFilter" onchange="filterLogs()" style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:14px;">
                        <option value="">All Actions</option>
                    </select>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <label style="font-size:13px;color:#6b7280;">From:</label>
                        <input type="date" id="dateFrom" onchange="filterLogs()" style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:14px;">
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <label style="font-size:13px;color:#6b7280;">To:</label>
                        <input type="date" id="dateTo" onchange="filterLogs()" style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:14px;">
                    </div>
                </div>

                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="totalEntries">0</div>
                        <div class="mini-stat-label">Total Entries</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="todayEntries">0</div>
                        <div class="mini-stat-label">Today</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="weekEntries">0</div>
                        <div class="mini-stat-label">This Week</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value" id="uniqueUsers">0</div>
                        <div class="mini-stat-label">Unique Users</div>
                    </div>
                </div>

                <div class="section">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logsTable">
                            <tr>
                                <td colspan="5" class="loading">Loading activity log...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="assets/js/activity_log.js"></script>
</body>
</html>
