<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'Branch Appointments';
$page_css = 'css/branch-admin-portal.css';
$current_page = 'appointments';

$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;
if (!$branch_id) {
    die("Branch not assigned.");
}

// Filters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';

// Base query
$sql = "
    SELECT a.*, 
           u_pat.name as patient_name, pp.patient_id as patient_code,
           u_doc.name as doctor_name, dp.specialization
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE a.branch_id = ?
";
$params = [$branch_id];

if ($date_filter) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}
if ($status_filter) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Appointments</h1>
                <p class="page-subtitle">View appointments at your branch</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <form method="GET" class="flex gap-4 items-center">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-input" value="<?php echo h($date_filter); ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>
                            >Scheduled</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>
                            >Confirmed</option>
                        <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>
                            >Checked In</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>
                            >Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>
                            >Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Filter</button>
                <a href="appointments.php" class="btn btn-outline" style="margin-top: 20px;">Reset</a>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📅 Appointments (
                    <?php echo count($appointments); ?>)
                </h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Appt No</th>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><strong>
                                        <?php echo h($apt['appointment_no']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo date('h:i A', strtotime($apt['start_time'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($apt['patient_name']); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo h($apt['patient_code']); ?>
                                    </div>
                                </td>
                                <td>
                                    Dr.
                                    <?php echo h($apt['doctor_name']); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo h($apt['specialization']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h(substr($apt['reason'] ?? '-', 0, 50)); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'scheduled' => '<span class="badge badge-blue">Scheduled</span>',
                                        'confirmed' => '<span class="badge badge-green">Confirmed</span>',
                                        'checked_in' => '<span class="badge badge-yellow">Checked In</span>',
                                        'in_progress' => '<span class="badge badge-yellow">In Progress</span>',
                                        'completed' => '<span class="badge badge-green">Completed</span>',
                                        'cancelled' => '<span class="badge badge-red">Cancelled</span>',
                                        'no_show' => '<span class="badge badge-gray">No Show</span>',
                                    ];
                                    echo $status_badges[$apt['status']] ?? $apt['status'];
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray">No appointments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>