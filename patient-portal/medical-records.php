<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Medical Records - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'medical-records';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found.");
}

$patient_profile_id = $patient['id'];
$active_tab = $_GET['tab'] ?? 'consultations';

// Fetch Medical Records (Consultations)
$records = [];
try {
    $stmt = $pdo->prepare("
        SELECT mr.*, u.name as doctor_name, dp.specialization, a.appointment_date
        FROM medical_records mr 
        JOIN doctor_profiles dp ON mr.doctor_id = dp.id 
        JOIN users u ON dp.user_id = u.id
        LEFT JOIN appointments a ON mr.appointment_id = a.id
        WHERE mr.patient_id = ? AND mr.status = 'finalized'
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$patient_profile_id]);
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table may not exist
}

// Fetch Diagnoses (extracted from medical_records)
$diagnoses = [];
try {
    $stmt = $pdo->prepare("
        SELECT mr.diagnosis, mr.created_at, u.name as doctor_name, dp.specialization
        FROM medical_records mr 
        JOIN doctor_profiles dp ON mr.doctor_id = dp.id 
        JOIN users u ON dp.user_id = u.id
        WHERE mr.patient_id = ? AND mr.status = 'finalized' AND mr.diagnosis IS NOT NULL AND mr.diagnosis != ''
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$patient_profile_id]);
    $diagnoses = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table may not exist
}

// Fetch Vitals History
$vitals = [];
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.name as recorded_by_name
        FROM vitals v
        LEFT JOIN users u ON v.recorded_by = u.id
        WHERE v.patient_id = ?
        ORDER BY v.recorded_at DESC
        LIMIT 20
    ");
    $stmt->execute([$patient_profile_id]);
    $vitals = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table may not exist
}

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Medical Records</h1>
                <p class="page-subtitle">View your consultation history and diagnoses</p>
            </div>
        </div>

        <div class="tabs">
            <a href="?tab=consultations"
                class="tab <?php echo $active_tab === 'consultations' ? 'active' : ''; ?>">Consultations</a>
            <a href="?tab=diagnoses"
                class="tab <?php echo $active_tab === 'diagnoses' ? 'active' : ''; ?>">Diagnoses</a>
            <a href="?tab=vitals" class="tab <?php echo $active_tab === 'vitals' ? 'active' : ''; ?>">Vitals History</a>
        </div>

        <?php if ($active_tab === 'consultations'): ?>
            <!-- Consultations Tab -->
            <?php if (count($records) > 0): ?>
                <?php foreach ($records as $record): ?>
                    <div class="record-card"
                        style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                        <div class="record-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="record-date" style="font-weight: 600;">📅
                                    <?php echo date('d F Y', strtotime($record['appointment_date'] ?? $record['created_at'])); ?>
                                </div>
                                <div class="record-doctor" style="font-size: 14px; color: #374151;">
                                    <?php echo h($record['doctor_name']); ?></div>
                                <div class="record-dept" style="font-size: 13px; color: #6b7280;">
                                    <?php echo h($record['specialization']); ?></div>
                            </div>
                            <span class="badge badge-green">Completed</span>
                        </div>
                        <div class="record-content" style="margin-top: 12px;">
                            <h5 style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Chief Complaint</h5>
                            <p style="margin: 0 0 12px 0;"><?php echo nl2br(h($record['chief_complaint'])); ?></p>

                            <h5 style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Diagnosis</h5>
                            <p style="margin: 0 0 12px 0;"><?php echo nl2br(h($record['diagnosis'])); ?></p>

                            <h5 style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Treatment Plan</h5>
                            <p style="margin: 0;"><?php echo nl2br(h($record['treatment_plan'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p class="text-center text-gray p-4">No medical records found. Your consultation records will appear here
                        after your doctor completes them.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($active_tab === 'diagnoses'): ?>
            <!-- Diagnoses Tab -->
            <?php if (count($diagnoses) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Your Diagnoses</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Diagnosis</th>
                                    <th>Doctor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnoses as $d): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
                                        <td><strong><?php echo h($d['diagnosis']); ?></strong></td>
                                        <td><?php echo h($d['doctor_name']); ?><br><span
                                                class="text-sm text-gray"><?php echo h($d['specialization']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <p class="text-center text-gray p-4">No diagnoses recorded yet.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($active_tab === 'vitals'): ?>
            <!-- Vitals History Tab -->
            <?php if (count($vitals) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vitals History</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Blood Pressure</th>
                                    <th>Heart Rate</th>
                                    <th>Temperature</th>
                                    <th>Weight</th>
                                    <th>SpO2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vitals as $v): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($v['recorded_at'])); ?></td>
                                        <td><?php echo h($v['blood_pressure'] ?? '-'); ?></td>
                                        <td><?php echo $v['heart_rate'] ? h($v['heart_rate']) . ' bpm' : '-'; ?></td>
                                        <td><?php echo $v['temperature'] ? h($v['temperature']) . '°C' : '-'; ?></td>
                                        <td><?php echo $v['weight'] ? h($v['weight']) . ' kg' : '-'; ?></td>
                                        <td><?php echo $v['oxygen_saturation'] ? h($v['oxygen_saturation']) . '%' : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <p class="text-center text-gray p-4">No vitals recorded yet. Your vital signs will appear here after being
                        recorded during visits.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>