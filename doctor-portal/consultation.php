<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Consultation - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'consultation';

$message = '';
$error = '';

// Get appointment ID from URL
$apt_id = $_GET['apt_id'] ?? null;
$view_only = isset($_GET['view']);

if (!$apt_id) {
    header("Location: appointments.php");
    exit();
}

// Fetch Appointment and Patient Data
$stmt = $pdo->prepare("
    SELECT a.*, 
           pp.id as patient_profile_id, pp.patient_id as patient_code, pp.date_of_birth, pp.gender, pp.blood_group, pp.medical_history, pp.allergies,
           u_pat.name as patient_name, u_pat.email as patient_email, u_pat.phone as patient_phone,
           dp.id as doctor_profile_id, dp.specialization,
           u_doc.name as doctor_name
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE a.id = ?
");
$stmt->execute([$apt_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    die("Appointment not found.");
}

// Check for existing medical record for this appointment
$stmt = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
$stmt->execute([$apt_id]);
$existing_record = $stmt->fetch();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_consultation'])) {
    $chief_complaint = trim($_POST['chief_complaint']);
    $symptoms = trim($_POST['symptoms']);
    $diagnosis = trim($_POST['diagnosis']);
    $treatment_plan = trim($_POST['treatment_plan']);
    $notes = trim($_POST['notes']);
    $action = $_POST['action']; // 'draft' or 'complete'

    if (empty($chief_complaint) || empty($diagnosis)) {
        $error = "Chief Complaint and Diagnosis are required.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($existing_record) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE medical_records SET chief_complaint = ?, symptoms = ?, diagnosis = ?, treatment_plan = ?, notes = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $status = $action === 'complete' ? 'finalized' : 'draft';
                $stmt->execute([$chief_complaint, $symptoms, $diagnosis, $treatment_plan, $notes, $status, $existing_record['id']]);
            } else {
                // Create new medical record
                $record_id = 'REC-' . bin2hex(random_bytes(4));
                $record_no = 'MR-' . date('Y') . '-' . mt_rand(100000, 999999);
                $status = $action === 'complete' ? 'finalized' : 'draft';

                $stmt = $pdo->prepare("INSERT INTO medical_records (id, record_no, patient_id, doctor_id, appointment_id, branch_id, chief_complaint, symptoms, diagnosis, treatment_plan, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $record_id, $record_no, 
                    $appointment['patient_profile_id'], 
                    $appointment['doctor_profile_id'], 
                    $apt_id, 
                    $appointment['branch_id'],
                    $chief_complaint, $symptoms, $diagnosis, $treatment_plan, $notes, $status
                ]);
            }

            // Update appointment status if completing
            if ($action === 'complete') {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
                $stmt->execute([$apt_id]);
            } else {
                // Mark as in_progress if just saving draft
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'in_progress' WHERE id = ? AND status IN ('checked_in', 'scheduled', 'confirmed')");
                $stmt->execute([$apt_id]);
            }

            $pdo->commit();

            if ($action === 'complete') {
                $message = "Consultation completed successfully!";
                header("Location: appointments.php?msg=completed");
                exit();
            } else {
                $message = "Draft saved.";
            }

            // Refresh record data
            $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
            $stmt->execute([$apt_id]);
            $existing_record = $stmt->fetch();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error saving: " . $e->getMessage();
        }
    }
}

// Calculate age
$dob = new DateTime($appointment['date_of_birth']);
$now = new DateTime();
$age = $dob->diff($now)->y;

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Consultation</h1>
                <p class="page-subtitle"><?php echo h($appointment['appointment_no']); ?> | <?php echo h($appointment['patient_name']); ?></p>
            </div>
            <?php if (!$view_only): ?>
            <div class="flex gap-2">
                <button form="consultationForm" name="action" value="draft" class="btn btn-outline">💾 Save Draft</button>
                <button form="consultationForm" name="action" value="complete" class="btn btn-success">✅ Complete Consultation</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Patient Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Patient Information</h3>
            </div>
            <div class="grid-2">
                <div>
                    <div class="profile-item"><label>Name</label><p><?php echo h($appointment['patient_name']); ?></p></div>
                    <div class="profile-item"><label>Age / Gender</label><p><?php echo $age; ?> years | <?php echo h($appointment['gender']); ?></p></div>
                    <div class="profile-item"><label>Blood Group</label><p><?php echo h($appointment['blood_group']); ?></p></div>
                </div>
                <div>
                    <div class="profile-item"><label>Patient ID</label><p><?php echo h($appointment['patient_code']); ?></p></div>
                    <div class="profile-item"><label>Phone</label><p><?php echo h($appointment['patient_phone']); ?></p></div>
                    <div class="profile-item"><label>Email</label><p><?php echo h($appointment['patient_email']); ?></p></div>
                </div>
            </div>
            <?php if (!empty($appointment['allergies'])): ?>
            <div class="alert alert-danger mt-4">
                <span>⚠️</span>
                <div><strong>ALLERGIES:</strong> <?php echo h($appointment['allergies']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($appointment['medical_history'])): ?>
            <div class="alert alert-info">
                <span>📋</span>
                <div><strong>MEDICAL HISTORY:</strong> <?php echo h($appointment['medical_history']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Consultation Notes -->
        <form method="POST" id="consultationForm">
            <input type="hidden" name="save_consultation" value="1">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Consultation Notes</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Chief Complaint *</label>
                    <textarea name="chief_complaint" class="form-textarea" rows="3" required <?php echo $view_only ? 'readonly' : ''; ?>><?php echo h($existing_record['chief_complaint'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">History / Symptoms</label>
                    <textarea name="symptoms" class="form-textarea" rows="3" <?php echo $view_only ? 'readonly' : ''; ?>><?php echo h($existing_record['symptoms'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Diagnosis *</label>
                    <textarea name="diagnosis" class="form-textarea" rows="2" required <?php echo $view_only ? 'readonly' : ''; ?>><?php echo h($existing_record['diagnosis'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Treatment Plan</label>
                    <textarea name="treatment_plan" class="form-textarea" rows="4" <?php echo $view_only ? 'readonly' : ''; ?>><?php echo h($existing_record['treatment_plan'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="notes" class="form-textarea" rows="2" <?php echo $view_only ? 'readonly' : ''; ?>><?php echo h($existing_record['notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <?php if (!$view_only): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="quick-actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="prescriptions.php?apt_id=<?php echo $apt_id; ?>" class="btn btn-outline">💊 Write Prescription</a>
                    <a href="lab-orders.php?apt_id=<?php echo $apt_id; ?>" class="btn btn-outline">🔬 Order Lab Test</a>
                    <a href="appointments.php" class="btn btn-outline">⬅️ Back to Queue</a>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($view_only): ?>
        <div class="card">
            <a href="appointments.php" class="btn btn-primary">⬅️ Back to Appointments</a>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
