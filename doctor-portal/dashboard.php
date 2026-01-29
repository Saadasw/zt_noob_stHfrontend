<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Doctor Dashboard - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'dashboard';

// Get doctor profile
$doctor_profile = null;
$today_appointments = 0;
$waiting_patients = 0;
$completed_today = 0;

try {
    // Get doctor profile for current user
    $stmt = $pdo->prepare("SELECT dp.*, b.name as branch_name FROM doctor_profiles dp 
        LEFT JOIN branches b ON dp.branch_id = b.id 
        WHERE dp.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor_profile = $stmt->fetch();

    if ($doctor_profile) {
        $doctor_id = $doctor_profile['id'];
        $today = date('Y-m-d');

        // Today's appointments (all statuses except cancelled)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $stmt->execute([$doctor_id, $today]);
        $today_appointments = $stmt->fetchColumn();

        // Waiting patients (checked_in status)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status = 'checked_in'");
        $stmt->execute([$doctor_id, $today]);
        $waiting_patients = $stmt->fetchColumn();

        // Completed today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status = 'completed'");
        $stmt->execute([$doctor_id, $today]);
        $completed_today = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // Fallback to 0 if error
}

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="welcome-banner">
            <h1 class="welcome-title">Good
                <?php echo (date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening')); ?>,
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle"><?php echo h($doctor_profile['specialization'] ?? 'Doctor'); ?> |
                <?php echo h($doctor_profile['branch_name'] ?? 'Branch'); ?></p>
            <p class="welcome-meta">License: <?php echo h($doctor_profile['license_number'] ?? 'N/A'); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Today's Appointments</div>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <a href="appointments.php" class="stat-link">View Schedule →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-label">Waiting Patients</div>
                <div class="stat-value"><?php echo $waiting_patients; ?></div>
                <a href="appointments.php" class="stat-link">View Queue →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value"><?php echo $completed_today; ?></div>
                <a href="appointments.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">📋</div>
                <div class="stat-label">Pending Lab Reviews</div>
                <div class="stat-value">0</div>
                <a href="lab-results.php" class="stat-link">Review Now →</a>
            </div>
        </div>

        <!-- Placeholder for rest of content -->
        <div class="card">
            <p class="text-center text-gray">Schedule and other modules are under construction.</p>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>