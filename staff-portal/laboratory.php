<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Laboratory - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'laboratory';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Laboratory</h1>
                <p class="page-subtitle">Manage lab orders and enter test results</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Pending Orders</button>
            <button class="tab">Enter Results</button>
        </div>

        <!-- Tab 1: Pending Orders -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Lab Orders</h3>
            </div>

            <div class="patient-card">
                <div class="patient-header">
                    <div>
                        <div class="patient-name">🔬 Complete Blood Count (CBC)</div>
                        <div class="patient-meta">Order ID: LAB-2026-001234 | Priority: Routine</div>
                    </div>
                    <span class="badge badge-yellow">⏳ Sample Collected</span>
                </div>
                <p class="text-sm"><strong>Patient:</strong> John Smith (PAT-2026-000123)</p>
                <div class="flex gap-2 mt-4">
                    <button class="btn btn-primary">Enter Results</button>
                </div>
            </div>
             <p class="text-sm text-gray mt-4">Showing 1 pending order (placeholder)</p>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
