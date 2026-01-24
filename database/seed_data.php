<?php
require 'config/db_connect.php';

// Helper to check if exists
function recordExists($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() > 0;
}

try {
    $pdo->beginTransaction();

    echo "<h3>Seeding Database...</h3>";

    // --- 1. Users ---
    $users = [
        ['u1', 'admin@stgeorgehospital.org', 'admin123', 'Dr. Sarah Mitchell', 'admin', '555-0101'],
        ['u2', 'john.doe@email.com', 'user123', 'John Doe', 'patient', '555-0102'],
        ['u3', 'dr.james@stgeorgehospital.org', 'doctor123', 'Dr. James Wilson', 'doctor', '555-0103'],
        ['u4', 'alice.staff@stgeorgehospital.org', 'staff123', 'Alice Thompson', 'staff', '555-0104'],
        // MockData.ts has 'Robert Smith' (u5) inactive, omitting for simplicity or adding:
        ['u5', 'robert.smith@email.com', 'user123', 'Robert Smith', 'patient', '555-0105'], 
    ];

    foreach ($users as $u) {
        if (!recordExists($pdo, 'users', $u[0])) {
            $stmt = $pdo->prepare("INSERT INTO users (id, email, password, name, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3], $u[4], $u[5]]);
            echo "Created User: {$u[3]}<br>";
        }
    }

    // --- 2. Branches ---
    $branches = [
        ['b1', 'St. George Central', 'SGC01', '77 Cathedral Square', 'London', 'UK', '555-9000', 'central@stgeorgehospital.org'],
        ['b2', 'St. George East Clinic', 'SGE02', '456 Riverside Way', 'London', 'UK', '555-8000', 'east@stgeorgehospital.org']
    ];

    foreach ($branches as $b) {
        if (!recordExists($pdo, 'branches', $b[0])) {
            $stmt = $pdo->prepare("INSERT INTO branches (id, name, code, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($b);
            echo "Created Branch: {$b[1]}<br>";
        }
    }

    // --- 3. Profiles ---
    
    // Doctor (u3 -> d1)
    if (!recordExists($pdo, 'doctor_profiles', 'd1')) {
        $stmt = $pdo->prepare("INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['d1', 'u3', 'b1', 'Cardiology', 'LIC-10022', 150.00]);
        echo "Created Doctor Profile: Dr. James Wilson<br>";
        
        // Schedule for Dr. James
        $stmt = $pdo->prepare("INSERT INTO doctor_weekly_schedules (id, doctor_id, branch_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
        for($i=1; $i<=5; $i++) {
            $stmt->execute(["sch-d1-$i", 'd1', 'b1', $i, '09:00:00', '17:00:00']);
        }
    }

    // Patient (u2 -> p1)
    if (!recordExists($pdo, 'patient_profiles', 'p1')) {
        $stmt = $pdo->prepare("INSERT INTO patient_profiles (id, user_id, patient_id, date_of_birth, gender, blood_group, allergies, medical_history) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['p1', 'u2', 'PAT-001', '1985-05-15', 'Male', 'O+', 'Peanuts, Penicillin', 'Type 2 Diabetes']);
        echo "Created Patient Profile: John Doe<br>";
    }

    // Staff (u4 -> s1) - assuming ID
    if (!recordExists($pdo, 'staff_profiles', 's1')) {
        $stmt = $pdo->prepare("INSERT INTO staff_profiles (id, user_id, branch_id, department, employee_id, designation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['s1', 'u4', 'b1', 'Nursing', 'EMP-001', 'Senior Nurse']);
        echo "Created Staff Profile: Alice Thompson<br>";
    }

    // --- 4. Appointments ---
    if (!recordExists($pdo, 'appointments', 'a1')) {
        $stmt = $pdo->prepare("INSERT INTO appointments 
            (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, status, reason) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['a1', 'SGH-1001', 'p1', 'd1', 'b1', '2023-12-01', '10:00:00', '10:30:00', 'confirmed', 'Regular heart checkup']);
        echo "Created Appointment: SGH-1001<br>";
    }

    // --- 5. Medicines ---
    $medicines = [
        ['m1', 'Amoxicillin', 'MED-AMX-01', 'Antibiotic', 12.50],
        ['m2', 'Metformin', 'MED-MET-02', 'Antidiabetic', 8.00]
    ];

    foreach ($medicines as $m) {
        if (!recordExists($pdo, 'medicines', $m[0])) {
            $stmt = $pdo->prepare("INSERT INTO medicines (id, name, code, category, unit_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($m);
            echo "Created Medicine: {$m[1]}<br>";
        }
    }

    // --- 6. Inventory ---
    if (!recordExists($pdo, 'inventory', 'inv1')) {
        $stmt = $pdo->prepare("INSERT INTO inventory (id, medicine_id, branch_id, quantity, batch_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['inv1', 'm1', 'b1', 500, 'BT-2024-001']);
        $stmt->execute(['inv2', 'm2', 'b1', 300, 'BT-2024-042']);
        echo "Created Inventory items<br>";
    }

    // --- 7. Medical Records ---
    if (!recordExists($pdo, 'medical_records', 'r1')) {
        $stmt = $pdo->prepare("INSERT INTO medical_records 
            (id, record_no, patient_id, doctor_id, chief_complaint, symptoms, diagnosis, treatment_plan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['r1', 'REC-5001', 'p1', 'd1', 'Chest tightness', 'Mild pain, shortness of breath', 'Angina Pectoris', 'Rest, Nitroglycerin']);
        echo "Created Medical Record: REC-5001<br>";
    }

    // --- 8. Bills ---
    if (!recordExists($pdo, 'bills', 'bill1')) {
        $stmt = $pdo->prepare("INSERT INTO bills (id, bill_no, patient_id, total_amount, payment_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['bill1', 'INV-2001', 'p1', 150.00, 'paid']);
        echo "Created Bill: INV-2001<br>";
    }

    $pdo->commit();
    echo "<h3>Seeding Complete! ✅</h3>";
    echo "<p><a href='../auth/login.php'>Go to Login</a></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3>Error Seeding Database</h3>";
    echo $e->getMessage();
}
?>
