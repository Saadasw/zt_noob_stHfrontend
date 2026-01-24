<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Doctor Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'doctors';

$message = '';
$error = '';

// Fetch branches for dropdown
$branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1")->fetchAll();

// Handle Create Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_doctor'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license = trim($_POST['license_number']);
    $branch_id = $_POST['branch_id'];
    $fee = floatval($_POST['consultation_fee']);

    if (empty($name) || empty($email) || empty($license)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Create user account
            $userId = 'USR-DOC-' . bin2hex(random_bytes(4));
            $password = password_hash('123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, 'doctor', ?, 1)");
            $stmt->execute([$userId, $name, $email, $password, $phone]);

            // Create doctor profile
            $docId = 'DOC-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$docId, $userId, $branch_id, $specialization, $license, $fee]);

            $pdo->commit();
            $message = "Doctor created successfully! Login: $email / 123";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Email or License number already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch doctors
$doctors = $pdo->query("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active, b.name as branch_name
    FROM doctor_profiles dp
    JOIN users u ON dp.user_id = u.id
    LEFT JOIN branches b ON dp.branch_id = b.id
    WHERE u.is_active = 1
    ORDER BY u.name
")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Doctor Management</h1>
                <p class="page-subtitle">Manage doctors and their profiles</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addDoctorModal')">+ Add New Doctor</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Branch</th>
                            <th>License No</th>
                            <th>Consult Fee</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $d): ?>
                        <tr>
                            <td>
                                <strong><?php echo h($d['name']); ?></strong>
                                <div class="text-sm text-gray"><?php echo h($d['email']); ?></div>
                            </td>
                            <td><?php echo h($d['specialization']); ?></td>
                            <td><?php echo h($d['branch_name'] ?? 'N/A'); ?></td>
                            <td><?php echo h($d['license_number']); ?></td>
                            <td>$<?php echo number_format($d['consultation_fee'], 2); ?></td>
                            <td>
                                <?php echo $d['is_available'] ? '<span class="badge badge-green">Available</span>' : '<span class="badge badge-gray">Unavailable</span>'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <button class="btn btn-sm btn-outline">Schedule</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($doctors)): ?>
                        <tr><td colspan="7" class="text-center">No doctors found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Doctor Modal -->
        <div id="addDoctorModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Doctor</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addDoctorModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_doctor" value="1">
                
                <div class="section-header">PERSONAL INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="Dr. John Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required placeholder="doctor@hospital.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" placeholder="+61...">
                    </div>
                </div>

                <div class="section-header">PROFESSIONAL DETAILS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Specialization *</label>
                        <select name="specialization" class="form-select" required>
                            <option value="">Select...</option>
                            <option>General Medicine</option>
                            <option>Cardiology</option>
                            <option>Dermatology</option>
                            <option>Orthopedics</option>
                            <option>Pediatrics</option>
                            <option>Neurology</option>
                            <option>Psychiatry</option>
                            <option>Ophthalmology</option>
                            <option>ENT</option>
                            <option>Gastroenterology</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">License Number *</label>
                        <input type="text" name="license_number" class="form-input" required placeholder="MED-VIC-XXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo h($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultation Fee ($)</label>
                        <input type="number" step="0.01" name="consultation_fee" class="form-input" value="100.00">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addDoctorModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Doctor Account</button>
                </div>
            </form>
        </div>

    </main>
</div>

<script>
function toggleModal(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
