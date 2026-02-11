<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <span class="sidebar-title">St. George</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠
            Dashboard</a>

        <p class="nav-section">Appointments</p>
        <a href="appointments.php" class="nav-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">📅
            My Appointments</a>
        <a href="book-appointment.php"
            class="nav-item <?php echo ($current_page == 'book-appointment') ? 'active' : ''; ?>">➕ Book New</a>

        <p class="nav-section">Health Records</p>
        <a href="medical-records.php"
            class="nav-item <?php echo ($current_page == 'medical-records') ? 'active' : ''; ?>">📋 Medical Records</a>
        <a href="prescriptions.php"
            class="nav-item <?php echo ($current_page == 'prescriptions') ? 'active' : ''; ?>">💊 Prescriptions</a>
        <a href="lab-results.php" class="nav-item <?php echo ($current_page == 'lab-results') ? 'active' : ''; ?>">🔬
            Lab Results</a>

        <p class="nav-section">Billing</p>
        <a href="billing.php" class="nav-item <?php echo ($current_page == 'billing') ? 'active' : ''; ?>">💳 Bills &
            Payments</a>

        <p class="nav-section">Account</p>
        <a href="ai-assistant.php" class="nav-item <?php echo ($current_page == 'ai-assistant') ? 'active' : ''; ?>">🤖
            AI Health Assistant</a>
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">👤 My
            Profile</a>
        <a href="notifications.php"
            class="nav-item <?php echo ($current_page == 'notifications') ? 'active' : ''; ?>">🔔 Notifications</a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>