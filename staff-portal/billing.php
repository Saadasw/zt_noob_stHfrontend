<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Billing - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'billing';

$message = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'pending';

// Handle Create Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bill'])) {
    $patient_id = $_POST['patient_id'];
    $items = $_POST['items'] ?? [];
    
    if (empty($patient_id) || empty($items)) {
        $error = "Please select a patient and add at least one item.";
    } else {
        try {
            $pdo->beginTransaction();

            $bill_id = 'BILL-' . bin2hex(random_bytes(4));
            $bill_no = 'INV-' . date('Y') . '-' . mt_rand(100000, 999999);
            $total = 0;

            // Calculate total
            foreach ($items as $item) {
                if (empty($item['description'])) continue;
                $amount = floatval($item['quantity']) * floatval($item['unit_price']);
                $total += $amount;
            }

            // Create bill
            $stmt = $pdo->prepare("INSERT INTO bills (id, bill_no, patient_id, branch_id, total_amount, due_amount, payment_status) VALUES (?, ?, ?, 'BR-MEL-01', ?, ?, 'pending')");
            $stmt->execute([$bill_id, $bill_no, $patient_id, $total, $total]);

            // Create bill items
            foreach ($items as $item) {
                if (empty($item['description'])) continue;
                $item_id = 'BI-' . bin2hex(random_bytes(4));
                $amount = floatval($item['quantity']) * floatval($item['unit_price']);
                
                $stmt = $pdo->prepare("INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_id, $bill_id, $item['description'], $item['type'] ?? 'other', intval($item['quantity']), floatval($item['unit_price']), $amount]);
            }

            $pdo->commit();
            $message = "Bill created successfully! Bill No: $bill_no | Total: $" . number_format($total, 2);
            $active_tab = 'pending';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $bill_id = $_POST['bill_id'];
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];

    try {
        $pdo->beginTransaction();

        // Get current bill
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
        $stmt->execute([$bill_id]);
        $bill = $stmt->fetch();

        $new_paid = $bill['paid_amount'] + $amount;
        $new_due = $bill['total_amount'] - $new_paid;
        $new_status = ($new_due <= 0) ? 'paid' : 'partial';

        // Update bill
        $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$new_paid, max(0, $new_due), $new_status, $bill_id]);

        // Create payment record
        $pay_id = 'PAY-' . bin2hex(random_bytes(4));
        $receipt_no = 'RCP-' . date('Y') . '-' . mt_rand(100000, 999999);
        $stmt = $pdo->prepare("INSERT INTO payments (id, bill_id, receipt_no, amount, method, status, paid_at) VALUES (?, ?, ?, ?, ?, 'completed', NOW())");
        $stmt->execute([$pay_id, $bill_id, $receipt_no, $amount, $method]);

        $pdo->commit();
        $message = "Payment of $" . number_format($amount, 2) . " processed. Receipt: $receipt_no";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Payment error: " . $e->getMessage();
    }
}

// Fetch patients for dropdown
$patients = $pdo->query("SELECT pp.id, pp.patient_id, u.name FROM patient_profiles pp JOIN users u ON pp.user_id = u.id ORDER BY u.name")->fetchAll();

