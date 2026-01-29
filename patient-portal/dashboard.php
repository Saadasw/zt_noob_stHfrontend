<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Patient Dashboard - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'dashboard';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found. Please contact hospital reception.");
}

$patient_profile_id = $patient['id'];

// Fetch Stats
// Upcoming Appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status NOT IN ('completed', 'cancelled', 'no_show')");
$stmt->execute([$patient_profile_id]);
$upcoming_count = $stmt->fetchColumn();

// Recent Lab Results (placeholder - would need prescriptions/lab_tests tables)
$lab_results_count = 0;

// Active Prescriptions (placeholder)
$prescriptions_count = 0;

// Outstanding Bills
$stmt = $pdo->prepare("SELECT COALESCE(SUM(due_amount), 0) FROM bills WHERE patient_id = ? AND payment_status IN ('pending', 'partial', 'overdue')");
$stmt->execute([$patient_profile_id]);
$outstanding_bills = $stmt->fetchColumn();

// Fetch next appointment
$stmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name, dp.specialization 
    FROM appointments a 
    JOIN doctor_profiles dp ON a.doctor_id = dp.id 
    JOIN users u ON dp.user_id = u.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status NOT IN ('completed', 'cancelled', 'no_show')
    ORDER BY a.appointment_date ASC, a.start_time ASC 
    LIMIT 1
");
$stmt->execute([$patient_profile_id]);
$next_appointment = $stmt->fetch();

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="welcome-banner">
            <h1 class="welcome-title">Welcome back, <?php echo h($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">Patient ID: <?php echo h($patient['patient_id']); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Upcoming Appointments</div>
                <div class="stat-value"><?php echo $upcoming_count; ?></div>
                <a href="appointments.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">🔬</div>
                <div class="stat-label">Lab Results Ready</div>
                <div class="stat-value"><?php echo $lab_results_count; ?></div>
                <a href="lab-results.php" class="stat-link">View Results →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💊</div>
                <div class="stat-label">Active Prescriptions</div>
                <div class="stat-value"><?php echo $prescriptions_count; ?></div>
                <a href="prescriptions.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">💳</div>
                <div class="stat-label">Outstanding Bills</div>
                <div class="stat-value">$<?php echo number_format($outstanding_bills, 2); ?></div>
                <a href="billing.php" class="stat-link">Pay Now →</a>
            </div>
        </div>

        <?php if ($next_appointment): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📅 Next Appointment</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Date</label>
                    <p><?php echo date('l, d F Y', strtotime($next_appointment['appointment_date'])); ?></p>
                </div>
                <div class="profile-item">
                    <label>Time</label>
                    <p><?php echo date('h:i A', strtotime($next_appointment['start_time'])); ?></p>
                </div>
                <div class="profile-item">
                    <label>Doctor</label>
                    <p><?php echo h($next_appointment['doctor_name']); ?></p>
                </div>
                <div class="profile-item">
                    <label>Department</label>
                    <p><?php echo h($next_appointment['specialization']); ?></p>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <a href="appointments.php" class="btn btn-primary">View Details</a>
                <button class="btn btn-outline">Reschedule</button>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <p class="text-center text-gray p-4">No upcoming appointments. <a href="book-appointment.php">Book one now</a>.</p>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
