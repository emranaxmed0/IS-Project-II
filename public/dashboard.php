<?php
session_start();

// 1. Session Protection Check
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// 2. Extract role_id safely
$user = $_SESSION['user'];
$roleId = (int)($user['role_id'] ?? $_SESSION['role_id'] ?? 0);

// 3. Route to the correct dashboard based on role
switch ($roleId) {
    case 1:
        // Farmer Role -> userdashboard.php
        header('Location: userdashboard.php');
        exit();

    case 2:
        // Extension Officer Role -> extensiondashboard.php
        header('Location: extensiondashboard.php');
        exit();

    case 3:
        // Admin Role -> admindashboard.php
        header('Location: admin.php');
        exit();

    default:
        // Fallback for missing or invalid roles
        session_destroy();
        header('Location: login.php?error=invalid_role');
        exit();
}