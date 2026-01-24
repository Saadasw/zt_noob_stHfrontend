<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Appointments - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'appointments';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Appointments</h1>
                <p class="page-subtitle"><?php echo date('l, d F Y'); ?></p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Today's Schedule</button>
            <button class="tab">Book Appointment</button>
        </div>

        <!-- Tab 1: Today's Schedule -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Appointments</h3>
                <div class="flex gap-2">
                    <select class="form-select" style="width: auto;">
                        <option>All Doctors</option>
                        <option>Dr. Sarah Johnson</option>
                        <option>Dr. Michael Chen</option>
                    </select>
                    <select class="form-select" style="width: auto;">
                        <option>All Status</option>
                        <option>Scheduled</option>
                        <option>Waiting</option>
                        <option>Completed</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>09:00</td>
                            <td>John Smith<br><span class="text-gray text-sm">PAT-2026-000123</span></td>
                            <td>Dr. Sarah Johnson<br><span class="text-gray text-sm">General Medicine</span>
                            </td>
                            <td><span class="badge badge-green">✅ Done</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td>09:30</td>
                            <td>Mary Johnson<br><span class="text-gray text-sm">PAT-2026-000456</span></td>
                            <td>Dr. Sarah Johnson<br><span class="text-gray text-sm">General Medicine</span>
                            </td>
                            <td><span class="badge badge-green">✅ Done</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr style="background: #eff6ff;">
                            <td>10:00</td>
                            <td>Robert Brown<br><span class="text-gray text-sm">PAT-2026-000789</span></td>
                            <td>Dr. Sarah Johnson<br><span class="text-gray text-sm">General Medicine</span>
                            </td>
                            <td><span class="badge badge-blue">🔵 In Progress</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td>10:30</td>
                            <td>Emma Wilson<br><span class="text-gray text-sm">PAT-2026-001012</span></td>
                            <td>Dr. Sarah Johnson<br><span class="text-gray text-sm">General Medicine</span>
                            </td>
                            <td><span class="badge badge-yellow">⏳ Waiting</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary">Check-in</button>
                                <button class="btn btn-sm btn-outline">Cancel</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray mt-4">Legend: ✅ Completed 🔵 In Progress ⏳ Waiting 🕐 Scheduled ❌
                Cancelled</p>
        </div>

        <!-- Additional Tabs & Functional Modals can be dynamically toggled or implemented in separate PHP scripts -->
    </main>
</div>

<script>
    // Simple tab switching logic for demonstration (can be moved to main js)
    const tabs = document.querySelectorAll('.tab');
    // Implement tab switching here or load partials via AJAX
</script>

<?php include '../includes/footer.php'; ?>
