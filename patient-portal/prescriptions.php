<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Prescriptions - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'prescriptions';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Prescriptions</h1>
                <p class="page-subtitle">View and manage your medications</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Current (3)</button>
            <button class="tab">Past</button>
            <button class="tab">Request Refill</button>
        </div>

        <div class="prescription-card">
            <div class="prescription-header">
                <div class="prescription-name">💊 Amoxicillin 500mg</div>
                <span class="badge badge-green">Active</span>
            </div>
            <div class="prescription-meta">
                <p><span>Prescribed by:</span> <strong>Dr. Sarah Johnson</strong></p>
                <p><span>Date:</span> <strong>15 January 2026</strong></p>
                <p><span>Dosage:</span> <strong>1 tablet, 3 times daily</strong></p>
                <p><span>Duration:</span> <strong>7 days</strong></p>
            </div>
            <div style="background: #f9fafb; padding: 10px; border-radius: 4px; margin: 12px 0;">
                <p class="text-sm"><strong>Instructions:</strong> Take after meals with water. Complete the full
                    course.</p>
            </div>
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-sm text-gray">Remaining: </span>
                    <strong style="color: #eab308;">4 days</strong>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-outline btn-sm">View Details</button>
                    <button class="btn btn-primary btn-sm">Request Refill</button>
                </div>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <span>⚠️</span>
            <div>
                <strong>Allergy Alert:</strong> Your records show allergy to Penicillin. All prescriptions have
                been reviewed for safety.
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
