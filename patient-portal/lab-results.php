<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Lab Results - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'lab-results';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Lab Results</h1>
                <p class="page-subtitle">View your laboratory test results</p>
            </div>
        </div>

        <div class="lab-card">
            <div class="lab-header">
                <div class="lab-title">
                    <div class="lab-icon">🔬</div>
                    <div>
                        <div class="lab-name">Complete Blood Count (CBC)</div>
                        <div class="lab-meta">Test Date: 20 January 2026 | Dr. Sarah Johnson</div>
                    </div>
                </div>
                <span class="badge badge-green">🟢 Normal</span>
            </div>
            <p class="text-sm text-gray">Lab: Melbourne CBD Pathology | Status: ✅ Results Ready</p>
            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary btn-sm">View Full Report</button>
                <button class="btn btn-outline btn-sm">Download PDF</button>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
