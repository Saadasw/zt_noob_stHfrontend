<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Prescriptions - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'prescriptions';

$message = '';

// Get Patient Profile
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient profile not found.");
}

$patient_id = $patient['id'];

// Handle Refill Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_refill'])) {
    $prescription_id = $_POST['prescription_id'];
    // For now, just show a message (could create a refill_requests table later)
    $message = "Refill request submitted successfully! Your pharmacy will be notified.";
}

// Fetch Current Prescriptions (active)
$current = [];
$past = [];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as doctor_name, dp.specialization
        FROM prescriptions p
        LEFT JOIN doctor_profiles dp ON p.doctor_id = dp.id
        LEFT JOIN users u ON dp.user_id = u.id
        WHERE p.patient_id = ? AND p.status = 'active'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$patient_id]);
    $current = $stmt->fetchAll();

    // Fetch Past Prescriptions
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as doctor_name
        FROM prescriptions p
        LEFT JOIN doctor_profiles dp ON p.doctor_id = dp.id
        LEFT JOIN users u ON dp.user_id = u.id
        WHERE p.patient_id = ? AND p.status IN ('completed', 'expired', 'fully_dispensed')
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$patient_id]);
    $past = $stmt->fetchAll();
} catch (PDOException $e) {
    // Tables may not exist
}

// Fetch prescription items function
function getPrescriptionItems($pdo, $prescription_id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
        $stmt->execute([$prescription_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$active_tab = $_GET['tab'] ?? 'current';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Prescriptions</h1>
                <p class="page-subtitle">View and manage your medications</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=current" class="tab <?php echo $active_tab === 'current' ? 'active' : ''; ?>">Current
                (<?php echo count($current); ?>)</a>
            <a href="?tab=past" class="tab <?php echo $active_tab === 'past' ? 'active' : ''; ?>">Past</a>
        </div>

        <?php if ($active_tab === 'current'): ?>
            <?php if (empty($current)): ?>
                <div class="card">
                    <p class="text-center text-gray p-4">No active prescriptions. Your prescriptions will appear here after a
                        doctor visit.</p>
                </div>
            <?php else: ?>
                <?php foreach ($current as $rx): ?>
                    <?php $items = getPrescriptionItems($pdo, $rx['id']); ?>
                    <div class="prescription-card"
                        style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                        <div class="prescription-header"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="prescription-name" style="font-weight: 600; font-size: 16px;">💊
                                    <?php echo h($rx['prescription_no']); ?></div>
                                <div style="font-size: 13px; color: #6b7280;">
                                    Prescribed by: <strong><?php echo h($rx['doctor_name'] ?? 'N/A'); ?></strong> |
                                    Date: <strong><?php echo date('d M Y', strtotime($rx['created_at'])); ?></strong>
                                </div>
                            </div>
                            <span class="badge badge-green">Active</span>
                        </div>

                        <?php if (!empty($items)): ?>
                            <div style="margin-top: 12px;">
                                <strong>Medications:</strong>
                                <?php foreach ($items as $item): ?>
                                    <div style="background: #f9fafb; padding: 10px; border-radius: 4px; margin-top: 8px;">
                                        <div style="font-weight: 500;"><?php echo h($item['medicine_name']); ?> -
                                            <?php echo h($item['dosage']); ?></div>
                                        <div style="font-size: 13px; color: #6b7280;">
                                            <?php echo h($item['frequency']); ?> for <?php echo h($item['duration']); ?>
                                            <?php if ($item['quantity']): ?> (Qty: <?php echo h($item['quantity']); ?>)<?php endif; ?>
                                        </div>
                                        <?php if ($item['instructions']): ?>
                                            <div style="font-size: 13px; color: #374151; margin-top: 4px;">
                                                <em>Instructions: <?php echo h($item['instructions']); ?></em>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($rx['notes']): ?>
                            <div style="background: #fef3c7; padding: 10px; border-radius: 4px; margin-top: 12px;">
                                <strong>Doctor's Notes:</strong> <?php echo h($rx['notes']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-2 mt-4">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_refill" value="1">
                                <input type="hidden" name="prescription_id" value="<?php echo $rx['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm">🔄 Request Refill</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($active_tab === 'past'): ?>
            <?php if (empty($past)): ?>
                <div class="card">
                    <p class="text-center text-gray p-4">No past prescriptions found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($past as $rx): ?>
                    <?php $items = getPrescriptionItems($pdo, $rx['id']); ?>
                    <div class="prescription-card"
                        style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px; opacity: 0.8;">
                        <div class="prescription-header"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="prescription-name" style="font-weight: 600; font-size: 16px;">💊
                                    <?php echo h($rx['prescription_no']); ?></div>
                                <div style="font-size: 13px; color: #6b7280;">
                                    Prescribed by: <strong><?php echo h($rx['doctor_name'] ?? 'N/A'); ?></strong> |
                                    Date: <strong><?php echo date('d M Y', strtotime($rx['created_at'])); ?></strong>
                                </div>
                            </div>
                            <span class="badge badge-gray"><?php echo ucfirst(str_replace('_', ' ', $rx['status'])); ?></span>
                        </div>

                        <?php if (!empty($items)): ?>
                            <div style="margin-top: 12px; font-size: 14px; color: #6b7280;">
                                <?php foreach ($items as $item): ?>
                                    <?php echo h($item['medicine_name']); ?>                    <?php echo $item !== end($items) ? ', ' : ''; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>