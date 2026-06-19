<aside class="sidebar">
    <div class="brand">
        <img src="../assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
        <h1>SecondPlan</h1>
        <div class="role-badge">Band Member</div>
    </div>
    <nav class="nav">
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'my_tasks.php' ? 'active' : '' ?>" href="my_tasks.php">
            <i class="bi bi-check2-circle nav-icon"></i>
            <span>My Tasks</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>" href="events.php">
            <i class="bi bi-calendar-event nav-icon"></i>
            <span>Events</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : '' ?>" href="schedule.php">
            <i class="bi bi-clock nav-icon"></i>
            <span>Schedule</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'expenses.php' ? 'active' : '' ?>" href="expenses.php">
            <i class="bi bi-upload nav-icon"></i>
            <span>Submit Expense</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'my_expenses.php' ? 'active' : '' ?>" href="my_expenses.php">
            <i class="bi bi-receipt nav-icon"></i>
            <span>My Expenses</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" href="profile.php">
            <i class="bi bi-person-circle nav-icon"></i>
            <span>Profile</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/auth/logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right btn-icon"></i>
            Logout
        </a>
    </div>
</aside>
