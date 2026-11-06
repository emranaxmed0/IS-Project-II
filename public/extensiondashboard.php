<?php
session_start();

// Verify user is logged in and is an Extension Officer (role_id = 2)
if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role_id'] ?? 0) !== 2) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';
require_once __DIR__ . '/../app/Services/AIService.php';

$user = $_SESSION['user'];
$officerName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'Officer');
$assignedRegion = htmlspecialchars($user['region'] ?? 'Nakuru / Rift Valley');
$officerId = (int)($user['user_id'] ?? $user['id'] ?? 0);

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

// Fetch crops from DB
$cropController = new CropController($pdo);
$crops = $cropController->getAllCrops();
$selectedCropId = (int)($_GET['crop_id'] ?? ($crops[0]['crop_id'] ?? 1));

// Determine Active Crop Name
$activeCropName = 'Kilimo';
foreach ($crops as $c) {
    if ((int)$c['crop_id'] === $selectedCropId) {
        $activeCropName = $c['crop_name'];
        break;
    }
}

// Handle "+ New Chat" button click
if (isset($_GET['action']) && $_GET['action'] === 'new_chat') {
    $newSessionId = 'chat_' . uniqid() . '_' . time();
    header("Location: extensiondashboard.php?farmer_id={$activeFarmerId}&crop_id={$selectedCropId}&session_id={$newSessionId}");
    exit();
}

// Active Chat Session ID
$sessionId = $_GET['session_id'] ?? null;

// Handle Advisory Query Submission (REAL-TIME AI RESPONSE + PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['query']) && $activeFarmerId > 0) {
    $submittedFarmerId = (int)($_POST['assisted_farmer_id'] ?? 0);
    $queryText = trim($_POST['query']);

    // Verify target farmer belongs to officer's assigned region
    $verifyStmt = $pdo->prepare("SELECT user_id FROM `User` WHERE user_id = ? AND region = ? AND role_id = 1");
    $verifyStmt->execute([$submittedFarmerId, $assignedRegion]);

    if ($verifyStmt->fetch()) {
        if (!$sessionId) {
            $sessionId = 'chat_' . uniqid() . '_' . time();
        }

        // 1. Generate real-time Swahili AI advice
        $aiService = new AIService();
        $aiResponse = $aiService->generateAdvice($queryText, $activeCropName, $assignedRegion);

        // 2. Insert both question and response directly into Query_Log for the farmer
        $insertStmt = $pdo->prepare("
            INSERT INTO Query_Log (user_id, crop_id, session_id, query_text, response_text, language) 
            VALUES (?, ?, ?, ?, ?, 'Swahili')
        ");
        $insertStmt->execute([$submittedFarmerId, $selectedCropId, $sessionId, $queryText, $aiResponse]);
    }

    // 3. Redirect (PRG Pattern) to prevent duplicate submission on refresh
    header("Location: extensiondashboard.php?farmer_id={$submittedFarmerId}&crop_id={$selectedCropId}&session_id={$sessionId}");
    exit();
}

