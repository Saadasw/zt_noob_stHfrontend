<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Write Prescription - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'prescriptions';

$message = '';
$error = '';

// Get Doctor Profile
$stmt = $pdo->prepare("SELECT id FROM doctor_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['id'];

// Get appointment context if provided
$apt_id = $_GET['apt_id'] ?? null;
$appointment = null;
$patient = null;

if ($apt_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, pp.id as patient_profile_id, pp.patient_id as patient_code, pp.allergies,
               u.name as patient_name
        FROM appointments a
        JOIN patient_profiles pp ON a.patient_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$apt_id]);
    $appointment = $stmt->fetch();
    $patient = $appointment;
}

// Fetch medicines for dropdown
$medicines = $pdo->query("SELECT * FROM medicines WHERE is_active = 1 ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $medicine_items = $_POST['medicines'] ?? [];
    $notes = trim($_POST['notes']);
    $valid_days = intval($_POST['valid_days'] ?? 30);

    if (empty($patient_id) || empty($medicine_items)) {
        $error = "Please select a patient and add at least one medicine.";
    } else {
        try {
            $pdo->beginTransaction();

            // Create prescription
            $rx_id = 'RX-' . bin2hex(random_bytes(4));
            $rx_no = 'RX-' . date('Y') . '-' . mt_rand(100000, 999999);
            $valid_until = date('Y-m-d', strtotime("+$valid_days days"));

            $stmt = $pdo->prepare("INSERT INTO prescriptions (id, prescription_no, patient_id, doctor_id, branch_id, valid_until, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$rx_id, $rx_no, $patient_id, $doctor_id, 'BR-MEL-01', $valid_until, $notes]);

            // Create prescription items
            foreach ($medicine_items as $item) {
                if (empty($item['name'])) continue;
                
                $item_id = 'RXI-' . bin2hex(random_bytes(4));
                $stmt = $pdo->prepare("INSERT INTO prescription_items (id, prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $item_id, $rx_id, 
                    $item['name'], 
                    $item['dosage'] ?? '', 
                    $item['frequency'] ?? '', 
                    $item['duration'] ?? '',
                    intval($item['quantity'] ?? 0),
                    $item['instructions'] ?? ''
                ]);
            }

            $pdo->commit();
            $message = "Prescription generated successfully! Prescription No: $rx_no";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch patients for dropdown (if no appointment context)
$patients_list = [];
if (!$patient) {
    $patients_list = $pdo->query("SELECT pp.id, pp.patient_id, u.name FROM patient_profiles pp JOIN users u ON pp.user_id = u.id ORDER BY u.name")->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Write Prescription</h1>
                <p class="page-subtitle">
                    <?php if ($patient): ?>
                        Patient: <?php echo h($patient['patient_name']); ?> (<?php echo h($patient['patient_code']); ?>)
                    <?php else: ?>
                        Select a patient to write prescription
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($patient && !empty($patient['allergies'])): ?>
        <div class="alert alert-danger">
            <span>⚠️</span>
            <div><strong>ALLERGY ALERT:</strong> <?php echo h($patient['allergies']); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($patient): ?>
                <input type="hidden" name="patient_id" value="<?php echo $patient['patient_profile_id']; ?>">
            <?php else: ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Select Patient</h3></div>
                <div class="form-group">
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient...</option>
                        <?php foreach ($patients_list as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo h($p['name']); ?> (<?php echo h($p['patient_id']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Medications</h3>
                </div>
                
                <div id="medicationList">
                    <div class="prescription-item" style="border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Medicine *</label>
                                <select name="medicines[0][name]" class="form-select" required>
                                    <option value="">Select Medicine...</option>
                                    <?php foreach ($medicines as $m): ?>
                                        <option value="<?php echo h($m['name']); ?>"><?php echo h($m['name']); ?> (<?php echo h($m['dosage_form']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dosage</label>
                                <select name="medicines[0][dosage]" class="form-select">
                                    <option>1 tablet(s)</option>
                                    <option>2 tablet(s)</option>
                                    <option>0.5 tablet(s)</option>
                                    <option>5ml</option>
                                    <option>10ml</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Frequency</label>
                                <select name="medicines[0][frequency]" class="form-select">
                                    <option>Once daily</option>
                                    <option>Twice daily</option>
                                    <option>Three times daily</option>
                                    <option>Four times daily</option>
                                    <option>As needed (PRN)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" name="medicines[0][duration]" class="form-input" value="7 days">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="medicines[0][quantity]" class="form-input" value="7">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Instructions</label>
                                <input type="text" name="medicines[0][instructions]" class="form-input" placeholder="e.g., Take after meals">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline" onclick="addMedicine()">+ Add Another Medicine</button>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Prescription Options</h3></div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Valid For (days)</label>
                        <input type="number" name="valid_days" class="form-input" value="30">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes for Pharmacist</label>
                    <textarea name="notes" class="form-textarea" rows="2" placeholder="Any special instructions..."></textarea>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="appointments.php" class="btn btn-outline">Cancel</a>
                <button type="submit" name="generate_prescription" value="1" class="btn btn-primary">💊 Generate Prescription</button>
            </div>
        </form>
    </main>
</div>

<script>
var medCount = 1;
function addMedicine() {
    var html = `
    <div class="prescription-item" style="border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Medicine *</label>
                <select name="medicines[${medCount}][name]" class="form-select">
                    <option value="">Select Medicine...</option>
                    <?php foreach ($medicines as $m): ?>
                        <option value="<?php echo h($m['name']); ?>"><?php echo h($m['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Dosage</label>
                <select name="medicines[${medCount}][dosage]" class="form-select">
                    <option>1 tablet(s)</option>
                    <option>2 tablet(s)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Frequency</label>
                <select name="medicines[${medCount}][frequency]" class="form-select">
                    <option>Once daily</option>
                    <option>Twice daily</option>
                    <option>Three times daily</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Duration</label>
                <input type="text" name="medicines[${medCount}][duration]" class="form-input" value="7 days">
            </div>
            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" name="medicines[${medCount}][quantity]" class="form-input" value="7">
            </div>
            <div class="form-group">
                <label class="form-label">Instructions</label>
                <input type="text" name="medicines[${medCount}][instructions]" class="form-input">
            </div>
        </div>
    </div>`;
    document.getElementById('medicationList').insertAdjacentHTML('beforeend', html);
    medCount++;
}
</script>

<?php include '../includes/footer.php'; ?>
