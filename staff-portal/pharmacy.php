<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Pharmacy - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'pharmacy';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Pharmacy</h1>
                <p class="page-subtitle">Dispense pending prescriptions</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Prescriptions</h3>
            </div>

            <div class="patient-card">
                <div class="patient-header">
                    <div>
                        <div class="patient-name">💊 Prescription #RX-2026-004521</div>
                        <div class="patient-meta">Patient: Emma Wilson (PAT-2026-001012)</div>
                    </div>
                    <span class="badge badge-yellow">Pending</span>
                </div>
                <div class="alert alert-danger" style="margin-top: 8px;">
                    <span>⚠️</span>
                    <div><strong>ALLERGY ALERT:</strong> Penicillin, Aspirin</div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button class="btn btn-outline">View Details</button>
                    <button class="btn btn-success">✅ Mark as Dispensed</button>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
