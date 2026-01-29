<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Book Appointment - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'book-appointment';

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

// Fetch available doctors
$doctors = $pdo->query("
    SELECT dp.id, dp.specialization, dp.consultation_fee, u.name, b.name as branch_name
    FROM doctor_profiles dp
    JOIN users u ON dp.user_id = u.id
    LEFT JOIN branches b ON dp.branch_id = b.id
    WHERE dp.is_available = 1 AND u.is_active = 1
    ORDER BY dp.specialization, u.name
")->fetchAll();

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    if (empty($doctor_id) || empty($date) || empty($time)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Get doctor's branch
            $stmt = $pdo->prepare("SELECT branch_id FROM doctor_profiles WHERE id = ?");
            $stmt->execute([$doctor_id]);
            $doc = $stmt->fetch();
            $branch_id = $doc['branch_id'] ?? 'BR-MEL-01';

            $apptId = 'APT-' . bin2hex(random_bytes(4));
            $apptNo = 'APT-' . date('Y') . '-' . mt_rand(100000, 999999);
            $start_time = $time;
            $end_time = date('H:i:s', strtotime($time) + 1800);

            $stmt = $pdo->prepare("INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$apptId, $apptNo, $patient_profile_id, $doctor_id, $branch_id, $date, $start_time, $end_time, $reason]);

            $message = "Appointment booked successfully! Your appointment number is: $apptNo";

        } catch (PDOException $e) {
            $error = "Booking failed: " . $e->getMessage();
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
                <h1 class="page-title">Book Appointment</h1>
                <p class="page-subtitle">Schedule a new appointment with a doctor</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
            <div class="card">
                <p class="text-center">
                    <a href="appointments.php" class="btn btn-primary">View My Appointments</a>
                </p>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="book_appointment" value="1">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 1: Select Doctor</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Choose a Doctor *</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">Select Doctor...</option>
                            <?php
                            $current_spec = '';
                            foreach ($doctors as $d):
                                if ($d['specialization'] !== $current_spec) {
                                    if ($current_spec !== '')
                                        echo '</optgroup>';
                                    echo '<optgroup label="' . h($d['specialization']) . '">';
                                    $current_spec = $d['specialization'];
                                }
                                ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo h($d['name']); ?>
                                    (<?php echo h($d['branch_name']); ?>)
                                    - $<?php echo number_format($d['consultation_fee'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_spec !== '')
                                echo '</optgroup>'; ?>
                        </select>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 2: Select Date & Time</h3>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Preferred Date *</label>
                            <input type="date" name="appointment_date" class="form-input" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Preferred Time *</label>
                            <select name="appointment_time" class="form-select" required>
                                <option value="">Select Time...</option>
                                <?php
                                for ($h = 9; $h < 17; $h++) {
                                    for ($m = 0; $m < 60; $m += 30) {
                                        $time = sprintf('%02d:%02d', $h, $m);
                                        $display = date('g:i A', strtotime($time));
                                        echo "<option value=\"$time\">$display</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <p class="text-sm text-gray">Note: Appointments are subject to availability. You will receive a
                        confirmation.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 3: Reason for Visit</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brief description of your concern</label>
                        <textarea name="reason" class="form-textarea" rows="3"
                            placeholder="e.g., Annual check-up, headache for 3 days, follow-up for blood pressure..."></textarea>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="appointments.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">📅 Book Appointment</button>
                </div>
            </form>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>