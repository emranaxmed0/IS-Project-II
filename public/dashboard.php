<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Extract role_id from session (defaults to 1 = Farmer)
$roleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['role'] ?? 1);

// Route based on user role
switch ($roleId) {
    case 1: // Farmer / Smallholder
        header('Location: userdashboard.php');
        exit();

    case 2: // Extension Officer
        header('Location: extensiondashboard.php');
        exit();

    case 3: // Admin
        header('Location: admin.php');
        exit();

    default:
        // Fallback for unexpected role values
        header('Location: userdashboard.php');
        exit();
}