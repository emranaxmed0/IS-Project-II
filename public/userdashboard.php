<?php
session_start();

if (!isset(\$_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';

$cropController = new CropController($pdo);
$crops =$cropController->getAllCrops();

\$user = $_SESSION['user'];$userName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'Kamau');
$userRegion = htmlspecialchars($user['region'] ?? 'Nakuru / Rift Valley');

// Active crop selection (defaults to first crop)
\$selectedCropId = (int)($_GET['crop_id'] ?? ($crops[0]['crop_id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide — Text Advisory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="bg-light">

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-shamba border-bottom border-dark px-3 py-2">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="userdashboard.php">
                <i class="bi bi-sprout me-2"></i>ShambaGuide
            </a>
            <div class="d-flex align-items-center gap-4 text-white ms-auto">
                <a href="#" class="text-white text-decoration-none fw-semibold">Kalenda</a>
                <a href="#" class="text-white text-decoration-none fw-semibold">Wadudu</a>
                <div class="dropdown">
                    <button class="btn btn-sm text-white dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                        <?= \$userName ?> (Profile)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-dark">
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4 px-4">
        <div class="row g-4">
            
            <!-- Sidebar (Crops & Location) -->
            <div class="col-md-3">
                <!-- Mazao Card -->
                <div class="card border-dark shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-dark py-2">
                        <h6 class="fw-bold mb-0 text-uppercase">MAZAO (CROPS)</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush">
                            <?php if (empty(\$crops)): ?>
                                <p class="text-muted small p-2 mb-0">Hakuna mazao.</p>
                            <?php else: ?>
                                <?php foreach ($crops as$c): ?>
                                    <a href="userdashboard.php?crop_id=<?= \$c['crop_id'] ?>" 
                                       class="list-group-item list-group-item-action crop-item rounded mb-2 py-2 px-3 d-flex align-items-center <?= ($selectedCropId === (int)$c['crop_id']) ? 'active' : '' ?>">
                                        <i class="bi bi-leaf text-success me-2"></i>
                                        <span><?= htmlspecialchars(\$c['crop_name']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Eneo Card -->
                <div class="card border-dark shadow-sm">
                    <div class="card-header bg-white border-bottom border-dark py-2">
                        <h6 class="fw-bold mb-0 text-uppercase">ENEO (LOCATION)</h6>
                    </div>
                    <div class="card-body p-3">
                        <select class="form-select border-dark">
                            <option selected><?= \$userRegion ?></option>
                            <option value="Nakuru">Nakuru / Rift Valley</option>
                            <option value="Uasin Gishu">Uasin Gishu</option>
                            <option value="Trans Nzoia">Trans Nzoia</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main Advisory Area -->
            <div class="col-md-9">
                <div class="card border-dark shadow-sm h-100 d-flex flex-column">