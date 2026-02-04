<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'Reports - St. George Hospital';
$page_css = '../admin-portal/css/admin-portal.css';
$current_page = 'reports';

// Get branch admin's branch
$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;

if (!$branch_id) {
    die("Branch not assigned. Please contact administrator.");
}

// Get branch info
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch = $stmt->fetch();

// Date range filter
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// ============================================
// APPOINTMENT STATISTICS
// ============================================
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments 
    WHERE branch_id = ? 
    AND DATE(appointment_date) BETWEEN ? AND ?
");
$stmt->execute([$branch_id, $date_from, $date_to]);
$appt_stats = $stmt->fetch();

// Appointments by doctor
$stmt = $pdo->prepare("
    SELECT u.name as doctor_name, COUNT(a.id) as count
    FROM appointments a
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u ON dp.user_id = u.id
    WHERE a.branch_id = ?
    AND DATE(a.appointment_date) BETWEEN ? AND ?
    GROUP BY dp.id, u.name
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$branch_id, $date_from, $date_to]);
$appt_by_doctor = $stmt->fetchAll();

// ============================================
// REVENUE STATISTICS
// ============================================
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_billed,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(due_amount), 0) as total_due
    FROM bills 
    WHERE branch_id = ? 
    AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$branch_id, $date_from, $date_to]);
$revenue_stats = $stmt->fetch();

// Recent payments
$stmt = $pdo->prepare("
    SELECT p.*, b.bill_no, u.name as patient_name
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN patient_profiles pp ON b.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE b.branch_id = ?
    AND DATE(p.paid_at) BETWEEN ? AND ?
    ORDER BY p.paid_at DESC
    LIMIT 10
");
$stmt->execute([$branch_id, $date_from, $date_to]);
$recent_payments = $stmt->fetchAll();

// ============================================
// STAFF & DOCTOR COUNTS
// ============================================
$stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_profiles WHERE branch_id = ? AND deleted_at IS NULL");
$stmt->execute([$branch_id]);
$doctor_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_profiles WHERE branch_id = ? AND deleted_at IS NULL");
$stmt->execute([$branch_id]);
$staff_count = $stmt->fetchColumn();

// ============================================
// LAB & PHARMACY ACTIVITY
// ============================================
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM lab_tests 
    WHERE branch_id = ? 
    AND status = 'completed'
    AND DATE(completed_at) BETWEEN ? AND ?
");
$stmt->execute([$branch_id, $date_from, $date_to]);
$lab_completed = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM prescriptions 
    WHERE status = 'fully_dispensed'
    AND DATE(updated_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$rx_dispensed = $stmt->fetchColumn();

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Reports</h1>
                <p class="page-subtitle">
                    <?php echo h($branch['name'] ?? 'Your Branch'); ?> - Performance Overview
                </p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card" style="margin-bottom: 24px;">
            <form method="GET" style="padding: 16px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-input" value="<?php echo h($date_from); ?>">
                </div>
                <div>
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-input" value="<?php echo h($date_to); ?>">
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #dbeafe;">📅</div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo $appt_stats['total'] ?? 0; ?>
                    </div>
                    <div class="stat-label">Appointments</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7;">💰</div>
                <div class="stat-content">
                    <div class="stat-value">$
                        <?php echo number_format($revenue_stats['total_paid'] ?? 0, 0); ?>
                    </div>
                    <div class="stat-label">Revenue Collected</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7;">🧪</div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo $lab_completed ?? 0; ?>
                    </div>
                    <div class="stat-label">Lab Tests Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f3e8ff;">💊</div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo $rx_dispensed ?? 0; ?>
                    </div>
                    <div class="stat-label">Prescriptions Dispensed</div>
                </div>
            </div>
        </div>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-top: 24px;">

            <!-- Appointment Status Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📅 Appointment Status</h3>
                </div>
                <div style="padding: 16px;">
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #dbeafe; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">
                                <?php echo $appt_stats['scheduled'] ?? 0; ?>
                            </div>
                            <div style="color: #1e40af;">Scheduled</div>
                        </div>
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #dcfce7; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">
                                <?php echo $appt_stats['completed'] ?? 0; ?>
                            </div>
                            <div style="color: #166534;">Completed</div>
                        </div>
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #fee2e2; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">
                                <?php echo $appt_stats['cancelled'] ?? 0; ?>
                            </div>
                            <div style="color: #991b1b;">Cancelled</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">💰 Revenue Summary</h3>
                </div>
                <div style="padding: 16px;">
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #f3f4f6; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">$
                                <?php echo number_format($revenue_stats['total_billed'] ?? 0, 0); ?>
                            </div>
                            <div style="color: #6b7280;">Total Billed</div>
                        </div>
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #dcfce7; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">$
                                <?php echo number_format($revenue_stats['total_paid'] ?? 0, 0); ?>
                            </div>
                            <div style="color: #166534;">Collected</div>
                        </div>
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #fef3c7; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">$
                                <?php echo number_format($revenue_stats['total_due'] ?? 0, 0); ?>
                            </div>
                            <div style="color: #d97706;">Outstanding</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👥 Staff Summary</h3>
                </div>
                <div style="padding: 16px;">
                    <div style="display: flex; gap: 16px;">
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #dbeafe; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">
                                <?php echo $doctor_count; ?>
                            </div>
                            <div style="color: #1e40af;">Active Doctors</div>
                        </div>
                        <div
                            style="flex: 1; text-align: center; padding: 16px; background: #f3e8ff; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: 600;">
                                <?php echo $staff_count; ?>
                            </div>
                            <div style="color: #7c3aed;">Active Staff</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments by Doctor -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👨‍⚕️ Appointments by Doctor</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appt_by_doctor as $d): ?>
                                <tr>
                                    <td>Dr.
                                        <?php echo h($d['doctor_name']); ?>
                                    </td>
                                    <td><span class="badge badge-blue">
                                            <?php echo $d['count']; ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($appt_by_doctor)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-gray">No appointments in this period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h3 class="card-title">💳 Recent Payments</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Bill #</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $p): ?>
                            <tr>
                                <td><strong>
                                        <?php echo h($p['receipt_no']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo h($p['bill_no']); ?>
                                </td>
                                <td>
                                    <?php echo h($p['patient_name']); ?>
                                </td>
                                <td>$
                                    <?php echo number_format($p['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php echo ucfirst($p['method']); ?>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($p['paid_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_payments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray">No payments in this period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>