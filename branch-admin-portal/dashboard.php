<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'Branch Dashboard';
$page_css = 'css/branch-admin-portal.css';
$current_page = 'dashboard';

// Get Branch Admin's branch_id
$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;

if (!$branch_id) {
    die("Branch not assigned. Contact system administrator.");
}

// Get branch info
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch = $stmt->fetch();

// Stats for this branch only
// Doctors count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_profiles WHERE branch_id = ? AND deleted_at IS NULL");
$stmt->execute([$branch_id]);
$doctors_count = $stmt->fetchColumn();

// Staff count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_profiles WHERE branch_id = ? AND deleted_at IS NULL");
$stmt->execute([$branch_id]);
$staff_count = $stmt->fetchColumn();

// Today's appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE branch_id = ? AND appointment_date = CURDATE()");
$stmt->execute([$branch_id]);
$today_appointments = $stmt->fetchColumn();

// Pending appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE branch_id = ? AND status = 'scheduled' AND appointment_date >= CURDATE()");
$stmt->execute([$branch_id]);
$pending_appointments = $stmt->fetchColumn();

// Recent appointments
$stmt = $pdo->prepare("
    SELECT a.*, u_pat.name as patient_name, u_doc.name as doctor_name, dp.specialization
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE a.branch_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.start_time ASC
    LIMIT 10
");
$stmt->execute([$branch_id]);
$recent_appointments = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Dashboard</h1>
                <p class="page-subtitle">
                    <?php echo h($branch['name']); ?> -
                    <?php echo h($branch['city']); ?>
                </p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">👨‍⚕️</div>
                <div class="stat-label">Doctors</div>
                <div class="stat-value">
                    <?php echo $doctors_count; ?>
                </div>
                <a href="doctors.php" class="stat-link">Manage →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">👤</div>
                <div class="stat-label">Staff Members</div>
                <div class="stat-value">
                    <?php echo $staff_count; ?>
                </div>
                <a href="staff.php" class="stat-link">Manage →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">📅</div>
                <div class="stat-label">Today's Appointments</div>
                <div class="stat-value">
                    <?php echo $today_appointments; ?>
                </div>
                <a href="appointments.php" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">⏳</div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">
                    <?php echo $pending_appointments; ?>
                </div>
                <a href="appointments.php" class="stat-link">View →</a>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📅 Upcoming Appointments</h3>
                <a href="appointments.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $apt): ?>
                            <tr>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo date('h:i A', strtotime($apt['start_time'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($apt['patient_name']); ?>
                                </td>
                                <td>
                                    Dr.
                                    <?php echo h($apt['doctor_name']); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo h($apt['specialization']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'scheduled' => '<span class="badge badge-blue">Scheduled</span>',
                                        'confirmed' => '<span class="badge badge-green">Confirmed</span>',
                                        'checked_in' => '<span class="badge badge-yellow">Checked In</span>',
                                        'completed' => '<span class="badge badge-green">Completed</span>',
                                        'cancelled' => '<span class="badge badge-red">Cancelled</span>',
                                    ];
                                    echo $status_badges[$apt['status']] ?? $apt['status'];
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_appointments)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray">No upcoming appointments.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Branch Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏥 Branch Information</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item"><label>Branch Name</label>
                    <p>
                        <?php echo h($branch['name']); ?>
                    </p>
                </div>
                <div class="profile-item"><label>Code</label>
                    <p>
                        <?php echo h($branch['code']); ?>
                    </p>
                </div>
                <div class="profile-item"><label>Address</label>
                    <p>
                        <?php echo h($branch['address']); ?>
                    </p>
                </div>
                <div class="profile-item"><label>City</label>
                    <p>
                        <?php echo h($branch['city']); ?>,
                        <?php echo h($branch['state']); ?>
                    </p>
                </div>
                <div class="profile-item"><label>Phone</label>
                    <p>
                        <?php echo h($branch['phone'] ?? 'N/A'); ?>
                    </p>
                </div>
                <div class="profile-item"><label>Email</label>
                    <p>
                        <?php echo h($branch['email'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>