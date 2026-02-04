<?php
/**
 * get-doctor-slots.php
 * AJAX endpoint to fetch available appointment slots for a doctor
 * Returns JSON with slots for the next 7 days
 */
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;

if (!$doctor_id) {
    echo json_encode(['error' => 'Doctor ID required']);
    exit();
}

// Get doctor's slot duration (default 30 min)
$stmt = $pdo->prepare("SELECT slot_duration FROM doctor_profiles WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();
$slot_duration = $doctor['slot_duration'] ?? 30;

// Get doctor's weekly schedule
$stmt = $pdo->prepare("
    SELECT day_of_week, start_time, end_time, slot_duration 
    FROM doctor_weekly_schedules 
    WHERE doctor_id = ? AND is_active = 1
");
$stmt->execute([$doctor_id]);
$schedules = $stmt->fetchAll();

// Index schedules by day of week
$schedule_by_day = [];
foreach ($schedules as $s) {
    $schedule_by_day[$s['day_of_week']] = $s;
}

// Get doctor's blocked dates for next 7 days
$today = new DateTime();
$end_date = (new DateTime())->modify('+7 days');

$stmt = $pdo->prepare("
    SELECT date, type, reason 
    FROM doctor_schedule_overrides 
    WHERE doctor_id = ? AND date BETWEEN ? AND ? AND type = 'blocked'
");
$stmt->execute([$doctor_id, $today->format('Y-m-d'), $end_date->format('Y-m-d')]);
$overrides = $stmt->fetchAll();

// Index overrides by date
$blocked_dates = [];
foreach ($overrides as $o) {
    $blocked_dates[$o['date']] = $o['reason'] ?? 'Day Off';
}

// Get booked appointments for next 7 days
$stmt = $pdo->prepare("
    SELECT appointment_date, start_time, end_time 
    FROM appointments 
    WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? AND status NOT IN ('cancelled', 'no_show')
");
$stmt->execute([$doctor_id, $today->format('Y-m-d'), $end_date->format('Y-m-d')]);
$appointments = $stmt->fetchAll();

// Index appointments by date and time
$booked_slots = [];
foreach ($appointments as $a) {
    $key = $a['appointment_date'] . '_' . $a['start_time'];
    $booked_slots[$key] = true;
}

// Generate slots for each day
$days = [];
$current = new DateTime();
$now_time = new DateTime();

for ($i = 0; $i < 7; $i++) {
    $date = clone $current;
    $date->modify("+$i days");
    $date_str = $date->format('Y-m-d');
    $day_of_week = (int) $date->format('w'); // 0=Sunday, 6=Saturday

    $day_data = [
        'date' => $date_str,
        'display_date' => $date->format('D, d M'),
        'day_name' => $date->format('l'),
        'is_today' => ($i === 0),
        'status' => 'available',
        'message' => '',
        'slots' => []
    ];

    // Check if blocked
    if (isset($blocked_dates[$date_str])) {
        $day_data['status'] = 'blocked';
        $day_data['message'] = $blocked_dates[$date_str];
        $days[] = $day_data;
        continue;
    }

    // Check if doctor works this day
    if (!isset($schedule_by_day[$day_of_week])) {
        $day_data['status'] = 'closed';
        $day_data['message'] = 'Not Working';
        $days[] = $day_data;
        continue;
    }

    $schedule = $schedule_by_day[$day_of_week];
    $slot_mins = $schedule['slot_duration'] ?? $slot_duration;

    // Generate time slots
    $start = new DateTime($date_str . ' ' . $schedule['start_time']);
    $end = new DateTime($date_str . ' ' . $schedule['end_time']);

    $available_count = 0;

    while ($start < $end) {
        $time_str = $start->format('H:i:s');
        $slot_key = $date_str . '_' . $time_str;

        $slot = [
            'time' => $time_str,
            'display_time' => $start->format('g:i A'),
            'available' => true
        ];

        // Check if booked
        if (isset($booked_slots[$slot_key])) {
            $slot['available'] = false;
            $slot['status'] = 'booked';
        }
        // Check if past time (for today only)
        else if ($i === 0 && $start < $now_time) {
            $slot['available'] = false;
            $slot['status'] = 'past';
        } else {
            $available_count++;
        }

        $day_data['slots'][] = $slot;
        $start->modify("+$slot_mins minutes");
    }

    if ($available_count === 0 && count($day_data['slots']) > 0) {
        $day_data['status'] = 'full';
        $day_data['message'] = 'Fully Booked';
    }

    $days[] = $day_data;
}

// Check if any slots available at all
$has_any_slots = false;
foreach ($days as $d) {
    if ($d['status'] === 'available') {
        foreach ($d['slots'] as $s) {
            if ($s['available']) {
                $has_any_slots = true;
                break 2;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'doctor_id' => $doctor_id,
    'slot_duration' => $slot_duration,
    'days' => $days,
    'has_available_slots' => $has_any_slots,
    'no_schedule' => empty($schedules)
]);
