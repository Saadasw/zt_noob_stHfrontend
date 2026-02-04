<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Patients - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'patients';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Patients</h1>
                <p class="page-subtitle">View and manage your patient records</p>
            </div>
        </div>



        <div class="card">
            <!-- Filter Bar -->
            <form method="GET" class="flex gap-2 mb-4">
                <input type="text" name="search" class="form-input" placeholder="🔍 Search by name or ID..."
                    style="width: 250px;" value="<?php echo h($_GET['search'] ?? ''); ?>">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID</th>
                            <th>DOB / Gender</th>
                            <th>Emergency Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch Patients
                        $search = $_GET['search'] ?? '';
                        $sql = "SELECT p.*, u.name, u.email, u.phone 
                                FROM patient_profiles p 
                                JOIN users u ON p.user_id = u.id 
                                WHERE u.is_active = 1";
                        $params = [];

                        if ($search) {
                            $sql .= " AND (u.name LIKE ? OR p.patient_id LIKE ?)";
                            $term = "%$search%";
                            $params = [$term, $term];
                        }

                        $sql .= " ORDER BY u.name ASC LIMIT 50";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $patients = $stmt->fetchAll();

                        if (count($patients) > 0):
                            foreach ($patients as $patient):
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo h($patient['name']); ?></strong>
                                        <div class="text-sm text-gray"><?php echo h($patient['email']); ?></div>
                                    </td>
                                    <td><?php echo h($patient['patient_id']); ?></td>
                                    <td>
                                        <?php echo h($patient['date_of_birth']); ?>
                                        <span class="text-sm text-gray">(<?php echo h($patient['gender']); ?>)</span>
                                    </td>
                                    <td>
                                        <?php echo h($patient['emergency_contact_name'] ?? '-'); ?>
                                        <div class="text-sm text-gray">
                                            <?php echo h($patient['emergency_contact_phone'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="patient-details.php?id=<?php echo h($patient['id']); ?>"
                                            class="btn btn-sm btn-outline">👁️ View Profile</a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No patients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>