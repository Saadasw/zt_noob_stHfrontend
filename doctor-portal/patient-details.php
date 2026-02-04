<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Patient Details - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'patients';

// Get patient profile ID from URL
$patient_profile_id = $_GET['id'] ?? null;

if (!$patient_profile_id) {
    header("Location: patients.php?error=no_patient");
    exit();
}

// =====================================================
// 1. FETCH PATIENT PROFILE
// =====================================================
$stmt = $pdo->prepare("
    SELECT pp.*, u.name, u.email, u.phone 
    FROM patient_profiles pp 
    JOIN users u ON pp.user_id = u.id 
    WHERE pp.id = ?
");
$stmt->execute([$patient_profile_id]);
$patient = $stmt->fetch();

if (!$patient) {
    header("Location: patients.php?error=not_found");
    exit();
}

// Calculate age
$dob = new DateTime($patient['date_of_birth']);
$now = new DateTime();
$age = $dob->diff($now)->y;

// =====================================================
// 2. FETCH ALL APPOINTMENTS (FROM ALL DOCTORS)
// =====================================================
$stmt = $pdo->prepare("
    SELECT a.*, 
           u_doc.name as doctor_name, 
           dp.specialization,
           b.name as branch_name
    FROM appointments a 
    LEFT JOIN doctor_profiles dp ON a.doctor_id = dp.id
    LEFT JOIN users u_doc ON dp.user_id = u_doc.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 50
");
$stmt->execute([$patient_profile_id]);
$appointments = $stmt->fetchAll();

// =====================================================
// 3. FETCH ALL MEDICAL RECORDS (FROM ALL DOCTORS)
// =====================================================
$stmt = $pdo->prepare("
    SELECT mr.*, 
           u_doc.name as doctor_name, 
           dp.specialization
    FROM medical_records mr 
    LEFT JOIN doctor_profiles dp ON mr.doctor_id = dp.id
    LEFT JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE mr.patient_id = ? AND mr.deleted_at IS NULL
    ORDER BY mr.created_at DESC
    LIMIT 50
");
$stmt->execute([$patient_profile_id]);
$records = $stmt->fetchAll();

// =====================================================
// 4. FETCH ALL PRESCRIPTIONS (FROM ALL DOCTORS)
// =====================================================
$stmt = $pdo->prepare("
    SELECT p.*, 
           u_doc.name as doctor_name, 
           dp.specialization
    FROM prescriptions p 
    LEFT JOIN doctor_profiles dp ON p.doctor_id = dp.id
    LEFT JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE p.patient_id = ?
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmt->execute([$patient_profile_id]);
$prescriptions = $stmt->fetchAll();

// Fetch prescription items for each prescription
$prescription_items = [];
if (!empty($prescriptions)) {
    $rx_ids = array_column($prescriptions, 'id');
    $placeholders = implode(',', array_fill(0, count($rx_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id IN ($placeholders)");
    $stmt->execute($rx_ids);
    $items = $stmt->fetchAll();
    foreach ($items as $item) {
        $prescription_items[$item['prescription_id']][] = $item;
    }
}

// =====================================================
// 5. FETCH ALL LAB TESTS (FROM ALL DOCTORS)
// =====================================================
$stmt = $pdo->prepare("
    SELECT lt.*, 
           ltt.name as test_name, ltt.category as test_category,
           u_doc.name as doctor_name
    FROM lab_tests lt 
    LEFT JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
    LEFT JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE lt.patient_id = ?
    ORDER BY lt.created_at DESC
    LIMIT 50
");
$stmt->execute([$patient_profile_id]);
$lab_tests = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Patient Details</h1>
                <p class="page-subtitle"><?php echo h($patient['patient_id']); ?> | <?php echo h($patient['name']); ?>
                </p>
            </div>
            <a href="patients.php" class="btn btn-outline">⬅️ Back to Patients</a>
        </div>

        <!-- Patient Profile Header -->
        <div class="card">
            <div class="flex gap-4 items-start mb-4">
                <div
                    style="width: 80px; height: 80px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: bold;">
                    <?php echo strtoupper(substr($patient['name'], 0, 2)); ?>
                </div>
                <div style="flex: 1;">
                    <h2 style="font-size: 20px; margin-bottom: 8px;"><?php echo h($patient['name']); ?></h2>
                    <div class="profile-grid">
                        <div class="profile-item"><label>Patient ID</label>
                            <p><?php echo h($patient['patient_id']); ?></p>
                        </div>
                        <div class="profile-item"><label>Age / Gender</label>
                            <p><?php echo $age; ?> years | <?php echo h($patient['gender']); ?></p>
                        </div>
                        <div class="profile-item"><label>Blood Group</label>
                            <p><?php echo h($patient['blood_group'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="profile-item"><label>DOB</label>
                            <p><?php echo date('d M Y', strtotime($patient['date_of_birth'])); ?></p>
                        </div>
                        <div class="profile-item"><label>Phone</label>
                            <p><?php echo h($patient['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="profile-item"><label>Email</label>
                            <p><?php echo h($patient['email']); ?></p>
                        </div>
                        <div class="profile-item"><label>Emergency Contact</label>
                            <p><?php echo h($patient['emergency_contact_name'] ?? 'N/A'); ?>
                                <?php echo $patient['emergency_contact_phone'] ? '(' . h($patient['emergency_contact_phone']) . ')' : ''; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($patient['allergies'])): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ ALLERGIES:</strong> <?php echo h($patient['allergies']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($patient['medical_history'])): ?>
                <div class="alert alert-info">
                    <strong>📋 MEDICAL HISTORY:</strong> <?php echo h($patient['medical_history']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabs Navigation -->
        <div class="card" style="padding: 0;">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="showTab('appointments')">📅 Appointments
                    (<?php echo count($appointments); ?>)</button>
                <button class="tab-btn" onclick="showTab('records')">📋 Medical Records
                    (<?php echo count($records); ?>)</button>
                <button class="tab-btn" onclick="showTab('prescriptions')">💊 Prescriptions
                    (<?php echo count($prescriptions); ?>)</button>
                <button class="tab-btn" onclick="showTab('labs')">🔬 Lab Tests
                    (<?php echo count($lab_tests); ?>)</button>
            </div>
        </div>

        <!-- Appointments Tab -->
        <div id="tab-appointments" class="tab-content card">
            <h3 class="card-title mb-4">📅 Appointment History</h3>
            <?php if (empty($appointments)): ?>
                <p class="text-gray text-center">No appointment history found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>
                                        <div class="text-sm text-gray">
                                            <?php echo date('h:i A', strtotime($apt['start_time'])); ?></div>
                                    </td>
                                    <td>
                                        Dr. <?php echo h($apt['doctor_name'] ?? 'Unknown'); ?>
                                        <div class="text-sm text-gray"><?php echo h($apt['specialization'] ?? ''); ?></div>
                                    </td>
                                    <td><?php echo h($apt['reason'] ?? 'Consultation'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $apt['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($apt['status'] === 'completed'): ?>
                                            <a href="consultation.php?apt_id=<?php echo $apt['id']; ?>&view=1"
                                                class="btn btn-sm btn-outline">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Medical Records Tab -->
        <div id="tab-records" class="tab-content card" style="display: none;">
            <h3 class="card-title mb-4">📋 Medical Records</h3>
            <?php if (empty($records)): ?>
                <p class="text-gray text-center">No medical records found.</p>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <div class="record-card">
                        <div class="record-header">
                            <div>
                                <strong><?php echo date('d M Y', strtotime($record['created_at'])); ?></strong>
                                <span class="text-gray">| Dr. <?php echo h($record['doctor_name'] ?? 'Unknown'); ?></span>
                                <?php if ($record['specialization']): ?>
                                    <span class="text-sm text-gray">(<?php echo h($record['specialization']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <span
                                class="badge badge-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></span>
                        </div>
                        <div class="record-body">
                            <div class="record-field"><label>Chief Complaint:</label>
                                <p><?php echo h($record['chief_complaint']); ?></p>
                            </div>
                            <div class="record-field"><label>Diagnosis:</label>
                                <p><?php echo h($record['diagnosis']); ?></p>
                            </div>
                            <?php if (!empty($record['treatment_plan'])): ?>
                                <div class="record-field"><label>Treatment Plan:</label>
                                    <p><?php echo h($record['treatment_plan']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($record['notes'])): ?>
                                <div class="record-field"><label>Notes:</label>
                                    <p><?php echo h($record['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Prescriptions Tab -->
        <div id="tab-prescriptions" class="tab-content card" style="display: none;">
            <h3 class="card-title mb-4">💊 Prescriptions</h3>
            <?php if (empty($prescriptions)): ?>
                <p class="text-gray text-center">No prescriptions found.</p>
            <?php else: ?>
                <?php foreach ($prescriptions as $rx): ?>
                    <div class="record-card">
                        <div class="record-header">
                            <div>
                                <strong><?php echo h($rx['prescription_no']); ?></strong>
                                <span class="text-gray">| <?php echo date('d M Y', strtotime($rx['created_at'])); ?></span>
                                <span class="text-gray">| Dr. <?php echo h($rx['doctor_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <span
                                class="badge badge-<?php echo $rx['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $rx['status'])); ?></span>
                        </div>
                        <div class="record-body">
                            <?php if (isset($prescription_items[$rx['id']])): ?>
                                <table class="inner-table">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prescription_items[$rx['id']] as $item): ?>
                                            <tr>
                                                <td><?php echo h($item['medicine_name']); ?></td>
                                                <td><?php echo h($item['dosage'] ?? '-'); ?></td>
                                                <td><?php echo h($item['frequency'] ?? '-'); ?></td>
                                                <td><?php echo h($item['duration'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray">No medicines listed.</p>
                            <?php endif; ?>
                            <?php if (!empty($rx['notes'])): ?>
                                <p class="mt-2"><strong>Notes:</strong> <?php echo h($rx['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Lab Tests Tab -->
        <div id="tab-labs" class="tab-content card" style="display: none;">
            <h3 class="card-title mb-4">🔬 Lab Tests</h3>
            <?php if (empty($lab_tests)): ?>
                <p class="text-gray text-center">No lab tests found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Test</th>
                                <th>Ordered By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lab_tests as $test): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($test['created_at'])); ?></td>
                                    <td>
                                        <?php echo h($test['test_name'] ?? 'Unknown Test'); ?>
                                        <div class="text-sm text-gray"><?php echo h($test['test_category'] ?? ''); ?></div>
                                    </td>
                                    <td>Dr. <?php echo h($test['doctor_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <?php
                                        $priority_badges = [
                                            'routine' => '<span class="badge badge-routine">Routine</span>',
                                            'urgent' => '<span class="badge badge-urgent">Urgent</span>',
                                            'stat' => '<span class="badge badge-stat">STAT</span>',
                                        ];
                                        echo $priority_badges[$test['priority'] ?? 'routine'] ?? '<span class="badge badge-routine">Routine</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'ordered' => '<span class="badge badge-ordered">Ordered</span>',
                                            'sample_pending' => '<span class="badge badge-ordered">Sample Pending</span>',
                                            'sample_collected' => '<span class="badge badge-processing">Collected</span>',
                                            'processing' => '<span class="badge badge-processing">Processing</span>',
                                            'completed' => '<span class="badge badge-completed">Completed</span>',
                                            'reviewed' => '<span class="badge badge-completed">Reviewed</span>',
                                        ];
                                        echo $status_badges[$test['status'] ?? 'ordered'] ?? '<span class="badge">' . ucfirst($test['status'] ?? 'Pending') . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="lab-orders.php?view_result=<?php echo $test['id']; ?>"
                                            class="btn btn-sm btn-outline">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<style>
    .tabs-nav {
        display: flex;
        border-bottom: 1px solid #e5e7eb;
        overflow-x: auto;
    }

    .tab-btn {
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
    }

    .tab-btn.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        font-weight: 500;
    }

    .tab-btn:hover {
        background: #f9fafb;
    }

    .record-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .record-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        border-radius: 8px 8px 0 0;
    }

    .record-body {
        padding: 16px;
    }

    .record-field {
        margin-bottom: 12px;
    }

    .record-field label {
        font-weight: 500;
        color: #374151;
        display: block;
        margin-bottom: 4px;
    }

    .record-field p {
        color: #6b7280;
    }

    .inner-table {
        width: 100%;
        border-collapse: collapse;
    }

    .inner-table th,
    .inner-table td {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        text-align: left;
        font-size: 13px;
    }

    .inner-table th {
        background: #f9fafb;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }

    .badge-completed,
    .badge-finalized,
    .badge-fully_dispensed {
        background: #dcfce7;
        color: #166534;
    }

    .badge-scheduled,
    .badge-active,
    .badge-pending {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-cancelled,
    .badge-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-in_progress,
    .badge-partial,
    .badge-partially_dispensed {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-draft {
        background: #f3f4f6;
        color: #6b7280;
    }

    .badge-urgent {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-routine {
        background: #f3f4f6;
        color: #6b7280;
    }

    .badge-stat {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-ordered,
    .badge-sample_pending,
    .badge-sample_collected {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-processing {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-reviewed {
        background: #dcfce7;
        color: #166534;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-top: 12px;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }

    .profile-item label {
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
    }

    .profile-item p {
        font-size: 14px;
        color: #111827;
        margin-top: 2px;
    }
</style>

<script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        // Remove active from buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        // Show selected tab
        document.getElementById('tab-' + tabName).style.display = 'block';
        // Find and activate button
        event.target.classList.add('active');
    }
</script>

<?php include '../includes/footer.php'; ?>