<?php
/**
 * St. George Hospital - Complete Database Seed Script
 * 
 * Run this file after importing schema.sql to populate the database with test data.
 * 
 * Usage:
 *   1. Import schema.sql into MySQL/phpMyAdmin
 *   2. Visit http://localhost/zt_noob_stHfrontend/database/seed.php
 * 
 * Test Credentials (all passwords: 123):
 *   - Admin:   admin@stgeorgehospital.org
 *   - Doctor:  dr.sarah@stgeorgehospital.org
 *   - Staff:   jane.smith@stgeorgehospital.org
 *   - Patients: john@gmail.com, emma@gmail.com, etc.
 */

require __DIR__ . '/../../config/db_connect.php';

echo "<!DOCTYPE html><html><head><title>Database Seed</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5;}.success{color:#22c55e;}.error{color:#dc2626;}.box{background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}</style></head><body>";
echo "<h1>🌱 St. George Hospital - Database Seed</h1>";

// Helper functions
function generateId($prefix) { 
    return $prefix . '-' . bin2hex(random_bytes(4)); 
}

function safeInsert($pdo, $sql, $params, $description) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo "<p class='success'>✅ $description</p>";
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color:#f59e0b;'>⏭️ $description (already exists)</p>";
            return true;
        }
        echo "<p class='error'>❌ $description - " . htmlspecialchars($e->getMessage()) . "</p>";
        return false;
    }
}

