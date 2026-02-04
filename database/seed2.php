<?php
/**
 * Seed2.php - Seed Medicines and Inventory for BR-MEL-01
 * Run this file to add sample medicines and inventory data
 */

require_once __DIR__ . '/../config/db_connect.php';

echo "<h1>Seeding Medicines & Inventory Data</h1>";

$branch_id = 'BR-MEL-01';

// Sample medicines data
$medicines = [
    ['id' => 'MED-001', 'code' => 'PARA500', 'name' => 'Paracetamol 500mg', 'generic_name' => 'Paracetamol', 'category' => 'Pain Relief', 'manufacturer' => 'GSK', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'unit_price' => 0.50],
    ['id' => 'MED-002', 'code' => 'AMOX500', 'name' => 'Amoxicillin 500mg', 'generic_name' => 'Amoxicillin', 'category' => 'Antibiotic', 'manufacturer' => 'Pfizer', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'unit_price' => 1.20],
    ['id' => 'MED-003', 'code' => 'IBUP400', 'name' => 'Ibuprofen 400mg', 'generic_name' => 'Ibuprofen', 'category' => 'Pain Relief', 'manufacturer' => 'Bayer', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'unit_price' => 0.80],
    ['id' => 'MED-004', 'code' => 'OMEP20', 'name' => 'Omeprazole 20mg', 'generic_name' => 'Omeprazole', 'category' => 'Gastrointestinal', 'manufacturer' => 'AstraZeneca', 'dosage_form' => 'Capsule', 'strength' => '20mg', 'unit_price' => 1.50],
    ['id' => 'MED-005', 'code' => 'LORA10', 'name' => 'Loratadine 10mg', 'generic_name' => 'Loratadine', 'category' => 'Antihistamine', 'manufacturer' => 'Schering', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'unit_price' => 0.60],
    ['id' => 'MED-006', 'code' => 'MTFM500', 'name' => 'Metformin 500mg', 'generic_name' => 'Metformin', 'category' => 'Diabetes', 'manufacturer' => 'Merck', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'unit_price' => 0.30],
    ['id' => 'MED-007', 'code' => 'ATOR20', 'name' => 'Atorvastatin 20mg', 'generic_name' => 'Atorvastatin', 'category' => 'Cholesterol', 'manufacturer' => 'Pfizer', 'dosage_form' => 'Tablet', 'strength' => '20mg', 'unit_price' => 1.80],
    ['id' => 'MED-008', 'code' => 'CIPR500', 'name' => 'Ciprofloxacin 500mg', 'generic_name' => 'Ciprofloxacin', 'category' => 'Antibiotic', 'manufacturer' => 'Bayer', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'unit_price' => 2.00],
    ['id' => 'MED-009', 'code' => 'AZIT250', 'name' => 'Azithromycin 250mg', 'generic_name' => 'Azithromycin', 'category' => 'Antibiotic', 'manufacturer' => 'Pfizer', 'dosage_form' => 'Tablet', 'strength' => '250mg', 'unit_price' => 3.50],
    ['id' => 'MED-010', 'code' => 'SALB4', 'name' => 'Salbutamol Inhaler', 'generic_name' => 'Salbutamol', 'category' => 'Respiratory', 'manufacturer' => 'GSK', 'dosage_form' => 'Inhaler', 'strength' => '100mcg', 'unit_price' => 15.00],
    ['id' => 'MED-011', 'code' => 'DICL50', 'name' => 'Diclofenac 50mg', 'generic_name' => 'Diclofenac', 'category' => 'Pain Relief', 'manufacturer' => 'Novartis', 'dosage_form' => 'Tablet', 'strength' => '50mg', 'unit_price' => 0.70],
    ['id' => 'MED-012', 'code' => 'RANITIDINE', 'name' => 'Ranitidine 150mg', 'generic_name' => 'Ranitidine', 'category' => 'Gastrointestinal', 'manufacturer' => 'GSK', 'dosage_form' => 'Tablet', 'strength' => '150mg', 'unit_price' => 0.45],
    ['id' => 'MED-013', 'code' => 'CETR10', 'name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'category' => 'Antihistamine', 'manufacturer' => 'UCB', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'unit_price' => 0.55],
    ['id' => 'MED-014', 'code' => 'PANT40', 'name' => 'Pantoprazole 40mg', 'generic_name' => 'Pantoprazole', 'category' => 'Gastrointestinal', 'manufacturer' => 'Takeda', 'dosage_form' => 'Tablet', 'strength' => '40mg', 'unit_price' => 1.25],
    ['id' => 'MED-015', 'code' => 'DOXY100', 'name' => 'Doxycycline 100mg', 'generic_name' => 'Doxycycline', 'category' => 'Antibiotic', 'manufacturer' => 'Pfizer', 'dosage_form' => 'Capsule', 'strength' => '100mg', 'unit_price' => 1.10],
];

// Insert medicines
$stmt = $pdo->prepare("INSERT IGNORE INTO medicines (id, code, name, generic_name, category, manufacturer, dosage_form, strength, unit_price, requires_prescription, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");

$medicine_count = 0;
foreach ($medicines as $m) {
    try {
        $stmt->execute([$m['id'], $m['code'], $m['name'], $m['generic_name'], $m['category'], $m['manufacturer'], $m['dosage_form'], $m['strength'], $m['unit_price']]);
        $medicine_count++;
    } catch (PDOException $e) {
        // Ignore duplicates
    }
}
echo "<p style='color: green;'>✅ Inserted/Updated $medicine_count medicines</p>";

// Insert inventory for BR-MEL-01
$stmt = $pdo->prepare("INSERT IGNORE INTO inventory (id, medicine_id, branch_id, quantity, reorder_level, batch_number, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");

$inventory_count = 0;
foreach ($medicines as $index => $m) {
    $inv_id = 'INV-MEL-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
    $qty = rand(50, 500);
    $reorder = 20;
    $batch = 'BATCH-' . date('Ym') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d', strtotime('+' . rand(6, 24) . ' months'));

    try {
        $stmt->execute([$inv_id, $m['id'], $branch_id, $qty, $reorder, $batch, $expiry]);
        $inventory_count++;
    } catch (PDOException $e) {
        // Ignore duplicates
    }
}
echo "<p style='color: green;'>✅ Inserted/Updated $inventory_count inventory records for branch $branch_id</p>";

echo "<hr><p><a href='../staff-portal/pharmacy.php'>Go to Pharmacy</a></p>";
?>