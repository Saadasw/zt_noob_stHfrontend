<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Lab Results - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'lab-results';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found.");
}

$patient_profile_id = $patient['id'];

// Viewing a specific result?
$viewing_result = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("
        SELECT lt.*, ltt.name as test_name, ltt.description as test_description, ltt.code as test_code,
               u.name as doctor_name, dp.specialization
        FROM lab_tests lt
        JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
        LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
        LEFT JOIN users u ON dp.user_id = u.id
        WHERE lt.id = ? AND lt.patient_id = ?
    ");
    $stmt->execute([$_GET['view'], $patient_profile_id]);
    $viewing_result = $stmt->fetch();
}

// Fetch Lab Tests
$stmt = $pdo->prepare("
    SELECT lt.*, ltt.name as test_name, ltt.description as test_description,
           u.name as doctor_name
    FROM lab_tests lt
    JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
    LEFT JOIN doctor_profiles dp ON lt.doctor_id = dp.id
    LEFT JOIN users u ON dp.user_id = u.id
    WHERE lt.patient_id = ?
    ORDER BY lt.created_at DESC
");
$stmt->execute([$patient_profile_id]);
$lab_tests = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Lab Results</h1>
                <p class="page-subtitle">View your laboratory test results</p>
            </div>
        </div>

        <?php if ($viewing_result): ?>
        <!-- Full Report View -->
        <div class="card" id="lab-report">
            <div class="card-header">
                <h3 class="card-title">📋 Lab Test Report</h3>
                <a href="lab-results.php" class="btn btn-outline btn-sm">← Back to List</a>
            </div>
            
            <div style="padding: 20px; border: 1px solid #e5e7eb; margin: 16px 0;">
                <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 16px;">
                    <h2 style="margin: 0; color: #2563eb;">🏥 St. George Hospital</h2>
                    <p style="margin: 4px 0; color: #6b7280;">Laboratory Report</p>
                </div>
                
                <div class="grid-2" style="gap: 16px; margin-bottom: 16px;">
                    <div>
                        <strong>Patient Name:</strong> <?php echo h($_SESSION['user_name']); ?><br>
                        <strong>Patient ID:</strong> <?php echo h($patient['patient_id']); ?><br>
                        <strong>Test Date:</strong> <?php echo date('d M Y', strtotime($viewing_result['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Test No:</strong> <?php echo h($viewing_result['test_no']); ?><br>
                        <strong>Test Code:</strong> <?php echo h($viewing_result['test_code'] ?? 'N/A'); ?><br>
                        <strong>Ordering Doctor:</strong> <?php echo h($viewing_result['doctor_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div style="background: #f0f9ff; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                    <h4 style="margin: 0 0 8px 0;">🔬 <?php echo h($viewing_result['test_name']); ?></h4>
                    <p style="margin: 0; color: #6b7280; font-size: 14px;"><?php echo h($viewing_result['test_description'] ?? ''); ?></p>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <h4 style="margin: 0 0 8px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">Results</h4>
                    <div style="white-space: pre-wrap; line-height: 1.6;"><?php echo nl2br(h($viewing_result['result'])); ?></div>
                </div>
                
                <?php if ($viewing_result['completed_at']): ?>
                <div style="font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 12px;">
                    <strong>Completed:</strong> <?php echo date('d M Y, h:i A', strtotime($viewing_result['completed_at'])); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-2">
                <button onclick="printReport()" class="btn btn-primary">🖨️ Print / Save as PDF</button>
                <a href="lab-results.php" class="btn btn-outline">← Back</a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Lab Tests List -->
        <?php if (count($lab_tests) > 0): ?>
            <?php foreach ($lab_tests as $test): ?>
            <div class="lab-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                <div class="lab-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div class="lab-title" style="display: flex; align-items: center; gap: 12px;">
                        <div class="lab-icon" style="font-size: 24px;">🔬</div>
                        <div>
                            <div class="lab-name" style="font-weight: 600; font-size: 16px;"><?php echo h($test['test_name']); ?></div>
                            <div class="lab-meta" style="font-size: 13px; color: #6b7280;">
                                Test Date: <?php echo date('d M Y', strtotime($test['created_at'])); ?> | 
                                Ordered by: <?php echo h($test['doctor_name'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $status_badges = [
                        'ordered' => '<span class="badge badge-yellow">⏳ Ordered</span>',
                        'sample_pending' => '<span class="badge badge-yellow">🧪 Sample Pending</span>',
                        'sample_collected' => '<span class="badge badge-blue">✔️ Sample Collected</span>',
                        'processing' => '<span class="badge badge-blue">⚙️ Processing</span>',
                        'completed' => '<span class="badge badge-green">✅ Results Ready</span>',
                        'reviewed' => '<span class="badge badge-green">✅ Results Ready</span>',
                    ];
                    echo $status_badges[$test['status']] ?? '<span class="badge">' . ucfirst($test['status']) . '</span>';
                    ?>
                </div>
                
                <?php if (($test['status'] === 'completed' || $test['status'] === 'reviewed') && !empty($test['result'])): ?>
                <div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-top: 12px;">
                    <strong>Result:</strong>
                    <p style="margin-top: 4px;"><?php echo nl2br(h(substr($test['result'], 0, 200))); ?><?php echo strlen($test['result']) > 200 ? '...' : ''; ?></p>
                </div>
                <?php endif; ?>

                <div class="flex gap-2 mt-4">
                    <?php if ($test['status'] === 'completed' || $test['status'] === 'reviewed'): ?>
                        <a href="?view=<?php echo $test['id']; ?>" class="btn btn-primary btn-sm">View Full Report</a>
                        <a href="?view=<?php echo $test['id']; ?>&print=1" class="btn btn-outline btn-sm" onclick="setTimeout(function(){ window.print(); }, 500);">Download PDF</a>
                    <?php else: ?>
                        <button class="btn btn-outline btn-sm" disabled>Results Pending...</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p class="text-center text-gray p-4">No lab tests found. Your test results will appear here once ordered by your doctor.</p>
            </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<script>
function printReport() {
    window.print();
}
</script>

<style>
@media print {
    .sidebar, .navbar, .page-header, .flex.gap-2, .btn {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    #lab-report {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>