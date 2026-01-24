<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Function to check role permission
function require_role($allowed_roles) {
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        // Redirect to unauthorized page or their own dashboard
        header("Location: ../unauthorized.php"); 
        exit();
    }
}
?>
