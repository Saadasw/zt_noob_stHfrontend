<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Write Prescription - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'prescriptions';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Write Prescription</h1>
                <p class="page-subtitle">Patient: Emma Wilson (PAT-2026-001012) | Consultation Date: <?php echo date('d F Y'); ?></p>
            </div>
        </div>

        <!-- Allergy Alert -->
        <div class="alert alert-danger">
            <span>⚠️</span>
            <div><strong>ALLERGY ALERT:</strong> Penicillin, Aspirin</div>
        </div>

        <!-- Add Medicines -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add Medications</h3>
            </div>
            <div class="form-group">
                <label class="form-label">Search Medicine</label>
                <input type="text" class="form-input" placeholder="🔍 Type medicine name...">
                <div style="margin-top: 8px; padding: 8px; background: #f9fafb; border-radius: 4px; font-size: 13px;">
                    <p style="font-weight: 500; margin-bottom: 4px;">Suggestions:</p>
                    <p style="padding: 4px; cursor: pointer;">• Propranolol 40mg Tablet</p>
                    <p style="padding: 4px; cursor: pointer;">• Propranolol 80mg Tablet</p>
                    <p style="padding: 4px; cursor: pointer;">• Propranolol 10mg Tablet</p>
                </div>
            </div>
        </div>

        <!-- Prescription Items -->
        <div class="section-header">PRESCRIPTION ITEMS</div>

        <div class="prescription-item">
            <div class="prescription-header">
                <div class="medicine-name">1. Propranolol 40mg Tablet</div>
                <button class="btn btn-sm btn-outline" style="color: #dc2626;">Remove</button>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Dosage</label>
                    <select class="form-select">
                        <option selected>1 tablet(s)</option>
                        <option>2 tablet(s)</option>
                        <option>0.5 tablet(s)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select class="form-select">
                        <option selected>Once daily</option>
                        <option>Twice daily</option>
                        <option>Three times daily</option>
                        <option>As needed (PRN)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (days)</label>
                    <input type="number" class="form-input" value="30">
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity (auto)</label>
                    <input type="number" class="form-input" value="30" readonly style="background: #f9fafb;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Time of Day</label>
                <div class="flex gap-4">
                    <label><input type="radio" name="time1"> Morning</label>
                    <label><input type="radio" name="time1"> Afternoon</label>
                    <label><input type="radio" name="time1" checked> Night</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Instructions</label>
                <input type="text" class="form-input" value="Take at bedtime. Do not stop suddenly.">
            </div>
            <div class="alert alert-success">
                <span>✅</span>
                <div>Interaction Check: No interactions found with current medications</div>
            </div>
        </div>

        <button class="btn btn-outline" style="margin-bottom: 16px;">+ Add Another Medicine</button>

        <!-- Templates -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Templates</h3>
                <select class="form-select" style="width: auto;">
                    <option>Load Template...</option>
                    <option>Migraine - Standard</option>
                    <option>Hypertension - Initial</option>
                    <option>Diabetes - Routine</option>
                    <option>Common Cold</option>
                </select>
            </div>
            <button class="btn btn-sm btn-outline">+ Save Current as Template</button>
        </div>

        <!-- Notes & Options -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Additional Notes for Pharmacist</h3>
            </div>
            <textarea class="form-textarea" rows="2">Please counsel patient on gradual dose adjustment if needed.</textarea>
            <div style="margin-top: 16px;">
                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Allow generic
                    substitution</label>
                <label style="display: block; margin-bottom: 8px;"><input type="checkbox"> Brand name
                    only</label>
                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" checked> Send to
                    patient portal</label>
                <label style="display: block;"><input type="checkbox" checked> Send to pharmacy</label>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn btn-outline">Cancel</button>
            <button class="btn btn-outline">Preview</button>
            <button class="btn btn-primary">Generate Prescription</button>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
