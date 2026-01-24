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
                    ];
                    echo $status_badges[$test['status']] ?? '<span class="badge">' . ucfirst($test['status']) . '</span>';
                    ?>
                </div>
                
                <?php if ($test['status'] === 'completed' && !empty($test['result'])): ?>
                <div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-top: 12px;">
                    <strong>Result:</strong>
                    <p style="margin-top: 4px;"><?php echo nl2br(h($test['result'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="flex gap-2 mt-4">
                    <?php if ($test['status'] === 'completed'): ?>
                        <button class="btn btn-primary btn-sm">View Full Report</button>
                        <button class="btn btn-outline btn-sm">Download PDF</button>
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

    </main>
</div>

<?php include '../includes/footer.php'; ?>
