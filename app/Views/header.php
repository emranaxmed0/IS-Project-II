<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Explicit Relative Path to style.css with Cache Busting -->
    <link rel="stylesheet" href="./css/style.css?v=<?= time(); ?>">
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">

    <!-- Green Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark shamba-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-flower1 fs-4"></i>
                <span>ShambaGuide</span>
            </a>
        </div>
    </nav>