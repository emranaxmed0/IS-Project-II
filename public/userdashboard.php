<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';

// Instantiate controller & fetch crops from DB
$cropController = new CropController($pdo);
$crops = $cropController->getAllCrops();

// Fetch current user details
$user = $_SESSION['user'];
$userName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'Kamau');
$userRegion = htmlspecialchars($user['region'] ?? 'Nakuru / Rift Valley');

// Active crop selection (defaults to first crop from DB)
$selectedCropId = (int)($_GET['crop_id'] ?? ($crops[0]['crop_id'] ?? 0));
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
                <a href="calendar.php" class="text-white text-decoration-none fw-semibold">Kalenda</a>
                <a href="crops&disease.php" class="text-white text-decoration-none fw-semibold">Wadudu</a>
                <div class="dropdown">
                    <button class="btn btn-sm text-white dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $userName ?> (Profile)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-dark">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
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
                <!-- MAZAO (CROPS) Card - Fetched Dynamically from DB -->
                <div class="card border-dark shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-dark py-2">
                        <h6 class="fw-bold mb-0 text-uppercase">MAZAO (CROPS)</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush">
                            <?php if (empty($crops)): ?>
                                <p class="text-muted small p-2 mb-0">Hakuna mazao kwenye kanzidata.</p>
                            <?php else: ?>
                                <?php foreach ($crops as $c): ?>
                                    <a href="userdashboard.php?crop_id=<?= $c['crop_id'] ?>" 
                                       class="list-group-item list-group-item-action crop-item rounded mb-2 py-2 px-3 d-flex align-items-center <?= ($selectedCropId === (int)$c['crop_id']) ? 'active' : '' ?>">
                                        <i class="bi bi-leaf text-success me-2"></i>
                                        <span><?= htmlspecialchars($c['crop_name']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ENEO (LOCATION) Card - Static Display of Registered Region -->
                <div class="card border-dark shadow-sm">
                    <div class="card-header bg-white border-bottom border-dark py-2">
                        <h6 class="fw-bold mb-0 text-uppercase">ENEO (LOCATION)</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="p-2 bg-light border border-dark rounded d-flex align-items-center">
                            <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
                            <span class="fw-semibold text-dark"><?= $userRegion ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Advisory Area -->
            <div class="col-md-9">
                <div class="card border-dark shadow-sm h-100 d-flex flex-column">
                    
                    <div class="card-header bg-white border-bottom border-dark py-3">
                        <h5 class="fw-bold mb-0">Mtaalamu wa Kilimo (Swahili Text Assistant)</h5>
                    </div>

                    <!-- Chat Container Placeholder -->
                    <div class="card-body p-4 flex-grow-1 overflow-auto chat-container d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="bi bi-chat-dots fs-1 d-block mb-2 text-shamba"></i>
                            <p class="mb-0 fw-semibold">Andika swali lako hapa chini ili kupata ushauri wa kilimo.</p>
                        </div>
                    </div>

                    <!-- Input Footer -->
                    <div class="card-footer bg-white border-top border-dark p-3">
                        <form method="POST" action="userdashboard.php" class="d-flex gap-2">
                            <input type="text" name="query" class="form-control border-dark" placeholder="Andika swali lako la kilimo hapa..." required>
                            <button type="submit" class="btn btn-shamba px-4 fw-bold">Tuma</button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Corrected Bootstrap JS Bundle Link -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>