try {
    $password = password_hash('123', PASSWORD_DEFAULT);

    // =====================================================
    // 1. BRANCHES
    // =====================================================
    echo "<div class='box'><h2>🏥 Branches</h2>";
    
    safeInsert($pdo, 
        "INSERT INTO branches (id, name, code, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        ['BR-MEL-01', 'Melbourne CBD', 'MEL-CBD', '123 Collins Street', 'Melbourne', 'VIC', '+61 3 9000 0001', 'melbourne@stgeorgehospital.org'],
        "Branch: Melbourne CBD"
    );
    
    safeInsert($pdo, 
        "INSERT INTO branches (id, name, code, address, city, state, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        ['BR-SYD-01', 'Sydney CBD', 'SYD-CBD', '456 George Street', 'Sydney', 'NSW', '+61 2 9000 0002', 'sydney@stgeorgehospital.org'],
        "Branch: Sydney CBD"
    );
    
    echo "</div>";

    // =====================================================
    // 2. USERS & PROFILES
    // =====================================================
    echo "<div class='box'><h2>👥 Users & Profiles</h2>";
    
    // Admin
    $adminId = 'USR-ADMIN-01';
    safeInsert($pdo, 
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$adminId, 'Super Admin', 'admin@stgeorgehospital.org', $password, 'admin', '+61 400 000 001'],
        "Admin: admin@stgeorgehospital.org"
    );
    
    // Doctor 1
    $docUserId1 = 'USR-DOC-01';
    $docProfId1 = 'DOC-PROF-01';
    safeInsert($pdo, 
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$docUserId1, 'Dr. Sarah Johnson', 'dr.sarah@stgeorgehospital.org', $password, 'doctor', '+61 400 000 002'],
        "Doctor: dr.sarah@stgeorgehospital.org"
    );
    safeInsert($pdo, 
        "INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)",
        [$docProfId1, $docUserId1, 'BR-MEL-01', 'General Medicine', 'MED-VIC-55555', 100.00],
        "Doctor Profile: Dr. Sarah Johnson"
    );
    
    // Doctor 2
    $docUserId2 = 'USR-DOC-02';
    $docProfId2 = 'DOC-PROF-02';
    safeInsert($pdo, 
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$docUserId2, 'Dr. Michael Chen', 'dr.michael@stgeorgehospital.org', $password, 'doctor', '+61 400 000 003'],
        "Doctor: dr.michael@stgeorgehospital.org"
    );
    safeInsert($pdo, 
        "INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)",
        [$docProfId2, $docUserId2, 'BR-MEL-01', 'Cardiology', 'MED-VIC-66666', 150.00],
        "Doctor Profile: Dr. Michael Chen"
    );
    
    // Staff
    $staffUserId = 'USR-STF-01';
    safeInsert($pdo, 
        "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$staffUserId, 'Jane Smith', 'jane.smith@stgeorgehospital.org', $password, 'staff', '+61 400 000 010'],
        "Staff: jane.smith@stgeorgehospital.org"
    );
    safeInsert($pdo, 
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
        
        safeInsert($pdo, 
            "INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
            [$userId, $p[0], $p[1], $password, 'patient', $p[6]],
            "Patient: {$p[0]} ({$p[1]})"
        );
        
        safeInsert($pdo, 
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
    echo "<div class='box'><h2>💊 Medicines</h2>";
    
    $medicines = [
        ['MED-001', 'Paracetamol 500mg', 'Paracetamol', 'PARA500', 'Analgesic', 'Tablet', '500mg', 0.50],
        ['MED-002', 'Amoxicillin 500mg', 'Amoxicillin', 'AMOX500', 'Antibiotic', 'Capsule', '500mg', 1.20],
        ['MED-003', 'Propranolol 40mg', 'Propranolol', 'PROP40', 'Beta Blocker', 'Tablet', '40mg', 0.80],
        ['MED-004', 'Metformin 500mg', 'Metformin', 'METF500', 'Antidiabetic', 'Tablet', '500mg', 0.60],
        ['MED-005', 'Omeprazole 20mg', 'Omeprazole', 'OMEP20', 'PPI', 'Capsule', '20mg', 0.90],
        ['MED-006', 'Sumatriptan 50mg', 'Sumatriptan', 'SUMA50', 'Triptan', 'Tablet', '50mg', 3.50],
        ['MED-007', 'Lisinopril 10mg', 'Lisinopril', 'LISI10', 'ACE Inhibitor', 'Tablet', '10mg', 0.70],
        ['MED-008', 'Atorvastatin 20mg', 'Atorvastatin', 'ATOR20', 'Statin', 'Tablet', '20mg', 1.00],
        ['MED-009', 'Aspirin 100mg', 'Aspirin', 'ASPI100', 'Analgesic', 'Tablet', '100mg', 0.30],
        ['MED-010', 'Ibuprofen 400mg', 'Ibuprofen', 'IBUP400', 'NSAID', 'Tablet', '400mg', 0.45],
    ];
    
    foreach ($medicines as $m) {
        safeInsert($pdo, 
            "INSERT INTO medicines (id, name, generic_name, code, category, dosage_form, strength, unit_price, requires_prescription, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)",
            $m,
            "Medicine: {$m[1]}"
        );
    }
    
    echo "</div>";

    // =====================================================
    // 4. LAB TEST TYPES
    // =====================================================
    echo "<div class='box'><h2>🔬 Lab Test Types</h2>";
    
    $labTests = [
        ['LTT-001', 'Complete Blood Count (CBC)', 'CBC', 25.00, 'Full blood cell analysis'],
        ['LTT-002', 'Lipid Panel', 'LIPID', 35.00, 'Cholesterol and triglycerides'],
        ['LTT-003', 'Blood Glucose (Fasting)', 'GLUC-F', 15.00, 'Fasting blood sugar level'],
        ['LTT-004', 'Liver Function Test', 'LFT', 45.00, 'Liver enzyme analysis'],
        ['LTT-005', 'Kidney Function Test', 'KFT', 40.00, 'Creatinine, BUN, eGFR'],
        ['LTT-006', 'Thyroid Panel (TSH, T3, T4)', 'THYROID', 55.00, 'Thyroid hormone levels'],
        ['LTT-007', 'Urinalysis', 'URINE', 20.00, 'Complete urine analysis'],
        ['LTT-008', 'COVID-19 PCR Test', 'COVID-PCR', 75.00, 'SARS-CoV-2 detection'],
    ];
    
    foreach ($labTests as $lt) {
        safeInsert($pdo, 
            "INSERT INTO lab_test_types (id, name, code, price, description, is_active) VALUES (?, ?, ?, ?, ?, 1)",
            $lt,
            "Lab Test: {$lt[1]}"
        );
    }
    
    echo "</div>";

    // =====================================================
    // 5. SAMPLE APPOINTMENTS (for today)
    // =====================================================
    echo "<div class='box'><h2>📅 Sample Appointments</h2>";
    
    $today = date('Y-m-d');
    
    if (count($patientProfileIds) >= 3) {
        // Appointment 1 - Scheduled
        safeInsert($pdo, 
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100001', $patientProfileIds[0], $docProfId1, 'BR-MEL-01', $today, '09:00:00', '09:30:00', 'General checkup', 'scheduled'],
            "Appointment: John Smith with Dr. Sarah @ 09:00"
        );
        
        // Appointment 2 - Checked In
        safeInsert($pdo, 
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100002', $patientProfileIds[1], $docProfId1, 'BR-MEL-01', $today, '10:00:00', '10:30:00', 'Migraine follow-up', 'checked_in'],
            "Appointment: Emma Wilson with Dr. Sarah @ 10:00"
        );
        
        // Appointment 3 - Scheduled for afternoon
        safeInsert($pdo, 
            "INSERT INTO appointments (id, appointment_no, patient_id, doctor_id, branch_id, appointment_date, start_time, end_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [generateId('APT'), 'APT-2026-100003', $patientProfileIds[2], $docProfId2, 'BR-MEL-01', $today, '14:00:00', '14:30:00', 'Heart palpitations', 'scheduled'],
            "Appointment: Michael Brown with Dr. Michael @ 14:00"
        );
    }
    
    echo "</div>";

    // =====================================================
    // 6. SAMPLE BILL
    // =====================================================
    echo "<div class='box'><h2>💳 Sample Bills</h2>";
    
    if (count($patientProfileIds) >= 1) {
        $billId = generateId('BILL');
        safeInsert($pdo, 
            "INSERT INTO bills (id, bill_no, patient_id, branch_id, total_amount, paid_amount, due_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$billId, 'INV-2026-000001', $patientProfileIds[0], 'BR-MEL-01', 150.00, 0, 150.00, 'pending'],
            "Bill: INV-2026-000001 - $150.00 (Pending)"
        );
        
        safeInsert($pdo, 
            "INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [generateId('BI'), $billId, 'General Consultation', 'consultation', 1, 100.00, 100.00],
            "Bill Item: Consultation Fee"
        );
        
        safeInsert($pdo, 
            "INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [generateId('BI'), $billId, 'Blood Test - CBC', 'lab_test', 1, 50.00, 50.00],
            "Bill Item: Lab Test"
        );
    }
    
    echo "</div>";

    // =====================================================
    // SUCCESS
    // =====================================================
    echo "<div class='box' style='background:#dcfce7;'>";
    echo "<h2>🎉 Database Seeding Complete!</h2>";
    echo "<p><strong>Test Credentials (Password: 123 for all)</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@stgeorgehospital.org</li>";
    echo "<li><strong>Doctor:</strong> dr.sarah@stgeorgehospital.org</li>";
    echo "<li><strong>Doctor 2:</strong> dr.michael@stgeorgehospital.org</li>";
    echo "<li><strong>Staff:</strong> jane.smith@stgeorgehospital.org</li>";
    echo "<li><strong>Patients:</strong> john@gmail.com, emma@gmail.com, mike@gmail.com</li>";
    echo "</ul>";
    echo "<p><a href='../auth/login.php' style='display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:4px;'>→ Go to Login</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='box' style='background:#fee2e2;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
