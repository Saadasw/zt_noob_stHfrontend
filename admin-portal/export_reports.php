<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (empty($type)) {
    die("Report type not specified.");
}

// Function to output CSV
function outputCSV($filename, $data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add header row if data exists
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Handle Daily Summary PDF (Render as printable HTML)
if ($type === 'daily_summary' && $format === 'pdf') {
    include 'print_report.php';
    exit;
}

// Handle CSV Exports
switch ($type) {
    case 'patients':
        $data = $pdo->query("
            SELECT id, email, name, phone, role, is_active, created_at 
            FROM users 
            WHERE role = 'patient'
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        outputCSV('patient_list_' . date('Y-m-d'), $data);
        break;

    case 'appointments':
        $data = $pdo->query("
            SELECT a.appointment_no, u_pat.name as patient_name, u_doc.name as doctor_name, 
                   a.appointment_date, a.status, a.created_at
            FROM appointments a
            JOIN patient_profiles pp ON a.patient_id = pp.id
            JOIN users u_pat ON pp.user_id = u_pat.id
            JOIN doctor_profiles dp ON a.doctor_id = dp.id
            JOIN users u_doc ON dp.user_id = u_doc.id
            ORDER BY a.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        outputCSV('appointment_log_' . date('Y-m-d'), $data);
        break;

    case 'revenue':
        $data = $pdo->query("
            SELECT b.bill_no, u.name as patient_name, b.total_amount, 
                   b.paid_amount, b.due_amount, b.payment_status, b.created_at
            FROM bills b
            JOIN patient_profiles pp ON b.patient_id = pp.id
            JOIN users u ON pp.user_id = u.id
            WHERE MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())
            ORDER BY b.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        outputCSV('monthly_revenue_' . date('F_Y'), $data);
        break;

    default:
        die("Invalid report type.");
}
?>
