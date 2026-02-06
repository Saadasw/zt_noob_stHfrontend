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

// Handle Add Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = intval($_POST['quantity']);
    $batch = trim($_POST['batch_number'] ?? '');
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $staff_branch_id = $_SESSION['staff_branch_id'] ?? 'BR-MEL-01';

    if ($quantity <= 0) {
        $_SESSION['pharmacy_error'] = "Quantity must be greater than 0.";
        header("Location: pharmacy.php?tab=inventory");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if inventory record already exists for this medicine+branch
        $stmt = $pdo->prepare("SELECT id, quantity FROM inventory WHERE medicine_id = ? AND branch_id = ?");
        $stmt->execute([$medicine_id, $staff_branch_id]);
        $existing = $stmt->fetch();

        $inventory_id = null;
        $qty_before = 0;

        if ($existing) {
            // Update existing
            $inventory_id = $existing['id'];
            $qty_before = $existing['quantity'];

            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, batch_number = ?, expiry_date = ?, last_updated = NOW() WHERE id = ?");
            $stmt->execute([$quantity, $batch, $expiry, $inventory_id]);
        } else {
            // Insert new
            $inventory_id = 'INV-' . strtoupper(bin2hex(random_bytes(6)));
            $stmt = $pdo->prepare("INSERT INTO inventory (id, medicine_id, branch_id, quantity, batch_number, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$inventory_id, $medicine_id, $staff_branch_id, $quantity, $batch, $expiry]);
        }

        // Log movement
        $move_id = 'MOV-' . strtoupper(bin2hex(random_bytes(8)));
        $stmt = $pdo->prepare("INSERT INTO stock_movements (id, inventory_id, medicine_id, branch_id, movement_type, quantity_change, quantity_before, quantity_after, batch_number, reason, performed_by) VALUES (?, ?, ?, ?, 'add', ?, ?, ?, ?, 'Stock Arrival', ?)");
        $stmt->execute([
            $move_id,
            $inventory_id,
            $medicine_id,
            $staff_branch_id,
            $quantity,
            $qty_before,
            $qty_before + $quantity,
            $batch,
            $_SESSION['user_id']
        ]);

        $pdo->commit();
        $_SESSION['pharmacy_message'] = "Stock added successfully!";
        header("Location: pharmacy.php?tab=inventory");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['pharmacy_error'] = "Error adding stock: " . $e->getMessage();
        header("Location: pharmacy.php?tab=inventory");
        exit;
    }
}

// Handle Edit Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stock'])) {
    $inventory_id = $_POST['inventory_id'];
    $new_quantity = intval($_POST['new_quantity']);
    $reason = trim($_POST['reason'] ?? 'Manual Adjustment');
    $staff_branch_id = $_SESSION['staff_branch_id'] ?? 'BR-MEL-01';

    if ($new_quantity < 0) {
        $_SESSION['pharmacy_error'] = "Quantity cannot be negative.";
        header("Location: pharmacy.php?tab=inventory");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? AND branch_id = ?");
        $stmt->execute([$inventory_id, $staff_branch_id]);
        $inv = $stmt->fetch();

        if (!$inv) {
            throw new Exception("Inventory record not found.");
        }

        $qty_before = $inv['quantity'];
        $qty_change = $new_quantity - $qty_before;

        if ($qty_change !== 0) {
            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = ?, last_updated = NOW() WHERE id = ?");
            $stmt->execute([$new_quantity, $inventory_id]);

            // Log movement
            $move_id = 'MOV-' . strtoupper(bin2hex(random_bytes(8)));
            $stmt = $pdo->prepare("INSERT INTO stock_movements (id, inventory_id, medicine_id, branch_id, movement_type, quantity_change, quantity_before, quantity_after, reason, performed_by) VALUES (?, ?, ?, ?, 'adjust', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $move_id,
                $inventory_id,
                $inv['medicine_id'],
                $staff_branch_id,
                $qty_change,
                $qty_before,
                $new_quantity,
                $reason,
                $_SESSION['user_id']
            ]);
        }

        $pdo->commit();
        $_SESSION['pharmacy_message'] = "Stock updated successfully!";
        header("Location: pharmacy.php?tab=inventory");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['pharmacy_error'] = "Error updating stock: " . $e->getMessage();
        header("Location: pharmacy.php?tab=inventory");
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

