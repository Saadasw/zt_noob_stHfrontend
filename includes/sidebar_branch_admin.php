<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <div>
            <div class="sidebar-title">St. George Hospital</div>
            <div class="sidebar-subtitle">Branch Admin Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠
            Dashboard</a>
        <p class="nav-section">Branch Management</p>
        <a href="doctors.php" class="nav-item <?php echo ($current_page == 'doctors') ? 'active' : ''; ?>">👨‍⚕️
            Doctors</a>
        <a href="staff.php" class="nav-item <?php echo ($current_page == 'staff') ? 'active' : ''; ?>">👤 Staff</a>
        <a href="appointments.php" class="nav-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">📅
            Appointments</a>
        <a href="reports.php" class="nav-item <?php echo ($current_page == 'reports') ? 'active' : ''; ?>">📊
            Reports</a>
        <p class="nav-section">Account</p>
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">👤 My
            Profile</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>