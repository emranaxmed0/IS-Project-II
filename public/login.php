<?php
session_start();

// If user is already logged in, redirect based on role
if (isset($_SESSION['user'])) {
    $roleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['role'] ?? 1);
    if ($roleId === 3) {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $authController = new AuthController($pdo);
    $user = $authController->login($identifier, $password);

    if ($user) {
        $_SESSION['user'] = $user;
        $roleId = (int)($user['role_id'] ?? 1);
        if ($roleId === 3) {
            header('Location: admin.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = 'Invalid credentials. Please check your details and try again.';
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
                        <i class="bi bi-box-arrow-in-right text-shamba fs-1"></i>
                        <h3 class="fw-bold mt-2 text-shamba">ShambaGuide</h3>
                        <p class="text-muted">Welcome back! Please log in</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number or Username</label>
                            <input type="text" name="identifier" class="form-control" placeholder="e.g. 0712345678" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-shamba w-100">Log In</button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">Don't have an account? <a href="signup.php" class="text-shamba text-decoration-none fw-semibold">Register here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/Views/footer.php'; ?>