// Fetch Medicines for Add Stock Dropdown
$stmt = $pdo->prepare("SELECT id, name, code, dosage_form, strength FROM medicines WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$medicines_list = $stmt->fetchAll();

// Fetch Stock Logs
$log_date_from = $_GET['log_date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$log_date_to = $_GET['log_date_to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT sm.*, m.name as medicine_name, u.name as user_name
    FROM stock_movements sm
    JOIN medicines m ON sm.medicine_id = m.id
    LEFT JOIN users u ON sm.performed_by = u.id
    WHERE sm.branch_id = ?
    AND DATE(sm.created_at) BETWEEN ? AND ?
    ORDER BY sm.created_at DESC
    LIMIT 100
");
$stmt->execute([$staff_branch_id, $log_date_from, $log_date_to]);
$stock_logs = $stmt->fetchAll();

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

        <?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending'; ?>
        <div class="tabs">
            <button class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>"
                onclick="switchTab('pending')">Pending Prescriptions
                (<?php echo count($pending); ?>)</button>
            <button class="tab <?php echo $active_tab === 'inventory' ? 'active' : ''; ?>"
                onclick="switchTab('inventory')">Inventory</button>
            <button class="tab <?php echo $active_tab === 'dispensed_history' ? 'active' : ''; ?>"
                onclick="switchTab('dispensed_history')">Dispensed History</button>
            <button class="tab <?php echo $active_tab === 'stock_logs' ? 'active' : ''; ?>"
                onclick="switchTab('stock_logs')">Stock Logs</button>
        </div>

        <!-- Pending Prescriptions Tab -->
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

        <!-- Inventory Tab -->
        <div id="tab-inventory" class="tab-content"
            style="<?php echo $active_tab !== 'inventory' ? 'display: none;' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Medicine Inventory</h3>
                    <button class="btn btn-sm btn-primary" onclick="openModal('addStockModal')">+ Add Stock</button>
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
                                <th>Actions</th>
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
                                    <td>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="openEditModal('<?php echo $m['id']; ?>', '<?php echo $m['quantity']; ?>', '<?php echo h($m['name']); ?>')">
                                            ✏️ Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inventory)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-gray">No medicines in inventory for this branch.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dispensed History Tab -->
        <div id="tab-dispensed_history" class="tab-content"
            style="<?php echo $active_tab !== 'dispensed_history' ? 'display: none;' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📋 Dispensed History</h3>
                </div>
                <form method="GET" style="padding: 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <input type="hidden" name="tab" value="dispensed_history">
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

    <!-- Stock Logs Tab -->
        <div id="tab-stock_logs" class="tab-content"
            style="<?php echo $active_tab !== 'stock_logs' ? 'display: none;' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📜 Stock Movement Log</h3>
                </div>
                <form method="GET" style="padding: 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <input type="hidden" name="tab" value="stock_logs">
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label class="form-label">From</label>
                            <input type="date" name="log_date_from" class="form-input" value="<?php echo h($log_date_from); ?>">
                        </div>
                        <div>
                            <label class="form-label">To</label>
                            <input type="date" name="log_date_to" class="form-input" value="<?php echo h($log_date_to); ?>">
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
                                <th>Date</th>
                                <th>Medicine</th>
                                <th>Type</th>
                                <th>Change</th>
                                <th>Before / After</th>
                                <th>Changed By</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock_logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo h($log['medicine_name']); ?></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'add' => 'badge-green',
                                                'dispense' => 'badge-blue',
                                                'adjust' => 'badge-yellow',
                                                'expired' => 'badge-red',
                                                'return' => 'badge-purple'
                                            ];
                                            $cls = $badges[$log['movement_type']] ?? 'badge-gray';
                                            ?>
                                            <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($log['movement_type']); ?></span>
                                        </td>
                                        <td style="font-weight: bold; color: <?php echo $log['quantity_change'] > 0 ? 'green' : 'red'; ?>">
                                            <?php echo $log['quantity_change'] > 0 ? '+' : ''; ?>    <?php echo $log['quantity_change']; ?>
                                        </td>
                                        <td class="text-sm text-gray">
                                            <?php echo $log['quantity_before']; ?> ➝ <?php echo $log['quantity_after']; ?>
                                        </td>
                                        <td><?php echo h($log['user_name'] ?? 'System'); ?></td>
                                        <td class="text-sm"><?php echo h($log['reason']); ?></td>
                                    </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stock_logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-gray">No stock logs found for this period.</td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modal Styles -->
<style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 100%;
        max-width: 500px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .modal-title {
        font-size: 20px;
        font-weight: bold;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
    }
</style>

<!-- Add Stock Modal -->
<div id="addStockModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Stock</h3>
            <button class="close-btn" onclick="closeModal('addStockModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_stock" value="1">
            
            <div class="form-group">
                <label class="form-label">Medicine</label>
                <select name="medicine_id" class="form-select" required>
                    <option value="">Select Medicine</option>
                    <?php foreach ($medicines_list as $med): ?>
                            <option value="<?php echo $med['id']; ?>">
                                <?php echo h($med['name']); ?> (<?php echo h($med['strength']); ?>)
                            </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Quantity to Add</label>
                    <input type="number" name="quantity" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Batch Number</label>
                    <input type="text" name="batch_number" class="form-input" placeholder="Optional">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-input">
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addStockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Stock Modal -->
<div id="editStockModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Stock Quantity</h3>
            <button class="close-btn" onclick="closeModal('editStockModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="edit_stock" value="1">
            <input type="hidden" name="inventory_id" id="edit_inventory_id">
            
            <div class="form-group">
                <label class="form-label">Medicine</label>
                <input type="text" id="edit_medicine_name" class="form-input" readonly style="background: #f3f4f6;">
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Current Qty</label>
                    <input type="text" id="edit_current_qty" class="form-input" readonly style="background: #f3f4f6;">
                </div>
                <div class="form-group">
                    <label class="form-label">New Quantity</label>
                    <input type="number" name="new_quantity" class="form-input" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Reason for Change</label>
                <select name="reason" class="form-select" required>
                    <option value="Manual Adjustment">Manual Adjustment</option>
                    <option value="Recount Correction">Recount Correction</option>
                    <option value="Damaged Stock">Damaged Stock</option>
                    <option value="Expired Stock">Expired Stock</option>
                    <option value="Returned Stock">Returned Stock</option>
                </select>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editStockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(function (c) {
            c.style.display = 'none';
        });
        document.querySelectorAll('.tab').forEach(function (t) {
            t.classList.remove('active');
        });
        document.getElementById('tab-' + tab).style.display = 'block';
        
        // Find the button that was clicked (based on tab name)
        const buttons = document.querySelectorAll('.tab');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes(tab)) {
                btn.classList.add('active');
            }
        });
    }

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function openEditModal(invId, currentQty, medName) {
        document.getElementById('edit_inventory_id').value = invId;
        document.getElementById('edit_current_qty').value = currentQty;
        document.getElementById('edit_medicine_name').value = medName;
        openModal('editStockModal');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = "none";
        }
    }
</script>

<?php include '../includes/footer.php'; ?>