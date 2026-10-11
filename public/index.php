<?php
session_start();

// 1. Session Protection Check
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// Helper function to render role names safely
function getRoleName($roleId) {
    switch ((int)$roleId) {
        case 1:
            return 'Farmer';
        case 2:
            return 'Extension Officer';
        case 3:
            return 'Admin';
        default:
            return 'Farmer';
    }
}

require_once __DIR__ . '/../app/Views/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header Banner with Link to Dashboard -->
    <div class="card auth-card shadow-sm mb-4">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-shamba-light text-shamba border border-success mb-2 px-3 py-2 fs-6 fw-semibold">
                    <i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars(getRoleName($user['role_id'] ?? 1)) ?>
                </span>
                <h2 class="fw-bold mb-1 text-dark">Welcome back, <?= htmlspecialchars($user['user_name'] ?? 'User') ?>!</h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-geo-alt-fill text-shamba me-1"></i> Region: <strong><?= htmlspecialchars($user['region'] ?? 'N/A') ?></strong> |
                    <i class="bi bi-telephone-fill text-shamba ms-2 me-1"></i> Phone: <strong><?= htmlspecialchars($user['phone_number'] ?? 'N/A') ?></strong>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-shamba px-4 fw-semibold">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-danger fw-semibold px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Log Out
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Advisory & Service Cards -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm auth-card p-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-shamba-light text-shamba rounded-circle p-3 me-3">
                            <i class="bi bi-sun fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Weather & Climate</h5>
                    </div>
                    <p class="text-muted">Check local weather forecasts and seasonal planting advice for <?= htmlspecialchars($user['region'] ?? 'your region') ?>.</p>
                    <a href="dashboard.php" class="text-shamba fw-bold text-decoration-none">View Weather Advisory &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm auth-card p-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-shamba-light text-shamba rounded-circle p-3 me-3">
                            <i class="bi bi-journal-medical fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Crop Protection</h5>
                    </div>
                    <p class="text-muted">Identify crop diseases, pests, and get recommended remedies from local agricultural experts.</p>
                    <a href="dashboard.php" class="text-shamba fw-bold text-decoration-none">Explore Crop Guide &rarr;</a>
                </div>
            </div>
        </div>

    
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/Views/footer.php'; ?>