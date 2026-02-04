<?php
/**
 * Billing Helper Functions
 * Used by lab and pharmacy to auto-add items to patient bills
 */

/**
 * Get or create a pending bill for a patient
 * @param PDO $pdo Database connection
 * @param string $patient_id Patient profile ID
 * @param string $branch_id Branch ID
 * @return array Bill record
 */
function getOrCreatePendingBill($pdo, $patient_id, $branch_id)
{
    // Look for existing pending/partial bill for this patient
    $stmt = $pdo->prepare("
        SELECT * FROM bills 
        WHERE patient_id = ? 
        AND branch_id = ?
        AND payment_status IN ('pending', 'partial')
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$patient_id, $branch_id]);
    $bill = $stmt->fetch();

    if ($bill) {
        return $bill;
    }

    // Create new bill
    $bill_id = 'BILL-' . bin2hex(random_bytes(4));
    $bill_no = 'INV-' . date('Y') . '-' . mt_rand(100000, 999999);
    $due_date = date('Y-m-d', strtotime('+30 days'));

    $stmt = $pdo->prepare("
        INSERT INTO bills (id, bill_no, patient_id, branch_id, total_amount, paid_amount, due_amount, payment_status, due_date)
        VALUES (?, ?, ?, ?, 0, 0, 0, 'pending', ?)
    ");
    $stmt->execute([$bill_id, $bill_no, $patient_id, $branch_id, $due_date]);

    return [
        'id' => $bill_id,
        'bill_no' => $bill_no,
        'patient_id' => $patient_id,
        'branch_id' => $branch_id,
        'total_amount' => 0,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'pending'
    ];
}

/**
 * Add an item to a bill and update totals
 * @param PDO $pdo Database connection
 * @param string $bill_id Bill ID
 * @param string $description Item description
 * @param string $item_type Type (consultation, lab_test, medicine, procedure, other)
 * @param int $quantity Quantity
 * @param float $unit_price Unit price
 * @return string|null Bill item ID or null on failure
 */
function addBillItem($pdo, $bill_id, $description, $item_type, $quantity, $unit_price)
{
    $item_id = 'BI-' . bin2hex(random_bytes(4));
    $amount = $quantity * $unit_price;

    // Insert bill item
    $stmt = $pdo->prepare("
        INSERT INTO bill_items (id, bill_id, description, item_type, quantity, unit_price, amount)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$item_id, $bill_id, $description, $item_type, $quantity, $unit_price, $amount]);

    // Update bill totals
    $stmt = $pdo->prepare("
        UPDATE bills 
        SET total_amount = total_amount + ?,
            due_amount = total_amount + ? - paid_amount,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$amount, $amount, $bill_id]);

    return $item_id;
}

/**
 * Check if a lab test has already been billed
 * @param PDO $pdo Database connection
 * @param string $test_id Lab test ID
 * @return bool True if already billed
 */
function isLabTestBilled($pdo, $test_id)
{
    $stmt = $pdo->prepare("SELECT bill_item_id FROM lab_tests WHERE id = ?");
    $stmt->execute([$test_id]);
    $result = $stmt->fetchColumn();
    return !empty($result);
}

/**
 * Check if a prescription has already been billed
 * @param PDO $pdo Database connection
 * @param string $prescription_id Prescription ID
 * @return bool True if already billed
 */
function isPrescriptionBilled($pdo, $prescription_id)
{
    $stmt = $pdo->prepare("SELECT bill_item_id FROM prescriptions WHERE id = ?");
    $stmt->execute([$prescription_id]);
    $result = $stmt->fetchColumn();
    return !empty($result);
}

/**
 * Mark lab test as billed
 * @param PDO $pdo Database connection
 * @param string $test_id Lab test ID
 * @param string $bill_item_id Bill item ID
 */
function markLabTestBilled($pdo, $test_id, $bill_item_id)
{
    $stmt = $pdo->prepare("UPDATE lab_tests SET bill_item_id = ? WHERE id = ?");
    $stmt->execute([$bill_item_id, $test_id]);
}

/**
 * Mark prescription as billed
 * @param PDO $pdo Database connection
 * @param string $prescription_id Prescription ID
 * @param string $bill_item_id Bill item ID
 */
function markPrescriptionBilled($pdo, $prescription_id, $bill_item_id)
{
    $stmt = $pdo->prepare("UPDATE prescriptions SET bill_item_id = ? WHERE id = ?");
    $stmt->execute([$bill_item_id, $prescription_id]);
}
?>