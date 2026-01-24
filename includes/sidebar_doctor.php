<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <span class="sidebar-title">St. George</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠 Dashboard</a>
        <p class="nav-section">Schedule</p>
        <a href="appointments.php" class="nav-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">📅 Today's Appointments</a>
        <a href="schedule.php" class="nav-item <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">🗓️ My Schedule</a>
        <p class="nav-section">Patients</p>
        <a href="patients.php" class="nav-item <?php echo ($current_page == 'patients') ? 'active' : ''; ?>">👥 My Patients</a>
        <a href="consultation.php" class="nav-item <?php echo ($current_page == 'consultation') ? 'active' : ''; ?>">📝 Consultation</a>
        <p class="nav-section">Orders</p>
        <a href="prescriptions.php" class="nav-item <?php echo ($current_page == 'prescriptions') ? 'active' : ''; ?>">💊 Prescriptions</a>
        <a href="lab-orders.php" class="nav-item <?php echo ($current_page == 'lab-orders') ? 'active' : ''; ?>">🔬 Lab Orders</a>
        <a href="lab-results.php" class="nav-item <?php echo ($current_page == 'lab-results') ? 'active' : ''; ?>">📊 Lab Results</a>
        <p class="nav-section">Documents</p>
        <a href="certificates.php" class="nav-item <?php echo ($current_page == 'certificates') ? 'active' : ''; ?>">📄 Certificates</a>
        <a href="referrals.php" class="nav-item <?php echo ($current_page == 'referrals') ? 'active' : ''; ?>">🔄 Referrals</a>
        <p class="nav-section">Account</p>
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">👤 My Profile</a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>