// Fetch all past chat sessions for the active farmer
$pastThreads = [];
if ($activeFarmerId > 0) {
    $threadsStmt = $pdo->prepare("
        SELECT session_id, query_text, created_at 
        FROM Query_Log 
        WHERE user_id = ? AND session_id IS NOT NULL 
        GROUP BY session_id 
        ORDER BY MAX(created_at) DESC
    ");
    $threadsStmt->execute([$activeFarmerId]);
    $pastThreads = $threadsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Default to most recent thread if none selected
if (!$sessionId && !empty($pastThreads)) {
    $sessionId = $pastThreads[0]['session_id'];
}

// Fetch messages for active thread
$messages = [];
if ($activeFarmerId > 0 && $sessionId) {
    $msgStmt = $pdo->prepare("
        SELECT query_id, query_text, response_text, created_at 
        FROM Query_Log 
        WHERE user_id = ? AND session_id = ? 
        ORDER BY created_at ASC
    ");
    $msgStmt->execute([$activeFarmerId, $sessionId]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../app/Views/header.php';
?>

<!-- Main Content -->
<div class="container-fluid py-4 px-4">

    <!-- Active Context & Farmer Selection Banner -->
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
        
        <!-- Sidebar: New Chat, Threads & Location -->
        <div class="col-md-3">
            <a href="extensiondashboard.php?action=new_chat&farmer_id=<?= $activeFarmerId ?>&crop_id=<?= $selectedCropId ?>" 
               class="btn btn-shamba w-100 py-2 fw-bold mb-3 border border-dark shadow-sm <?= $activeFarmer ? '' : 'disabled' ?>">
                + New Chat
            </a>

            <!-- Recent Chat Conversations -->
            <div class="card border-dark shadow-sm mb-4">
                <div class="card-header bg-white border-bottom border-dark py-2">
                    <h6 class="fw-bold mb-0 text-uppercase small">Recent Conversations</h6>
                </div>
                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php if (empty($pastThreads)): ?>
                            <p class="text-muted small p-2 mb-0">Hakuna mazungumzo yaliyopita.</p>
                        <?php else: ?>
                            <?php foreach ($pastThreads as $thread): ?>
                                <a href="extensiondashboard.php?farmer_id=<?= $activeFarmerId ?>&crop_id=<?= $selectedCropId ?>&session_id=<?= $thread['session_id'] ?>" 
                                   class="list-group-item list-group-item-action rounded mb-1 py-2 px-2 text-truncate small <?= ($sessionId === $thread['session_id']) ? 'active bg-shamba text-white' : '' ?>">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= htmlspecialchars(mb_strimwidth($thread['query_text'], 0, 25, "...")) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ENEO Card -->
            <div class="card border-dark shadow-sm">
                <div class="card-header bg-white border-bottom border-dark py-2">
                    <h6 class="fw-bold mb-0 text-uppercase small">ENEO (LOCATION)</h6>
                </div>
                <div class="card-body p-3">
                    <div class="p-2 bg-light border border-dark rounded d-flex align-items-center">
                        <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
                        <span class="fw-semibold text-dark"><?= $assignedRegion ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Advisory Chat Window -->
        <div class="col-md-9">
            <div class="card border-dark shadow-sm h-100 d-flex flex-column" style="min-height: 520px;">
                
                <div class="card-header bg-white border-bottom border-dark py-3">
                    <h5 class="fw-bold mb-0">
                        Mtaalamu wa Kilimo (Assisting: <?= $activeFarmer ? htmlspecialchars($activeFarmer['user_name']) : 'N/A' ?>)
                    </h5>
                </div>

                <!-- Chat Messages Scroll Container -->
                <div class="card-body p-4 flex-grow-1 overflow-auto chat-container" style="max-height: 420px;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted h-100 d-flex flex-column justify-content-center align-items-center my-5">
                            <i class="bi bi-chat-dots fs-1 d-block mb-2 text-shamba"></i>
                            <?php if ($activeFarmer): ?>
                                <p class="mb-0 fw-semibold">Andika swali la kilimo hapa chini kwa niaba ya <strong><?= htmlspecialchars($activeFarmer['user_name']) ?></strong>.</p>
                            <?php else: ?>
                                <p class="mb-0 fw-semibold">Chagua mkulima kwanza ili kuanza mazungumzo.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <!-- Question Bubble -->
                            <div class="d-flex justify-content-end mb-3">
                                <div class="bg-success text-white p-3 rounded-3 col-md-8 border border-dark shadow-sm">
                                    <p class="mb-1"><?= htmlspecialchars($msg['query_text']) ?></p>
                                    <small class="text-white-50 d-block text-end"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></small>
                                </div>
                            </div>

                            <!-- AI Response Bubble -->
                            <?php if (!empty($msg['response_text'])): ?>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="bg-light p-3 rounded-3 col-md-9 border border-dark shadow-sm">
                                        <h6 class="fw-bold text-shamba mb-2">
                                            <i class="bi bi-robot me-1"></i> ShambaGuide AI Response:
                                        </h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($msg['response_text'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input Footer -->
                <div class="card-footer bg-white border-top border-dark p-3">
                    <form method="POST" action="extensiondashboard.php?farmer_id=<?= $activeFarmerId ?>&crop_id=<?= $selectedCropId ?><?= $sessionId ? '&session_id=' . $sessionId : '' ?>" class="d-flex gap-2" id="extensionQueryForm">
                        <input type="hidden" name="assisted_farmer_id" value="<?= $activeFarmerId ?>">
                        <input type="text" name="query" class="form-control border-dark" 
                               placeholder="<?= $activeFarmer ? 'Uliza swali kwa niaba ya ' . htmlspecialchars($activeFarmer['user_name']) . '...' : 'Chagua mkulima kwanza...' ?>" 
                               <?= $activeFarmer ? 'required' : 'disabled' ?>>
                        <button type="submit" class="btn btn-shamba px-4 fw-bold" id="extensionSubmitBtn" <?= $activeFarmer ? '' : 'disabled' ?>>Tuma</button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var container = document.querySelector('.chat-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }

    // Disable button & show spinner during POST submit
    var form = document.getElementById('extensionQueryForm');
    var btn = document.getElementById('extensionSubmitBtn');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Inatuma...';
        });
    }
});
</script>

<?php 
require_once __DIR__ . '/../app/Views/footer.php'; 
?>