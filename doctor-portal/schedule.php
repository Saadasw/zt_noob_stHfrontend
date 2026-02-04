<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Schedule - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'schedule';

$message = '';
$error = '';

// =====================================================
// 1. GET DOCTOR PROFILE
// =====================================================
$stmt = $pdo->prepare("SELECT dp.*, u.name as doctor_name FROM doctor_profiles dp JOIN users u ON dp.user_id = u.id WHERE dp.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor_profile = $stmt->fetch();

if (!$doctor_profile) {
    $error = "Doctor profile not found. Please contact admin.";
    $doctor_id = null;
    $branch_id = null;
    $slot_duration = 30;
} else {
    $doctor_id = $doctor_profile['id'];
    $branch_id = $doctor_profile['branch_id'];
    $slot_duration = $doctor_profile['slot_duration'] ?? 30;
}

// =====================================================
// 2. WEEK CALCULATION + NAVIGATION
// =====================================================
$week_offset = isset($_GET['week']) ? intval($_GET['week']) : 0;

// Calculate week start (Monday) and end (Sunday)
$today = new DateTime();
$today->modify("monday this week"); // Get Monday of current week
$today->modify(($week_offset * 7) . " days"); // Apply offset

$week_start = clone $today;
$week_end = clone $today;
$week_end->modify('+6 days');

// Generate array of dates for the week
$week_dates = [];
$current = clone $week_start;
for ($i = 0; $i < 7; $i++) {
    $week_dates[] = clone $current;
    $current->modify('+1 day');
}

// Format for display
$week_display = $week_start->format('d M') . ' - ' . $week_end->format('d M Y');

// =====================================================
// 3. HANDLE BLOCK TIME OFF
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block']) && $doctor_id) {
    $block_date = $_POST['block_date'];
    $block_reason = trim($_POST['block_reason']);

    if (empty($block_date) || empty($block_reason)) {
        $error = "Please select a date and reason.";
    } else {
        try {
            $overrideId = 'OVR-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO doctor_schedule_overrides (id, doctor_id, branch_id, date, type, reason) VALUES (?, ?, ?, ?, 'unavailable', ?)");
            $stmt->execute([$overrideId, $doctor_id, $branch_id, $block_date, $block_reason]);
            $message = "Time off blocked successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Block Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_block']) && $doctor_id) {
    $block_id = $_POST['block_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM doctor_schedule_overrides WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$block_id, $doctor_id]);
        $message = "Block removed successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// =====================================================
// 4. FETCH DATA FROM DATABASE
// =====================================================
$weekly_schedule = [];
$overrides = [];
$appointments = [];
$blocked_dates = [];

if ($doctor_id) {
    // Get weekly schedule (working hours per day)
    $stmt = $pdo->prepare("SELECT * FROM doctor_weekly_schedules WHERE doctor_id = ? AND is_active = 1");
    $stmt->execute([$doctor_id]);
    $schedule_rows = $stmt->fetchAll();
    foreach ($schedule_rows as $row) {
        $weekly_schedule[$row['day_of_week']] = $row;
    }

    // Get overrides for this week
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedule_overrides WHERE doctor_id = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$doctor_id, $week_start->format('Y-m-d'), $week_end->format('Y-m-d')]);
    $override_rows = $stmt->fetchAll();
    foreach ($override_rows as $row) {
        $overrides[$row['date']] = $row;
    }

    // Get appointments for this week
    $stmt = $pdo->prepare("
        SELECT a.*, pp.patient_id as patient_code, u.name as patient_name 
        FROM appointments a 
        LEFT JOIN patient_profiles pp ON a.patient_id = pp.id
        LEFT JOIN users u ON pp.user_id = u.id
        WHERE a.doctor_id = ? AND a.appointment_date BETWEEN ? AND ? AND a.status NOT IN ('cancelled')
        ORDER BY a.appointment_date, a.start_time
    ");
    $stmt->execute([$doctor_id, $week_start->format('Y-m-d'), $week_end->format('Y-m-d')]);
    $appt_rows = $stmt->fetchAll();
    foreach ($appt_rows as $row) {
        $key = $row['appointment_date'] . '_' . $row['start_time'];
        $appointments[$key] = $row;
    }

    // Get all upcoming blocked dates for modal display
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedule_overrides WHERE doctor_id = ? AND date >= CURDATE() ORDER BY date ASC");
    $stmt->execute([$doctor_id]);
    $blocked_dates = $stmt->fetchAll();
}

// =====================================================
// 5. GENERATE TIME SLOTS
// =====================================================
function generateTimeSlots($start_time, $end_time, $slot_duration) {
    $slots = [];
    $current = strtotime($start_time);
    $end = strtotime($end_time);
    
    while ($current < $end) {
        $slots[] = date('H:i', $current);
        $current += $slot_duration * 60;
    }
    return $slots;
}

// Get all unique time slots across the week
$all_time_slots = [];
$default_start = '09:00';
$default_end = '17:00';

// If schedule exists, get times from it; otherwise use defaults
if (!empty($weekly_schedule)) {
    $earliest_start = '23:59';
    $latest_end = '00:00';
    foreach ($weekly_schedule as $day_sched) {
        if ($day_sched['start_time'] < $earliest_start) $earliest_start = $day_sched['start_time'];
        if ($day_sched['end_time'] > $latest_end) $latest_end = $day_sched['end_time'];
    }
    $all_time_slots = generateTimeSlots($earliest_start, $latest_end, $slot_duration);
} else {
    // No schedule defined - use default
    $all_time_slots = generateTimeSlots($default_start, $default_end, $slot_duration);
}

// =====================================================
// 6. SLOT STATUS LOGIC
// =====================================================
function getSlotStatus($date, $time, $day_of_week, $weekly_schedule, $overrides, $appointments, $slot_duration) {
    $date_str = $date->format('Y-m-d');
    $time_str = $time . ':00';
    $now = new DateTime();
    
    // 1. Check for override (blocked day)
    if (isset($overrides[$date_str])) {
        return ['status' => 'unavailable', 'label' => 'Day Off', 'reason' => $overrides[$date_str]['reason']];
    }
    
    // 2. Check if day has schedule
    if (!isset($weekly_schedule[$day_of_week])) {
        return ['status' => 'unavailable', 'label' => 'Day Off', 'reason' => 'No schedule'];
    }
    
    $day_schedule = $weekly_schedule[$day_of_week];
    
    // 3. Check if time is within working hours
    if ($time_str < $day_schedule['start_time'] || $time_str >= $day_schedule['end_time']) {
        return ['status' => 'outside', 'label' => '', 'reason' => 'Outside hours'];
    }
    
    // 4. Check for appointment
    $appt_key = $date_str . '_' . $time_str;
    if (isset($appointments[$appt_key])) {
        $appt = $appointments[$appt_key];
        $label = substr($appt['patient_name'] ?? 'Booked', 0, 10);
        
        // Check if this is current appointment
        $slot_start = new DateTime($date_str . ' ' . $time_str);
        $slot_end = clone $slot_start;
        $slot_end->modify("+{$slot_duration} minutes");
        
        if ($now >= $slot_start && $now < $slot_end) {
            return ['status' => 'current', 'label' => '🔵 ' . $label, 'reason' => 'Current'];
        }
        
        return ['status' => 'booked', 'label' => $label, 'reason' => 'Appointment'];
    }
    
    // 5. Check if slot is in the past
    $slot_datetime = new DateTime($date_str . ' ' . $time_str);
    if ($slot_datetime < $now) {
        return ['status' => 'available', 'label' => '-', 'reason' => 'Past'];
    }
    
    // 6. Available
    return ['status' => 'available', 'label' => 'Available', 'reason' => ''];
}

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Schedule</h1>
                <p class="page-subtitle">Week of <?php echo $week_display; ?></p>
            </div>
            <div class="flex gap-2">
                <a href="?week=<?php echo $week_offset - 1; ?>" class="btn btn-outline">&lt; Prev Week</a>
                <a href="?week=0" class="btn btn-primary">Today</a>
                <a href="?week=<?php echo $week_offset + 1; ?>" class="btn btn-outline">Next &gt;</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="flex justify-between items-center">
                <div class="flex gap-2">
                    <span class="text-sm text-gray">Slot: <?php echo $slot_duration; ?> min</span>
                </div>
                <button class="btn btn-outline" onclick="toggleModal('availabilityModal')">⚙️ Manage Availability</button>
            </div>
        </div>

        <?php if (!$doctor_id): ?>
            <div class="card">
                <p class="text-center text-gray">Doctor profile not found. Please contact administrator.</p>
            </div>
        <?php elseif (empty($weekly_schedule)): ?>
            <div class="card">
                <p class="text-center text-gray">No schedule configured yet. Please contact administrator to set up your working hours.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <?php foreach ($week_dates as $date): ?>
                                    <th><?php echo $date->format('D d'); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_time_slots as $time_slot): ?>
                                <tr>
                                    <td><?php echo $time_slot; ?></td>
                                    <?php foreach ($week_dates as $date): 
                                        $day_of_week = (int)$date->format('w'); // 0=Sun, 1=Mon...6=Sat
                                        $slot_info = getSlotStatus($date, $time_slot, $day_of_week, $weekly_schedule, $overrides, $appointments, $slot_duration);
                                        
                                        if ($slot_info['status'] === 'outside') {
                                            echo '<td class="slot-outside"></td>';
                                        } else {
                                            $class = 'slot-' . $slot_info['status'];
                                            echo '<td class="' . $class . '">' . h($slot_info['label']) . '</td>';
                                        }
                                    endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 12px; font-size: 12px; color: #6b7280;">
                    Legend: 
                    <span style="background: #dbeafe; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Booked</span>
                    <span style="background: #dcfce7; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Available</span>
                    <span style="background: #f3f4f6; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Unavailable</span>
                    <span style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Current</span>
                </div>
                <?php
                // Calculate stats
                $total_slots = 0;
                $booked_slots = 0;
                $available_slots = 0;
                foreach ($all_time_slots as $time_slot) {
                    foreach ($week_dates as $date) {
                        $day_of_week = (int)$date->format('w');
                        $slot_info = getSlotStatus($date, $time_slot, $day_of_week, $weekly_schedule, $overrides, $appointments, $slot_duration);
                        if ($slot_info['status'] !== 'outside' && $slot_info['status'] !== 'unavailable') {
                            $total_slots++;
                            if ($slot_info['status'] === 'booked' || $slot_info['status'] === 'current') {
                                $booked_slots++;
                            } else {
                                $available_slots++;
                            }
                        }
                    }
                }
                ?>
                <p class="text-sm text-gray mt-4">This Week: <?php echo $total_slots; ?> slots | <?php echo $booked_slots; ?> booked | <?php echo $available_slots; ?> available</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Manage Availability Modal -->
<div id="availabilityModal" class="modal-overlay" style="display: none;">
    <div class="card modal-card" style="max-width: 600px;">
        <div class="card-header">
            <h3 class="card-title">⚙️ Manage Availability</h3>
            <button class="btn btn-sm btn-outline" onclick="toggleModal('availabilityModal')">Close</button>
        </div>

        <!-- Block Time Off Form -->
        <div class="section-header">BLOCK TIME OFF</div>
        <form method="POST">
            <input type="hidden" name="add_block" value="1">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="block_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Reason *</label>
                    <select name="block_reason" class="form-select" required>
                        <option value="">Select reason...</option>
                        <option value="Personal Leave">Personal Leave</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Conference">Conference</option>
                        <option value="Training">Training</option>
                        <option value="Holiday">Holiday</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">+ Block This Date</button>
        </form>

        <!-- Existing Blocked Dates -->
        <div class="section-header" style="margin-top: 24px;">UPCOMING BLOCKED DATES</div>
        <?php if (empty($blocked_dates)): ?>
            <p class="text-gray">No upcoming blocked dates.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_dates as $block): ?>
                            <tr>
                                <td><?php echo date('D, M j, Y', strtotime($block['date'])); ?></td>
                                <td><?php echo h($block['reason']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="delete_block" value="1">
                                        <input type="hidden" name="block_id" value="<?php echo h($block['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Remove this blocked date?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="flex gap-2 mt-4">
            <button type="button" class="btn btn-outline" onclick="toggleModal('availabilityModal')">Close</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-card {
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    margin: 0;
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
.schedule-table td {
    text-align: center;
    padding: 8px 4px;
    font-size: 12px;
    min-width: 80px;
}
.slot-available {
    background: #dcfce7;
    color: #166534;
}
.slot-booked {
    background: #dbeafe;
    color: #1e40af;
}
.slot-unavailable {
    background: #f3f4f6;
    color: #6b7280;
}
.slot-current {
    background: #2563eb;
    color: white;
    font-weight: bold;
}
.slot-outside {
    background: #fafafa;
}
</style>

<script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'flex' : 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>