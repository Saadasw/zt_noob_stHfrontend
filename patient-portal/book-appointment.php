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
    SELECT dp.id, dp.specialization, dp.consultation_fee, dp.slot_duration, u.name, b.name as branch_name
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
        $error = "Please select a doctor and time slot.";
    } else {
        // Double-check slot is still available (race condition protection)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND start_time = ? AND status NOT IN ('cancelled', 'no_show')");
        $stmt->execute([$doctor_id, $date, $time]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            $error = "Sorry, this slot was just booked by another patient. Please select a different time.";
        } else {
            try {
                // Get doctor's branch and slot duration
                $stmt = $pdo->prepare("SELECT branch_id, slot_duration FROM doctor_profiles WHERE id = ?");
                $stmt->execute([$doctor_id]);
                $doc = $stmt->fetch();
                $branch_id = $doc['branch_id'] ?? 'BR-MEL-01';
                $slot_mins = $doc['slot_duration'] ?? 30;

                $apptId = 'APT-' . bin2hex(random_bytes(4));
                $apptNo = 'APT-' . date('Y') . '-' . mt_rand(100000, 999999);
                $start_time = $time;
                $end_time = date('H:i:s', strtotime($time) + ($slot_mins * 60));

                $stmt = $pdo->prepare("INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                $stmt->execute([$apptId, $apptNo, $patient_profile_id, $doctor_id, $branch_id, $date, $start_time, $end_time, $reason]);

                $message = "Appointment booked successfully! Your appointment number is: $apptNo";

            } catch (PDOException $e) {
                $error = "Booking failed: " . $e->getMessage();
            }
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

            <form method="POST" id="bookingForm">
                <input type="hidden" name="book_appointment" value="1">
                <input type="hidden" name="appointment_date" id="selected_date" value="">
                <input type="hidden" name="appointment_time" id="selected_time" value="">

                <!-- Step 1: Select Doctor -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 1: Select Doctor</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Choose a Doctor *</label>
                        <select name="doctor_id" id="doctor_select" class="form-select" required>
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
                                <option value="<?php echo $d['id']; ?>" data-fee="<?php echo $d['consultation_fee']; ?>">
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

                <!-- Step 2: Select Slot (Calendar View) -->
                <div class="card" id="slot-section" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">Step 2: Select Available Slot</h3>
                    </div>

                    <div id="loading-slots" class="text-center p-4" style="display: none;">
                        <p>Loading available slots...</p>
                    </div>

                    <div id="no-schedule" class="text-center p-4" style="display: none;">
                        <p class="text-gray">⚠️ This doctor has no schedule configured. Please select a different doctor.
                        </p>
                    </div>

                    <div id="no-slots" class="text-center p-4" style="display: none;">
                        <p class="text-gray">❌ No available slots in the next 7 days. Please try a different doctor.</p>
                    </div>

                    <div id="slots-calendar" class="slots-calendar"></div>

                    <div id="selected-slot-info" class="selected-slot-info" style="display: none;">
                        <strong>Selected:</strong> <span id="selected-slot-text"></span>
                    </div>
                </div>

                <!-- Step 3: Reason -->
                <div class="card" id="reason-section" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">Step 3: Reason for Visit</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brief description of your concern (optional)</label>
                        <textarea name="reason" class="form-textarea" rows="3"
                            placeholder="e.g., Annual check-up, headache for 3 days, follow-up..."></textarea>
                    </div>
                </div>

                <div class="flex gap-2" id="submit-section" style="display: none;">
                    <a href="appointments.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submit-btn" disabled>📅 Book Appointment</button>
                </div>
            </form>
        <?php endif; ?>

    </main>
</div>

<style>
    .slots-calendar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 16px 0;
    }

    .day-column {
        min-width: 120px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .day-header {
        background: #2563eb;
        color: white;
        padding: 12px 8px;
        text-align: center;
        font-weight: 500;
    }

    .day-header.today {
        background: #059669;
    }

    .day-header.blocked,
    .day-header.closed,
    .day-header.full {
        background: #6b7280;
    }

    .day-date {
        font-size: 12px;
        opacity: 0.9;
    }

    .day-slots {
        max-height: 300px;
        overflow-y: auto;
        padding: 8px;
    }

    .day-message {
        padding: 20px 8px;
        text-align: center;
        color: #6b7280;
        font-size: 13px;
    }

    .slot-btn {
        display: block;
        width: 100%;
        padding: 8px;
        margin-bottom: 4px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        background: #f0fdf4;
        color: #166534;
        cursor: pointer;
        font-size: 13px;
        text-align: center;
        transition: all 0.2s;
    }

    .slot-btn:hover {
        background: #dcfce7;
        border-color: #22c55e;
    }

    .slot-btn.selected {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    .slot-btn.booked,
    .slot-btn.past {
        background: #f3f4f6;
        color: #9ca3af;
        cursor: not-allowed;
        text-decoration: line-through;
    }

    .selected-slot-info {
        background: #dbeafe;
        padding: 12px 16px;
        border-radius: 6px;
        margin-top: 12px;
        color: #1e40af;
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

    .p-4 {
        padding: 16px;
    }
</style>

<script>
    document.getElementById('doctor_select').addEventListener('change', function () {
        const doctorId = this.value;
        const slotSection = document.getElementById('slot-section');
        const reasonSection = document.getElementById('reason-section');
        const submitSection = document.getElementById('submit-section');
        const loadingEl = document.getElementById('loading-slots');
        const noScheduleEl = document.getElementById('no-schedule');
        const noSlotsEl = document.getElementById('no-slots');
        const calendarEl = document.getElementById('slots-calendar');
        const selectedInfo = document.getElementById('selected-slot-info');

        // Reset
        document.getElementById('selected_date').value = '';
        document.getElementById('selected_time').value = '';
        document.getElementById('submit-btn').disabled = true;
        selectedInfo.style.display = 'none';

        if (!doctorId) {
            slotSection.style.display = 'none';
            reasonSection.style.display = 'none';
            submitSection.style.display = 'none';
            return;
        }

        slotSection.style.display = 'block';
        loadingEl.style.display = 'block';
        noScheduleEl.style.display = 'none';
        noSlotsEl.style.display = 'none';
        calendarEl.innerHTML = '';

        fetch('get-doctor-slots.php?doctor_id=' + doctorId)
            .then(response => response.json())
            .then(data => {
                loadingEl.style.display = 'none';

                if (data.error) {
                    noScheduleEl.textContent = data.error;
                    noScheduleEl.style.display = 'block';
                    return;
                }

                if (data.no_schedule) {
                    noScheduleEl.style.display = 'block';
                    return;
                }

                if (!data.has_available_slots) {
                    noSlotsEl.style.display = 'block';
                    return;
                }

                // Render calendar
                renderCalendar(data.days);
                reasonSection.style.display = 'block';
                submitSection.style.display = 'flex';
            })
            .catch(err => {
                loadingEl.style.display = 'none';
                noScheduleEl.textContent = 'Error loading slots. Please try again.';
                noScheduleEl.style.display = 'block';
            });
    });

    function renderCalendar(days) {
        const calendarEl = document.getElementById('slots-calendar');
        calendarEl.innerHTML = '';

        days.forEach(day => {
            const col = document.createElement('div');
            col.className = 'day-column';

            let headerClass = 'day-header';
            if (day.is_today) headerClass += ' today';
            if (day.status !== 'available') headerClass += ' ' + day.status;

            col.innerHTML = `
            <div class="${headerClass}">
                <div>${day.day_name}</div>
                <div class="day-date">${day.display_date}</div>
            </div>
        `;

            if (day.status !== 'available' || day.slots.length === 0) {
                col.innerHTML += `<div class="day-message">${day.message || 'No slots'}</div>`;
            } else {
                const slotsDiv = document.createElement('div');
                slotsDiv.className = 'day-slots';

                day.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'slot-btn';
                    btn.textContent = slot.display_time;

                    if (!slot.available) {
                        btn.className += ' ' + (slot.status || 'booked');
                        btn.disabled = true;
                    } else {
                        btn.onclick = function () { selectSlot(day.date, slot.time, slot.display_time, day.display_date); };
                    }

                    slotsDiv.appendChild(btn);
                });

                col.appendChild(slotsDiv);
            }

            calendarEl.appendChild(col);
        });
    }

    function selectSlot(date, time, displayTime, displayDate) {
        // Remove previous selection
        document.querySelectorAll('.slot-btn.selected').forEach(btn => {
            btn.classList.remove('selected');
        });

        // Mark this as selected
        event.target.classList.add('selected');

        // Update hidden fields
        document.getElementById('selected_date').value = date;
        document.getElementById('selected_time').value = time;

        // Show selected info
        document.getElementById('selected-slot-text').textContent = displayDate + ' at ' + displayTime;
        document.getElementById('selected-slot-info').style.display = 'block';

        // Enable submit
        document.getElementById('submit-btn').disabled = false;
    }
</script>

<?php include '../includes/footer.php'; ?>