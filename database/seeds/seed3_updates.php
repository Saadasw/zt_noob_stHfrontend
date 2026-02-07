<?php
/**
 * Seed3.php - Update Data & Add Missing Records
 * 
 * This script adds:
 * 1. Branch Admin (User & Profile)
 * 2. Doctor Weekly Schedules (for existing doctors)
 * 3. Updates to Lab Test Types (new columns)
 */

require_once __DIR__ . '/../../config/db_connect.php';

echo "<h1>Seeding Updates & Missing Data</h1>";

// Helper function
function safeExecute($pdo, $sql, $params, $desc)
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo "<p style='color:green;'>✅ $desc</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color:orange;'>⏭️ $desc (already exists)</p>";
        } else {
            echo "<p style='color:red;'>❌ $desc - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// =====================================================
// 1. BRANCH ADMIN SEEDING
// =====================================================
echo "<h2>1. Seeding Branch Admin</h2>";
$password = password_hash('123', PASSWORD_DEFAULT);
$branchAdminId = 'USR-BA-MEL-01';

// Create User
safeExecute(
    $pdo,
    "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
    [$branchAdminId, 'Melbourne Branch Manager', 'manager.mel@stgeorge.com', $password, 'branch_admin', '03-9999-8888'],
    "Branch Admin User (manager.mel@stgeorge.com)"
);

// Create Profile
// Ensure we have a branch ID (assuming BR-MEL-01 exists from seed1)
safeExecute(
    $pdo,
    "INSERT INTO branch_admin_profiles (id, user_id, branch_id, employee_id, designation) VALUES (?, ?, ?, ?, ?)",
    ['BAP-MEL-01', $branchAdminId, 'BR-MEL-01', 'EMP-BA-001', 'Branch Manager'],
    "Branch Admin Profile"
);


// =====================================================
// 2. DOCTOR SCHEDULE SEEDING
// =====================================================
echo "<h2>2. Seeding Doctor Schedules</h2>";

// Doctors from seed1.php
$doctors = [
    'DOC-PROF-01' => 'Dr. Sarah Johnson',
    'DOC-PROF-02' => 'Dr. Michael Chen'
];

$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday'
];

foreach ($doctors as $docId => $docName) {
    echo "<h3>Schedules for $docName ($docId)</h3>";
    foreach ($days as $dayNum => $dayName) {
        $scheduleId = "SCH-$docId-$dayNum";
        // Dr. Sarah: 9am - 5pm, Dr. Michael: 10am - 4pm
        $start = ($docId == 'DOC-PROF-01') ? '09:00:00' : '10:00:00';
        $end = ($docId == 'DOC-PROF-01') ? '17:00:00' : '16:00:00';

        safeExecute(
            $pdo,
            "INSERT INTO doctor_weekly_schedules (id, doctor_id, branch_id, day_of_week, start_time, end_time, slot_duration, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, 30, 1)",
            [$scheduleId, $docId, 'BR-MEL-01', $dayNum, $start, $end],
            "$dayName Schedule ($start - $end)"
        );
    }
}


// =====================================================
// 3. UPDATING LAB TEST TYPES
// =====================================================
echo "<h2>3. Updating Lab Test Types</h2>";

$labUpdates = [
    'LTT-001' => ['category' => 'Hematology', 'sample_type' => 'Blood', 'turnaround_hours' => 24, 'fasting_required' => 0], // CBC
    'LTT-002' => ['category' => 'Biochemistry', 'sample_type' => 'Blood', 'turnaround_hours' => 24, 'fasting_required' => 1], // Lipid
    'LTT-003' => ['category' => 'Biochemistry', 'sample_type' => 'Blood', 'turnaround_hours' => 12, 'fasting_required' => 1], // Glucose
    'LTT-004' => ['category' => 'Biochemistry', 'sample_type' => 'Blood', 'turnaround_hours' => 24, 'fasting_required' => 0], // LFT
    'LTT-005' => ['category' => 'Biochemistry', 'sample_type' => 'Blood', 'turnaround_hours' => 24, 'fasting_required' => 0], // KFT
    'LTT-006' => ['category' => 'Biochemistry', 'sample_type' => 'Blood', 'turnaround_hours' => 48, 'fasting_required' => 0], // Thyroid
    'LTT-007' => ['category' => 'Pathology', 'sample_type' => 'Urine', 'turnaround_hours' => 24, 'fasting_required' => 0], // Urinalysis
    'LTT-008' => ['category' => 'Microbiology', 'sample_type' => 'Swab', 'turnaround_hours' => 12, 'fasting_required' => 0], // COVID
];

foreach ($labUpdates as $id => $data) {
    safeExecute(
        $pdo,
        "UPDATE lab_test_types SET category = ?, sample_type = ?, turnaround_hours = ?, fasting_required = ? WHERE id = ?",
        [$data['category'], $data['sample_type'], $data['turnaround_hours'], $data['fasting_required'], $id],
        "Updated Test Type: $id"
    );
}

echo "<hr><p><strong>Done!</strong> You can now use Branch Admin login: <code>manager.mel@stgeorge.com</code> / <code>123</code></p>";
echo "<p><a href='../auth/login.php'>Go to Login</a></p>";
?>