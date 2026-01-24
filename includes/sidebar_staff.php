<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <span class="sidebar-title">St. George</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠 Dashboard</a>
        <p class="nav-section">Patients</p>
        <a href="patients.php" class="nav-item <?php echo ($current_page == 'patients') ? 'active' : ''; ?>">👤 Register/Search</a>
        <p class="nav-section">Appointments</p>
        <a href="appointments.php" class="nav-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">📅 Schedule & Booking</a>
        <p class="nav-section">Services</p>
        <a href="laboratory.php" class="nav-item <?php echo ($current_page == 'laboratory') ? 'active' : ''; ?>">🔬 Laboratory</a>
        <a href="pharmacy.php" class="nav-item <?php echo ($current_page == 'pharmacy') ? 'active' : ''; ?>">💊 Pharmacy</a>
        <a href="billing.php" class="nav-item <?php echo ($current_page == 'billing') ? 'active' : ''; ?>">💳 Billing</a>
        <p class="nav-section">Account</p>
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">👤 My Profile</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>
