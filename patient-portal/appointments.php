<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'My Appointments - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'appointments';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Appointments</h1>
                <p class="page-subtitle">View and manage your appointments</p>
            </div>
            <a href="book-appointment.php" class="btn btn-primary">➕ Book New Appointment</a>
        </div>

        <div class="tabs">
            <button class="tab active">Upcoming</button>
            <button class="tab">Past Appointments</button>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upcoming Appointments</h3>
                <span class="badge badge-blue">2 appointments</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>APT-2026-001234</strong></td>
                            <td>
                                <div style="font-weight: 500;">27 Jan 2026</div>
                                <div class="text-sm text-gray">10:30 AM</div>
                            </td>
                            <td>Dr. Sarah Johnson</td>
                            <td>General Medicine</td>
                            <td>Melbourne CBD</td>
                            <td><span class="badge badge-green">Confirmed</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">View</button>
                                <button class="btn btn-sm btn-outline">Reschedule</button>
                                <button class="btn btn-sm btn-outline">Cancel</button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>APT-2026-001289</strong></td>
                            <td>
                                <div style="font-weight: 500;">05 Feb 2026</div>
                                <div class="text-sm text-gray">02:00 PM</div>
                            </td>
                            <td>Dr. Michael Chen</td>
                            <td>Cardiology</td>
                            <td>Sydney CBD</td>
                            <td><span class="badge badge-yellow">Pending</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">View</button>
                                <button class="btn btn-sm btn-outline">Reschedule</button>
                                <button class="btn btn-sm btn-outline">Cancel</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Past Appointments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Past Appointments</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>APT-2026-001100</strong></td>
                            <td>
                                <div style="font-weight: 500;">15 Jan 2026</div>
                                <div class="text-sm text-gray">09:00 AM</div>
                            </td>
                            <td>Dr. Sarah Johnson</td>
                            <td>General Medicine</td>
                            <td><span class="badge badge-gray">Completed</span></td>
                            <td>
                                <a href="medical-records.php" class="btn btn-sm btn-outline">View Record</a>
                                <button class="btn btn-sm btn-primary">Book Follow-up</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
