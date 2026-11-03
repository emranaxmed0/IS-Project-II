<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$userName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Primary ShambaGuide Custom Styles -->
    <style>
        :root {
            --sg-green-primary: #2e7d32;
            --sg-green-dark: #1b5e20;
            --sg-green-light: #e8f5e9;
            --sg-green-subtle: #a5d6a7;
            --sg-bg-body: #f1f8e9;
        }

        body {
            background-color: var(--sg-bg-body) !important;
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            color: #2c3e50;
        }

        .shamba-navbar {
            background-color: var(--sg-green-primary) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .auth-card {
            border: none !important;
            border-top: 5px solid var(--sg-green-primary) !important;
            border-radius: 12px !important;
            background-color: #ffffff !important;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.12) !important;
        }

        .text-shamba {
            color: var(--sg-green-primary) !important;
        }

        .bg-shamba-light {
            background-color: var(--sg-green-light) !important;
        }

        .btn-shamba {
            background-color: var(--sg-green-primary) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 600 !important;
        }

        .btn-shamba:hover {
            background-color: var(--sg-green-dark) !important;
            color: #ffffff !important;
        }
    </style>

    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time(); ?>">
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">

    <!-- Top Green Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark shamba-navbar py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <span>ShambaGuide</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shambaNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="shambaNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3 text-white">
                    <a href="dashboard.php" class="nav-link text-white fw-semibold">
                        Dashboard
                    </a>
                    <a href="calendar.php" class="nav-link text-white fw-semibold">
                        Kalenda
                    </a>
                    <a href="crops&disease.php" class="nav-link text-white fw-semibold">
                        Wadudu na Magonjwa
                    </a>

                    <?php if ($user): ?>
                        <div class="nav-item dropdown ms-lg-2">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= $userName ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-dark shadow-sm">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger fw-semibold" href="logout.php">Logout</a></li>
                            </ul>
                            <?php endif; ?>
            </div>
        </div>
    </nav>