// Fetch pending bills
$pending_bills = $pdo->query("
    SELECT b.*, u.name as patient_name, pp.patient_id as patient_code
    FROM bills b
    JOIN patient_profiles pp ON b.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE b.payment_status IN ('pending', 'partial', 'overdue')
    ORDER BY b.created_at DESC
")->fetchAll();

// Fetch paid bills
$paid_bills = $pdo->query("
    SELECT b.*, u.name as patient_name, pp.patient_id as patient_code
    FROM bills b
    JOIN patient_profiles pp ON b.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE b.payment_status = 'paid'
    ORDER BY b.created_at DESC
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
                <h1 class="page-title">Billing & Payments</h1>
                <p class="page-subtitle">Create bills and process payments</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=pending" class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">Pending (<?php echo count($pending_bills); ?>)</a>
            <a href="?tab=paid" class="tab <?php echo $active_tab === 'paid' ? 'active' : ''; ?>">Paid</a>
            <a href="?tab=create" class="tab <?php echo $active_tab === 'create' ? 'active' : ''; ?>">Create New Bill</a>
        </div>

        <?php if ($active_tab === 'pending'): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pending Bills</h3></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Patient</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_bills as $b): ?>
                        <tr>
                            <td><strong><?php echo h($b['bill_no']); ?></strong></td>
                            <td><?php echo h($b['patient_name']); ?><br><span class="text-sm text-gray"><?php echo h($b['patient_code']); ?></span></td>
                            <td>$<?php echo number_format($b['total_amount'], 2); ?></td>
                            <td>$<?php echo number_format($b['paid_amount'], 2); ?></td>
                            <td style="color: #dc2626; font-weight: 600;">$<?php echo number_format($b['due_amount'], 2); ?></td>
                            <td>
                                <?php if ($b['payment_status'] === 'pending'): ?>
                                    <span class="badge badge-yellow">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-blue">Partial</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showPaymentModal('<?php echo $b['id']; ?>', <?php echo $b['due_amount']; ?>)">💳 Pay</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending_bills)): ?>
                        <tr><td colspan="7" class="text-center text-gray">No pending bills.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Modal -->
        <div id="paymentModal" class="card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="card-header">
                <h3 class="card-title">Process Payment</h3>
                <button class="btn btn-sm btn-outline" onclick="hidePaymentModal()">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="process_payment" value="1">
                <input type="hidden" name="bill_id" id="modal_bill_id">
                <div class="form-group">
                    <label class="form-label">Amount Due</label>
                    <input type="text" id="modal_due" class="form-input" readonly style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Amount *</label>
                    <input type="number" step="0.01" name="amount" id="modal_amount" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select name="method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline" onclick="hidePaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Payment</button>
                </div>
            </form>
        </div>
        <div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;" onclick="hidePaymentModal()"></div>

        <?php elseif ($active_tab === 'paid'): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Paid Bills</h3></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Patient</th>
                            <th>Total</th>
                            <th>Paid On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paid_bills as $b): ?>
                        <tr>
                            <td><strong><?php echo h($b['bill_no']); ?></strong></td>
                            <td><?php echo h($b['patient_name']); ?></td>
                            <td>$<?php echo number_format($b['total_amount'], 2); ?></td>
                            <td><?php echo date('d M Y', strtotime($b['updated_at'])); ?></td>
                            <td><button class="btn btn-sm btn-outline">View Receipt</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($active_tab === 'create'): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Create New Bill</h3></div>
            <form method="POST">
                <input type="hidden" name="create_bill" value="1">
                
                <div class="form-group">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient...</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo h($p['name']); ?> (<?php echo h($p['patient_id']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="section-header">BILL ITEMS</div>
                <div id="billItems">
                    <div class="grid-2" style="gap: 8px; margin-bottom: 8px;">
                        <input type="text" name="items[0][description]" class="form-input" placeholder="Description (e.g., Consultation Fee)" required>
                        <select name="items[0][type]" class="form-select">
                            <option value="consultation">Consultation</option>
                            <option value="lab_test">Lab Test</option>
                            <option value="medicine">Medicine</option>
                            <option value="procedure">Procedure</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="number" name="items[0][quantity]" class="form-input" placeholder="Qty" value="1" style="width: 80px;">
                        <input type="number" step="0.01" name="items[0][unit_price]" class="form-input" placeholder="Unit Price" required>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addBillItem()">+ Add Item</button>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create Bill</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>

<script>
var itemCount = 1;
function addBillItem() {
    var html = `
    <div class="grid-2" style="gap: 8px; margin-bottom: 8px;">
        <input type="text" name="items[${itemCount}][description]" class="form-input" placeholder="Description">
        <select name="items[${itemCount}][type]" class="form-select">
            <option value="consultation">Consultation</option>
            <option value="lab_test">Lab Test</option>
            <option value="medicine">Medicine</option>
            <option value="other">Other</option>
        </select>
        <input type="number" name="items[${itemCount}][quantity]" class="form-input" placeholder="Qty" value="1" style="width: 80px;">
        <input type="number" step="0.01" name="items[${itemCount}][unit_price]" class="form-input" placeholder="Unit Price">
    </div>`;
    document.getElementById('billItems').insertAdjacentHTML('beforeend', html);
    itemCount++;
}

function showPaymentModal(billId, due) {
    document.getElementById('modal_bill_id').value = billId;
    document.getElementById('modal_due').value = '$' + due.toFixed(2);
    document.getElementById('modal_amount').value = due.toFixed(2);
    document.getElementById('paymentModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function hidePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
