<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Patient Management - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'patients';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Patient Management</h1>
                <p class="page-subtitle">Register new patients or search existing records</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Register New</button>
            <button class="tab">Search Patient</button>
        </div>

        <!-- Tab 1: Register New Patient -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Register New Patient</h3>
            </div>

            <div class="section-header">PERSONAL INFORMATION</div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" class="form-input" placeholder="Enter first name">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" class="form-input" placeholder="Enter last name">
                </div>
                <!-- Additional fields omitted for brevity, can be added as needed -->
            </div>
            
            <div class="flex gap-2 mt-4">
                <button class="btn btn-outline">Clear Form</button>
                <button class="btn btn-primary">Register Patient</button>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
