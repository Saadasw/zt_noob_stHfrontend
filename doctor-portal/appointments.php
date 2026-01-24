<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Today\'s Appointments - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'appointments';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Today's Appointments</h1>
                <p class="page-subtitle"><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <span class="badge badge-blue">Total: 12</span>
                <span class="badge badge-green">Completed: 6</span>
                <span class="badge badge-blue">In Progress: 1</span>
                <span class="badge badge-yellow">Waiting: 3</span>
            </div>
        </div>

        <div class="section-header">🔵 CURRENT PATIENT</div>
        <div class="patient-card current">
            <div class="patient-header">
                <div>
                    <div class="patient-name">10:30 AM | Emma Wilson</div>
                    <div class="patient-meta">Patient ID: PAT-2026-001012 | Age: 34 yrs | Female | Blood Group: A+</div>
                </div>
                <span class="badge badge-blue">New Patient</span>
            </div>
            <div class="patient-reason">
                <strong>Reason:</strong> Persistent headaches and dizziness
            </div>
            <div class="patient-alerts">
                ⚠️ Allergies: Penicillin, Aspirin<br>
                📋 Chronic: Migraine (diagnosed 2023)
            </div>
            <div class="flex gap-2 mt-4">
                <a href="patients.php" class="btn btn-outline">👁️ View Full History</a>
                <a href="consultation.php" class="btn btn-primary">📝 Start Consultation</a>
                <button class="btn btn-outline">❌ Mark No-Show</button>
            </div>
        </div>

        <div class="section-header">⏳ WAITING QUEUE (3)</div>
        <div class="patient-card waiting">
            <div class="patient-header">
                <div>
                    <div class="patient-name">11:00 AM | James Taylor (PAT-2026-001345)</div>
                    <div class="patient-meta">Follow-up | Male, 52 yrs | Waiting since: 10:45 AM (15 mins)</div>
                </div>
                <span class="badge badge-yellow">Waiting</span>
            </div>
            <div class="patient-reason"><strong>Reason:</strong> Blood pressure review</div>
            <div class="flex gap-2 mt-4">
                <a href="patients.php" class="btn btn-sm btn-outline">View</a>
                <a href="consultation.php" class="btn btn-sm btn-primary">Start Consultation</a>
            </div>
        </div>

        <div class="section-header">✅ COMPLETED TODAY (6)</div>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Completed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>09:00 AM</td>
                            <td>John Smith (PAT-2026-000123)</td>
                            <td>Follow-up</td>
                            <td>09:25 AM</td>
                            <td><a href="patients.php" class="btn btn-sm btn-outline">View Record</a></td>
                        </tr>
                        <tr>
                            <td>09:30 AM</td>
                            <td>Mary Johnson (PAT-2026-000456)</td>
                            <td>Consultation</td>
                            <td>09:58 AM</td>
                            <td><a href="patients.php" class="btn btn-sm btn-outline">View Record</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
