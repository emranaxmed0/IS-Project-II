<?php
session_start();

// Verify user is logged in and is an Extension Officer (role_id = 2)
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role_id'] ?? 0) !== 2) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';

$user = $_SESSION['user'];
$officerName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'Officer');
$assignedRegion = htmlspecialchars($user['region'] ?? 'Nakuru / Rift Valley');
$officerId = (int)($user['user_id'] ?? $user['id'] ?? 0);

$successMessage = '';
$errorMessage = '';

// Fetch all farmers operating in this Extension Officer's assigned region
$farmersStmt = $pdo->prepare("
    SELECT user_id, user_name, phone_number, region 
    FROM `User` 
    WHERE region = ? AND role_id = 1 
    ORDER BY user_name ASC
");
$farmersStmt->execute([$assignedRegion]);
$farmers = $farmersStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine currently active farmer (defaults to first farmer in list if available)
$activeFarmerId = (int)($_REQUEST['farmer_id'] ?? $_REQUEST['assisted_farmer_id'] ?? ($farmers[0]['user_id'] ?? 0));
$activeFarmer = null;

foreach ($farmers as $f) {
    if ((int)$f['user_id'] === $activeFarmerId) {
        $activeFarmer = $f;
        break;
    }
}

// Handle Advisory Query Submission on Behalf of Selected Farmer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $submittedFarmerId = (int)($_POST['assisted_farmer_id'] ?? 0);
    $queryText = trim($_POST['query'] ?? '');

    if ($submittedFarmerId > 0 && !empty($queryText)) {
        // Verify the target farmer belongs to the officer's region
        $verifyStmt = $pdo->prepare("SELECT user_id FROM `User` WHERE user_id = ? AND region = ? AND role_id = 1");
        $verifyStmt->execute([$submittedFarmerId, $assignedRegion]);

        if ($verifyStmt->fetch()) {
            // Save advisory request into Query_Log table for the selected farmer
            $insertStmt = $pdo->prepare("
                INSERT INTO Query_Log (user_id, query_text, language) 
                VALUES (?, ?, 'Swahili')
            ");
            
            if ($insertStmt->execute([$submittedFarmerId, $queryText])) {
                $successMessage = "Swali limetumwa kikamilifu kwa niaba ya mkulima.";
            } else {
                $errorMessage = "Imeshindwa kuhifadhi swali kwenye kanzidata.";
            }
        } else {
            $errorMessage = "Mkulima aliyeteuliwa hayupo katika eneo lako.";
        }
    } else {
        $errorMessage = "Tafadhali chagua mkulima na uandike swali kabla ya kutuma.";
    }
}

// Fetch Query Logs for the active farmer to display past advisory history
$queryLogs = [];
if ($activeFarmerId > 0) {
    $logsStmt = $pdo->prepare("
        SELECT query_id, query_text, response_text, created_at 
        FROM Query_Log 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $logsStmt->execute([$activeFarmerId]);
    $queryLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch crops from DB for sidebar display
$cropController = new CropController($pdo);
$crops = $cropController->getAllCrops();
$selectedCropId = (int)($_GET['crop_id'] ?? ($crops[0]['crop_id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide — Extension Officer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="bg-light">

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-shamba border-bottom border-dark px-3 py-2">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="extensiondashboard.php">
                <i class="bi bi-person-badge me-2"></i>ShambaGuide — Extension Portal
            </a>
            <div class="d-flex align-items-center gap-4 text-white ms-auto">
                <a href="calendar.php" class="text-white text-decoration-none fw-semibold">Kalenda</a>
                <a href="crops&disease.php" class="text-white text-decoration-none fw-semibold">Wadudu</a>
                <div class="dropdown">
                    <button class="btn btn-sm text-white dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                        <?= $officerName ?> (Officer)
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

        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show border border-dark mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show border border-dark mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Active Context Banner -->
        <div class="card border-dark shadow-sm mb-4">
            <div class="card-body p-3 bg-white d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-shamba px-3 py-2 border border-dark fs-6">
                        <i class="bi bi-geo-alt-fill me-1"></i> Eneo: <?= $assignedRegion ?>
                    </span>
                    <?php if ($activeFarmer): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 border border-dark fs-6">
                            <i class="bi bi-person-fill me-1"></i> Unamsaidia: <strong><?= htmlspecialchars($activeFarmer['user_name']) ?></strong> (<?= htmlspecialchars($activeFarmer['phone_number']) ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary text-white px-3 py-2 border border-dark fs-6">
                            Hakuna Mkulima Aliyeteuliwa
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Farmer Selector Dropdown -->
                <form method="GET" action="extensiondashboard.php" class="d-flex align-items-center gap-2">
                    <?php if ($selectedCropId): ?>
                        <input type="hidden" name="crop_id" value="<?= $selectedCropId ?>">
                    <?php endif; ?>
                    <label for="farmer_id" class="fw-semibold mb-0 text-nowrap">Chagua Mkulima:</label>
                    <select name="farmer_id" id="farmer_id" class="form-select border-dark" onchange="this.form.submit()">
                        <?php if (empty($farmers)): ?>
                            <option value="">Hakuna wakulima katika eneo hili</option>
                        <?php else: ?>
                            <?php foreach ($farmers as $f): ?>
                                <option value="<?= $f['user_id'] ?>" <?= ((int)$f['user_id'] === $activeFarmerId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['user_name']) ?> (<?= htmlspecialchars($f['phone_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Sidebar (Crops & Location) -->
            <div class="col-md-3">
                
                <!-- MAZAO Card -->
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
                                    <a href="extensiondashboard.php?farmer_id=<?= $activeFarmerId ?>&crop_id=<?= $c['crop_id'] ?>" 
                                       class="list-group-item list-group-item-action crop-item rounded mb-2 py-2 px-3 d-flex align-items-center <?= ($selectedCropId === (int)$c['crop_id']) ? 'active' : '' ?>">
                                        <i class="bi bi-leaf text-success me-2"></i>
                                        <span><?= htmlspecialchars($c['crop_name']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ENEO Card -->
                <div class="card border-dark shadow-sm">
                    <div class="card-header bg-white border-bottom border-dark py-2">
                        <h6 class="fw-bold mb-0 text-uppercase">ENEO (LOCATION)</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="p-2 bg-light border border-dark rounded d-flex align-items-center">
                            <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
                            <span class="fw-semibold text-dark"><?= $assignedRegion ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Main Text Advisory Assistant -->
            <div class="col-md-9">
                <div class="card border-dark shadow-sm h-100 d-flex flex-column">
                    
                    <div class="card-header bg-white border-bottom border-dark py-3">
                        <h5 class="fw-bold mb-0">
                            Mtaalamu wa Kilimo (Assisting: <?= $activeFarmer ? htmlspecialchars($activeFarmer['user_name']) : 'N/A' ?>)
                        </h5>
                    </div>

                    <!-- Chat / Query History Container -->
                    <div class="card-body p-4 flex-grow-1 overflow-auto chat-container">
                        <?php if (empty($queryLogs)): ?>
                            <div class="text-center text-muted h-100 d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-chat-dots fs-1 d-block mb-2 text-shamba"></i>
                                <?php if ($activeFarmer): ?>
                                    <p class="mb-0 fw-semibold">Andika swali la kilimo hapa chini kwa niaba ya <strong><?= htmlspecialchars($activeFarmer['user_name']) ?></strong>.</p>
                                <?php else: ?>
                                    <p class="mb-0 fw-semibold">Sajili au chagua mkulima kwanza ili kuanza kuuliza maswali.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($queryLogs as $log): ?>
                                <!-- User / Extension Query Bubble -->
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="chat-bubble-user p-3 rounded-3 col-9">
                                        <p class="mb-1"><?= htmlspecialchars($log['query_text']) ?></p>
                                        <small class="text-muted d-block text-end"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></small>
                                    </div>
                                </div>

                                <!-- Response Bubble (if response generated) -->
                                <?php if (!empty($log['response_text'])): ?>
                                    <div class="d-flex justify-content-start mb-4">
                                        <div class="chat-bubble-ai p-3 rounded-3 col-10">
                                            <h6 class="fw-bold text-shamba mb-2">
                                                <i class="bi bi-robot me-1"></i> ShambaGuide AI Response:
                                            </h6>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($log['response_text'])) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Input Footer -->
                    <div class="card-footer bg-white border-top border-dark p-3">
                        <form method="POST" action="extensiondashboard.php" class="d-flex gap-2">
                            <input type="hidden" name="assisted_farmer_id" value="<?= $activeFarmerId ?>">
                            <input type="text" name="query" class="form-control border-dark" 
                                   placeholder="<?= $activeFarmer ? 'Uliza swali kwa niaba ya ' . htmlspecialchars($activeFarmer['user_name']) . '...' : 'Chagua mkulima kwanza...' ?>" 
                                   <?= $activeFarmer ? 'required' : 'disabled' ?>>
                            <button type="submit" class="btn btn-shamba px-4 fw-bold" <?= $activeFarmer ? '' : 'disabled' ?>>Tuma</button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>