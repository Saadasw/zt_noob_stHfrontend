<?php
// Fetch stats again for the report (already authenticated in export_reports.php)
$stats = [];
$stats['total_patients'] = $pdo->query("SELECT COUNT(*) FROM patient_profiles")->fetchColumn();
$stats['revenue_month'] = $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM bills WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$stats['appointments_month'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())")->fetchColumn();
$stats['lab_pending'] = $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status NOT IN ('completed', 'cancelled')")->fetchColumn();

// Fetch recent appointments for the summary
$recent_appointments = $pdo->query("
    SELECT a.appointment_no, a.appointment_date, a.status, 
           u_pat.name as patient_name, u_doc.name as doctor_name
    FROM appointments a
    JOIN patient_profiles pp ON a.patient_id = pp.id
    JOIN users u_pat ON pp.user_id = u_pat.id
    JOIN doctor_profiles dp ON a.doctor_id = dp.id
    JOIN users u_doc ON dp.user_id = u_doc.id
    ORDER BY a.created_at DESC
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Summary Report - <?php echo date('d M Y'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
        .report-title { font-size: 24px; color: #2563eb; margin: 0; }
        .report-meta { color: #666; margin-top: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-box { border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-label { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .stat-value { font-size: 20px; font-weight: bold; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f9fafb; text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 4px;">Print Report</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 4px;">Close</button>
    </div>

    <div class="header">
        <h1 class="report-title">Daily Summary Report</h1>
        <div class="report-meta">St. George Hospital | Generated on <?php echo date('d F Y, h:i A'); ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Total Patients</div>
            <div class="stat-value"><?php echo number_format($stats['total_patients']); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-value">$<?php echo number_format($stats['revenue_month'], 2); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Monthly Apps</div>
            <div class="stat-value"><?php echo $stats['appointments_month']; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Pending Lab</div>
            <div class="stat-value"><?php echo $stats['lab_pending']; ?></div>
        </div>
    </div>

    <h3>Recent Appointments</h3>
    <table>
        <thead>
            <tr>
                <th>Appt #</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_appointments as $apt): ?>
            <tr>
                <td><?php echo h($apt['appointment_no']); ?></td>
                <td><?php echo h($apt['patient_name']); ?></td>
                <td><?php echo h($apt['doctor_name']); ?></td>
                <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                <td><?php echo ucfirst($apt['status']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: center; color: #9ca3af; font-size: 12px;">
        --- End of Report ---
    </div>
</body>
</html>
