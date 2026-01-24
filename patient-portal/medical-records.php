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

// Fetch Medical Records
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
            <button class="tab active">Consultations</button>
            <button class="tab">Diagnoses</button>
            <button class="tab">Vitals History</button>
        </div>

        <?php if (count($records) > 0): ?>
            <?php foreach ($records as $record): ?>
            <div class="record-card">
                <div class="record-header">
                    <div>
                        <div class="record-date">📅 <?php echo date('d F Y', strtotime($record['appointment_date'] ?? $record['created_at'])); ?></div>
                        <div class="record-doctor"><?php echo h($record['doctor_name']); ?></div>
                        <div class="record-dept"><?php echo h($record['specialization']); ?></div>
                    </div>
                    <span class="badge badge-green">Completed</span>
                </div>
                <div class="record-content">
                    <h5>Chief Complaint</h5>
                    <p><?php echo nl2br(h($record['chief_complaint'])); ?></p>
                    
                    <h5>Diagnosis</h5>
                    <p><?php echo nl2br(h($record['diagnosis'])); ?></p>
                    
                    <h5>Treatment Plan</h5>
                    <p><?php echo nl2br(h($record['treatment_plan'])); ?></p>
                </div>
                <div class="record-actions">
                    <button class="btn btn-outline btn-sm">Download PDF</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p class="text-center text-gray p-4">No medical records found. Your consultation records will appear here after your doctor completes them.</p>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
