<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Today\'s Appointments - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'appointments';

$message = '';

// Get Doctor Profile for logged-in user
$stmt = $pdo->prepare("SELECT id, specialization FROM doctor_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor profile not found for this user. Please contact admin.");
}

$doctor_id = $doctor['id'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $apt_id = $_POST['apt_id'];
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $apt_id]);
    $message = "Appointment status updated.";
}

// Fetch Today's Appointments for this Doctor
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, 
           u_pat.name as patient_name, pp.patient_id as patient_code, pp.gender, pp.date_of_birth, pp.blood_group, pp.medical_history
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY a.start_time ASC
");
$stmt->execute([$doctor_id, $today]);
$appointments = $stmt->fetchAll();

// Group by status
$current = array_filter($appointments, fn($a) => $a['status'] === 'in_progress');
$waiting = array_filter($appointments, fn($a) => in_array($a['status'], ['checked_in', 'confirmed', 'scheduled']));
$completed = array_filter($appointments, fn($a) => in_array($a['status'], ['completed', 'cancelled', 'no_show']));

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Today's Appointments</h1>
                <p class="page-subtitle"><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <span class="badge badge-blue">Total: <?php echo count($appointments); ?></span>
                <span class="badge badge-green">Completed: <?php echo count($completed); ?></span>
                <span class="badge badge-yellow">Waiting: <?php echo count($waiting); ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <?php if (count($current) > 0): ?>
        <div class="section-header">🔵 CURRENT PATIENT</div>
        <?php foreach ($current as $apt): ?>
        <div class="patient-card current" style="border-left: 4px solid #2563eb;">
            <div class="patient-header">
                <div>
                    <div class="patient-name"><?php echo date('h:i A', strtotime($apt['start_time'])); ?> | <?php echo h($apt['patient_name']); ?></div>
                    <div class="patient-meta">
                        Patient ID: <?php echo h($apt['patient_code']); ?> | 
                        <?php echo h($apt['gender']); ?> | 
                        Blood: <?php echo h($apt['blood_group']); ?>
                    </div>
                </div>
                <span class="badge badge-blue">In Progress</span>
            </div>
            <div class="patient-reason">
                <strong>Reason:</strong> <?php echo h($apt['reason'] ?: 'Not specified'); ?>
            </div>
            <?php if (!empty($apt['medical_history'])): ?>
            <div class="patient-alerts">
                📋 <?php echo h($apt['medical_history']); ?>
            </div>
            <?php endif; ?>
            <div class="flex gap-2 mt-4">
                <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>" class="btn btn-primary">📝 Continue Consultation</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                    <input type="hidden" name="new_status" value="completed">
                    <button type="submit" class="btn btn-success">✅ Mark Complete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($waiting) > 0): ?>
        <div class="section-header">⏳ WAITING QUEUE (<?php echo count($waiting); ?>)</div>
        <?php foreach ($waiting as $apt): ?>
        <div class="patient-card waiting" style="border-left: 4px solid #eab308;">
            <div class="patient-header">
                <div>
                    <div class="patient-name"><?php echo date('h:i A', strtotime($apt['start_time'])); ?> | <?php echo h($apt['patient_name']); ?> (<?php echo h($apt['patient_code']); ?>)</div>
                    <div class="patient-meta"><?php echo h($apt['gender']); ?> | Reason: <?php echo h($apt['reason'] ?: 'Not specified'); ?></div>
                </div>
                <span class="badge badge-yellow">⏳ <?php echo ucfirst($apt['status']); ?></span>
            </div>
            <div class="flex gap-2 mt-4">
                <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-primary">📝 Start Consultation</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                    <input type="hidden" name="new_status" value="no_show">
                    <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Mark as No Show?');">❌ No-Show</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($waiting) === 0 && count($current) === 0): ?>
        <div class="card">
            <p class="text-center text-gray p-4">No patients waiting. Great job! 🎉</p>
        </div>
        <?php endif; ?>

        <?php if (count($completed) > 0): ?>
        <div class="section-header">✅ COMPLETED TODAY (<?php echo count($completed); ?>)</div>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed as $apt): ?>
                        <tr>
                            <td><?php echo date('h:i A', strtotime($apt['start_time'])); ?></td>
                            <td><?php echo h($apt['patient_name']); ?> (<?php echo h($apt['patient_code']); ?>)</td>
                            <td>
                                <?php if ($apt['status'] === 'completed'): ?>
                                    <span class="badge badge-green">✅ Completed</span>
                                <?php elseif ($apt['status'] === 'cancelled'): ?>
                                    <span class="badge badge-red">❌ Cancelled</span>
                                <?php else: ?>
                                    <span class="badge badge-red">⚠️ No Show</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>&view=1" class="btn btn-sm btn-outline">View Record</a>
                            </td>
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
