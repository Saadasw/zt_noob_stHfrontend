<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Reschedule Appointment - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'appointments';

$message = '';
$error = '';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found.");
}

$patient_profile_id = $patient['id'];

// Get appointment ID from URL
$apt_id = $_GET['apt_id'] ?? null;

if (!$apt_id) {
    header("Location: appointments.php");
    exit();
}

// Fetch the appointment
$stmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name, dp.specialization, dp.id as doctor_id
    FROM appointments a 
    JOIN doctor_profiles dp ON a.doctor_id = dp.id 
    JOIN users u ON dp.user_id = u.id
    WHERE a.id = ? AND a.patient_id = ? AND a.status NOT IN ('completed', 'cancelled', 'no_show')
");
$stmt->execute([$apt_id, $patient_profile_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: appointments.php?error=not_found");
    exit();
}

// Handle reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule'])) {
    $new_date = $_POST['appointment_date'];
    $new_time = $_POST['appointment_time'];

    if (empty($new_date) || empty($new_time)) {
        $error = "Please select a new date and time.";
    } else {
        try {
            $end_time = date('H:i:s', strtotime($new_time) + 1800);

            $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ?, status = 'scheduled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_date, $new_time, $end_time, $apt_id]);

            $message = "Appointment rescheduled successfully!";

            // Refresh appointment data
            $stmt = $pdo->prepare("SELECT a.*, u.name as doctor_name, dp.specialization FROM appointments a JOIN doctor_profiles dp ON a.doctor_id = dp.id JOIN users u ON dp.user_id = u.id WHERE a.id = ?");
            $stmt->execute([$apt_id]);
            $appointment = $stmt->fetch();

        } catch (PDOException $e) {
            $error = "Reschedule failed: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Reschedule Appointment</h1>
                <p class="page-subtitle">Change the date or time of your appointment</p>
            </div>
            <a href="appointments.php" class="btn btn-outline">← Back to Appointments</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo h($message); ?>
            </div>
            <div class="card">
                <p class="text-center">
                    <a href="appointments.php" class="btn btn-primary">View My Appointments</a>
                </p>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo h($error); ?>
                </div>
            <?php endif; ?>

            <!-- Current Appointment Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Appointment</h3>
                </div>
                <div class="profile-grid">
                    <div class="profile-item">
                        <label>Appointment No</label>
                        <p>
                            <?php echo h($appointment['appointment_no']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Doctor</label>
                        <p>Dr.
                            <?php echo h($appointment['doctor_name']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Specialization</label>
                        <p>
                            <?php echo h($appointment['specialization']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Current Date</label>
                        <p>
                            <?php echo date('l, d F Y', strtotime($appointment['appointment_date'])); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Current Time</label>
                        <p>
                            <?php echo date('h:i A', strtotime($appointment['start_time'])); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Status</label>
                        <p><span class="badge badge-<?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span></p>
                    </div>
                </div>
            </div>

            <!-- Reschedule Form -->
            <form method="POST">
                <input type="hidden" name="reschedule" value="1">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select New Date & Time</h3>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">New Date *</label>
                            <input type="date" name="appointment_date" class="form-input" required
                                min="<?php echo date('Y-m-d'); ?>" value="<?php echo $appointment['appointment_date']; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Time *</label>
                            <select name="appointment_time" class="form-select" required>
                                <option value="">Select Time...</option>
                                <?php
                                for ($h = 9; $h < 17; $h++) {
                                    for ($m = 0; $m < 60; $m += 30) {
                                        $time = sprintf('%02d:%02d', $h, $m);
                                        $display = date('g:i A', strtotime($time));
                                        $selected = ($time === date('H:i', strtotime($appointment['start_time']))) ? 'selected' : '';
                                        echo "<option value=\"$time\" $selected>$display</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <p class="text-sm text-gray">Note: Rescheduled appointments are subject to doctor availability.</p>
                </div>

                <div class="flex gap-2">
                    <a href="appointments.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">📅 Reschedule Appointment</button>
                </div>
            </form>
        <?php endif; ?>

    </main>
</div>

<style>
    .badge-scheduled {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-confirmed {
        background: #dcfce7;
        color: #166534;
    }

    .badge-checked_in {
        background: #fef3c7;
        color: #92400e;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<?php include '../includes/footer.php'; ?>