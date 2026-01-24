<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Department Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'departments';

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Department Management</h1>
                <p class="page-subtitle">Manage hospital departments</p>
            </div>
            <button class="btn btn-primary" onclick="alert('Feature coming soon')">+ Add Department</button>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Doctors</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>General Medicine</strong><br><span class="text-gray text-sm">Primary
                                    care and general health</span></td>
                            <td>6</td>
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Cardiology</strong><br><span class="text-gray text-sm">Heart and
                                    cardiovascular system</span></td>
                            <td>3</td>
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Orthopedics</strong><br><span class="text-gray text-sm">Bones, joints,
                                    and muscles</span></td>
                            <td>3</td>
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Pediatrics</strong><br><span class="text-gray text-sm">Children's
                                    health</span></td>
                            <td>3</td>
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Dermatology</strong><br><span class="text-gray text-sm">Skin, hair, and
                                    nails</span></td>
                            <td>3</td>
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray mt-4">Total: 5 departments | 18 doctors</p>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
