<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <div>
            <div class="sidebar-title">St. George Hospital</div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠
            Dashboard</a>
        <p class="nav-section">Management</p>
        <a href="branches.php" class="nav-item <?php echo ($current_page == 'branches') ? 'active' : ''; ?>">🏥 Branch
            Management</a>
        <a href="users.php" class="nav-item <?php echo ($current_page == 'users') ? 'active' : ''; ?>">👥 User
            Management</a>
        <a href="doctors.php" class="nav-item <?php echo ($current_page == 'doctors') ? 'active' : ''; ?>">👨‍⚕️ Doctor
            Management</a>
        <a href="staff.php" class="nav-item <?php echo ($current_page == 'staff') ? 'active' : ''; ?>">👤 Staff
            Management</a>
        <a href="departments.php" class="nav-item <?php echo ($current_page == 'departments') ? 'active' : ''; ?>">🏷️
            Departments</a>
        <a href="services.php" class="nav-item <?php echo ($current_page == 'services') ? 'active' : ''; ?>">💰 Service
            Charges</a>
        <a href="lab-tests.php" class="nav-item <?php echo ($current_page == 'lab-tests') ? 'active' : ''; ?>">🔬 Lab
            Test Types</a>
        <p class="nav-section">Analytics</p>
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