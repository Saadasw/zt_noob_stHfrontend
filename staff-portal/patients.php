<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Patient Management - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'patients';

$message = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'register';

// --- HANDLE REGISTRATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_patient'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $ec_name = trim($_POST['ec_name']);
    $ec_rel = trim($_POST['ec_rel']);
    $ec_phone = trim($_POST['ec_phone']);
    $allergies = trim($_POST['allergies']);
    $conditions = trim($_POST['conditions']);

    if (empty($first_name) || empty($last_name) || empty($dob) || empty($gender) || empty($phone)) {
        $error = "Please fill in all required fields (*)";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create User Account
            $userId = 'USR-PAT-' . bin2hex(random_bytes(4));
            $name = $first_name . ' ' . $last_name;
            // Default password for new patients: birth year (e.g., 1990) or '123'
            $password = password_hash('123', PASSWORD_DEFAULT);

            // If email is empty, use a dummy one to satisfy unique constraint or allow null if schema permits (Schema says NOT NULL)
            // Schema users.email IS NOT NULL and UNIQUE. We must provide one.
            if (empty($email)) {
                $email = strtolower($first_name . '.' . $last_name . '.' . uniqid() . '@patient.hospital.com');
            }

            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, 'patient', ?, 1)");
            $stmt->execute([$userId, $name, $email, $password, $phone]);

            // 2. Create Patient Profile
            $profileId = 'PROF-' . bin2hex(random_bytes(4));
            $patientId = 'PAT-' . date('Y') . '-' . mt_rand(1000, 9999);

            $medical_history = "Allergies: $allergies\nConditions: $conditions";

            $stmt = $pdo->prepare("INSERT INTO patient_profiles (id, user_id, patient_id, date_of_birth, gender, blood_group, address, emergency_contact_name, emergency_contact_phone, medical_history) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$profileId, $userId, $patientId, $dob, $gender, $blood_group, $address, $ec_name, $ec_phone, $medical_history]);

            $pdo->commit();
            $message = "Patient registered successfully! Patient ID: $patientId";
            $active_tab = 'register'; // Stay on register tab to show success

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "Email or Phone already registered.";
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

// --- HANDLE SEARCH ---
$patients = [];
if ($active_tab === 'search') {
    $search = $_GET['search'] ?? '';
    if ($search) {
        $sql = "SELECT p.*, u.name, u.email, u.phone 
                FROM patient_profiles p 
                JOIN users u ON p.user_id = u.id 
                WHERE u.is_active = 1 AND (u.name LIKE ? OR p.patient_id LIKE ? OR u.phone LIKE ?)";
        $stmt = $pdo->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term]);
        $patients = $stmt->fetchAll();
    }
}

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Patient Management</h1>
                <p class="page-subtitle">Register new patients or search existing records</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=register" class="tab <?php echo $active_tab === 'register' ? 'active' : ''; ?>">Register
                New</a>
            <a href="?tab=search" class="tab <?php echo $active_tab === 'search' ? 'active' : ''; ?>">Search Patient</a>
        </div>

        <?php if ($active_tab === 'register'): ?>
            <!-- Tab 1: Register New Patient -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Register New Patient</h3>
                </div>

                <form method="POST">
                    <input type="hidden" name="register_patient" value="1">

                    <div class="section-header">PERSONAL INFORMATION</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-input" required placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-input" required placeholder="Enter last name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" name="dob" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-input" required placeholder="+61...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="Enter email address">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" rows="2" placeholder="Enter full address"></textarea>
                    </div>

                    <div class="section-header">EMERGENCY CONTACT</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="ec_name" class="form-input" placeholder="Emergency contact name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="ec_rel" class="form-input" placeholder="e.g. Spouse, Parent">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="ec_phone" class="form-input" placeholder="+61...">
                        </div>
                    </div>

                    <div class="section-header">MEDICAL INFORMATION</div>
                    <div class="form-group">
                        <label class="form-label">Known Allergies (comma separated)</label>
                        <input type="text" name="allergies" class="form-input" placeholder="e.g., Penicillin, Aspirin">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Existing Conditions (comma separated)</label>
                        <input type="text" name="conditions" class="form-input" placeholder="e.g., Diabetes, Hypertension">
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button type="reset" class="btn btn-outline">Clear Form</button>
                        <button type="submit" class="btn btn-primary">Register Patient</button>
                    </div>
                </form>
            </div>

        <?php elseif ($active_tab === 'search'): ?>
            <!-- Tab 2: Search Patient -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Search Patient</h3>
                </div>
                <form method="GET" class="form-group">
                    <input type="hidden" name="tab" value="search">
                    <div class="flex gap-2">
                        <input type="text" name="search" class="form-input"
                            placeholder="🔍 Enter patient name, ID, or phone..." style="flex: 1;"
                            value="<?php echo h($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>

                <div class="section-header">SEARCH RESULTS</div>

                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $p): ?>
                        <div class="patient-card">
                            <div class="patient-header">
                                <div>
                                    <div class="patient-name">👤 <?php echo h($p['name']); ?></div>
                                    <div class="patient-meta"><?php echo h($p['patient_id']); ?> | <?php echo h($p['phone']); ?>
                                    </div>
                                    <div class="patient-meta">DOB: <?php echo h($p['date_of_birth']); ?> |
                                        <?php echo h($p['gender']); ?> | Blood: <?php echo h($p['blood_group']); ?></div>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <a href="profile_view.php?id=<?php echo $p['user_id']; ?>" class="btn btn-sm btn-outline">View
                                    Profile</a>
                                <a href="appointments.php?patient_id=<?php echo $p['patient_id']; ?>"
                                    class="btn btn-sm btn-outline">Book Appointment</a>
                                <a href="billing.php?patient_id=<?php echo $p['patient_id']; ?>"
                                    class="btn btn-sm btn-outline">Create Bill</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray text-center p-4">No patients found. Try searching.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>