<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require '../includes/billing_helper.php';
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
    $staff_branch_id = $_SESSION['staff_branch_id'] ?? 'BR-MEL-01';

    try {
        // First check if prescription is already dispensed (prevent double submit)
        $stmt = $pdo->prepare("SELECT status FROM prescriptions WHERE id = ?");
        $stmt->execute([$rx_id]);
        $rx_status = $stmt->fetchColumn();

        if ($rx_status !== 'active') {
            // Already dispensed, redirect to prevent double processing
            $_SESSION['pharmacy_message'] = "This prescription was already processed.";
            header("Location: pharmacy.php");
            exit;
        }

        $pdo->beginTransaction();

        // Get all prescription items
        $stmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
        $stmt->execute([$rx_id]);
        $items = $stmt->fetchAll();

        $insufficient_stock = [];

        // First pass: validate ALL items have sufficient stock
        foreach ($items as $item) {
            $qty_needed = $item['quantity'] ?? 1;

            // Try to find inventory by medicine_id first, then by name
            if ($item['medicine_id']) {
                $stmt = $pdo->prepare("
                    SELECT i.*, m.name as medicine_name 
                    FROM inventory i 
                    JOIN medicines m ON i.medicine_id = m.id 
                    WHERE i.medicine_id = ? AND i.branch_id = ?
                ");
                $stmt->execute([$item['medicine_id'], $staff_branch_id]);
            } else {
                // Fallback: match by medicine name
                $stmt = $pdo->prepare("
                    SELECT i.*, m.name as medicine_name 
                    FROM inventory i 
                    JOIN medicines m ON i.medicine_id = m.id 
                    WHERE m.name LIKE ? AND i.branch_id = ?
                ");
                $stmt->execute(['%' . $item['medicine_name'] . '%', $staff_branch_id]);
            }
            $inv = $stmt->fetch();

            if (!$inv) {
                $insufficient_stock[] = $item['medicine_name'] . " (not in inventory)";
            } elseif ($inv['quantity'] < $qty_needed) {
                $insufficient_stock[] = $item['medicine_name'] . " (need $qty_needed, have {$inv['quantity']})";
            }
        }

        // If any item has insufficient stock, fail the entire dispense
        if (!empty($insufficient_stock)) {
            $pdo->rollBack();
            $_SESSION['pharmacy_error'] = "Insufficient stock for: " . implode(", ", $insufficient_stock);
            header("Location: pharmacy.php");
            exit;
        } else {
            // Second pass: deduct inventory for ALL items
            foreach ($items as $item) {
                $qty_needed = $item['quantity'] ?? 1;

                if ($item['medicine_id']) {
                    $stmt = $pdo->prepare("
                        UPDATE inventory 
                        SET quantity = quantity - ?, last_updated = NOW() 
                        WHERE medicine_id = ? AND branch_id = ?
                    ");
                    $stmt->execute([$qty_needed, $item['medicine_id'], $staff_branch_id]);
                } else {
                    // Fallback: match by medicine name
                    $stmt = $pdo->prepare("
                        UPDATE inventory i
                        JOIN medicines m ON i.medicine_id = m.id
                        SET i.quantity = i.quantity - ?, i.last_updated = NOW()
                        WHERE m.name LIKE ? AND i.branch_id = ?
                    ");
                    $stmt->execute([$qty_needed, '%' . $item['medicine_name'] . '%', $staff_branch_id]);
                }
            }

            // Update prescription status
            $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'fully_dispensed' WHERE id = ?");
            $stmt->execute([$rx_id]);

            // Update prescription items status
            $stmt = $pdo->prepare("UPDATE prescription_items SET dispense_status = 'complete' WHERE prescription_id = ?");
            $stmt->execute([$rx_id]);

            // Auto-billing: Add medicines to patient bill
            $total_billed = 0;
            $bill_no = '';

            if (!isPrescriptionBilled($pdo, $rx_id)) {
                // Get prescription details for patient_id
                $stmt = $pdo->prepare("SELECT patient_id FROM prescriptions WHERE id = ?");
                $stmt->execute([$rx_id]);
                $rx = $stmt->fetch();

                if ($rx) {
                    // Get or create pending bill for patient
                    $bill = getOrCreatePendingBill($pdo, $rx['patient_id'], $staff_branch_id);
                    $bill_no = $bill['bill_no'];
                    $first_bill_item_id = null;

                    // Add each medicine as a bill item
                    foreach ($items as $item) {
                        $qty = $item['quantity'] ?? 1;

                        // Get medicine price
                        $unit_price = 0;
                        if ($item['medicine_id']) {
                            $stmt = $pdo->prepare("SELECT unit_price FROM medicines WHERE id = ?");
                            $stmt->execute([$item['medicine_id']]);
                            $unit_price = $stmt->fetchColumn() ?: 0;
                        }

                        if ($unit_price > 0) {
                            $bill_item_id = addBillItem(
                                $pdo,
                                $bill['id'],
                                'Medicine: ' . $item['medicine_name'],
                                'medicine',
                                $qty,
                                $unit_price
                            );
                            $total_billed += ($qty * $unit_price);
                            if (!$first_bill_item_id)
                                $first_bill_item_id = $bill_item_id;
                        }
                    }

                    // Mark prescription as billed (store first bill_item_id)
                    if ($first_bill_item_id) {
                        markPrescriptionBilled($pdo, $rx_id, $first_bill_item_id);
                    }
                }
            }

            $pdo->commit();

            // Redirect to prevent double submission (PRG pattern)
            if ($total_billed > 0) {
                $_SESSION['pharmacy_message'] = "Prescription dispensed! Billed $" . number_format($total_billed, 2) . " to Bill #" . $bill_no;
            } else {
                $_SESSION['pharmacy_message'] = "Prescription dispensed successfully! Inventory updated.";
            }
            header("Location: pharmacy.php");
            exit;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['pharmacy_error'] = "Error: " . $e->getMessage();
        header("Location: pharmacy.php");
        exit;
    }
}

// Get flash messages from session
if (isset($_SESSION['pharmacy_message'])) {
    $message = $_SESSION['pharmacy_message'];
    unset($_SESSION['pharmacy_message']);
}
if (isset($_SESSION['pharmacy_error'])) {
    $error = $_SESSION['pharmacy_error'];
    unset($_SESSION['pharmacy_error']);
}

// Fetch inventory for this staff's branch
$staff_branch_id = $_SESSION['staff_branch_id'] ?? 'BR-MEL-01';
$stmt = $pdo->prepare("
    SELECT i.*, m.name, m.code, m.dosage_form, m.strength, m.unit_price
    FROM inventory i
    JOIN medicines m ON i.medicine_id = m.id
    WHERE i.branch_id = ?
    ORDER BY m.name
");
$stmt->execute([$staff_branch_id]);
$inventory = $stmt->fetchAll();

// Dispensed history with date filter
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT p.*, u.name as patient_name, pp.patient_id as patient_code,
           ud.name as doctor_name
    FROM prescriptions p
    JOIN patient_profiles pp ON p.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    JOIN doctor_profiles dp ON p.doctor_id = dp.id
    JOIN users ud ON dp.user_id = ud.id
    WHERE p.status = 'fully_dispensed'
    AND DATE(p.updated_at) BETWEEN ? AND ?
    ORDER BY p.updated_at DESC
    LIMIT 50
");
$stmt->execute([$date_from, $date_to]);
$dispensed = $stmt->fetchAll();

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

        <?php $active_tab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'pending'; ?>
        <div class="tabs">
            <button class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>"
                onclick="switchTab('pending')">Pending Prescriptions
                (<?php echo count($pending); ?>)</button>
            <button class="tab <?php echo $active_tab === 'inventory' ? 'active' : ''; ?>"
                onclick="switchTab('inventory')">Inventory</button>
            <button class="tab <?php echo $active_tab === 'history' ? 'active' : ''; ?>"
                onclick="switchTab('history')">Dispensed History</button>
        </div>

        <div id="tab-pending" class="tab-content"
            style="<?php echo $active_tab !== 'pending' ? 'display: none;' : ''; ?>">
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
                                    <?php echo $item['quantity']; ?>)
                                </li>
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

        <div id="tab-inventory" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Medicine Inventory</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Medicine</th>
                                <th>Form / Strength</th>
                                <th>Qty</th>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th>Unit Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $m): ?>
                                <?php
                                $is_low = $m['quantity'] <= $m['reorder_level'];
                                $is_expired = $m['expiry_date'] && strtotime($m['expiry_date']) < time();
                                $is_expiring = $m['expiry_date'] && strtotime($m['expiry_date']) < strtotime('+3 months');
                                ?>
                                <tr>
                                    <td><?php echo h($m['code']); ?></td>
                                    <td><?php echo h($m['name']); ?></td>
                                    <td><?php echo h($m['dosage_form']); ?> / <?php echo h($m['strength']); ?></td>
                                    <td>
                                        <?php if ($is_low): ?>
                                            <span class="badge badge-red"><?php echo $m['quantity']; ?> (Low)</span>
                                        <?php else: ?>
                                            <span class="badge badge-green"><?php echo $m['quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo h($m['batch_number']); ?></td>
                                    <td>
                                        <?php if ($is_expired): ?>
                                            <span class="badge badge-red">Expired</span>
                                        <?php elseif ($is_expiring): ?>
                                            <span
                                                class="badge badge-yellow"><?php echo date('M Y', strtotime($m['expiry_date'])); ?></span>
                                        <?php else: ?>
                                            <?php echo $m['expiry_date'] ? date('M Y', strtotime($m['expiry_date'])) : 'N/A'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($m['unit_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inventory)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-gray">No medicines in inventory for this branch.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-history" class="tab-content"
            style="<?php echo $active_tab !== 'history' ? 'display: none;' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📋 Dispensed History</h3>
                </div>
                <form method="GET" style="padding: 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <input type="hidden" name="tab" value="history">
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label class="form-label">From</label>
                            <input type="date" name="date_from" class="form-input" value="<?php echo h($date_from); ?>">
                        </div>
                        <div>
                            <label class="form-label">To</label>
                            <input type="date" name="date_to" class="form-input" value="<?php echo h($date_to); ?>">
                        </div>
                        <div style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        </div>
                    </div>
                </form>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Prescription #</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Dispensed Date</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispensed as $rx): ?>
                                <?php
                                $items_stmt = $pdo->prepare("SELECT medicine_name, quantity FROM prescription_items WHERE prescription_id = ?");
                                $items_stmt->execute([$rx['id']]);
                                $rx_items = $items_stmt->fetchAll();
                                $items_str = implode(', ', array_map(function ($i) {
                                    return $i['medicine_name'] . ' (' . $i['quantity'] . ')';
                                }, $rx_items));
                                ?>
                                <tr>
                                    <td><strong><?php echo h($rx['prescription_no']); ?></strong></td>
                                    <td><?php echo h($rx['patient_name']); ?></td>
                                    <td>Dr. <?php echo h($rx['doctor_name']); ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($rx['updated_at'])); ?></td>
                                    <td class="text-sm"><?php echo h($items_str); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dispensed)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray">No dispensed prescriptions in this date
                                        range.</td>
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
    function switchTab(tab) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(function (c) {
            c.style.display = 'none';
        });
        // Remove active from all tabs
        document.querySelectorAll('.tab').forEach(function (t) {
            t.classList.remove('active');
        });
        // Show selected tab content
        document.getElementById('tab-' + tab).style.display = 'block';
        // Mark clicked tab as active
        event.target.classList.add('active');
    }
</script>

<?php include '../includes/footer.php'; ?>