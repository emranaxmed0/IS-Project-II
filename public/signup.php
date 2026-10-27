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
        $message = "Usajili umefanikiwa! Unaweza kuingia.";
        $isSuccess = true;
    } else {
        $message = "Usajili umeshindwa. Huenda namba ya simu imeshasajiliwa.";
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
                        <p class="text-muted">Fungua Akaunti Mpya</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $isSuccess ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="signup.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jina Kamili</label>
                            <input type="text" name="user_name" class="form-control" placeholder="k.m. John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nambari ya Simu</label>
                            <input type="text" name="phone_number" class="form-control" placeholder="k.m. 0712345678" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Neno la Siri</label>
                            <input type="password" name="password" class="form-control" placeholder="Andika Neno" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Eneo</label>
                            <input type="text" name="region" class="form-control" placeholder="k.m. Nakuru" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Jukumu la Akaunti</label>
                            <select name="role_id" class="form-select" required>
                                <option value="1">Farmer</option>
                                <option value="2">Extension Officer</option>
                                <option value="3">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-shamba w-100">Fungua Akaunti</button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">Je, tayari una akaunti? <a href="login.php" class="text-shamba text-decoration-none fw-semibold">Ingia hapa</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/Views/footer.php'; ?>