<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Service Charges - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'services';

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Service Charges</h1>
                <p class="page-subtitle">Manage consultation fees, lab test prices, and service charges</p>
            </div>
            <button class="btn btn-primary" onclick="alert('Feature coming soon')">+ Add New Service</button>
        </div>

        <div class="flex gap-2 mb-4">
            <select class="form-select" style="width: auto;">
                <option>All Categories</option>
                <option>Consultation</option>
                <option>Laboratory</option>
                <option>Pharmacy</option>
                <option>Other</option>
            </select>
            <input type="text" class="form-input" placeholder="🔍 Search services..." style="width: 200px;">
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Consultation Fees</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price (AUD)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>General Consultation (Standard)</td>
                            <td>$85.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>General Consultation (Extended)</td>
                            <td>$120.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Specialist Consultation</td>
                            <td>$150.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Follow-up Consultation</td>
                            <td>$65.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Laboratory Tests</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price (AUD)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Complete Blood Count (CBC)</td>
                            <td>$45.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Lipid Profile</td>
                            <td>$65.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Liver Function Test (LFT)</td>
                            <td>$55.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Kidney Function Test (KFT)</td>
                            <td>$55.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Thyroid Panel (TSH, T3, T4)</td>
                            <td>$75.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Blood Glucose (Fasting)</td>
                            <td>$25.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>HbA1c</td>
                            <td>$40.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Urinalysis</td>
                            <td>$20.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pharmacy & Other</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price (AUD)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pharmacy Dispensing Fee</td>
                            <td>$15.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Medical Certificate</td>
                            <td>$30.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Medical Report</td>
                            <td>$50.00</td>
                            <td><button class="btn btn-sm btn-outline">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
