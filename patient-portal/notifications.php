<?php
/**
 * Patient Notifications Page
 * 
 * Displays notifications by querying existing bills and lab_tests tables.
 * Uses Option 1 approach - no separate notifications table needed.
 */

require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Notifications - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'notifications';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found. Please contact hospital reception.");
}

$patient_profile_id = $patient['id'];

// Get pending/overdue bills (Bill Notifications)
$stmt = $pdo->prepare("
    SELECT b.*, br.name as branch_name 
    FROM bills b
    LEFT JOIN branches br ON b.branch_id = br.id
    WHERE b.patient_id = ? AND b.payment_status IN ('pending', 'partial', 'overdue')
    ORDER BY 
        CASE b.payment_status 
            WHEN 'overdue' THEN 1 
            WHEN 'partial' THEN 2 
            ELSE 3 
        END,
        b.created_at DESC
    LIMIT 10
");
$stmt->execute([$patient_profile_id]);
$pending_bills = $stmt->fetchAll();

// Get recent lab results (completed in last 30 days)
$stmt = $pdo->prepare("
    SELECT lt.*, ltt.name as test_name, ltt.category, u.name as doctor_name
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
    LEFT JOIN users u ON dp.user_id = u.id
    WHERE lt.patient_id = ? AND lt.status = 'completed'
    AND lt.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY lt.completed_at DESC
    LIMIT 10
");
$stmt->execute([$patient_profile_id]);
$recent_lab_results = $stmt->fetchAll();

// Get upcoming appointments (in next 7 days)
$stmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name, dp.specialization, br.name as branch_name
    FROM appointments a
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u ON dp.user_id = u.id
    LEFT JOIN branches br ON a.branch_id = br.id
    WHERE a.patient_id = ? 
    AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND a.status IN ('scheduled', 'confirmed')
    ORDER BY a.appointment_date ASC, a.start_time ASC
    LIMIT 5
");
$stmt->execute([$patient_profile_id]);
$upcoming_appointments = $stmt->fetchAll();

// Count totals for summary
$total_notifications = count($pending_bills) + count($recent_lab_results) + count($upcoming_appointments);

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">🔔 Notifications</h1>
                <p class="page-subtitle">Stay updated with your bills, lab results, and appointments</p>
            </div>
            <div class="notification-badge-large">
                <?php echo $total_notifications; ?> Updates
            </div>
        </div>

        <?php if ($total_notifications == 0): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>All Caught Up!</h3>
                    <p>You have no new notifications at this time.</p>
                </div>
            </div>
        <?php else: ?>

            <!-- Pending Bills Section -->
            <?php if (count($pending_bills) > 0): ?>
                <div class="card notification-section">
                    <div class="card-header">
                        <h3 class="card-title">💳 Outstanding Bills</h3>
                        <span class="badge badge-warning">
                            <?php echo count($pending_bills); ?> pending
                        </span>
                    </div>
                    <div class="notification-list">
                        <?php foreach ($pending_bills as $bill): ?>
                            <div class="notification-item <?php echo $bill['payment_status'] === 'overdue' ? 'urgent' : ''; ?>">
                                <div class="notification-icon bill">💳</div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        Bill #
                                        <?php echo h($bill['bill_no']); ?>
                                        <?php if ($bill['payment_status'] === 'overdue'): ?>
                                            <span class="badge badge-danger">OVERDUE</span>
                                        <?php elseif ($bill['payment_status'] === 'partial'): ?>
                                            <span class="badge badge-warning">Partially Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-details">
                                        Amount Due: <strong>$
                                            <?php echo number_format($bill['due_amount'], 2); ?>
                                        </strong>
                                        <?php if ($bill['due_date']): ?>
                                            | Due:
                                            <?php echo date('d M Y', strtotime($bill['due_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-meta">
                                        <?php echo $bill['branch_name'] ?? 'N/A'; ?> •
                                        Created:
                                        <?php echo date('d M Y', strtotime($bill['created_at'])); ?>
                                    </div>
                                </div>
                                <a href="billing.php" class="btn btn-sm btn-primary">Pay Now</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Lab Results Section -->
            <?php if (count($recent_lab_results) > 0): ?>
                <div class="card notification-section">
                    <div class="card-header">
                        <h3 class="card-title">🔬 New Lab Results</h3>
                        <span class="badge badge-success">
                            <?php echo count($recent_lab_results); ?> ready
                        </span>
                    </div>
                    <div class="notification-list">
                        <?php foreach ($recent_lab_results as $lab): ?>
                            <div class="notification-item">
                                <div class="notification-icon lab">🔬</div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php echo h($lab['test_name']); ?>
                                        <span class="badge badge-success">Results Ready</span>
                                    </div>
                                    <div class="notification-details">
                                        Test #
                                        <?php echo h($lab['test_no']); ?>
                                        <?php if ($lab['category']): ?>
                                            | Category:
                                            <?php echo h($lab['category']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-meta">
                                        <?php if ($lab['doctor_name']): ?>
                                            Ordered by Dr.
                                            <?php echo h($lab['doctor_name']); ?> •
                                        <?php endif; ?>
                                        Completed:
                                        <?php echo date('d M Y', strtotime($lab['completed_at'])); ?>
                                    </div>
                                </div>
                                <a href="lab-results.php" class="btn btn-sm btn-outline">View Results</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upcoming Appointments Section -->
            <?php if (count($upcoming_appointments) > 0): ?>
                <div class="card notification-section">
                    <div class="card-header">
                        <h3 class="card-title">📅 Upcoming Appointments</h3>
                        <span class="badge badge-info">
                            <?php echo count($upcoming_appointments); ?> this week
                        </span>
                    </div>
                    <div class="notification-list">
                        <?php foreach ($upcoming_appointments as $apt): ?>
                            <?php
                            $apt_date = strtotime($apt['appointment_date']);
                            $is_today = date('Y-m-d', $apt_date) === date('Y-m-d');
                            $is_tomorrow = date('Y-m-d', $apt_date) === date('Y-m-d', strtotime('+1 day'));
                            ?>
                            <div class="notification-item <?php echo $is_today ? 'urgent' : ''; ?>">
                                <div class="notification-icon appointment">📅</div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        Appointment with Dr.
                                        <?php echo h($apt['doctor_name']); ?>
                                        <?php if ($is_today): ?>
                                            <span class="badge badge-danger">TODAY</span>
                                        <?php elseif ($is_tomorrow): ?>
                                            <span class="badge badge-warning">Tomorrow</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-details">
                                        <?php echo date('l, d M Y', $apt_date); ?> at
                                        <strong>
                                            <?php echo date('h:i A', strtotime($apt['start_time'])); ?>
                                        </strong>
                                    </div>
                                    <div class="notification-meta">
                                        <?php echo h($apt['specialization']); ?> •
                                        <?php echo h($apt['branch_name'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <a href="appointments.php" class="btn btn-sm btn-outline">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<style>
    .notification-badge-large {
        background: #4A7BF7;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 14px;
    }

    .notification-section {
        margin-bottom: 20px;
    }

    .notification-section .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 16px;
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
        border-left: 4px solid #e5e7eb;
    }

    .notification-item.urgent {
        border-left-color: #dc2626;
        background: #fef2f2;
    }

    .notification-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .notification-icon.bill {
        background: #fef3c7;
    }

    .notification-icon.lab {
        background: #dcfce7;
    }

    .notification-icon.appointment {
        background: #dbeafe;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .notification-details {
        color: #4b5563;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .notification-meta {
        color: #9ca3af;
        font-size: 12px;
    }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-success {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-info {
        background: #dbeafe;
        color: #2563eb;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    .empty-state h3 {
        color: #1f2937;
        margin-bottom: 8px;
    }

    @media (max-width: 768px) {
        .notification-item {
            flex-direction: column;
        }

        .notification-item .btn {
            align-self: flex-start;
            margin-top: 12px;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>