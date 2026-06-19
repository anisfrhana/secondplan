<aside class="sidebar">
    <div class="brand">
        <img src="../assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
        <h1>SecondPlan</h1>
        <div class="role-badge">Customer</div>
    </div>
    <nav class="nav">
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'booking.php' ? 'active' : '' ?>" href="booking.php">
            <i class="bi bi-calendar-event nav-icon"></i>
            <span>Booking</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'my_bookings.php' ? 'active' : '' ?>" href="my_bookings.php">
            <i class="bi bi-journal-check nav-icon"></i>
            <span>My Bookings</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'merchandise.php' ? 'active' : '' ?>" href="merchandise.php">
            <i class="bi bi-box-seam nav-icon"></i>
            <span>Merchandise</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>" href="cart.php">
            <i class="bi bi-cart3 nav-icon"></i>
            <span>Cart</span>
        </a>
        <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>" href="orders.php">
            <i class="bi bi-bag-check nav-icon"></i>
            <span>My Orders</span>
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
