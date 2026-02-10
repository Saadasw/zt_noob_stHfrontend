<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Archives';
$page_css = 'css/admin-portal.css';
$current_page = 'archives';

$message = '';
$error = '';

// Archive Stats
try {
    $archived_appointments = $pdo->query("SELECT COUNT(*) FROM appointments_archive")->fetchColumn();
    $archived_bills = $pdo->query("SELECT COUNT(*) FROM bills_archive")->fetchColumn();
    $archived_payments = $pdo->query("SELECT COUNT(*) FROM payments_archive")->fetchColumn();
    $archived_lab_tests = $pdo->query("SELECT COUNT(*) FROM lab_tests_archive")->fetchColumn();
} catch (PDOException $e) {
    // Tables may not exist yet
    $archived_appointments = 0;
    $archived_bills = 0;
    $archived_payments = 0;
    $archived_lab_tests = 0;
}

// Count active records eligible for archiving
try {
    $eligible_appointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status IN ('completed','cancelled','no_show') AND appointment_date < CURDATE() - INTERVAL 90 DAY")->fetchColumn();
    $eligible_bills = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status = 'paid' AND created_at < CURDATE() - INTERVAL 365 DAY")->fetchColumn();
    $eligible_lab_tests = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'completed' AND completed_at < CURDATE() - INTERVAL 180 DAY")->fetchColumn();
} catch (PDOException $e) {
    $eligible_appointments = 0;
    $eligible_bills = 0;
    $eligible_lab_tests = 0;
}

