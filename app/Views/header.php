<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide - Dashboard</title>

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
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">

    <!-- Top Green Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark shamba-navbar py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 fs-4" href="index.php">
                <i class="bi bi-flower1"></i>
                <span>ShambaGuide</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="userdashboard.php" class="btn btn-sm btn-outline-light px-3 me-2">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>