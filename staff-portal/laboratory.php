<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Laboratory - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'laboratory';

$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $test_id = $_POST['test_id'];
    $new_status = $_POST['new_status'];
    $result = trim($_POST['result'] ?? '');

    try {
        if ($new_status === 'completed' && !empty($result)) {
            $stmt = $pdo->prepare("UPDATE lab_tests SET status = ?, result = ?, completed_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $result, $test_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE lab_tests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $test_id]);
        }
        $message = "Test status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch pending tests
$pending = $pdo->query("
    SELECT lt.*, ltt.name as test_name, u.name as patient_name, pp.patient_id as patient_code,
           ud.name as doctor_name
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    JOIN patient_profiles pp ON lt.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
    LEFT JOIN users ud ON dp.user_id = ud.id
    WHERE lt.status IN ('ordered', 'sample_pending', 'sample_collected', 'processing')
    ORDER BY 
        CASE lt.priority WHEN 'stat' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END,
        lt.created_at ASC
")->fetchAll();

// Fetch completed (recent)
$completed = $pdo->query("
    SELECT lt.*, ltt.name as test_name, u.name as patient_name
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    JOIN patient_profiles pp ON lt.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE lt.status = 'completed'
    ORDER BY lt.completed_at DESC
    LIMIT 20
")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Laboratory</h1>
                <p class="page-subtitle">Process lab tests and enter results</p>
            </div>
            <div class="flex gap-2">
                <span class="badge badge-yellow">Pending: <?php echo count($pending); ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active">Pending Tests (<?php echo count($pending); ?>)</button>
            <button class="tab">Completed</button>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Pending Lab Tests</h3></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Test No</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Ordered By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $t): ?>
                        <tr>
                            <td>
                                <?php if ($t['priority'] === 'stat'): ?>
                                    <span class="badge badge-red">🚨 STAT</span>
                                <?php elseif ($t['priority'] === 'urgent'): ?>
                                    <span class="badge badge-yellow">⚡ Urgent</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Routine</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo h($t['test_no']); ?></strong></td>
                            <td><?php echo h($t['patient_name']); ?><br><span class="text-sm text-gray"><?php echo h($t['patient_code']); ?></span></td>
                            <td><?php echo h($t['test_name']); ?></td>
                            <td><?php echo h($t['doctor_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'ordered' => '<span class="badge badge-yellow">Ordered</span>',
                                    'sample_pending' => '<span class="badge badge-yellow">Sample Pending</span>',
                                    'sample_collected' => '<span class="badge badge-blue">Sample Collected</span>',
                                    'processing' => '<span class="badge badge-blue">Processing</span>',
                                ];
                                echo $status_badges[$t['status']] ?? $t['status'];
                                ?>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'ordered'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="test_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="new_status" value="sample_collected">
                                    <button type="submit" class="btn btn-sm btn-primary">🧪 Collect Sample</button>
                                </form>
                                <?php elseif ($t['status'] === 'sample_collected'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="test_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="new_status" value="processing">
                                    <button type="submit" class="btn btn-sm btn-primary">⚙️ Start Processing</button>
                                </form>
                                <?php elseif ($t['status'] === 'processing'): ?>
                                <button class="btn btn-sm btn-primary" onclick="showResultModal('<?php echo $t['id']; ?>', '<?php echo h($t['test_name']); ?>')">📝 Enter Result</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending)): ?>
                        <tr><td colspan="7" class="text-center text-gray">No pending tests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Result Entry Modal -->
        <div id="resultModal" class="card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="card-header">
                <h3 class="card-title">Enter Test Result</h3>
                <button class="btn btn-sm btn-outline" onclick="hideResultModal()">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="test_id" id="result_test_id">
                <input type="hidden" name="new_status" value="completed">
                <div class="form-group">
                    <label class="form-label">Test</label>
                    <input type="text" id="result_test_name" class="form-input" readonly style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label class="form-label">Result *</label>
                    <textarea name="result" class="form-textarea" rows="4" required placeholder="Enter test results, values, observations..."></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline" onclick="hideResultModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">✅ Complete & Release Result</button>
                </div>
            </form>
        </div>
        <div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;" onclick="hideResultModal()"></div>

    </main>
</div>

<script>
function showResultModal(testId, testName) {
    document.getElementById('result_test_id').value = testId;
    document.getElementById('result_test_name').value = testName;
    document.getElementById('resultModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function hideResultModal() {
    document.getElementById('resultModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
