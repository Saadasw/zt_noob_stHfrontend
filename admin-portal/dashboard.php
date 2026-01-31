<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Admin Dashboard - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'dashboard';

// Get selected branch filter
$selected_branch_id = $_SESSION['selected_branch_id'] ?? null;
$selected_branch_name = $_SESSION['selected_branch_name'] ?? 'All Branches';

// Fetch Real Stats (with branch filter)
try {
    if ($selected_branch_id) {
        // Filter by branch - need to join with profile tables
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT u.id) FROM users u 
            LEFT JOIN patient_profiles pp ON u.id = pp.user_id 
            WHERE u.role='patient' AND u.is_active=1");
        $stmt->execute();
        $total_patients = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_profiles WHERE branch_id = ?");
        $stmt->execute([$selected_branch_id]);
        $total_doctors = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_profiles WHERE branch_id = ?");
        $stmt->execute([$selected_branch_id]);
        $total_staff = $stmt->fetchColumn();

        $total_branches = 1; // Only showing selected branch
    } else {
        // All branches
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='patient' AND is_active=1");
        $total_patients = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='doctor' AND is_active=1");
        $total_doctors = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='staff' AND is_active=1");
        $total_staff = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM branches WHERE is_active=1");
        $total_branches = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // Fallback if tables are empty/migrating
    $total_patients = 0;
    $total_doctors = 0;
    $total_staff = 0;
    $total_branches = 0;
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">System Overview</h1>
                <p class="page-subtitle"><?php echo date('l, d F Y'); ?> | <?php echo h($selected_branch_name); ?></p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-value"><?php echo number_format($total_patients); ?></div>
                <a href="#" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">👨‍⚕️</div>
                <div class="stat-label">Total Doctors</div>
                <div class="stat-value"><?php echo number_format($total_doctors); ?></div>
                <a href="doctors.php" class="stat-link">Manage →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">👤</div>
                <div class="stat-label">Total Staff</div>
                <div class="stat-value"><?php echo number_format($total_staff); ?></div>
                <a href="users.php" class="stat-link">Manage →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🏥</div>
                <div class="stat-label">Active Branches</div>
                <div class="stat-value"><?php echo number_format($total_branches); ?></div>
                <a href="branches.php" class="stat-link">Manage →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Activity</h3>
            </div>
            <div class="grid-3">
                <div style="text-align: center; padding: 16px; background: #f9fafb; border-radius: 4px;">
                    <div style="font-size: 28px; font-weight: bold;">0</div>
                    <div class="text-sm text-gray">📅 Appointments Today</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f9fafb; border-radius: 4px;">
                    <div style="font-size: 28px; font-weight: bold;">$0.00</div>
                    <div class="text-sm text-gray">💳 Revenue Today</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f9fafb; border-radius: 4px;">
                    <div style="font-size: 28px; font-weight: bold;">0</div>
                    <div class="text-sm text-gray">👤 New Patients Today</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Branch-wise Summary</h3>
                <a href="branches.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Code</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch branches
                        $stmt = $pdo->query("SELECT name, code, is_active FROM branches LIMIT 5");
                        if ($stmt->rowCount() > 0) {
                            while ($branch = $stmt->fetch()) {
                                $status_badge = $branch['is_active'] ? '<span class="badge badge-green">🟢 Active</span>' : '<span class="badge badge-red">🔴 Inactive</span>';
                                echo "<tr>
                                    <td><strong>" . h($branch['name']) . "</strong></td>
                                    <td>" . h($branch['code']) . "</td>
                                    <td>$status_badge</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center'>No branches found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="quick-actions" style="flex-direction: column;">
                    <a href="users.php" class="quick-action-btn">➕ Add New User</a>
                    <a href="doctors.php" class="quick-action-btn">➕ Add New Doctor</a>
                    <a href="branches.php" class="quick-action-btn">➕ Add New Branch</a>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                </div>
                <!-- Logic for audit logs would go here -->
                <p class="text-gray text-center" style="padding: 20px;">No recent activity.</p>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>