<?php
/**
 * Patient Self-Registration Page
 * 
 * Allows new patients to create their own accounts.
 * Creates entries in both 'users' and 'patient_profiles' tables.
 */

require '../config/db_connect.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: ../admin-portal/dashboard.php");
            break;
        case 'branch_admin':
            header("Location: ../branch-admin-portal/dashboard.php");
            break;
        case 'doctor':
            header("Location: ../doctor-portal/dashboard.php");
            break;
        case 'staff':
            header("Location: ../staff-portal/dashboard.php");
            break;
        case 'patient':
            header("Location: ../patient-portal/dashboard.php");
            break;
    }
    exit();
}

$error = '';
$success = '';
$patientId = '';

/**
 * Generate a unique Patient ID in format: PAT-YYYY-NNNNN
 */
function generatePatientId($pdo)
{
    $year = date('Y');
    $prefix = "PAT-{$year}-";

    // Get the highest patient_id for this year
    $stmt = $pdo->prepare("
        SELECT patient_id FROM patient_profiles 
        WHERE patient_id LIKE ? 
        ORDER BY patient_id DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $lastId = $stmt->fetchColumn();

    if ($lastId) {
        // Extract number and increment
        $lastNum = (int) substr($lastId, -5);
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }

    return $prefix . str_pad($newNum, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate a UUID v4
 */
function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');

    // Validation
    $errors = [];

    // Name validation
    if (empty($name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Name must be between 2 and 100 characters.";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists. Please login instead.";
        }
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9\-\+\s]{10,20}$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    // Confirm password validation
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Date of birth validation
    if (empty($dateOfBirth)) {
        $errors[] = "Date of birth is required.";
    } else {
        $dob = new DateTime($dateOfBirth);
        $today = new DateTime();
        if ($dob > $today) {
            $errors[] = "Date of birth cannot be in the future.";
        }
        $age = $today->diff($dob)->y;
        if ($age > 120) {
            $errors[] = "Please enter a valid date of birth.";
        }
    }

    // Gender validation
    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Please select a valid gender.";
    }

    // If there are validation errors
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        // All validation passed - create user and patient profile
        try {
            $pdo->beginTransaction();

            // Generate IDs
            $userId = generateUUID();
            $profileId = generateUUID();
            $patientId = generatePatientId($pdo);

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $stmt = $pdo->prepare("
                INSERT INTO users (id, email, password, name, role, phone, is_active, created_at)
                VALUES (?, ?, ?, ?, 'patient', ?, 1, NOW())
            ");
            $stmt->execute([$userId, $email, $hashedPassword, $name, $phone]);

            // Insert into patient_profiles table
            $stmt = $pdo->prepare("
                INSERT INTO patient_profiles (id, user_id, patient_id, date_of_birth, gender, address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$profileId, $userId, $patientId, $dateOfBirth, $gender, $address ?: null]);

            $pdo->commit();

            $success = "Registration successful! Your Patient ID is <strong>{$patientId}</strong>. Please save this for your records.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $error = "Registration failed. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - St. George Hospital</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .register-card {
            background: white;
            padding: 32px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            gap: 10px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #2563eb;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }

        h2 {
            text-align: center;
            color: #374151;
            margin-bottom: 24px;
            font-size: 18px;
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .form-label .required {
            color: #dc2626;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            font-size: 16px;
            margin-top: 8px;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
            line-height: 1.6;
        }

        .success strong {
            font-size: 18px;
            display: block;
            margin: 8px 0;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-card {
                padding: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="register-card">
        <div class="logo">
            <div class="logo-icon">SG</div>
            <div class="logo-text">St. George</div>
        </div>

        <h2>Create Your Patient Account</h2>

        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                ✅
                <?php echo $success; ?>
                <br><br>
                <a href="login.php" class="btn"
                    style="display: inline-block; text-decoration: none; padding: 10px 24px; width: auto;">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" required placeholder="John Smith"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-input" required placeholder="john@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-input" required placeholder="04XX XXX XXX"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <input type="password" name="password" class="form-input" required placeholder="••••••••"
                            minlength="8">
                        <div class="password-hint">Minimum 8 characters</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="form-input" required placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" class="form-input" required
                            value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender <span class="required">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>
                                >Male</option>
                            <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>
                                >Female</option>
                            <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>
                                >Other</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Address <span style="color: #9ca3af;">(Optional)</span></label>
                        <textarea name="address" class="form-textarea"
                            placeholder="123 Main Street, Sydney NSW 2000"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>