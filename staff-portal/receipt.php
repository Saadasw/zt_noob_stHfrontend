<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

if (!isset($_GET['bill_id'])) {
    die("Bill ID not specified.");
}

$bill_id = $_GET['bill_id'];

// Fetch Bill & Patient Details
$stmt = $pdo->prepare("
    SELECT b.*, 
           u.name as patient_name, u.email as patient_email, u.phone as patient_phone,
           pp.patient_id as patient_code, pp.address as patient_address
    FROM bills b
    JOIN patient_profiles pp ON b.patient_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if (!$bill) {
    die("Bill not found.");
}

// Fetch Bill Items
$stmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
$stmt->execute([$bill_id]);
$items = $stmt->fetchAll();

// Fetch Payments
$stmt = $pdo->prepare("SELECT * FROM payments WHERE bill_id = ? ORDER BY paid_at DESC");
$stmt->execute([$bill_id]);
$payments = $stmt->fetchAll();

$page_title = 'Receipt - ' . $bill['bill_no'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title; ?>
    </title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f3f4f6;
            padding: 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .receipt-title {
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4b5563;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .info-group h4 {
            margin: 0 0 10px 0;
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
        }

        .info-group p {
            margin: 0;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #f9fafb;
            color: #4b5563;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .total-box {
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total-row.final {
            border-top: 2px solid #eee;
            font-weight: bold;
            font-size: 18px;
            padding-top: 15px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-partial {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .status-pending {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .btn-print {
            display: block;
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            text-align: center;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="receipt-container">
        <div class="header">
            <div class="hospital-name">St. George Hospital</div>
            <div>123 Medical Center Drive, Melbourne, VIC 3000</div>
            <div>Phone: (03) 9123 4567 | Email: billing@stgeorge.com.au</div>
            <div style="margin-top: 20px;">
                <span class="status-badge status-<?php echo strtolower($bill['payment_status']); ?>">
                    <?php echo ucfirst($bill['payment_status']); ?>
                </span>
            </div>
            <h2 class="receipt-title" style="margin-top: 20px;">
                <?php echo $request_type ?? 'PAYMENT RECEIPT'; ?>
            </h2>
        </div>

        <div class="info-grid">
            <div class="info-group">
                <h4>Billed To</h4>
                <p><strong>
                        <?php echo htmlspecialchars($bill['patient_name']); ?>
                    </strong></p>
                <p>ID:
                    <?php echo htmlspecialchars($bill['patient_code']); ?>
                </p>
                <p>
                    <?php echo htmlspecialchars($bill['patient_address'] ?? ''); ?>
                </p>
                <p>
                    <?php echo htmlspecialchars($bill['patient_phone'] ?? ''); ?>
                </p>
            </div>
            <div class="info-group" style="text-align: right;">
                <h4>Receipt Details</h4>
                <p><strong>Bill No:</strong>
                    <?php echo htmlspecialchars($bill['bill_no']); ?>
                </p>
                <p><strong>Date:</strong>
                    <?php echo date('d M Y', strtotime($bill['created_at'])); ?>
                </p>

                <?php if (!empty($payments)): ?>
                    <?php $last_pay = $payments[0]; ?>
                    <p><strong>Receipt No:</strong>
                        <?php echo htmlspecialchars($last_pay['receipt_no']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width: 100px;">Type</th>
                    <th style="width: 80px; text-align: center;">Qty</th>
                    <th style="width: 120px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['description']); ?>
                        </td>
                        <td><small style="color: #6b7280; text-transform: capitalize;">
                                <?php echo str_replace('_', ' ', $item['item_type']); ?>
                            </small></td>
                        <td style="text-align: center;">
                            <?php echo $item['quantity']; ?>
                        </td>
                        <td style="text-align: right;">$
                            <?php echo number_format($item['amount'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>$
                        <?php echo number_format($bill['total_amount'], 2); ?>
                    </span>
                </div>
                <div class="total-row final">
                    <span>Total Amount</span>
                    <span>$
                        <?php echo number_format($bill['total_amount'], 2); ?>
                    </span>
                </div>

                <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee;">
                    <?php foreach ($payments as $pay): ?>
                        <div class="total-row" style="color: #166534;">
                            <span>
                                Paid (
                                <?php echo ucfirst($pay['method']); ?>)
                                <br><small style="font-weight:normal; color:#6b7280;">
                                    <?php echo date('d M Y', strtotime($pay['paid_at'])); ?>
                                </small>
                            </span>
                            <span>-$
                                <?php echo number_format($pay['amount'], 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-row"
                        style="margin-top: 10px; font-weight: bold; color: <?php echo $bill['due_amount'] > 0 ? '#dc2626' : '#374151'; ?>">
                        <span>Balance Due</span>
                        <span>$
                            <?php echo number_format($bill['due_amount'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated receipt and requires no signature.</p>
            <p>Generated on
                <?php echo date('d M Y, h:i A'); ?>
            </p>
        </div>

        <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
    </div>

</body>

</html>