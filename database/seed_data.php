<?php
/**
 * St. George Hospital - Complete Database Seed Script
 * 
 * This file consolidates all previous seed scripts into one.
 * Run this after importing schema.sql to populate the database with all test data.
 * 
 * Usage:
 *   Visit http://localhost/stgeorgehospital/database/seed_data.php
 */

require_once __DIR__ . '/../config/db_connect.php';

echo "<!DOCTYPE html><html><head><title>Database Seed</title><style>body{font-family:'Segoe UI',Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f8fafc;color:#1e293b;}.success{color:#166534;background:#dcfce7;padding:2px 6px;border-radius:4px;}.error{color:#991b1b;background:#fee2e2;padding:2px 6px;border-radius:4px;}.warning{color:#854d0e;background:#fef9c3;padding:2px 6px;border-radius:4px;}.box{background:#fff;padding:24px;border-radius:8px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e2e8f0;}h2{margin-top:0;color:#0f172a;border-bottom:2px solid #e2e8f0;padding-bottom:12px;font-size:1.25rem;}h3{margin:16px 0 8px;font-size:1rem;color:#475569;}ul{list-style:none;padding:0;}li{margin-bottom:8px;padding:8px;background:#f1f5f9;border-radius:4px;display:flex;justify-content:space-between;align-items:center;}.btn{display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:500;transition:background 0.2s;}.btn:hover{background:#1d4ed8;}</style></head><body>";
echo "<h1>🌱 St. George Hospital - Unified Database Seed</h1>";

// Helper functions
function generateId($prefix)
{
    return $prefix . '-' . bin2hex(random_bytes(4));
}

function safeInsert($pdo, $sql, $params, $description)
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo "<div style='font-size:0.9em; margin-bottom:4px;'><span class='success'>✅ Success</span> $description</div>";
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<div style='font-size:0.9em; margin-bottom:4px;'><span class='warning'>⏭️ Skipped</span> $description (Duplicate)</div>";
            return true;
        }
        echo "<div style='font-size:0.9em; margin-bottom:4px;'><span class='error'>❌ Error</span> $description - " . htmlspecialchars($e->getMessage()) . "</div>";
        return false;
    }
}

