<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';


$auth = new AuthController($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $auth->login($_POST['phone_number'], $_POST['password']);
    
    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid phone number or password.";
    }
}

require_once __DIR__ . '/../app/Views/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card auth-card shadow-sm p-3">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right text-shamba fs-1"></i>
                        <h3 class="fw-bold mt-2 text-shamba">ShambaGuide</h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" placeholder="0712345678" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-shamba w-100 py-2">Log In</button>
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