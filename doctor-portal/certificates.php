<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Medical Certificates - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'certificates';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Issue Medical Certificate</h1>
                <p class="page-subtitle">Patient: Emma Wilson (PAT-2026-001012) | Consultation Date: <?php echo date('d F Y'); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Certificate Type</h3>
            </div>
            <div class="flex gap-4">
                <label
                    style="padding: 16px; border: 2px solid #2563eb; border-radius: 4px; cursor: pointer; background: #eff6ff;">
                    <input type="radio" name="cert-type" checked style="margin-right: 8px;"> Sick Leave
                    Certificate
                </label>
                <label style="padding: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
                    <input type="radio" name="cert-type" style="margin-right: 8px;"> Fitness to Work
                </label>
                <label style="padding: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
                    <input type="radio" name="cert-type" style="margin-right: 8px;"> Fitness to Travel
                </label>
                <label style="padding: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
                    <input type="radio" name="cert-type" style="margin-right: 8px;"> General Medical
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Certificate Details</h3>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Period of Leave - From</label>
                    <input type="date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Period of Leave - To</label>
                    <input type="date" class="form-input" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                </div>
            </div>
            <p class="text-sm text-gray mb-4">Duration: 3 days</p>

            <div class="form-group">
                <label class="form-label">Condition/Reason</label>
                <div class="flex gap-4">
                    <label><input type="radio" name="condition"> Show diagnosis</label>
                    <label><input type="radio" name="condition" checked> "Medical condition"
                        (confidential)</label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Additional Comments</label>
                <textarea class="form-textarea"
                    rows="3">Patient is unfit for work due to a medical condition and requires rest. Review recommended if symptoms persist beyond specified period.</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Recommendations</label>
                <div>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Rest at
                        home</label>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox"> Light duties
                        only</label>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox"> Avoid
                        driving</label>
                    <label style="display: block;"><input type="checkbox"> Avoid heavy lifting</label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Employer/Institution Name (optional)</label>
                <input type="text" class="form-input" value="ABC Corporation Pty Ltd">
            </div>

            <div class="flex gap-2">
                <button class="btn btn-outline">Preview Certificate</button>
                <button class="btn btn-primary">Generate & Sign Certificate</button>
            </div>
        </div>

        <!-- Recent Certificates -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Recent Certificates</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cert No</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Period</th>
                            <th>Date Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CERT-2026-001198</td>
                            <td>John Smith</td>
                            <td>Sick Leave</td>
                            <td>24-25 Jan (2 days)</td>
                            <td>24 Jan 2026</td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td>CERT-2026-001150</td>
                            <td>Mary Johnson</td>
                            <td>Fitness to Work</td>
                            <td>N/A</td>
                            <td>20 Jan 2026</td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
