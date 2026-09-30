<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$auth = new AuthController($pdo);
$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = $auth->register(
        $_POST['role_id'],
        $_POST['user_name'],
        $_POST['phone_number'],
        $_POST['password'],
        $_POST['region']
    );

    if ($success) {
        $message = "Registration successful! You can now log in.";
        $isSuccess = true;
    } else {
        $message = "Registration failed. Phone number may already be registered.";
    }
}

require_once __DIR__ . '/../app/Views/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card auth-card p-3">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill text-shamba fs-1"></i>
                        <h3 class="fw-bold mt-2 text-shamba">ShambaGuide</h3>
                        <p class="text-muted">Create a new account</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $isSuccess ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="signup.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="user_name" class="form-control" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" placeholder="e.g. 0712345678" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Region</label>
                            <input type="text" name="region" class="form-control" placeholder="e.g. Nakuru" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Account Role</label>
                            <select name="role_id" class="form-select" required>
                                <option value="1">Farmer</option>
                                <option value="2">Extension Officer</option>
                                <option value="3">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-shamba w-100">Create Account</button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">Already have an account? <a href="login.php" class="text-shamba text-decoration-none fw-semibold">Log in here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/Views/footer.php'; ?>