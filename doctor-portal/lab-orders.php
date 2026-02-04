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
    SELECT lt.*, ltt.name as test_name, ltt.code as test_code, u.name as patient_name, pp.patient_id as patient_code,
           u_doc.name as doctor_name
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    JOIN patient_profiles pp ON lt.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
    LEFT JOIN users u_doc ON dp.user_id = u_doc.id
    WHERE lt.doctor_id = ?
    ORDER BY lt.created_at DESC
    LIMIT 20
");
$recent_orders->execute([$doctor_id]);
$orders = $recent_orders->fetchAll();

// Handle viewing specific result
$view_test = null;
if (isset($_GET['view_result'])) {
    $stmt = $pdo->prepare("
        SELECT lt.*, ltt.name as test_name, ltt.code as test_code, ltt.category as test_category,
               u.name as patient_name, pp.patient_id as patient_code,
               u_doc.name as doctor_name, dp.specialization
        FROM lab_tests lt
        JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
        JOIN patient_profiles pp ON lt.patient_id = pp.id
        JOIN users u ON pp.user_id = u.id
        LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
        LEFT JOIN users u_doc ON dp.user_id = u_doc.id
        WHERE lt.id = ?
    ");
    $stmt->execute([$_GET['view_result']]);
    $view_test = $stmt->fetch();
}

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
                                <th>Priority</th>
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
                                        $priority_badges = [
                                            'routine' => '<span class="badge badge-gray">Routine</span>',
                                            'urgent' => '<span class="badge badge-yellow">Urgent</span>',
                                            'stat' => '<span class="badge badge-red">STAT</span>',
                                        ];
                                        echo $priority_badges[$o['priority']] ?? $o['priority'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'ordered' => '<span class="badge badge-yellow">Ordered</span>',
                                            'sample_pending' => '<span class="badge badge-yellow">Sample Pending</span>',
                                            'sample_collected' => '<span class="badge badge-blue">Collected</span>',
                                            'processing' => '<span class="badge badge-blue">Processing</span>',
                                            'completed' => '<span class="badge badge-green">Completed</span>',
                                            'reviewed' => '<span class="badge badge-green">Reviewed</span>',
                                        ];
                                        echo $status_badges[$o['status']] ?? $o['status'];
                                        ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                                    <td>
                                        <a href="?view_result=<?php echo $o['id']; ?>#result-modal"
                                            class="btn btn-sm btn-primary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-gray">No lab orders yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Lab Result Detail Modal -->
<?php if ($view_test): ?>
    <div id="result-modal" class="modal-overlay" style="display: flex;">
        <div class="modal-card card" style="max-width: 700px;">
            <div class="card-header">
                <h3 class="card-title">🔬 Lab Test Details</h3>
                <a href="lab-orders.php" class="btn btn-sm btn-outline">Close</a>
            </div>

            <!-- Test Info -->
            <div class="result-section">
                <div class="section-header">TEST INFORMATION</div>
                <div class="grid-2">
                    <div class="profile-item"><label>Test No</label>
                        <p><?php echo h($view_test['test_no']); ?></p>
                    </div>
                    <div class="profile-item"><label>Test Name</label>
                        <p><?php echo h($view_test['test_name']); ?></p>
                    </div>
                    <div class="profile-item"><label>Category</label>
                        <p><?php echo h($view_test['test_category'] ?? 'General'); ?></p>
                    </div>
                    <div class="profile-item"><label>Priority</label>
                        <p>
                            <?php
                            $priority_badges = [
                                'routine' => '<span class="badge badge-gray">Routine</span>',
                                'urgent' => '<span class="badge badge-yellow">Urgent</span>',
                                'stat' => '<span class="badge badge-red">STAT</span>',
                            ];
                            echo $priority_badges[$view_test['priority']] ?? $view_test['priority'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Patient Info -->
            <div class="result-section">
                <div class="section-header">PATIENT</div>
                <div class="grid-2">
                    <div class="profile-item"><label>Name</label>
                        <p><?php echo h($view_test['patient_name']); ?></p>
                    </div>
                    <div class="profile-item"><label>Patient ID</label>
                        <p><?php echo h($view_test['patient_code']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="result-section">
                <div class="section-header">STATUS</div>
                <div class="status-timeline">
                    <div
                        class="status-item <?php echo in_array($view_test['status'], ['ordered', 'sample_pending', 'sample_collected', 'processing', 'completed', 'reviewed']) ? 'done' : ''; ?>">
                        <span class="status-dot"></span>
                        <span>Ordered</span>
                        <small><?php echo date('d M Y, h:i A', strtotime($view_test['created_at'])); ?></small>
                    </div>
                    <div
                        class="status-item <?php echo in_array($view_test['status'], ['sample_collected', 'processing', 'completed', 'reviewed']) ? 'done' : ''; ?>">
                        <span class="status-dot"></span>
                        <span>Sample Collected</span>
                    </div>
                    <div
                        class="status-item <?php echo in_array($view_test['status'], ['processing', 'completed', 'reviewed']) ? 'done' : ''; ?>">
                        <span class="status-dot"></span>
                        <span>Processing</span>
                    </div>
                    <div
                        class="status-item <?php echo in_array($view_test['status'], ['completed', 'reviewed']) ? 'done' : ''; ?>">
                        <span class="status-dot"></span>
                        <span>Completed</span>
                        <?php if ($view_test['completed_at']): ?>
                            <small><?php echo date('d M Y, h:i A', strtotime($view_test['completed_at'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="status-item <?php echo $view_test['status'] === 'reviewed' ? 'done' : ''; ?>">
                        <span class="status-dot"></span>
                        <span>Reviewed</span>
                    </div>
                </div>
            </div>

            <!-- Result -->
            <div class="result-section">
                <div class="section-header">LAB RESULT</div>
                <?php if (in_array($view_test['status'], ['completed', 'reviewed']) && !empty($view_test['result'])): ?>
                    <div class="result-box">
                        <?php echo nl2br(h($view_test['result'])); ?>
                    </div>
                <?php elseif (in_array($view_test['status'], ['completed', 'reviewed'])): ?>
                    <div class="result-box text-gray">Result data not yet entered by lab.</div>
                <?php else: ?>
                    <div class="result-box text-gray">⏳ Test not yet completed. Current status:
                        <?php echo ucfirst($view_test['status']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-4">
                <a href="lab-orders.php" class="btn btn-outline">← Back to Orders</a>
                <?php if ($view_test['status'] === 'completed'): ?>
                    <a href="lab-results.php?view=<?php echo $view_test['id']; ?>" class="btn btn-primary">Review & Release to
                        Patient</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-card {
        max-width: 700px;
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        margin: 0;
    }

    .result-section {
        margin-bottom: 20px;
    }

    .result-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 16px;
        white-space: pre-wrap;
        font-family: inherit;
        line-height: 1.6;
    }

    .status-timeline {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #f3f4f6;
        border-radius: 4px;
        color: #6b7280;
    }

    .status-item.done {
        background: #dcfce7;
        color: #166534;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #9ca3af;
    }

    .status-item.done .status-dot {
        background: #22c55e;
    }

    .status-item small {
        font-size: 11px;
        color: inherit;
        opacity: 0.7;
    }

    .badge-gray {
        background: #f3f4f6;
        color: #6b7280;
    }

    .badge-yellow {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-red {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-blue {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-green {
        background: #dcfce7;
        color: #166534;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<script>
    function switchTab(tabName) {
        document.getElementById('tab-order').style.display = 'none';
        document.getElementById('tab-myorders').style.display = 'none';
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

        if (tabName === 'order') {
            document.getElementById('tab-order').style.display = 'block';
            document.querySelectorAll('.tab')[0].classList.add('active');
        } else {
            document.getElementById('tab-myorders').style.display = 'block';
            document.querySelectorAll('.tab')[1].classList.add('active');
        }
    }

    // Auto-show My Orders tab if viewing a result
    <?php if ($view_test): ?>
        switchTab('myorders');
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>