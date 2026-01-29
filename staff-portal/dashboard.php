<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Staff Dashboard - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'dashboard';

// Get staff profile
$staff_profile = null;
$department = $_SESSION['staff_department'] ?? 'general';
$branch_id = $_SESSION['staff_branch_id'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT sp.*, b.name as branch_name FROM staff_profiles sp 
        LEFT JOIN branches b ON sp.branch_id = b.id 
        WHERE sp.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff_profile = $stmt->fetch();
    if ($staff_profile) {
        $department = $staff_profile['department'];
    }
} catch (PDOException $e) {
    // Fallback
}

// Department labels
$dept_labels = [
    'reception' => ['name' => 'Reception', 'icon' => '🏥', 'color' => 'blue'],
    'laboratory' => ['name' => 'Laboratory', 'icon' => '🔬', 'color' => 'green'],
    'pharmacy' => ['name' => 'Pharmacy', 'icon' => '💊', 'color' => 'purple'],
    'billing' => ['name' => 'Billing', 'icon' => '💰', 'color' => 'yellow'],
];
$dept_info = $dept_labels[$department] ?? ['name' => 'Staff', 'icon' => '👤', 'color' => 'gray'];

// Fetch department-specific stats
$today = date('Y-m-d');
$stat1 = $stat2 = $stat3 = $stat4 = 0;

