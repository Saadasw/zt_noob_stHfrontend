<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Patients - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'patients';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Patients</h1>
                <p class="page-subtitle">View and manage your patient records</p>
            </div>
        </div>

        <div class="card">
            <div class="flex gap-4">
                <select class="form-select" style="width: auto;">
                    <option>All Patients</option>
                    <option>Recent Consultations</option>
                    <option>With Lab Alerts</option>
                </select>
                <select class="form-select" style="width: auto;">
                    <option>Last 30 Days</option>
                    <option>All Time</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID</th>
                            <th>Last Visit</th>
                            <th>Diagnosis</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>John Smith</strong>
                                <div class="text-sm text-gray">Male, 45 yrs</div>
                            </td>
                            <td>PAT-2026-000123</td>
                            <td>27 Jan 2026</td>
                            <td>Hypertension</td>
                            <td><button class="btn btn-sm btn-outline">👁️ View</button></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Mary Johnson</strong>
                                <div class="text-sm text-gray">Female, 58 yrs</div>
                            </td>
                            <td>PAT-2026-000456</td>
                            <td>24 Jan 2026</td>
                            <td>Dyslipidemia <span class="badge badge-red">🔴 Lab Alert</span></td>
                            <td><button class="btn btn-sm btn-outline">👁️ View</button></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Emma Wilson</strong>
                                <div class="text-sm text-gray">Female, 34 yrs</div>
                            </td>
                            <td>PAT-2026-001012</td>
                            <td>27 Jan 2026</td>
                            <td>Migraine</td>
                            <td><button class="btn btn-sm btn-outline">👁️ View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-between items-center mt-4 text-sm text-gray">
                <span>Showing 1-3 of 234 patients</span>
                <div class="flex gap-2">
                    <button class="btn btn-sm btn-outline">&lt; Prev</button>
                    <button class="btn btn-sm btn-primary">1</button>
                    <button class="btn btn-sm btn-outline">Next &gt;</button>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
