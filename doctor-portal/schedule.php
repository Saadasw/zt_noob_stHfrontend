<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Schedule - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'schedule';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Schedule</h1>
                <p class="page-subtitle">Week of 27 Jan - 02 Feb 2026</p>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-outline">&lt; Prev Week</button>
                <button class="btn btn-primary">Today</button>
                <button class="btn btn-outline">Next &gt;</button>
            </div>
        </div>

        <div class="card">
            <div class="flex justify-between items-center">
                <div class="flex gap-2">
                    <button class="btn btn-outline">Daily</button>
                    <button class="btn btn-primary">Weekly</button>
                    <button class="btn btn-outline">Monthly</button>
                </div>
                <button class="btn btn-outline">⚙️ Manage Availability</button>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Mon 27</th>
                            <th>Tue 28</th>
                            <th>Wed 29</th>
                            <th>Thu 30</th>
                            <th>Fri 31</th>
                            <th>Sat 01</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>09:00</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>09:30</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>10:00</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>10:30</td>
                            <td class="slot-current">🔵 E.Wilson</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>11:00</td>
                            <td class="slot-booked">J.Taylor</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>11:30</td>
                            <td class="slot-booked">S.Davis</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>12:00-01:30</td>
                            <td class="slot-unavailable">LUNCH</td>
                            <td class="slot-unavailable">LUNCH</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-unavailable">LUNCH</td>
                            <td class="slot-unavailable">LUNCH</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>02:00</td>
                            <td class="slot-booked">M.Lee</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-booked">Booked</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                        <tr>
                            <td>02:30</td>
                            <td class="slot-booked">J.White</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-available">Available</td>
                            <td class="slot-unavailable">Day Off</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 12px; font-size: 12px; color: #6b7280;">
                Legend: <span style="background: #dbeafe; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Booked</span>
                <span style="background: #dcfce7; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Available</span>
                <span style="background: #f3f4f6; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Unavailable</span>
                <span style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 4px; margin: 0 4px;">Current</span>
            </div>
            <p class="text-sm text-gray mt-4">This Week: 42 slots | 35 booked | 7 available</p>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
