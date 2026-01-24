<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Lab Results Review - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'lab-results';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Lab Results - Pending Review</h1>
                <p class="page-subtitle">Review and release lab results to patients</p>
            </div>
        </div>

        <div class="alert alert-warning">
            <span>⚠️</span>
            <div>You have <strong>5 lab results</strong> pending review</div>
        </div>

        <!-- Pending Reviews -->
        <div class="patient-card" style="border-left: 4px solid #eab308;">
            <div class="patient-header">
                <div>
                    <div class="patient-name">🔬 Complete Blood Count (CBC)</div>
                    <div class="patient-meta">Patient: John Smith (PAT-2026-000123)</div>
                    <div class="patient-meta">Ordered: 23 Jan 2026 | Resulted: 25 Jan 2026</div>
                    <div class="text-sm" style="color: #854d0e; margin-top: 4px;">Note: Hemoglobin slightly low
                        (12.8 g/dL)</div>
                </div>
                <span class="badge badge-yellow">🟡 Abnormal</span>
            </div>
            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary btn-sm">Review Results</button>
                <a href="patients.php" class="btn btn-outline btn-sm">View Patient History</a>
            </div>
        </div>

        <div class="patient-card" style="border-left: 4px solid #dc2626;">
            <div class="patient-header">
                <div>
                    <div class="patient-name">🔬 Lipid Profile</div>
                    <div class="patient-meta">Patient: Mary Johnson (PAT-2026-000456)</div>
                    <div class="patient-meta">Ordered: 22 Jan 2026 | Resulted: 24 Jan 2026</div>
                    <div class="text-sm" style="color: #dc2626; margin-top: 4px;">Note: LDL significantly
                        elevated (185 mg/dL)</div>
                </div>
                <span class="badge badge-red">🔴 Critical</span>
            </div>
            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary btn-sm">Review Results</button>
                <a href="patients.php" class="btn btn-outline btn-sm">View Patient History</a>
                <button class="btn btn-outline btn-sm">📞 Call Patient</button>
            </div>
        </div>

        <div class="patient-card" style="border-left: 4px solid #22c55e;">
            <div class="patient-header">
                <div>
                    <div class="patient-name">🔬 HbA1c</div>
                    <div class="patient-meta">Patient: James Taylor (PAT-2026-001345)</div>
                    <div class="patient-meta">Ordered: 20 Jan 2026 | Resulted: 23 Jan 2026</div>
                    <div class="text-sm" style="color: #22c55e; margin-top: 4px;">Result: 5.8% (Well controlled)
                    </div>
                </div>
                <span class="badge badge-green">🟢 Normal</span>
            </div>
            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary btn-sm">Review Results</button>
                <a href="patients.php" class="btn btn-outline btn-sm">View Patient History</a>
            </div>
        </div>

        <!-- Lab Result Detail -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Lab Result Review</h3>
                <span class="badge badge-red">Critical Values</span>
            </div>
            <div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                <div class="grid-2">
                    <div class="profile-item"><label>Patient</label>
                        <p>Mary Johnson (PAT-2026-000456)</p>
                    </div>
                    <div class="profile-item"><label>Lab ID</label>
                        <p>LAB-2026-009012</p>
                    </div>
                    <div class="profile-item"><label>Test</label>
                        <p>Lipid Profile</p>
                    </div>
                    <div class="profile-item"><label>Collection</label>
                        <p>22 Jan 2026, 08:00 AM</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Result</th>
                            <th>Reference Range</th>
                            <th>Status</th>
                            <th>Previous</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Cholesterol</td>
                            <td><strong>265 mg/dL</strong></td>
                            <td>&lt; 200</td>
                            <td><span class="badge badge-red">🔴 High</span></td>
                            <td>242</td>
                        </tr>
                        <tr>
                            <td>LDL Cholesterol</td>
                            <td><strong>185 mg/dL</strong></td>
                            <td>&lt; 100</td>
                            <td><span class="badge badge-red">🔴 High</span></td>
                            <td>165</td>
                        </tr>
                        <tr>
                            <td>HDL Cholesterol</td>
                            <td><strong>42 mg/dL</strong></td>
                            <td>&gt; 60</td>
                            <td><span class="badge badge-yellow">🟡 Low</span></td>
                            <td>45</td>
                        </tr>
                        <tr>
                            <td>Triglycerides</td>
                            <td><strong>190 mg/dL</strong></td>
                            <td>&lt; 150</td>
                            <td><span class="badge badge-yellow">🟡 High</span></td>
                            <td>160</td>
                        </tr>
                        <tr>
                            <td>TC/HDL Ratio</td>
                            <td><strong>6.3</strong></td>
                            <td>&lt; 5</td>
                            <td><span class="badge badge-red">🔴 High</span></td>
                            <td>5.4</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-header">DOCTOR'S REVIEW</div>
            <div class="form-group">
                <label class="form-label">Review Notes</label>
                <textarea class="form-textarea"
                    rows="3">Significant worsening of lipid profile despite current medication. Will increase statin dose and recommend dietary consultation. Patient to follow up in 6 weeks for repeat lipid panel.</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Action Required</label>
                <div>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Schedule
                        follow-up appointment</label>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Modify
                        prescription</label>
                    <label style="display: block; margin-bottom: 8px;"><input type="checkbox"> Refer to
                        specialist</label>
                    <label style="display: block;"><input type="checkbox" checked> Contact patient</label>
                </div>
            </div>

            <div class="quick-actions">
                <a href="prescriptions.php" class="quick-action-btn">💊 Write Prescription</a>
                <button class="quick-action-btn">📅 Schedule Follow-up</button>
                <button class="quick-action-btn">📞 Contact Patient</button>
            </div>

            <div class="flex gap-2 mt-4">
                <button class="btn btn-outline">Save Review</button>
                <button class="btn btn-success">Mark as Reviewed & Release to Patient</button>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
