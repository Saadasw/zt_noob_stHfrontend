<?php
require '../config/db_connect.php';
session_start();

$error = '';

if (isset($_SESSION['user_id'])) {
    // Redirect existing session
    switch($_SESSION['user_role']) {
        case 'admin': header("Location: ../admin-portal/dashboard.php"); break;
        case 'doctor': header("Location: ../doctor-portal/dashboard.php"); break;
        case 'staff': header("Location: ../staff-portal/dashboard.php"); break;
        case 'patient': header("Location: ../patient-portal/dashboard.php"); break;
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT id, name, password, role FROM users WHERE email = :email AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'admin': header("Location: ../admin-portal/dashboard.php"); break;
                case 'doctor': header("Location: ../doctor-portal/dashboard.php"); break;
                case 'staff': header("Location: ../staff-portal/dashboard.php"); break;
                case 'patient': header("Location: ../patient-portal/dashboard.php"); break;
                default: $error = "Invalid role assigned.";
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - St. George Hospital</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: white; padding: 32px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        .logo { display: flex; align-items: center; justify-content: center; margin-bottom: 24px; gap: 10px; }
        .logo-icon { width: 40px; height: 40px; background: #2563eb; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px; }
        .logo-text { font-size: 24px; font-weight: bold; color: #1f2937; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; text-align: center;}
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <div class="logo-icon">SG</div>
            <div class="logo-text">St. George</div>
        </div>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" required placeholder="name@stgeorgehospital.com.au">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p style="text-align: center; margin-top: 16px; font-size: 14px; color: #6b7280;">
            Forgot password? Contact IT Support.
        </p>
    </div>
</body>
</html>
