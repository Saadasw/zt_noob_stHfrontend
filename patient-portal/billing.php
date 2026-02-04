<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Bills & Payments - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'billing';

$message = '';
$error = '';

// Get patient profile
$stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();
$patient_id = $patient['id'] ?? null;

// Handle "Pay Now" (demo - marks bill as paid)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill'])) {
    $bill_id = $_POST['bill_id'];
    $amount = floatval($_POST['amount']);

    try {
        $pdo->beginTransaction();

        // Create payment record
        $payment_id = 'PAY-' . bin2hex(random_bytes(4));
        $receipt_no = 'RCP-' . date('Y') . '-' . mt_rand(100000, 999999);

        $stmt = $pdo->prepare("
            INSERT INTO payments (id, bill_id, receipt_no, amount, method, status, paid_at)
            VALUES (?, ?, ?, ?, 'online', 'completed', NOW())
        ");
        $stmt->execute([$payment_id, $bill_id, $receipt_no, $amount]);

        // Update bill
        $stmt = $pdo->prepare("
            UPDATE bills 
            SET paid_amount = paid_amount + ?,
                due_amount = due_amount - ?,
                payment_status = IF(due_amount - ? <= 0, 'paid', 'partial'),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$amount, $amount, $amount, $bill_id]);

        $pdo->commit();
        $message = "Payment successful! Receipt: " . $receipt_no;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Payment error: " . $e->getMessage();
    }
}

// Get active tab
$active_tab = $_GET['tab'] ?? 'bills';

// Fetch patient's bills
$bills = [];
$total_due = 0;
$total_paid = 0;
$pending_count = 0;

if ($patient_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM bills 
        WHERE patient_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    $bills = $stmt->fetchAll();

    // Calculate totals
    foreach ($bills as $b) {
        $total_due += $b['due_amount'];
        $total_paid += $b['paid_amount'];
        if ($b['payment_status'] === 'pending' || $b['payment_status'] === 'partial') {
            $pending_count++;
        }
    }
}

// Fetch payment history
$payments = [];
if ($patient_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, b.bill_no 
        FROM payments p
        JOIN bills b ON p.bill_id = b.id
        WHERE b.patient_id = ?
        ORDER BY p.paid_at DESC
        LIMIT 20
    ");
    $stmt->execute([$patient_id]);
    $payments = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Bills & Payments</h1>
                <p class="page-subtitle">View your bills and payment history</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">💰</div>
                <div class="stat-content">
                    <div class="stat-value">$
                        <?php echo number_format($total_due, 2); ?>
                    </div>
                    <div class="stat-label">Total Due</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">✅</div>
                <div class="stat-content">
                    <div class="stat-value">$
                        <?php echo number_format($total_paid, 2); ?>
                    </div>
                    <div class="stat-label">Total Paid</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">📋</div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo $pending_count; ?>
                    </div>
                    <div class="stat-label">Pending Bills</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=bills" class="tab <?php echo $active_tab === 'bills' ? 'active' : ''; ?>">My Bills</a>
            <a href="?tab=payments" class="tab <?php echo $active_tab === 'payments' ? 'active' : ''; ?>">Payment
                History</a>
        </div>

        <?php if ($active_tab === 'bills'): ?>
            <!-- Bills Tab -->
            <?php if (empty($bills)): ?>
                <div class="card">
                    <div style="padding: 40px; text-align: center; color: #6b7280;">
                        <p>📋 No bills found.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <?php
                    // Fetch bill items
                    $stmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
                    $stmt->execute([$bill['id']]);
                    $items = $stmt->fetchAll();

                    // Status badge
                    $status_badges = [
                        'pending' => '<span class="badge badge-yellow">Pending</span>',
                        'partial' => '<span class="badge badge-blue">Partial</span>',
                        'paid' => '<span class="badge badge-green">Paid</span>',
                        'overdue' => '<span class="badge badge-red">Overdue</span>',
                    ];
                    ?>
                    <div class="card" style="margin-bottom: 16px;">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">
                                    <?php echo h($bill['bill_no']); ?>
                                </h3>
                                <span class="text-sm text-gray">
                                    Created:
                                    <?php echo date('d M Y', strtotime($bill['created_at'])); ?>
                                </span>
                            </div>
                            <?php echo $status_badges[$bill['payment_status']] ?? $bill['payment_status']; ?>
                        </div>

                        <!-- Bill Items -->
                        <div style="padding: 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <strong>Items:</strong>
                            <table style="width: 100%; margin-top: 8px;">
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td style="padding: 4px 0;">
                                            <?php echo h($item['description']); ?>
                                            <?php if ($item['quantity'] > 1): ?>
                                                <span class="text-sm text-gray">(×
                                                    <?php echo $item['quantity']; ?>)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; padding: 4px 0;">
                                            $
                                            <?php echo number_format($item['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td class="text-gray">No items</td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Bill Summary -->
                        <div style="padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-size: 14px;">
                                    Total: <strong>$
                                        <?php echo number_format($bill['total_amount'], 2); ?>
                                    </strong> |
                                    Paid: <span style="color: #16a34a;">$
                                        <?php echo number_format($bill['paid_amount'], 2); ?>
                                    </span> |
                                    Due: <span style="color: #dc2626;">$
                                        <?php echo number_format($bill['due_amount'], 2); ?>
                                    </span>
                                </span>
                            </div>
                            <?php if ($bill['due_amount'] > 0): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="pay_bill" value="1">
                                    <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $bill['due_amount']; ?>">
                                    <button type="submit" class="btn btn-primary"
                                        onclick="return confirm('Pay $<?php echo number_format($bill['due_amount'], 2); ?> for this bill?');">
                                        💳 Pay Now ($
                                        <?php echo number_format($bill['due_amount'], 2); ?>)
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php else: ?>
            <!-- Payment History Tab -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment History</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Bill #</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><strong>
                                            <?php echo h($p['receipt_no']); ?>
                                        </strong></td>
                                    <td>
                                        <?php echo h($p['bill_no']); ?>
                                    </td>
                                    <td>$
                                        <?php echo number_format($p['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo ucfirst($p['method']); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y, h:i A', strtotime($p['paid_at'])); ?>
                                    </td>
                                    <td><span class="badge badge-green">Completed</span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-gray">No payment history.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<style>
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 600;
    }

    .stat-label {
        color: #6b7280;
        font-size: 14px;
    }
</style>

<?php include '../includes/footer.php'; ?>