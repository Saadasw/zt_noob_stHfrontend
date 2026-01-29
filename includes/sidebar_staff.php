<?php
$dept = $_SESSION['staff_department'] ?? 'general';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">SG</div>
        <span class="sidebar-title">St. George</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">🏠
            Dashboard</a>

        <?php if ($dept === 'reception' || $dept === 'general'): ?>
            <p class="nav-section">Reception</p>
            <a href="patients.php" class="nav-item <?php echo ($current_page == 'patients') ? 'active' : ''; ?>">👤
                Patients</a>
            <a href="appointments.php" class="nav-item <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">📅
                Appointments</a>
        <?php endif; ?>

        <?php if ($dept === 'laboratory' || $dept === 'general'): ?>
            <p class="nav-section">Laboratory</p>
            <a href="laboratory.php" class="nav-item <?php echo ($current_page == 'laboratory') ? 'active' : ''; ?>">🔬 Lab
                Tests</a>
        <?php endif; ?>

        <?php if ($dept === 'pharmacy' || $dept === 'general'): ?>
            <p class="nav-section">Pharmacy</p>
            <a href="pharmacy.php" class="nav-item <?php echo ($current_page == 'pharmacy') ? 'active' : ''; ?>">💊
                Prescriptions</a>
        <?php endif; ?>

        <?php if ($dept === 'billing' || $dept === 'general'): ?>
            <p class="nav-section">Billing</p>
            <a href="billing.php" class="nav-item <?php echo ($current_page == 'billing') ? 'active' : ''; ?>">💳 Bills &
                Payments</a>
        <?php endif; ?>

        <p class="nav-section">Account</p>
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">👤 My
            Profile</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>