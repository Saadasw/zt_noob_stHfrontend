<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Referrals - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'referrals';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Create Referral</h1>
                <p class="page-subtitle">Patient: Emma Wilson (PAT-2026-001012)</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Referral To</h3>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select class="form-select">
                        <option selected>Neurology</option>
                        <option>Cardiology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Referred To</label>
                    <select class="form-select">
                        <option selected>Dr. Michael Chen - Neurologist</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Priority</label>
                <div class="flex gap-4">
                    <label><input type="radio" name="priority"> Routine</label>
                    <label><input type="radio" name="priority" checked> Urgent</label>
                    <label><input type="radio" name="priority"> Emergency</label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Clinical Information</h3>
            </div>
            <div class="form-group">
                <label class="form-label">Reason for Referral</label>
                <textarea class="form-textarea"
                    rows="2">Chronic migraines not responding to preventive therapy.</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Relevant History</label>
                <textarea class="form-textarea" rows="3">- Migraine since 2023
- Current: Propranolol 40mg daily
- Previous: Sumatriptan PRN (partial relief)</textarea>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-outline">Cancel</button>
                <button class="btn btn-primary">Submit Referral</button>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Outgoing</button>
            <button class="tab">Incoming</button>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>REF-2026-000890</td>
                            <td>John Smith</td>
                            <td>Dr. Michael Chen</td>
                            <td><span class="badge badge-green">Scheduled</span></td>
                        </tr>
                        <tr>
                            <td>REF-2026-000845</td>
                            <td>Mary Johnson</td>
                            <td>Dr. Lisa Wong</td>
                            <td><span class="badge badge-yellow">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
