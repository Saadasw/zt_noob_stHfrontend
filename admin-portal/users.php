<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'User Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'users';

$message = '';
$error = '';

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    // $branch_id = $_POST['branch_id']; // Not in Users table yet, would need profile tables

    if (empty($email) || empty($password) || empty($first_name)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            try {
                $name = $first_name . ' ' . $last_name;
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $userId = 'USR-' . time(); // Simple ID generation

                $sql = "INSERT INTO users (id, email, password, name, role, phone) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId, $email, $hashed_password, $name, $role, $phone]);

                $message = "User created successfully with ID: $userId";
                
                // TODO: Insert into specific profile tables (doctor_profiles, etc) if needed
                
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Fetch Users with Search
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users";
$params = [];

if ($search) {
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR role LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage all system users</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addUserModal')">+ Add New User</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <!-- Filter Bar -->
            <form method="GET" class="flex gap-2 mb-4">
                <input type="text" name="search" class="form-input" placeholder="🔍 Search users..." style="width: 200px;" value="<?php echo h($search); ?>">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
                <?php if($search): ?>
                    <a href="users.php" class="btn btn-sm btn-outline">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo h($user['id']); ?></td>
                            <td><?php echo h($user['name']); ?></td>
                            <td><?php echo h($user['email']); ?></td>
                            <td>
                                <?php 
                                $badge = 'badge-gray';
                                if($user['role'] == 'admin') $badge = 'badge-red';
                                if($user['role'] == 'doctor') $badge = 'badge-blue';
                                if($user['role'] == 'staff') $badge = 'badge-yellow';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo ucfirst(h($user['role'])); ?></span>
                            </td>
                            <td>
                                <?php echo $user['is_active'] ? '<span class="badge badge-green">🟢 Active</span>' : '<span class="badge badge-red">🔴 Inactive</span>'; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button class="btn btn-sm btn-outline">Edit</button>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline" style="color: #dc2626; border-color: #dc2626;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Modal (Styled as Card overlay for now to match static html style) -->
        <div id="addUserModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New User</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addUserModal')">Close</button>
            </div>

            <form method="POST">
                <input type="hidden" name="create_user" value="1">
                
                <div class="section-header">ACCOUNT INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">User Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                            <option value="patient">Patient</option>
                        </select>
                    </div>
                    <!-- Branch selection omitted until connected to profiles -->
                </div>

                <div class="section-header">PERSONAL INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-input" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input">
                    </div>
                </div>

                <div class="section-header">LOGIN CREDENTIALS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-input" required value="Password123">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>

    </main>
</div>

<script>
function toggleModal(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        el.scrollIntoView({behavior: "smooth"});
    } else {
        el.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