// Handle Archive Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_action'])) {
    $action = $_POST['archive_action'];
    $admin_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        if ($action === 'appointments') {
            // Archive old completed/cancelled/no_show appointments (older than 90 days)
            $stmt = $pdo->prepare("
                INSERT INTO appointments_archive 
                SELECT a.*, NOW(), ? 
                FROM appointments a 
                WHERE a.status IN ('completed','cancelled','no_show') 
                AND a.appointment_date < CURDATE() - INTERVAL 90 DAY
            ");
            $stmt->execute([$admin_id]);
            $count = $stmt->rowCount();

            // Delete archived records from source
            $pdo->exec("
                DELETE FROM appointments 
                WHERE status IN ('completed','cancelled','no_show') 
                AND appointment_date < CURDATE() - INTERVAL 90 DAY
            ");

            $message = "$count appointment(s) archived successfully.";

        } elseif ($action === 'bills') {
            // First: Archive payments for bills that will be archived
            // (Must happen BEFORE bill deletion due to CASCADE)
            $stmt = $pdo->prepare("
                INSERT INTO payments_archive 
                SELECT p.*, NOW(), ? 
                FROM payments p 
                INNER JOIN bills b ON p.bill_id = b.id 
                WHERE b.payment_status = 'paid' 
                AND b.created_at < CURDATE() - INTERVAL 365 DAY
            ");
            $stmt->execute([$admin_id]);
            $payment_count = $stmt->rowCount();

            // Then: Archive the bills
            $stmt = $pdo->prepare("
                INSERT INTO bills_archive 
                SELECT b.*, NOW(), ? 
                FROM bills b 
                WHERE b.payment_status = 'paid' 
                AND b.created_at < CURDATE() - INTERVAL 365 DAY
            ");
            $stmt->execute([$admin_id]);
            $bill_count = $stmt->rowCount();

            // Delete archived bills (CASCADE will clean up bill_items and payments)
            $pdo->exec("
                DELETE FROM bills 
                WHERE payment_status = 'paid' 
                AND created_at < CURDATE() - INTERVAL 365 DAY
            ");

            $message = "$bill_count bill(s) and $payment_count payment(s) archived successfully.";

        } elseif ($action === 'lab_tests') {
            // Archive old completed lab tests (older than 180 days)
            $stmt = $pdo->prepare("
                INSERT INTO lab_tests_archive 
                SELECT lt.*, NOW(), ? 
                FROM lab_tests lt 
                WHERE lt.status = 'completed' 
                AND lt.completed_at < CURDATE() - INTERVAL 180 DAY
            ");
            $stmt->execute([$admin_id]);
            $count = $stmt->rowCount();

            // Delete archived records from source
            $pdo->exec("
                DELETE FROM lab_tests 
                WHERE status = 'completed' 
                AND completed_at < CURDATE() - INTERVAL 180 DAY
            ");

            $message = "$count lab test(s) archived successfully.";
        }

        $pdo->commit();

        // Refresh counts
        $archived_appointments = $pdo->query("SELECT COUNT(*) FROM appointments_archive")->fetchColumn();
        $archived_bills = $pdo->query("SELECT COUNT(*) FROM bills_archive")->fetchColumn();
        $archived_payments = $pdo->query("SELECT COUNT(*) FROM payments_archive")->fetchColumn();
        $archived_lab_tests = $pdo->query("SELECT COUNT(*) FROM lab_tests_archive")->fetchColumn();

        $eligible_appointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status IN ('completed','cancelled','no_show') AND appointment_date < CURDATE() - INTERVAL 90 DAY")->fetchColumn();
        $eligible_bills = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status = 'paid' AND created_at < CURDATE() - INTERVAL 365 DAY")->fetchColumn();
        $eligible_lab_tests = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'completed' AND completed_at < CURDATE() - INTERVAL 180 DAY")->fetchColumn();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Archive failed: " . $e->getMessage();
    }
}

// Current tab
$tab = $_GET['tab'] ?? 'overview';

// Fetch archived records for display
$archived_data = [];
try {
    if ($tab === 'appointments') {
        $archived_data = $pdo->query("SELECT * FROM appointments_archive ORDER BY archived_at DESC LIMIT 50")->fetchAll();
    } elseif ($tab === 'bills') {
        $archived_data = $pdo->query("SELECT * FROM bills_archive ORDER BY archived_at DESC LIMIT 50")->fetchAll();
    } elseif ($tab === 'lab_tests') {
        $archived_data = $pdo->query("SELECT * FROM lab_tests_archive ORDER BY archived_at DESC LIMIT 50")->fetchAll();
    }
} catch (PDOException $e) {
    // Tables may not exist yet
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">📦 Data Archives</h1>
                <p class="page-subtitle">Manage archived records | Historical data storage</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <!-- Archive Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Archived Appointments</div>
                <div class="stat-value">
                    <?php echo number_format($archived_appointments); ?>
                </div>
                <a href="archives.php?tab=appointments" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💳</div>
                <div class="stat-label">Archived Bills</div>
                <div class="stat-value">
                    <?php echo number_format($archived_bills); ?>
                </div>
                <a href="archives.php?tab=bills" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">🧾</div>
                <div class="stat-label">Archived Payments</div>
                <div class="stat-value">
                    <?php echo number_format($archived_payments); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🔬</div>
                <div class="stat-label">Archived Lab Tests</div>
                <div class="stat-value">
                    <?php echo number_format($archived_lab_tests); ?>
                </div>
                <a href="archives.php?tab=lab_tests" class="stat-link">View →</a>
            </div>
        </div>

        <!-- Archive Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Archive Old Records</h3>
            </div>
            <div class="grid-3" style="padding: 16px;">
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 16px;">
                    <h4 style="margin: 0 0 8px 0;">📅 Appointments</h4>
                    <p class="text-sm text-gray" style="margin: 0 0 8px 0;">
                        Completed/cancelled appointments older than 90 days
                    </p>
                    <p style="margin: 0 0 12px 0; font-weight: bold;">
                        <?php echo $eligible_appointments; ?> eligible record(s)
                    </p>
                    <form method="POST"
                        onsubmit="return confirm('Archive <?php echo $eligible_appointments; ?> appointment(s)? This will move them from the active table to the archive.');">
                        <input type="hidden" name="archive_action" value="appointments">
                        <button type="submit" class="btn btn-sm btn-primary" <?php echo $eligible_appointments == 0 ? 'disabled' : ''; ?>>
                            Archive Now
                        </button>
                    </form>
                </div>
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 16px;">
                    <h4 style="margin: 0 0 8px 0;">💳 Bills & Payments</h4>
                    <p class="text-sm text-gray" style="margin: 0 0 8px 0;">
                        Fully paid bills older than 1 year (includes associated payments)
                    </p>
                    <p style="margin: 0 0 12px 0; font-weight: bold;">
                        <?php echo $eligible_bills; ?> eligible bill(s)
                    </p>
                    <form method="POST"
                        onsubmit="return confirm('Archive <?php echo $eligible_bills; ?> bill(s) and their payments? This will move them from the active tables to the archive.');">
                        <input type="hidden" name="archive_action" value="bills">
                        <button type="submit" class="btn btn-sm btn-primary" <?php echo $eligible_bills == 0 ? 'disabled' : ''; ?>>
                            Archive Now
                        </button>
                    </form>
                </div>
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 16px;">
                    <h4 style="margin: 0 0 8px 0;">🔬 Lab Tests</h4>
                    <p class="text-sm text-gray" style="margin: 0 0 8px 0;">
                        Completed lab tests older than 180 days
                    </p>
                    <p style="margin: 0 0 12px 0; font-weight: bold;">
                        <?php echo $eligible_lab_tests; ?> eligible record(s)
                    </p>
                    <form method="POST"
                        onsubmit="return confirm('Archive <?php echo $eligible_lab_tests; ?> lab test(s)? This will move them from the active table to the archive.');">
                        <input type="hidden" name="archive_action" value="lab_tests">
                        <button type="submit" class="btn btn-sm btn-primary" <?php echo $eligible_lab_tests == 0 ? 'disabled' : ''; ?>>
                            Archive Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <div class="flex gap-2">
                    <a href="archives.php?tab=overview"
                        class="btn btn-sm <?php echo $tab === 'overview' ? 'btn-primary' : 'btn-outline'; ?>">Overview</a>
                    <a href="archives.php?tab=appointments"
                        class="btn btn-sm <?php echo $tab === 'appointments' ? 'btn-primary' : 'btn-outline'; ?>">Appointments</a>
                    <a href="archives.php?tab=bills"
                        class="btn btn-sm <?php echo $tab === 'bills' ? 'btn-primary' : 'btn-outline'; ?>">Bills</a>
                    <a href="archives.php?tab=lab_tests"
                        class="btn btn-sm <?php echo $tab === 'lab_tests' ? 'btn-primary' : 'btn-outline'; ?>">Lab
                        Tests</a>
                </div>
            </div>

            <?php if ($tab === 'overview'): ?>
                <div style="padding: 24px; text-align: center;">
                    <p class="text-gray">Select a tab above to view archived records, or use the archive actions to move old
                        records.</p>
                    <p class="text-sm text-gray" style="margin-top: 8px;">
                        Archive tables store historical records that have been moved from active tables to improve system
                        performance.
                        Archived data is preserved and can be viewed here but is no longer included in active queries.
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'appointments'): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Appt No</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Archived At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_data as $row): ?>
                                <tr>
                                    <td>
                                        <?php echo h($row['appointment_no']); ?>
                                    </td>
                                    <td>
                                        <?php echo h($row['appointment_date']); ?>
                                    </td>
                                    <td>
                                        <?php echo h($row['start_time']); ?> -
                                        <?php echo h($row['end_time']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badge = 'badge-gray';
                                        if ($row['status'] === 'completed')
                                            $status_badge = 'badge-green';
                                        if ($row['status'] === 'cancelled')
                                            $status_badge = 'badge-red';
                                        ?>
                                        <span class="badge <?php echo $status_badge; ?>">
                                            <?php echo ucfirst(h($row['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-sm text-gray">
                                        <?php echo h($row['archived_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($archived_data)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray">No archived appointments.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'bills'): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Archived At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_data as $row): ?>
                                <tr>
                                    <td>
                                        <?php echo h($row['bill_no']); ?>
                                    </td>
                                    <td>$
                                        <?php echo number_format($row['total_amount'], 2); ?>
                                    </td>
                                    <td>$
                                        <?php echo number_format($row['paid_amount'], 2); ?>
                                    </td>
                                    <td><span class="badge badge-green">
                                            <?php echo ucfirst(h($row['payment_status'])); ?>
                                        </span></td>
                                    <td class="text-sm text-gray">
                                        <?php echo h($row['archived_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($archived_data)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray">No archived bills.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'lab_tests'): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Test No</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Completed At</th>
                                <th>Archived At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_data as $row): ?>
                                <tr>
                                    <td>
                                        <?php echo h($row['test_no']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $priority_badge = 'badge-gray';
                                        if ($row['priority'] === 'urgent')
                                            $priority_badge = 'badge-red';
                                        if ($row['priority'] === 'stat')
                                            $priority_badge = 'badge-yellow';
                                        ?>
                                        <span class="badge <?php echo $priority_badge; ?>">
                                            <?php echo ucfirst(h($row['priority'])); ?>
                                        </span>
                                    </td>
                                    <td><span class="badge badge-green">
                                            <?php echo ucfirst(h($row['status'])); ?>
                                        </span></td>
                                    <td class="text-sm">
                                        <?php echo h($row['completed_at']); ?>
                                    </td>
                                    <td class="text-sm text-gray">
                                        <?php echo h($row['archived_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($archived_data)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray">No archived lab tests.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
    .alert-success {
        background: #dcfce7;
        color: #166534;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    @media (max-width: 768px) {
        .grid-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>