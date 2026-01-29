<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Lab Results Review - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'lab-results';

$message = '';
$error = '';

// Get Doctor Profile
$stmt = $pdo->prepare("SELECT id FROM doctor_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['id'] ?? null;

// Handle Review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_reviewed'])) {
    $test_id = $_POST['test_id'];
    $review_notes = trim($_POST['review_notes'] ?? '');

    try {
        // Try with reviewed_by column first, fall back to simpler update
        try {
            $stmt = $pdo->prepare("UPDATE lab_tests SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW(), result = CONCAT(COALESCE(result, ''), '\n\nDoctor Review: ', ?) WHERE id = ?");
            $stmt->execute([$doctor_id, $review_notes, $test_id]);
        } catch (PDOException $e) {
            // Fallback if reviewed_by column doesn't exist
            $stmt = $pdo->prepare("UPDATE lab_tests SET status = 'reviewed', result = CONCAT(COALESCE(result, ''), '\n\nDoctor Review: ', ?) WHERE id = ?");
            $stmt->execute([$review_notes, $test_id]);
        }
        $message = "Lab result marked as reviewed and released to patient.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch completed lab tests for this doctor that need review
$pending_results = [];
$selected_result = null;

try {
    $stmt = $pdo->prepare("
        SELECT lt.*, ltt.name as test_name, ltt.code as test_code,
               u.name as patient_name, pp.patient_id as patient_code
        FROM lab_tests lt
        JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
        JOIN patient_profiles pp ON lt.patient_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE lt.doctor_id = ? AND lt.status = 'completed'
        ORDER BY lt.created_at DESC
    ");
    $stmt->execute([$doctor_id]);
    $pending_results = $stmt->fetchAll();

    // If viewing a specific result
    if (isset($_GET['view'])) {
        $stmt = $pdo->prepare("
            SELECT lt.*, ltt.name as test_name, ltt.code as test_code,
                   u.name as patient_name, pp.patient_id as patient_code
            FROM lab_tests lt
            JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
            JOIN patient_profiles pp ON lt.patient_id = pp.id
            JOIN users u ON pp.user_id = u.id
            WHERE lt.id = ? AND lt.doctor_id = ?
        ");
        $stmt->execute([$_GET['view'], $doctor_id]);
        $selected_result = $stmt->fetch();
    }
} catch (PDOException $e) {
    // Table may not have all columns
}

// Count pending reviews
$pending_count = count($pending_results);

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

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($pending_count > 0): ?>
            <div class="alert alert-warning">
                <span>⚠️</span>
                <div>You have <strong><?php echo $pending_count; ?> lab result(s)</strong> pending review</div>
            </div>
        <?php endif; ?>

        <!-- Pending Reviews List -->
        <?php if (empty($pending_results)): ?>
            <div class="card">
                <p class="text-center text-gray" style="padding: 40px;">No lab results pending review.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pending_results as $r): ?>
                <div class="patient-card" style="border-left: 4px solid #eab308;">
                    <div class="patient-header">
                        <div>
                            <div class="patient-name">🔬 <?php echo h($r['test_name']); ?></div>
                            <div class="patient-meta">Patient: <?php echo h($r['patient_name']); ?>
                                (<?php echo h($r['patient_code']); ?>)</div>
                            <div class="patient-meta">Test No: <?php echo h($r['test_no']); ?> | Ordered:
                                <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                            </div>
                            <?php if ($r['result']): ?>
                                <div class="text-sm" style="color: #854d0e; margin-top: 4px;">Result:
                                    <?php echo h(substr($r['result'], 0, 100)); ?>
                                    <?php echo strlen($r['result']) > 100 ? '...' : ''; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-yellow">Pending Review</span>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <a href="?view=<?php echo $r['id']; ?>" class="btn btn-primary btn-sm">Review Results</a>
                        <a href="patients.php" class="btn btn-outline btn-sm">View Patient History</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Lab Result Detail (when viewing) -->
        <?php if ($selected_result): ?>
            <div class="card mt-4" id="review-section">
                <div class="card-header">
                    <h3 class="card-title">Lab Result Review</h3>
                    <span class="badge badge-yellow">Pending Review</span>
                </div>
                <div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                    <div class="grid-2">
                        <div class="profile-item"><label>Patient</label>
                            <p><?php echo h($selected_result['patient_name']); ?>
                                (<?php echo h($selected_result['patient_code']); ?>)</p>
                        </div>
                        <div class="profile-item"><label>Test No</label>
                            <p><?php echo h($selected_result['test_no']); ?></p>
                        </div>
                        <div class="profile-item"><label>Test</label>
                            <p><?php echo h($selected_result['test_name']); ?></p>
                        </div>
                        <div class="profile-item"><label>Ordered</label>
                            <p><?php echo date('d M Y, h:i A', strtotime($selected_result['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="section-header">LAB RESULT</div>
                <div
                    style="background: #fff; padding: 16px; border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 16px; white-space: pre-wrap;">
                    <?php echo h($selected_result['result'] ?: 'No result data entered yet.'); ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="mark_reviewed" value="1">
                    <input type="hidden" name="test_id" value="<?php echo $selected_result['id']; ?>">

                    <div class="section-header">DOCTOR'S REVIEW</div>
                    <div class="form-group">
                        <label class="form-label">Review Notes</label>
                        <textarea name="review_notes" class="form-textarea" rows="3"
                            placeholder="Enter your review notes, recommendations, follow-up instructions..."></textarea>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <a href="lab-results.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-success">✓ Mark as Reviewed & Release to Patient</button>
                    </div>
                </form>
            </div>

            <script>
                // Auto-scroll to review section
                document.getElementById('review-section').scrollIntoView({ behavior: 'smooth' });
            </script>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>