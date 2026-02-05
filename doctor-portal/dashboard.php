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
$pending_lab_reviews = 0;
$todays_schedule = [];
$recent_activity = [];

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

        // Today's appointments count (all statuses except cancelled)
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

        // Pending lab reviews count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_orders 
            WHERE doctor_id = ? AND status = 'completed' AND (doctor_reviewed = 0 OR doctor_reviewed IS NULL)");
        $stmt->execute([$doctor_id]);
        $pending_lab_reviews = $stmt->fetchColumn();

        // Today's schedule - list of appointments
        $stmt = $pdo->prepare("
            SELECT a.id, a.start_time, a.status, a.reason, u.name as patient_name, pp.patient_id as patient_code
            FROM appointments a
            LEFT JOIN patient_profiles pp ON a.patient_id = pp.id
            LEFT JOIN users u ON pp.user_id = u.id
            WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status != 'cancelled'
            ORDER BY a.start_time ASC
            LIMIT 10
        ");
        $stmt->execute([$doctor_id, $today]);
        $todays_schedule = $stmt->fetchAll();

        // Recent activity - last 5 completed consultations
        $stmt = $pdo->prepare("
            SELECT a.id, a.appointment_date, a.start_time, u.name as patient_name, a.status
            FROM appointments a
            LEFT JOIN patient_profiles pp ON a.patient_id = pp.id
            LEFT JOIN users u ON pp.user_id = u.id
            WHERE a.doctor_id = ? AND a.status = 'completed'
            ORDER BY a.appointment_date DESC, a.start_time DESC
            LIMIT 5
        ");
        $stmt->execute([$doctor_id]);
        $recent_activity = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Fallback to defaults if error
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
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
            </h1>
            <p class="welcome-subtitle"><?php echo h($doctor_profile['specialization'] ?? 'Doctor'); ?> |
                <?php echo h($doctor_profile['branch_name'] ?? 'Branch'); ?>
            </p>
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
                <div class="stat-value"><?php echo $pending_lab_reviews; ?></div>
                <a href="lab-results.php" class="stat-link">Review Now →</a>
            </div>
        </div>

        <!-- Today's Schedule Snapshot -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Today's Schedule</h3>
                <a href="appointments.php" class="btn btn-sm btn-outline">View All →</a>
            </div>
            <?php if (empty($todays_schedule)): ?>
                <p class="text-center text-gray p-4">No appointments scheduled for today.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todays_schedule as $apt): ?>
                            <tr>
                                <td><?php echo date('h:i A', strtotime($apt['start_time'])); ?></td>
                                <td>
                                    <strong><?php echo h($apt['patient_name'] ?? 'Unknown'); ?></strong>
                                    <div class="text-sm text-gray"><?php echo h($apt['patient_code'] ?? ''); ?></div>
                                </td>
                                <td><?php echo h($apt['reason'] ?? 'Not specified'); ?></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'scheduled' => 'badge-blue',
                                        'confirmed' => 'badge-blue',
                                        'checked_in' => 'badge-yellow',
                                        'in_progress' => 'badge-blue',
                                        'completed' => 'badge-green',
                                        'no_show' => 'badge-red'
                                    ];
                                    $badge_class = $status_badges[$apt['status']] ?? 'badge-gray';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?></span>
                                </td>
                                <td>
                                    <?php if ($apt['status'] === 'in_progress'): ?>
                                        <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-primary">Continue</a>
                                    <?php elseif (in_array($apt['status'], ['scheduled', 'confirmed', 'checked_in'])): ?>
                                        <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-outline">Start</a>
                                    <?php else: ?>
                                        <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>&view=1" class="btn btn-sm btn-outline">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="section-header">QUICK ACTIONS</div>
        <div class="stats-grid">
            <a href="schedule.php" class="stat-card" style="text-decoration: none;">
                <div class="stat-icon blue">🗓️</div>
                <div class="stat-label">My Schedule</div>
                <div class="stat-value" style="font-size: 14px;">View weekly calendar</div>
            </a>
            <a href="patients.php" class="stat-card" style="text-decoration: none;">
                <div class="stat-icon green">👥</div>
                <div class="stat-label">My Patients</div>
                <div class="stat-value" style="font-size: 14px;">Search patient records</div>
            </a>
            <a href="prescriptions.php" class="stat-card" style="text-decoration: none;">
                <div class="stat-icon yellow">💊</div>
                <div class="stat-label">Prescriptions</div>
                <div class="stat-value" style="font-size: 14px;">Manage prescriptions</div>
            </a>
            <a href="lab-orders.php" class="stat-card" style="text-decoration: none;">
                <div class="stat-icon red">🔬</div>
                <div class="stat-label">Lab Orders</div>
                <div class="stat-value" style="font-size: 14px;">Order lab tests</div>
            </a>
        </div>

        <!-- Recent Activity -->
        <?php if (!empty($recent_activity)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Recent Activity</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $activity): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($activity['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($activity['start_time'])); ?></td>
                            <td><?php echo h($activity['patient_name'] ?? 'Unknown'); ?></td>
                            <td><span class="badge badge-green">✅ Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>