<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Lab Orders - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'lab-orders';

$message = '';
$error = '';

// Get Doctor Profile
$stmt = $pdo->prepare("SELECT id FROM doctor_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['id'];

// Get appointment context if provided
$apt_id = $_GET['apt_id'] ?? null;
$patient = null;

if ($apt_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, pp.id as patient_profile_id, pp.patient_id as patient_code,
               u.name as patient_name
        FROM appointments a
        JOIN patient_profiles pp ON a.patient_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$apt_id]);
    $patient = $stmt->fetch();
}

// Fetch test types
$test_types = $pdo->query("SELECT * FROM lab_test_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_tests'])) {
    $patient_id = $_POST['patient_id'];
    $tests = $_POST['tests'] ?? [];
    $priority = $_POST['priority'] ?? 'routine';
    $notes = trim($_POST['notes']);

    if (empty($patient_id) || empty($tests)) {
        $error = "Please select a patient and at least one test.";
    } else {
        try {
            foreach ($tests as $test_type_id) {
                $test_id = 'LAB-' . bin2hex(random_bytes(4));
                $test_no = 'LT-' . date('Y') . '-' . mt_rand(100000, 999999);

                $stmt = $pdo->prepare("INSERT INTO lab_tests (id, test_no, patient_id, doctor_id, test_type_id, branch_id, priority, status, result) VALUES (?, ?, ?, ?, ?, 'BR-MEL-01', ?, 'ordered', ?)");
                $stmt->execute([$test_id, $test_no, $patient_id, $doctor_id, $test_type_id, $priority, $notes]);
            }
            $message = count($tests) . " lab test(s) ordered successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch patients for dropdown
$patients_list = $pdo->query("SELECT pp.id, pp.patient_id, u.name FROM patient_profiles pp JOIN users u ON pp.user_id = u.id ORDER BY u.name")->fetchAll();

// Fetch recent orders by this doctor
$recent_orders = $pdo->prepare("
    SELECT lt.*, ltt.name as test_name, u.name as patient_name, pp.patient_id as patient_code
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    JOIN patient_profiles pp ON lt.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE lt.doctor_id = ?
    ORDER BY lt.created_at DESC
    LIMIT 20
");
$recent_orders->execute([$doctor_id]);
$orders = $recent_orders->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Lab Orders</h1>
                <p class="page-subtitle">Order lab tests and view results</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('order')">Order New Test</button>
            <button class="tab" onclick="switchTab('myorders')">My Orders</button>
        </div>

        <!-- Tab 1: Order New Test -->
        <div id="tab-order" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Order Lab Tests</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="order_tests" value="1">

                    <?php if ($patient): ?>
                        <input type="hidden" name="patient_id" value="<?php echo $patient['patient_profile_id']; ?>">
                        <p><strong>Patient:</strong> <?php echo h($patient['patient_name']); ?>
                            (<?php echo h($patient['patient_code']); ?>)</p>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Select Patient...</option>
                                <?php foreach ($patients_list as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['name']); ?>
                                        (<?php echo h($p['patient_id']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="section-header">SELECT TESTS</div>
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 8px; margin-bottom: 16px;">
                        <?php foreach ($test_types as $tt): ?>
                            <label
                                style="display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer;">
                                <input type="checkbox" name="tests[]" value="<?php echo $tt['id']; ?>">
                                <div>
                                    <div style="font-weight: 500;"><?php echo h($tt['name']); ?></div>
                                    <div class="text-sm text-gray">$<?php echo number_format($tt['price'], 2); ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT (Emergency)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Clinical Notes</label>
                        <textarea name="notes" class="form-textarea" rows="2"
                            placeholder="Reason for tests, clinical suspicion..."></textarea>
                    </div>

                    <div class="flex gap-2">
                        <a href="appointments.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">🔬 Order Tests</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 2: My Orders -->
        <div id="tab-myorders" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Lab Orders</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Test No</th>
                                <th>Patient</th>
                                <th>Test</th>
                                <th>Status</th>
                                <th>Ordered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><strong><?php echo h($o['test_no']); ?></strong></td>
                                    <td><?php echo h($o['patient_name']); ?></td>
                                    <td><?php echo h($o['test_name']); ?></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'ordered' => '<span class="badge badge-yellow">Ordered</span>',
                                            'sample_pending' => '<span class="badge badge-yellow">Sample Pending</span>',
                                            'sample_collected' => '<span class="badge badge-blue">Collected</span>',
                                            'processing' => '<span class="badge badge-blue">Processing</span>',
                                            'completed' => '<span class="badge badge-green">Completed</span>',
                                        ];
                                        echo $status_badges[$o['status']] ?? $o['status'];
                                        ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                                    <td>
                                        <?php if ($o['status'] === 'completed'): ?>
                                            <button class="btn btn-sm btn-primary">View Result</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline">Track</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-gray">No lab orders yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    function switchTab(tabName) {
        // Hide all tab contents
        document.getElementById('tab-order').style.display = 'none';
        document.getElementById('tab-myorders').style.display = 'none';

        // Remove active class from all tabs
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

        // Show selected tab and set active
        if (tabName === 'order') {
            document.getElementById('tab-order').style.display = 'block';
            document.querySelectorAll('.tab')[0].classList.add('active');
        } else {
            document.getElementById('tab-myorders').style.display = 'block';
            document.querySelectorAll('.tab')[1].classList.add('active');
        }
    }
</script>

<?php include '../includes/footer.php'; ?>