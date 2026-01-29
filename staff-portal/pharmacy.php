<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Pharmacy - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'pharmacy';

$message = '';
$error = '';

// Fetch pending prescriptions
$pending = $pdo->query("
    SELECT p.*, u.name as patient_name, pp.patient_id as patient_code,
           ud.name as doctor_name
    FROM prescriptions p
    JOIN patient_profiles pp ON p.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    JOIN doctor_profiles dp ON p.doctor_id = dp.id
    JOIN users ud ON dp.user_id = ud.id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
")->fetchAll();

// Handle dispense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispense'])) {
    $rx_id = $_POST['prescription_id'];
    try {
        $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'fully_dispensed' WHERE id = ?");
        $stmt->execute([$rx_id]);

        $stmt = $pdo->prepare("UPDATE prescription_items SET dispense_status = 'complete' WHERE prescription_id = ?");
        $stmt->execute([$rx_id]);

        $message = "Prescription dispensed successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch medicines inventory
$inventory = $pdo->query("SELECT * FROM medicines WHERE is_active = 1 ORDER BY name LIMIT 20")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Pharmacy</h1>
                <p class="page-subtitle">Dispense medications and manage inventory</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('pending')">Pending Prescriptions
                (<?php echo count($pending); ?>)</button>
            <button class="tab" onclick="switchTab('inventory')">Inventory</button>
        </div>

        <div id="tab-pending" class="tab-content">
            <div class="card-header">
                <h3 class="card-title">Pending Prescriptions</h3>
            </div>

            <?php foreach ($pending as $rx): ?>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-weight: 600; font-size: 16px;">💊 <?php echo h($rx['prescription_no']); ?>
                            </div>
                            <div class="text-sm text-gray">
                                Patient: <?php echo h($rx['patient_name']); ?> (<?php echo h($rx['patient_code']); ?>)<br>
                                Prescribed by: <?php echo h($rx['doctor_name']); ?> |
                                Date: <?php echo date('d M Y', strtotime($rx['created_at'])); ?>
                            </div>
                        </div>
                        <span class="badge badge-yellow">Pending</span>
                    </div>

                    <?php
                    // Fetch items for this prescription
                    $items = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
                    $items->execute([$rx['id']]);
                    $rx_items = $items->fetchAll();
                    ?>

                    <div style="margin-top: 12px; background: #f9fafb; padding: 12px; border-radius: 4px;">
                        <strong>Medications:</strong>
                        <ul style="margin: 8px 0 0 20px;">
                            <?php foreach ($rx_items as $item): ?>
                                <li><?php echo h($item['medicine_name']); ?> - <?php echo h($item['dosage']); ?> -
                                    <?php echo h($item['frequency']); ?> x <?php echo h($item['duration']); ?> (Qty:
                                    <?php echo $item['quantity']; ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if (!empty($rx['notes'])): ?>
                        <div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-radius: 4px;">
                            <strong>Pharmacist Notes:</strong> <?php echo h($rx['notes']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex gap-2 mt-4">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="dispense" value="1">
                            <input type="hidden" name="prescription_id" value="<?php echo $rx['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary"
                                onclick="return confirm('Confirm dispensing this prescription?');">✅ Dispense All</button>
                        </form>
                        <button class="btn btn-sm btn-outline">Print Label</button>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($pending)): ?>
                <p class="text-center text-gray p-4">No pending prescriptions.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Medicine Inventory (Sample)</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Form</th>
                            <th>Strength</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $m): ?>
                            <tr>
                                <td><?php echo h($m['code']); ?></td>
                                <td><?php echo h($m['name']); ?></td>
                                <td><?php echo h($m['dosage_form']); ?></td>
                                <td><?php echo h($m['strength']); ?></td>
                                <td>$<?php echo number_format($m['unit_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>