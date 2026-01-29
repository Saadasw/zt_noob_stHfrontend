<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Appointments - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'appointments';

$message = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'schedule';

// --- Fetch Doctors for dropdown ---
$doctors = $pdo->query("SELECT dp.id as doctor_profile_id, u.name, dp.specialization 
                        FROM doctor_profiles dp 
                        JOIN users u ON dp.user_id = u.id 
                        WHERE u.is_active = 1")->fetchAll();

// --- Fetch Patients for dropdown ---
$patients_list = $pdo->query("SELECT pp.id as patient_profile_id, pp.patient_id, u.name 
                              FROM patient_profiles pp 
                              JOIN users u ON pp.user_id = u.id 
                              WHERE u.is_active = 1 
                              ORDER BY u.name")->fetchAll();

// --- Fetch Branches ---
$branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1")->fetchAll();
// If no branches exist, we'll need at least one for FK constraint
if (empty($branches)) {
    // Insert a default branch
    $branchId = 'BR-MEL-01';
    $pdo->exec("INSERT IGNORE INTO branches (id, name, code, address, city, state) VALUES ('$branchId', 'Melbourne CBD', 'MEL-CBD', '123 Collins St', 'Melbourne', 'VIC')");
    $branches = [['id' => $branchId, 'name' => 'Melbourne CBD']];
}

// --- HANDLE BOOKING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $branch_id = $_POST['branch_id'] ?? $branches[0]['id'];
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    if (empty($patient_id) || empty($doctor_id) || empty($date) || empty($time)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $apptId = 'APT-' . bin2hex(random_bytes(4));
            $apptNo = 'APT-' . date('Y') . '-' . mt_rand(100000, 999999);
            $start_time = $time;
            $end_time = date('H:i:s', strtotime($time) + 1800); // 30 min slot

            $stmt = $pdo->prepare("INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$apptId, $apptNo, $patient_id, $doctor_id, $branch_id, $date, $start_time, $end_time, $reason]);

            $message = "Appointment booked successfully! Appointment No: $apptNo";
            $active_tab = 'book';
        } catch (PDOException $e) {
            $error = "Booking failed: " . $e->getMessage();
        }
    }
}

// --- HANDLE CHECK-IN / CANCEL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $apt_id = $_POST['apt_id'];
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $apt_id]);
    $message = "Appointment status updated.";
}

// --- Fetch Today's Appointments ---
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, 
           u_pat.name as patient_name, pp.patient_id as patient_code,
           u_doc.name as doctor_name, dp.specialization
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE a.appointment_date = ?
    ORDER BY a.start_time ASC
");
$stmt->execute([$today]);
$appointments = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Appointments</h1>
                <p class="page-subtitle"><?php echo date('l, d F Y'); ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=schedule" class="tab <?php echo $active_tab === 'schedule' ? 'active' : ''; ?>">Today's Schedule</a>
            <a href="?tab=book" class="tab <?php echo $active_tab === 'book' ? 'active' : ''; ?>">Book Appointment</a>
        </div>

        <?php if ($active_tab === 'schedule'): ?>
        <!-- Tab 1: Today's Schedule -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Appointments (<?php echo count($appointments); ?>)</h3>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($apt['start_time'])); ?></td>
                                <td>
                                    <?php echo h($apt['patient_name']); ?><br>
                                    <span class="text-gray text-sm"><?php echo h($apt['patient_code']); ?></span>
                                </td>
                                <td>
                                    <?php echo h($apt['doctor_name']); ?><br>
                                    <span class="text-gray text-sm"><?php echo h($apt['specialization']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'scheduled' => '<span class="badge badge-gray">🕐 Scheduled</span>',
                                        'confirmed' => '<span class="badge badge-blue">✔️ Confirmed</span>',
                                        'checked_in' => '<span class="badge badge-yellow">⏳ Waiting</span>',
                                        'in_progress' => '<span class="badge badge-blue">🔵 In Progress</span>',
                                        'completed' => '<span class="badge badge-green">✅ Done</span>',
                                        'cancelled' => '<span class="badge badge-red">❌ Cancelled</span>',
                                        'no_show' => '<span class="badge badge-red">⚠️ No Show</span>',
                                    ];
                                    echo $status_badges[$apt['status']] ?? $apt['status'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($apt['status'] === 'scheduled' || $apt['status'] === 'confirmed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="checked_in">
                                        <button type="submit" class="btn btn-sm btn-primary">Check-in</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Cancel this appointment?');">Cancel</button>
                                    </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline">View</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No appointments scheduled for today.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray mt-4">Legend: ✅ Completed 🔵 In Progress ⏳ Waiting 🕐 Scheduled ❌ Cancelled</p>
        </div>

        <?php elseif ($active_tab === 'book'): ?>
        <!-- Tab 2: Book Appointment -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Book New Appointment</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="book_appointment" value="1">

                <div class="section-header">STEP 1: SELECT PATIENT</div>
                <div class="form-group">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient...</option>
                        <?php foreach ($patients_list as $p): ?>
                            <option value="<?php echo $p['patient_profile_id']; ?>"><?php echo h($p['name']); ?> (<?php echo h($p['patient_id']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="section-header">STEP 2: SELECT DOCTOR</div>
                <div class="form-group">
                    <label class="form-label">Doctor *</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select Doctor...</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['doctor_profile_id']; ?>"><?php echo h($d['name']); ?> - <?php echo h($d['specialization']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="section-header">STEP 3: SELECT DATE & TIME</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="appointment_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time *</label>
                        <select name="appointment_time" class="form-select" required>
                            <option value="">Select Time...</option>
                            <?php
                            // Generate time slots from 09:00 to 17:00
                            for ($h = 9; $h < 17; $h++) {
                                for ($m = 0; $m < 60; $m += 30) {
                                    $time = sprintf('%02d:%02d', $h, $m);
                                    echo "<option value=\"$time\">$time</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="branch_id" value="<?php echo $branches[0]['id']; ?>">

                <div class="section-header">STEP 4: REASON FOR VISIT (Optional)</div>
                <div class="form-group">
                    <textarea name="reason" class="form-textarea" rows="2" placeholder="e.g. Follow-up for blood pressure check"></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="reset" class="btn btn-outline">Clear</button>
                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
