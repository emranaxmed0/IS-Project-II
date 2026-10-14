<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$roleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['role'] ?? 1);
if ($roleId !== 3) {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';

$cropController = new CropController($pdo);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['crop_name'] ?? '');
        $season = trim($_POST['growing_season'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($cropController->addCrop($name, $description, $season, $region)) {
            $message = 'Crop added successfully!';
        } else {
            $error = 'Failed to add crop.';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['crop_id'] ?? 0);
        $name = trim($_POST['crop_name'] ?? '');
        $season = trim($_POST['growing_season'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($cropController->updateCrop($id, $name, $description, $season, $region)) {
            $message = 'Crop updated successfully!';
        } else {
            $error = 'Failed to update crop.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['crop_id'] ?? 0);
        if ($cropController->deleteCrop($id)) {
            $message = 'Crop deleted successfully!';
        } else {
            $error = 'Failed to delete crop.';
        }
    }
}

$crops = $cropController->getAllCrops();
$users = $cropController->getAllUsers();

require_once __DIR__ . '/../app/Views/header.php';
?>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="p-4 mb-4 bg-light rounded-3 shadow-sm border border-dark">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-shamba mb-1">Administrative Dashboard</h2>
                <p class="text-muted mb-0">System Overview & Management Interface</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-shamba px-4 py-2 border border-dark" data-bs-toggle="modal" data-bs-target="#addCropModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Crop
                </button>
                <a href="logout.php" class="btn btn-danger px-3 py-2 border border-dark fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm bg-white p-3 border border-dark border-start border-4 border-start-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Crops</span>
                        <h3 class="fw-bold text-shamba mb-0"><?= count($crops) ?></h3>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="bi bi-journal-text"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm bg-white p-3 border border-dark border-start border-4 border-start-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Registered Users</span>
                        <h3 class="fw-bold text-primary mb-0"><?= count($users) ?></h3>
                    </div>
                    <div class="fs-1 text-primary opacity-50"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border border-dark" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border border-dark" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Pills -->
    <ul class="nav nav-pills mb-3" id="adminPills" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold me-2 border border-dark" id="crops-tab" data-bs-toggle="pill" data-bs-target="#crops-pane" type="button" role="tab">
                Crop Directory
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold border border-dark" id="users-tab" data-bs-toggle="pill" data-bs-target="#users-pane" type="button" role="tab">
                User Directory
            </button>
        </li>
    </ul>

    <div class="tab-content" id="adminPillsContent">
        <!-- Crop Directory Tab -->
        <div class="tab-pane fade show active" id="crops-pane" role="tabpanel">
            <div class="card shadow-sm border border-dark">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered border-dark table-hover align-middle mb-0">
                            <thead>
                                <tr style="background-color: #198754; color: #ffffff;">
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">ID</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Crop Name</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Growing Season</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Region</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Description</th>
                                    <th class="py-3 text-center border-dark" style="background-color: #198754; color: #ffffff;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($crops)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted border-dark">No crop records existing.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($crops as $c): ?>
                                        <tr>
                                            <td class="border-dark"><strong>#<?= htmlspecialchars($c['crop_id']) ?></strong></td>
                                            <td class="fw-bold text-shamba border-dark"><?= htmlspecialchars($c['crop_name'] ?? '') ?></td>
                                            <td class="border-dark"><span class="badge bg-success bg-opacity-10 text-success border border-dark"><?= htmlspecialchars($c['growing_season'] ?? 'N/A') ?></span></td>
                                            <td class="border-dark"><?= htmlspecialchars($c['region'] ?? 'N/A') ?></td>
                                            <td class="text-muted small border-dark"><?= htmlspecialchars($c['description'] ?? '') ?></td>
                                            <td class="text-center border-dark">
                                                <button class="btn btn-sm btn-outline-primary me-1 border-dark" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCropModal<?= $c['crop_id'] ?>">
                                                    Edit
                                                </button>
                                                <form method="POST" action="admin.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this crop?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="crop_id" value="<?= $c['crop_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-dark">Delete</button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editCropModal<?= $c['crop_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content border border-dark">
                                                    <form method="POST" action="admin.php">
                                                        <div class="modal-header bg-light border-bottom border-dark">
                                                            <h5 class="modal-title fw-bold text-shamba">Edit Crop Information</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="crop_id" value="<?= $c['crop_id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Crop Name</label>
                                                                <input type="text" name="crop_name" class="form-control border-dark" value="<?= htmlspecialchars($c['crop_name'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Growing Season</label>
                                                                <input type="text" name="growing_season" class="form-control border-dark" value="<?= htmlspecialchars($c['growing_season'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Region</label>
                                                                <input type="text" name="region" class="form-control border-dark" value="<?= htmlspecialchars($c['region'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Description</label>
                                                                <textarea name="description" class="form-control border-dark" rows="3"><?= htmlspecialchars($c['description'] ?? '') ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top border-dark">
                                                            <button type="button" class="btn btn-secondary border-dark" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-shamba border-dark">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Directory Tab -->
        <div class="tab-pane fade" id="users-pane" role="tabpanel">
            <div class="card shadow-sm border border-dark">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered border-dark table-hover align-middle mb-0">
                            <thead>
                                <tr style="background-color: #198754; color: #ffffff;">
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">ID</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Name</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Phone Number</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Region</th>
                                    <th class="py-3 border-dark" style="background-color: #198754; color: #ffffff;">Assigned Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted border-dark">No users registered yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="border-dark"><strong>#<?= htmlspecialchars($u['user_id'] ?? $u['id'] ?? '') ?></strong></td>
                                            <td class="fw-bold border-dark"><?= htmlspecialchars($u['user_name'] ?? $u['name'] ?? $u['username'] ?? 'User') ?></td>
                                            <td class="border-dark"><?= htmlspecialchars($u['phone_number'] ?? $u['phone'] ?? 'N/A') ?></td>
                                            <td class="border-dark"><?= htmlspecialchars($u['region'] ?? 'N/A') ?></td>
                                            <td class="border-dark">
                                                <?php
                                                    $rId = (int)($u['role_id'] ?? $u['role'] ?? 1);
                                                    if ($rId === 3) echo '<span class="badge bg-danger border border-dark">Admin</span>';
                                                    elseif ($rId === 2) echo '<span class="badge bg-primary border border-dark">Extension Officer</span>';
                                                    else echo '<span class="badge bg-secondary border border-dark">Farmer</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Crop Modal -->
<div class="modal fade" id="addCropModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border border-dark">
            <form method="POST" action="admin.php">
                <div class="modal-header bg-light border-bottom border-dark">
                    <h5 class="modal-title fw-bold text-shamba">Add New Crop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Crop Name</label>
                        <input type="text" name="crop_name" class="form-control border-dark" placeholder="e.g. Maize" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Growing Season</label>
                        <input type="text" name="growing_season" class="form-control border-dark" placeholder="e.g. Long Rains" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Region</label>
                        <input type="text" name="region" class="form-control border-dark" placeholder="e.g. Rift Valley" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control border-dark" rows="3" placeholder="Enter crop details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-dark">
                    <button type="button" class="btn btn-secondary border-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-shamba border-dark">Add Crop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/Views/footer.php'; ?>