try {
    switch ($department) {
        case 'reception':
            // Today's appointments
            $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '$today'");
            $stat1 = $stmt->fetchColumn();
            // Waiting patients
            $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '$today' AND status = 'checked_in'");
            $stat2 = $stmt->fetchColumn();
            // Pending check-in
            $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '$today' AND status = 'scheduled'");
            $stat3 = $stmt->fetchColumn();
            // Completed today
            $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '$today' AND status = 'completed'");
            $stat4 = $stmt->fetchColumn();
            break;

        case 'laboratory':
            // Pending samples
            $stmt = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'ordered'");
            $stat1 = $stmt->fetchColumn();
            // Samples collected
            $stmt = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'sample_collected'");
            $stat2 = $stmt->fetchColumn();
            // Processing
            $stmt = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'processing'");
            $stat3 = $stmt->fetchColumn();
            // Completed today (needs result entry)
            $stmt = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'completed' AND DATE(created_at) = '$today'");
            $stat4 = $stmt->fetchColumn();
            break;

        case 'pharmacy':
            // Pending prescriptions
            $stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'pending'");
            $stat1 = $stmt->fetchColumn();
            // Processing
            $stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'processing'");
            $stat2 = $stmt->fetchColumn();
            // Ready for pickup
            $stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'ready'");
            $stat3 = $stmt->fetchColumn();
            // Dispensed today
            $stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'dispensed' AND DATE(updated_at) = '$today'");
            $stat4 = $stmt->fetchColumn();
            break;

        case 'billing':
            // Pending bills
            $stmt = $pdo->query("SELECT COUNT(*) FROM billings WHERE payment_status = 'pending'");
            $stat1 = $stmt->fetchColumn();
            // Partial payments
            $stmt = $pdo->query("SELECT COUNT(*) FROM billings WHERE payment_status = 'partial'");
            $stat2 = $stmt->fetchColumn();
            // Today's collections
            $stmt = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM billings WHERE DATE(updated_at) = '$today'");
            $stat3 = $stmt->fetchColumn();
            // Completed today
            $stmt = $pdo->query("SELECT COUNT(*) FROM billings WHERE payment_status = 'paid' AND DATE(updated_at) = '$today'");
            $stat4 = $stmt->fetchColumn();
            break;
    }
} catch (PDOException $e) {
    // Tables may not exist yet
}

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="welcome-banner" style="border-left: 4px solid var(--<?php echo $dept_info['color']; ?>);">
            <h1 class="welcome-title"><?php echo $dept_info['icon']; ?> Welcome,
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle"><?php echo h($dept_info['name']); ?> Department |
                <?php echo h($staff_profile['branch_name'] ?? 'Branch'); ?></p>
            <p class="welcome-meta">Employee ID: <?php echo h($staff_profile['employee_id'] ?? 'N/A'); ?> |
                <?php echo date('l, d F Y'); ?></p>
        </div>

        <div class="stats-grid">
            <?php if ($department === 'reception'): ?>
                <div class="stat-card">
                    <div class="stat-icon blue">📅</div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value"><?php echo $stat1; ?></div>
                    <a href="appointments.php" class="stat-link">View All →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">⏳</div>
                    <div class="stat-label">Waiting Patients</div>
                    <div class="stat-value"><?php echo $stat2; ?></div>
                    <a href="appointments.php" class="stat-link">View Queue →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gray">📋</div>
                    <div class="stat-label">Pending Check-in</div>
                    <div class="stat-value"><?php echo $stat3; ?></div>
                    <a href="appointments.php" class="stat-link">Check In →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value"><?php echo $stat4; ?></div>
                </div>

            <?php elseif ($department === 'laboratory'): ?>
                <div class="stat-card">
                    <div class="stat-icon yellow">📋</div>
                    <div class="stat-label">Pending Samples</div>
                    <div class="stat-value"><?php echo $stat1; ?></div>
                    <a href="laboratory.php" class="stat-link">Collect →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">🧪</div>
                    <div class="stat-label">Samples Collected</div>
                    <div class="stat-value"><?php echo $stat2; ?></div>
                    <a href="laboratory.php" class="stat-link">Process →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">⚙️</div>
                    <div class="stat-label">Processing</div>
                    <div class="stat-value"><?php echo $stat3; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value"><?php echo $stat4; ?></div>
                </div>

            <?php elseif ($department === 'pharmacy'): ?>
                <div class="stat-card">
                    <div class="stat-icon yellow">📋</div>
                    <div class="stat-label">Pending Prescriptions</div>
                    <div class="stat-value"><?php echo $stat1; ?></div>
                    <a href="pharmacy.php" class="stat-link">View →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">⚙️</div>
                    <div class="stat-label">Processing</div>
                    <div class="stat-value"><?php echo $stat2; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">📦</div>
                    <div class="stat-label">Ready for Pickup</div>
                    <div class="stat-value"><?php echo $stat3; ?></div>
                    <a href="pharmacy.php" class="stat-link">Dispense →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-label">Dispensed Today</div>
                    <div class="stat-value"><?php echo $stat4; ?></div>
                </div>

            <?php elseif ($department === 'billing'): ?>
                <div class="stat-card">
                    <div class="stat-icon yellow">📋</div>
                    <div class="stat-label">Pending Bills</div>
                    <div class="stat-value"><?php echo $stat1; ?></div>
                    <a href="billing.php" class="stat-link">View →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">💳</div>
                    <div class="stat-label">Partial Payments</div>
                    <div class="stat-value"><?php echo $stat2; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💰</div>
                    <div class="stat-label">Today's Collections</div>
                    <div class="stat-value">$<?php echo number_format($stat3, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">✅</div>
                    <div class="stat-label">Bills Paid Today</div>
                    <div class="stat-value"><?php echo $stat4; ?></div>
                </div>

            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon blue">📅</div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value">0</div>
                    <a href="appointments.php" class="stat-link">View All →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions based on department -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="quick-actions">
                <?php if ($department === 'reception'): ?>
                    <a href="patients.php" class="quick-action-btn">👤 Register Patient</a>
                    <a href="appointments.php" class="quick-action-btn">📅 Book Appointment</a>
                    <a href="appointments.php" class="quick-action-btn">✅ Check-in Patient</a>
                <?php elseif ($department === 'laboratory'): ?>
                    <a href="laboratory.php" class="quick-action-btn">🧪 Collect Sample</a>
                    <a href="laboratory.php" class="quick-action-btn">📝 Enter Results</a>
                <?php elseif ($department === 'pharmacy'): ?>
                    <a href="pharmacy.php" class="quick-action-btn">💊 View Prescriptions</a>
                    <a href="pharmacy.php" class="quick-action-btn">📦 Dispense Medicine</a>
                <?php elseif ($department === 'billing'): ?>
                    <a href="billing.php" class="quick-action-btn">📄 Create Bill</a>
                    <a href="billing.php" class="quick-action-btn">💳 Collect Payment</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>