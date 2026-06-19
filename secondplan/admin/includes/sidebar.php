<aside class="sidebar">
    <div class="brand">
        <img src="../assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
        <h1>SecondPlan</h1>
        <div class="role-badge">Admin</div>
    </div>
    <nav class="nav">
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="users.php">
            <i class="bi bi-people nav-icon"></i>
            <span>Users</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : '' ?>" href="bookings.php">
            <i class="bi bi-journal-check nav-icon"></i>
            <span>Bookings</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>" href="events.php">
            <i class="bi bi-calendar-event nav-icon"></i>
            <span>Events</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : '' ?>" href="tasks.php">
            <i class="bi bi-check2-circle nav-icon"></i>
            <span>Tasks</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'expenses.php' ? 'active' : '' ?>" href="expenses.php">
            <i class="bi bi-wallet2 nav-icon"></i>
            <span>Expenses</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'merchandise.php' ? 'active' : '' ?>" href="merchandise.php">
            <i class="bi bi-box-seam nav-icon"></i>
            <span>Merchandise</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>" href="orders.php">
            <i class="bi bi-bag-check nav-icon"></i>
            <span>Orders</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>" href="reports.php">
            <i class="bi bi-graph-up nav-icon"></i>
            <span>Reports</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'activity_log.php' ? 'active' : '' ?>" href="activity_log.php">
            <i class="bi bi-clock-history nav-icon"></i>
            <span>Activity Log</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>" href="settings.php">
            <i class="bi bi-gear nav-icon"></i>
            <span>Settings</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/auth/logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right btn-icon"></i>
            Logout
        </a>
    </div>
</aside>
