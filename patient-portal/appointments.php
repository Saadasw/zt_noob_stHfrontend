<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'My Appointments - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'appointments';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found.");
}
$patient_profile_id = $patient['id'];

$message = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'upcoming';

// Handle Cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $apt_id = $_POST['apt_id'];
    // Check if appointment is at least 24 hours away
    $stmt = $pdo->prepare("SELECT appointment_date, start_time FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->execute([$apt_id, $patient_profile_id]);
    $apt = $stmt->fetch();

    if ($apt) {
        $apt_datetime = strtotime($apt['appointment_date'] . ' ' . $apt['start_time']);
        $hours_until = ($apt_datetime - time()) / 3600;

        if ($hours_until < 24) {
            $error = "Cannot cancel appointment less than 24 hours before scheduled time.";
        } else {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = 'Cancelled by patient' WHERE id = ?");
            $stmt->execute([$apt_id]);
            $message = "Appointment cancelled successfully.";
        }
    }
}

// Fetch Upcoming Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name, dp.specialization, b.name as branch_name
    FROM appointments a 
    JOIN doctor_profiles dp ON a.doctor_id = dp.id 
    JOIN users u ON dp.user_id = u.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status NOT IN ('completed', 'cancelled', 'no_show')
    ORDER BY a.appointment_date ASC, a.start_time ASC
");
$stmt->execute([$patient_profile_id]);
$upcoming = $stmt->fetchAll();

// Fetch Past Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name, dp.specialization
    FROM appointments a 
    JOIN doctor_profiles dp ON a.doctor_id = dp.id 
    JOIN users u ON dp.user_id = u.id
    WHERE a.patient_id = ? AND (a.appointment_date < CURDATE() OR a.status IN ('completed', 'cancelled', 'no_show'))
    ORDER BY a.appointment_date DESC
    LIMIT 10
");
$stmt->execute([$patient_profile_id]);
$past = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Appointments</h1>
                <p class="page-subtitle">View and manage your appointments</p>
            </div>
            <a href="book-appointment.php" class="btn btn-primary">➕ Book New Appointment</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=upcoming" class="tab <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>">Upcoming
                (<?php echo count($upcoming); ?>)</a>
            <a href="?tab=past" class="tab <?php echo $active_tab === 'past' ? 'active' : ''; ?>">Past Appointments</a>
        </div>

        <?php if ($active_tab === 'upcoming'): ?>
            <!-- Upcoming Appointments -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Appointments</h3>
                </div>

                <?php if (count($upcoming) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $apt): ?>
                                    <tr>
                                        <td><strong><?php echo h($apt['appointment_no']); ?></strong></td>
                                        <td>
                                            <div style="font-weight: 500;">
                                                <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></div>
                                            <div class="text-sm text-gray">
                                                <?php echo date('h:i A', strtotime($apt['start_time'])); ?></div>
                                        </td>
                                        <td><?php echo h($apt['doctor_name']); ?></td>
                                        <td><?php echo h($apt['specialization']); ?></td>
                                        <td>
                                            <?php
                                            $status_badges = [
                                                'scheduled' => '<span class="badge badge-yellow">Scheduled</span>',
                                                'confirmed' => '<span class="badge badge-green">Confirmed</span>',
                                                'checked_in' => '<span class="badge badge-blue">Checked In</span>',
                                                'in_progress' => '<span class="badge badge-blue">In Progress</span>',
                                            ];
                                            echo $status_badges[$apt['status']] ?? '<span class="badge">' . ucfirst($apt['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="book-appointment.php?reschedule=<?php echo $apt['id']; ?>"
                                                class="btn btn-sm btn-outline">Reschedule</a>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                <input type="hidden" name="cancel_appointment" value="1">
                                                <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline"
                                                    style="color: #dc2626;">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray p-4">No upcoming appointments. <a href="book-appointment.php">Book one
                            now</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'past'): ?>
            <!-- Past Appointments -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Past Appointments</h3>
                </div>

                <?php if (count($past) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past as $apt): ?>
                                    <tr>
                                        <td><strong><?php echo h($apt['appointment_no']); ?></strong></td>
                                        <td>
                                            <div style="font-weight: 500;">
                                                <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></div>
                                            <div class="text-sm text-gray">
                                                <?php echo date('h:i A', strtotime($apt['start_time'])); ?></div>
                                        </td>
                                        <td><?php echo h($apt['doctor_name']); ?></td>
                                        <td><?php echo h($apt['specialization']); ?></td>
                                        <td>
                                            <?php if ($apt['status'] === 'completed'): ?>
                                                <span class="badge badge-gray">Completed</span>
                                            <?php elseif ($apt['status'] === 'cancelled'): ?>
                                                <span class="badge badge-red">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge badge-red">No Show</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="medical-records.php?apt_id=<?php echo $apt['id']; ?>"
                                                class="btn btn-sm btn-outline">View Record</a>
                                            <button class="btn btn-sm btn-primary">Book Follow-up</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray p-4">No past appointments.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>