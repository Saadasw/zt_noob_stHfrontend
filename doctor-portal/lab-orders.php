<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Order Lab Tests - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'lab-orders';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Order Lab Tests</h1>
                <p class="page-subtitle">Patient: Emma Wilson (PAT-2026-001012) | Date: <?php echo date('d F Y'); ?></p>
            </div>
        </div>

        <!-- Search -->
        <div class="card">
            <div class="form-group">
                <label class="form-label">Search Test</label>
                <input type="text" class="form-input" placeholder="🔍 Type test name...">
            </div>
        </div>

        <!-- Common Panels -->
        <div class="section-header">COMMON TEST PANELS</div>
        <div class="grid-3">
            <label class="test-checkbox"><input type="checkbox"> Complete Blood Count (CBC)</label>
            <label class="test-checkbox selected"><input type="checkbox" checked> Lipid Profile</label>
            <label class="test-checkbox"><input type="checkbox"> Liver Function Test (LFT)</label>
            <label class="test-checkbox"><input type="checkbox"> Kidney Function Test (KFT)</label>
            <label class="test-checkbox selected"><input type="checkbox" checked> Thyroid Panel (TSH, T3,
                T4)</label>
            <label class="test-checkbox"><input type="checkbox"> HbA1c</label>
            <label class="test-checkbox"><input type="checkbox"> Blood Glucose (Fasting)</label>
            <label class="test-checkbox"><input type="checkbox"> Electrolytes</label>
            <label class="test-checkbox"><input type="checkbox"> Urinalysis</label>
        </div>

        <!-- Individual Tests -->
        <div class="section-header">INDIVIDUAL TESTS</div>
        <div class="grid-3">
            <label class="test-checkbox"><input type="checkbox"> ESR</label>
            <label class="test-checkbox"><input type="checkbox"> CRP</label>
            <label class="test-checkbox selected"><input type="checkbox" checked> Vitamin D</label>
            <label class="test-checkbox"><input type="checkbox"> Vitamin B12</label>
            <label class="test-checkbox"><input type="checkbox"> Iron Studies</label>
            <label class="test-checkbox"><input type="checkbox"> Ferritin</label>
        </div>

        <!-- Selected Tests -->
        <div class="section-header">SELECTED TESTS (3)</div>
        <div class="card">
            <div style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>1. Lipid Profile</strong>
                        <p class="text-sm text-gray">Includes: Total Cholesterol, LDL, HDL, Triglycerides</p>
                        <p class="text-sm text-gray">Sample: Blood (Fasting required)</p>
                    </div>
                    <button class="btn btn-sm btn-outline" style="color: #dc2626;">Remove</button>
                </div>
            </div>
            <div style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>2. Thyroid Panel (TSH, T3, T4)</strong>
                        <p class="text-sm text-gray">Sample: Blood</p>
                    </div>
                    <button class="btn btn-sm btn-outline" style="color: #dc2626;">Remove</button>
                </div>
            </div>
            <div style="padding: 12px 0;">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>3. Vitamin D (25-OH)</strong>
                        <p class="text-sm text-gray">Sample: Blood</p>
                    </div>
                    <button class="btn btn-sm btn-outline" style="color: #dc2626;">Remove</button>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Details</h3>
            </div>
            <div class="form-group">
                <label class="form-label">Priority</label>
                <div class="flex gap-4">
                    <label><input type="radio" name="priority"> Routine</label>
                    <label><input type="radio" name="priority" checked> Urgent</label>
                    <label><input type="radio" name="priority"> STAT</label>
                </div>
            </div>
            <div class="alert alert-warning">
                <span>⚠️</span>
                <div><strong>Fasting Required:</strong> Lipid Profile requires 10-12 hours fasting</div>
            </div>
            <div class="form-group">
                <label class="form-label">Preferred Lab</label>
                <select class="form-select">
                    <option selected>Melbourne CBD Pathology</option>
                    <option>Sydney CBD Pathology</option>
                    <option>Brisbane Pathology</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Clinical Notes for Lab</label>
                <textarea class="form-textarea"
                    rows="3">Patient with chronic migraines. Checking for secondary causes. Please check thyroid and vitamin D levels.</textarea>
            </div>
            <div style="margin-top: 16px;">
                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Send copy to
                    patient portal</label>
                <label style="display: block;"><input type="checkbox" checked> Notify me when results are
                    ready</label>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn btn-outline">Cancel</button>
            <button class="btn btn-primary">Submit Lab Order</button>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
