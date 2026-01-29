<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Reports - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'reports';

// Fetch statistics
$stats = [];

// Total Users by Role
$stats['users'] = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE is_active = 1 GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

// Total Appointments
$stats['appointments_today'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$stats['appointments_month'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())")->fetchColumn();
$stats['appointments_completed'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed' AND MONTH(appointment_date) = MONTH(CURDATE())")->fetchColumn();

// Revenue
try {
    $stats['revenue_today'] = $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM bills WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stats['revenue_month'] = $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM bills WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
    $stats['outstanding'] = $pdo->query("SELECT COALESCE(SUM(due_amount), 0) FROM bills WHERE payment_status IN ('pending', 'partial', 'overdue')")->fetchColumn();
} catch (PDOException $e) {
    $stats['revenue_today'] = 0;
    $stats['revenue_month'] = 0;
    $stats['outstanding'] = 0;
}

// Patients
$stats['total_patients'] = $pdo->query("SELECT COUNT(*) FROM patient_profiles")->fetchColumn();
$stats['new_patients_month'] = $pdo->query("SELECT COUNT(*) FROM patient_profiles WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

// Lab Tests
try {
    $stats['lab_pending'] = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status NOT IN ('completed', 'cancelled')")->fetchColumn();
    $stats['lab_completed_today'] = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'completed' AND DATE(completed_at) = CURDATE()")->fetchColumn();
} catch (PDOException $e) {
    $stats['lab_pending'] = 0;
    $stats['lab_completed_today'] = 0;
}

// Recent Appointments
$recent_appointments = $pdo->query("
    SELECT a.appointment_no, a.appointment_date, a.status, 
           u_pat.name as patient_name, u_doc.name as doctor_name
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Reports & Analytics</h1>
                <p class="page-subtitle"><?php echo date('F Y'); ?> Overview</p>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-value"><?php echo number_format($stats['total_patients']); ?></div>
                <div class="stat-link">+<?php echo $stats['new_patients_month']; ?> this month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <div class="stat-label">Revenue (This Month)</div>
                <div class="stat-value">$<?php echo number_format($stats['revenue_month'], 2); ?></div>
                <div class="stat-link">Today: $<?php echo number_format($stats['revenue_today'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">📅</div>
                <div class="stat-label">Appointments (This Month)</div>
                <div class="stat-value"><?php echo $stats['appointments_month']; ?></div>
                <div class="stat-link"><?php echo $stats['appointments_completed']; ?> completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">💳</div>
                <div class="stat-label">Outstanding Bills</div>
                <div class="stat-value">$<?php echo number_format($stats['outstanding'], 2); ?></div>
                <div class="stat-link">Pending collection</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- User Breakdown -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Users by Role</h3></div>
                <div style="padding: 16px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                        <span>👨‍⚕️ Doctors</span>
                        <strong><?php echo $stats['users']['doctor'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                        <span>👩‍💼 Staff</span>
                        <strong><?php echo $stats['users']['staff'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                        <span>👤 Patients</span>
                        <strong><?php echo $stats['users']['patient'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>🔐 Admins</span>
                        <strong><?php echo $stats['users']['admin'] ?? 0; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Lab Stats -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Laboratory</h3></div>
                <div style="padding: 16px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                        <span>🔬 Pending Tests</span>
                        <strong style="color: #f59e0b;"><?php echo $stats['lab_pending']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>✅ Completed Today</span>
                        <strong style="color: #22c55e;"><?php echo $stats['lab_completed_today']; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recent Appointments</h3></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment #</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $apt): ?>
                        <tr>
                            <td><strong><?php echo h($apt['appointment_no']); ?></strong></td>
                            <td><?php echo h($apt['patient_name']); ?></td>
                            <td><?php echo h($apt['doctor_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'scheduled' => 'badge-yellow',
                                    'confirmed' => 'badge-blue',
                                    'completed' => 'badge-green',
                                    'cancelled' => 'badge-red',
                                ];
                                $class = $badges[$apt['status']] ?? 'badge-gray';
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo ucfirst($apt['status']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Options -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Export Reports</h3></div>
            <div class="flex gap-2" style="padding: 16px;">
                <button class="btn btn-outline">📊 Daily Summary (PDF)</button>
                <button class="btn btn-outline">📈 Monthly Revenue (Excel)</button>
                <button class="btn btn-outline">👥 Patient List (CSV)</button>
                <button class="btn btn-outline">📅 Appointment Log (CSV)</button>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
