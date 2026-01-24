<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Medical Records - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'medical-records';

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

        <div class="record-card">
            <div class="record-header">
                <div>
                    <div class="record-date">📅 15 January 2026</div>
                    <div class="record-doctor">Dr. Sarah Johnson</div>
                    <div class="record-dept">General Medicine - Melbourne CBD</div>
                </div>
                <span class="badge badge-green">Completed</span>
            </div>
            <div class="record-content">
                <h5>Chief Complaint</h5>
                <p>Persistent cough for 5 days with mild fever and body ache</p>
                <h5>Diagnosis</h5>
                <p>Upper Respiratory Tract Infection (ICD-10: J06.9)</p>
                <h5>Treatment Plan</h5>
                <p>Prescribed antibiotics, rest and hydration recommended</p>
            </div>
            <div class="record-actions">
                <button class="btn btn-primary btn-sm">View Full Details</button>
                <button class="btn btn-outline btn-sm">Download PDF</button>
                <a href="prescriptions.php" class="btn btn-outline btn-sm">View Prescription</a>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
