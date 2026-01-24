<?php
require '../config/db_connect.php';

// Check if any admin exists
$stmt = $pdo->query("SELECT count(*) FROM users WHERE role = 'admin'");
$count = $stmt->fetchColumn();

if ($count > 0) {
    die("Admin users already exist. Please login to create more users.");
}

// Create default admin
$email = 'admin@stgeorgehospital.com';
$password = 'admin123';
$name = 'System Administrator';
$role = 'admin';
$id = 'USR-ADMIN-001';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (id, email, password, name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $email, $hashed_password, $name, $role]);
    
    echo "<h1>Success!</h1>";
    echo "<p>Admin user created.</p>";
    echo "<p>Email: <strong>$email</strong></p>";
    echo "<p>Password: <strong>$password</strong></p>";
    echo "<p><a href='../auth/login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    die("Error creating admin: " . $e->getMessage());
}
?>
