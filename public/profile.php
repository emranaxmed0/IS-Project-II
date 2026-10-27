<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'];
$userId = (int)($user['user_id'] ?? $user['id'] ?? 0);

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Update Profile Details
    if ($action === 'update_profile') {
        $userName = trim($_POST['user_name'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $region = trim($_POST['region'] ?? '');

        if (!empty($userName) && !empty($phoneNumber) && !empty($region)) {
            // Check if phone number is registered to another user
            $checkStmt = $pdo->prepare("SELECT user_id FROM user WHERE phone_number = ? AND user_id != ?");
            $checkStmt->execute([$phoneNumber, $userId]);

            if ($checkStmt->fetch()) {
                $errorMessage = "Phone number is already registered to another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE user SET user_name = ?, phone_number = ?, region = ? WHERE user_id = ?");
                if ($stmt->execute([$userName, $phoneNumber, $region, $userId])) {
                    $_SESSION['user']['user_name'] = $userName;
                    $_SESSION['user']['phone_number'] = $phoneNumber;
                    $_SESSION['user']['region'] = $region;
                    $user = $_SESSION['user'];
                    $successMessage = "Profile details updated successfully!";
                } else {
                    $errorMessage = "Failed to update profile details.";
                }
            }
        } else {
            $errorMessage = "All profile fields are required.";
        }
    }

    // Action 2: Change Password
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
            if ($newPassword !== $confirmPassword) {
                $errorMessage = "New passwords do not match.";
            } else {
                $stmt = $pdo->prepare("SELECT password FROM user WHERE user_id = ?");
                $stmt->execute([$userId]);
                $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($dbUser && (password_verify($currentPassword, $dbUser['password']) || $currentPassword === $dbUser['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE user SET password = ? WHERE user_id = ?");
                    if ($updateStmt->execute([$hashedPassword, $userId])) {
                        $successMessage = "Password changed successfully!";
                    } else {
                        $errorMessage = "Failed to update password.";
                    }
                } else {
                    $errorMessage = "Incorrect current password.";
                }
            }
        } else {
            $errorMessage = "All password fields are required.";
        }
    }
}

$userName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? '');
$userPhone = htmlspecialchars($user['phone_number'] ?? '');
$userRegion = htmlspecialchars($user['region'] ?? '');
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShambaGuide — User Profile</title>
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
                <a href="userdashboard.php" class="text-white text-decoration-none fw-semibold">Dashboard</a>
                <a href="calendar.php" class="text-white text-decoration-none fw-semibold">Kalenda</a>
                <a href="crops&disease.php" class="text-white text-decoration-none fw-semibold">Wadudu</a>
                <a href="logout.php" class="btn btn-sm btn-outline-light fw-bold">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show border border-dark mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show border border-dark mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Information Card -->
                <div class="card border-dark shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom border-dark py-3">
                        <h5 class="fw-bold mb-0 text-shamba"><i class="bi bi-person-gear me-2"></i>Update Profile Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Full Name (Jina Kamili)</label>
                                <input type="text" name="user_name" class="form-control border-dark" value="<?= $userName ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Phone Number (Nambari ya Simu)</label>
                                <input type="text" name="phone_number" class="form-control border-dark" value="<?= $userPhone ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Region / County (Mkoa / Eneo)</label>
                                <input type="text" name="region" class="form-control border-dark" value="<?= $userRegion ?>" required>
                            </div>
                            <button type="submit" class="btn btn-shamba fw-bold w-100">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="card border-dark shadow-sm">
                    <div class="card-header bg-white border-bottom border-dark py-3">
                        <h5 class="fw-bold mb-0 text-shamba"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control border-dark" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Password</label>
                                <input type="password" name="new_password" class="form-control border-dark" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control border-dark" required>
                            </div>
                            <button type="submit" class="btn btn-shamba fw-bold w-100">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>