try {
    $password = password_hash('123', PASSWORD_DEFAULT);

    // =====================================================
    // 1. BRANCHES
    // =====================================================
    echo "<div class='box'><h2>1. Branches</h2>";

    safeInsert(
        $pdo,
        "INSERT INTO branches (id, name, code, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        ['BR-MEL-01', 'Melbourne CBD', 'MEL-CBD', '123 Collins Street', 'Melbourne', 'VIC', '+61 3 9000 0001', 'melbourne@stgeorgehospital.org'],
        "Branch: Melbourne CBD"
    );

    safeInsert(
        $pdo,
        "INSERT INTO branches (id, name, code, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        ['BR-SYD-01', 'Sydney CBD', 'SYD-CBD', '456 George Street', 'Sydney', 'NSW', '+61 2 9000 0002', 'sydney@stgeorgehospital.org'],
        "Branch: Sydney CBD"
    );
    echo "</div>";

    // =====================================================
    // 2. USERS & PROFILES
    // =====================================================
    echo "<div class='box'><h2>2. Users & Profiles</h2>";

    // Admin
    $adminId = 'USR-ADMIN-01';
    safeInsert(
        $pdo,
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$adminId, 'Super Admin', 'admin@stgeorgehospital.org', $password, 'admin', '+61 400 000 001'],
        "Admin: admin@stgeorgehospital.org"
    );

    // Branch Admin
    $branchAdminId = 'USR-BA-MEL-01';
    safeInsert(
        $pdo,
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$branchAdminId, 'Melbourne Branch Manager', 'manager.mel@stgeorge.com', $password, 'branch_admin', '03-9999-8888'],
        "Branch Admin: manager.mel@stgeorge.com"
    );
    safeInsert(
        $pdo,
        "INSERT INTO branch_admin_profiles (id, user_id, branch_id, employee_id, designation) VALUES (?, ?, ?, ?, ?)",
        ['BAP-MEL-01', $branchAdminId, 'BR-MEL-01', 'EMP-BA-001', 'Branch Manager'],
        "Branch Admin Profile"
    );

    // Doctor 1
    $docUserId1 = 'USR-DOC-01';
    $docProfId1 = 'DOC-PROF-01';
    safeInsert(
        $pdo,
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$docUserId1, 'Dr. Sarah Johnson', 'dr.sarah@stgeorgehospital.org', $password, 'doctor', '+61 400 000 002'],
        "Doctor: dr.sarah@stgeorgehospital.org"
    );
    safeInsert(
        $pdo,
        "INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)",
        [$docProfId1, $docUserId1, 'BR-MEL-01', 'General Medicine', 'MED-VIC-55555', 100.00],
        "Doctor Profile: Dr. Sarah Johnson"
    );

    // Doctor 2
    $docUserId2 = 'USR-DOC-02';
    $docProfId2 = 'DOC-PROF-02';
    safeInsert(
        $pdo,
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$docUserId2, 'Dr. Michael Chen', 'dr.michael@stgeorgehospital.org', $password, 'doctor', '+61 400 000 003'],
        "Doctor: dr.michael@stgeorgehospital.org"
    );
    safeInsert(
        $pdo,
        "INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)",
        [$docProfId2, $docUserId2, 'BR-MEL-01', 'Cardiology', 'MED-VIC-66666', 150.00],
        "Doctor Profile: Dr. Michael Chen"
    );

    // Staff
    $staffUserId = 'USR-STF-01';
    safeInsert(
        $pdo,
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$staffUserId, 'Jane Smith', 'jane.smith@stgeorgehospital.org', $password, 'staff', '+61 400 000 010'],
        "Staff: jane.smith@stgeorgehospital.org"
    );
    safeInsert(
        $pdo,
        "INSERT INTO staff_profiles (id, user_id, branch_id, department, employee_id, designation) VALUES (?, ?, ?, ?, ?, ?)",
        ['STF-PROF-01', $staffUserId, 'BR-MEL-01', 'Reception', 'EMP-001', 'Senior Receptionist'],
        "Staff Profile: Jane Smith"
    );

    // Patients
    $patients = [
        ['John Smith', 'john@gmail.com', 'Male', '1980-05-15', 'O+', 'Hypertension', '+61 400 100 001'],
        ['Emma Wilson', 'emma@gmail.com', 'Female', '1992-08-22', 'A-', 'Migraine', '+61 400 100 002'],
        ['Michael Brown', 'mike@gmail.com', 'Male', '1975-12-10', 'B+', 'Diabetes Type 2', '+61 400 100 003'],
        ['Sarah Davis', 'sarah.d@gmail.com', 'Female', '1988-03-30', 'O-', 'Asthma', '+61 400 100 004'],
        ['Robert Taylor', 'robert.t@gmail.com', 'Male', '1965-07-25', 'AB+', 'Heart Disease', '+61 400 100 005'],
    ];

    $patientProfileIds = [];
    $patNum = 1001;

    foreach ($patients as $p) {
        $userId = generateId('USR-PAT');
        $profId = generateId('PROF-PAT');
        $patientId = 'PAT-2026-' . str_pad($patNum++, 4, '0', STR_PAD_LEFT);

        safeInsert(
            $pdo,
            "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
            [$userId, $p[0], $p[1], $password, 'patient', $p[6]],
            "Patient: {$p[0]} ({$p[1]})"
        );

        safeInsert(
            $pdo,
            "INSERT INTO patient_profiles (id, user_id, patient_id, date_of_birth, gender, blood_group, medical_history) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$profId, $userId, $patientId, $p[3], $p[2], $p[4], $p[5]],
            "Patient Profile: $patientId"
        );

        $patientProfileIds[] = $profId;
    }
    echo "</div>";

    // =====================================================
    // 3. MEDICINES
    // =====================================================
    echo "<div class='box'><h2>3. Medicines</h2>";

    $medicines = [
        ['MED-001', 'PARA500', 'Paracetamol 500mg', 'Paracetamol', 'Pain Relief', 'GSK', 'Tablet', '500mg', 0.50],
        ['MED-002', 'AMOX500', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'Pfizer', 'Capsule', '500mg', 1.20],
        ['MED-003', 'IBUP400', 'Ibuprofen 400mg', 'Ibuprofen', 'Pain Relief', 'Bayer', 'Tablet', '400mg', 0.80],
        ['MED-004', 'OMEP20', 'Omeprazole 20mg', 'Omeprazole', 'Gastrointestinal', 'AstraZeneca', 'Capsule', '20mg', 1.50],
        ['MED-005', 'LORA10', 'Loratadine 10mg', 'Loratadine', 'Antihistamine', 'Schering', 'Tablet', '10mg', 0.60],
        ['MED-006', 'MTFM500', 'Metformin 500mg', 'Metformin', 'Diabetes', 'Merck', 'Tablet', '500mg', 0.30],
        ['MED-007', 'ATOR20', 'Atorvastatin 20mg', 'Atorvastatin', 'Cholesterol', 'Pfizer', 'Tablet', '20mg', 1.80],
        ['MED-008', 'CIPR500', 'Ciprofloxacin 500mg', 'Ciprofloxacin', 'Antibiotic', 'Bayer', 'Tablet', '500mg', 2.00],
        ['MED-009', 'AZIT250', 'Azithromycin 250mg', 'Azithromycin', 'Antibiotic', 'Pfizer', 'Tablet', '250mg', 3.50],
        ['MED-010', 'SALB4', 'Salbutamol Inhaler', 'Salbutamol', 'Respiratory', 'GSK', 'Inhaler', '100mcg', 15.00],
        ['MED-011', 'DICL50', 'Diclofenac 50mg', 'Diclofenac', 'Pain Relief', 'Novartis', 'Tablet', '50mg', 0.70],
        ['MED-012', 'RANITIDINE', 'Ranitidine 150mg', 'Ranitidine', 'Gastrointestinal', 'GSK', 'Tablet', '150mg', 0.45],
        ['MED-013', 'CETR10', 'Cetirizine 10mg', 'Cetirizine', 'Antihistamine', 'UCB', 'Tablet', '10mg', 0.55],
        ['MED-014', 'PANT40', 'Pantoprazole 40mg', 'Pantoprazole', 'Gastrointestinal', 'Takeda', 'Tablet', '40mg', 1.25],
        ['MED-015', 'DOXY100', 'Doxycycline 100mg', 'Doxycycline', 'Antibiotic', 'Pfizer', 'Capsule', '100mg', 1.10],
    ];

    foreach ($medicines as $m) {
        safeInsert(
            $pdo,
            "INSERT INTO medicines (id, code, name, generic_name, category, manufacturer, dosage_form, strength, unit_price, requires_prescription, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)",
            $m,
            "Medicine: {$m[2]}"
        );
    }
    echo "</div>";

    // =====================================================
    // 4. INVENTORY
    // =====================================================
    echo "<div class='box'><h2>4. Inventory (Melbourne)</h2>";
    $branch_id = 'BR-MEL-01';

    foreach ($medicines as $index => $m) {
        $inv_id = 'INV-MEL-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $qty = rand(50, 500);
        $reorder = 20;
        $batch = 'BATCH-' . date('Ym') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d', strtotime('+' . rand(6, 24) . ' months'));

        safeInsert(
            $pdo,
            "INSERT INTO inventory (id, medicine_id, branch_id, quantity, reorder_level, batch_number, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$inv_id, $m[0], $branch_id, $qty, $reorder, $batch, $expiry],
            "Inventory: {$m[2]} (Qty: $qty)"
        );
    }
    echo "</div>";

    // =====================================================
    // 5. LAB TEST TYPES
    // =====================================================
    echo "<div class='box'><h2>5. Lab Test Types</h2>";

    $labTests = [
        ['LTT-001', 'Complete Blood Count (CBC)', 'CBC', 25.00, 'Full blood cell analysis', 'Hematology', 'Blood', 24, 0],
        ['LTT-002', 'Lipid Panel', 'LIPID', 35.00, 'Cholesterol and triglycerides', 'Biochemistry', 'Blood', 24, 1],
        ['LTT-003', 'Blood Glucose (Fasting)', 'GLUC-F', 15.00, 'Fasting blood sugar level', 'Biochemistry', 'Blood', 12, 1],
        ['LTT-004', 'Liver Function Test', 'LFT', 45.00, 'Liver enzyme analysis', 'Biochemistry', 'Blood', 24, 0],
        ['LTT-005', 'Kidney Function Test', 'KFT', 40.00, 'Creatinine, BUN, eGFR', 'Biochemistry', 'Blood', 24, 0],
        ['LTT-006', 'Thyroid Panel (TSH, T3, T4)', 'THYROID', 55.00, 'Thyroid hormone levels', 'Biochemistry', 'Blood', 48, 0],
        ['LTT-007', 'Urinalysis', 'URINE', 20.00, 'Complete urine analysis', 'Pathology', 'Urine', 24, 0],
        ['LTT-008', 'COVID-19 PCR Test', 'COVID-PCR', 75.00, 'SARS-CoV-2 detection', 'Microbiology', 'Swab', 12, 0],
    ];

    foreach ($labTests as $lt) {
        // Prepare statement considering potential column updates
        safeInsert(
            $pdo,
            "INSERT INTO lab_test_types (id, name, code, price, description, category, sample_type, turnaround_hours, fasting_required, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE category=VALUES(category), sample_type=VALUES(sample_type), turnaround_hours=VALUES(turnaround_hours), fasting_required=VALUES(fasting_required)",
            $lt,
            "Lab Test: {$lt[1]}"
        );
    }
    echo "</div>";

    // =====================================================
    // 6. DOCTOR SCHEDULES
    // =====================================================
    echo "<div class='box'><h2>6. Doctor Schedules</h2>";

    $doctorIds = [$docProfId1, $docProfId2];
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];

    foreach ($doctorIds as $index => $docId) {
        $start = ($index === 0) ? '09:00:00' : '10:00:00';
        $end = ($index === 0) ? '17:00:00' : '16:00:00';

        foreach ($days as $dayNum => $dayName) {
            $scheduleId = "SCH-$docId-$dayNum";
            safeInsert(
                $pdo,
                "INSERT INTO doctor_weekly_schedules (id, doctor_id, branch_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (?, ?, ?, ?, ?, ?, 30, 1)",
                [$scheduleId, $docId, 'BR-MEL-01', $dayNum, $start, $end],
                "Schedule: $docId on $dayName"
            );
        }
    }
    echo "</div>";

    // =====================================================
    // 7. SAMPLE APPOINTMENTS (Today)
    // =====================================================
    echo "<div class='box'><h2>7. Sample Appointments</h2>";

    $today = date('Y-m-d');

    if (count($patientProfileIds) >= 3) {
        safeInsert(
            $pdo,
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100001', $patientProfileIds[0], $docProfId1, 'BR-MEL-01', $today, '09:00:00', '09:30:00', 'General checkup', 'scheduled'],
            "Appointment: John Smith with Dr. Sarah @ 09:00"
        );

        safeInsert(
            $pdo,
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100002', $patientProfileIds[1], $docProfId1, 'BR-MEL-01', $today, '10:00:00', '10:30:00', 'Migraine follow-up', 'checked_in'],
            "Appointment: Emma Wilson with Dr. Sarah @ 10:00"
        );

        safeInsert(
            $pdo,
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100003', $patientProfileIds[2], $docProfId2, 'BR-MEL-01', $today, '14:00:00', '14:30:00', 'Heart palpitations', 'scheduled'],
            "Appointment: Michael Brown with Dr. Michael @ 14:00"
        );
    }
    echo "</div>";

    // =====================================================
    // 8. SAMPLE BILLS
    // =====================================================
    echo "<div class='box'><h2>8. Sample Bills</h2>";

    if (count($patientProfileIds) >= 1) {
        $billId = generateId('BILL');
        $saved = safeInsert(
            $pdo,
            "INSERT INTO bills (id, bill_no, patient_id, branch_id, total_amount, paid_amount, due_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$billId, 'INV-2026-000001', $patientProfileIds[0], 'BR-MEL-01', 150.00, 0, 150.00, 'pending'],
            "Bill: INV-2026-000001 - $150.00 (Pending)"
        );

        if ($saved) {
            safeInsert(
                $pdo,
                "INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [generateId('BI'), $billId, 'General Consultation', 'consultation', 1, 100.00, 100.00],
                "Bill Item: Consultation Fee"
            );

            safeInsert(
                $pdo,
                "INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [generateId('BI'), $billId, 'Blood Test - CBC', 'lab_test', 1, 50.00, 50.00],
                "Bill Item: Lab Test"
            );
        }
    }
    echo "</div>";

    // =====================================================
    // SUCCESS
    // =====================================================
    echo "<div class='box' style='background:#f0fdf4; border-color:#bbf7d0;'>";
    echo "<h2 style='color:#166534; border-bottom-color:#bbf7d0;'>🎉 Database Seeding Complete!</h2>";
    echo "<div class='grid-2' style='display:grid; grid-template-columns:1fr 1fr; gap:20px;'>";
    echo "<div>";
    echo "<h3>👤 Test Credentials</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@stgeorgehospital.org</li>";
    echo "<li><strong>Branch Admin:</strong> manager.mel@stgeorge.com</li>";
    echo "<li><strong>Doctor 1:</strong> dr.sarah@stgeorgehospital.org</li>";
    echo "<li><strong>Doctor 2:</strong> dr.michael@stgeorgehospital.org</li>";
    echo "<li><strong>Staff:</strong> jane.smith@stgeorgehospital.org</li>";
    echo "<li><strong>Patients:</strong> john@gmail.com, emma@gmail.com...</li>";
    echo "</ul>";
    echo "<p><strong>Password:</strong> 123 (for all users)</p>";
    echo "</div>";
    echo "<div>";
    echo "<h3>📊 Data Summary</h3>";
    echo "<ul>";
    echo "<li>Branches: 2</li>";
    echo "<li>Users: " . (5 + count($patients)) . "</li>";
    echo "<li>Medicines: " . count($medicines) . "</li>";
    echo "<li>Inventory: " . count($medicines) . " items</li>";
    echo "<li>Lab Tests: " . count($labTests) . " types</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "<div style='margin-top:20px; text-align:center;'>";
    echo "<a href='../auth/login.php' class='btn'>Go to Login Page</a>";
    echo "</div>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>