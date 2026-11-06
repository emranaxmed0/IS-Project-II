<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$farmerMessages = [];
try {
    $farmerId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    
    if ($farmerId > 0) {
        $msgStmt = $pdo->prepare("
            SELECT m.*, u.user_name AS sender_name 
            FROM Messages m
            JOIN `User` u ON m.sender_id = u.user_id
            WHERE m.receiver_id = :farmer_id
            ORDER BY m.created_at DESC
        ");
        $msgStmt->execute([':farmer_id' => $farmerId]);
        $farmerMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Soft fallback if table structure is initializing
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/CropController.php';
require_once __DIR__ . '/../app/Services/AIService.php';

$userId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
$user = $_SESSION['user'];
$userName = htmlspecialchars($user['user_name'] ?? $user['name'] ?? 'Mkulima');
$userRegion = htmlspecialchars($user['region'] ?? 'Nakuru');

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

// Handle "New Chat" button click
if (isset($_GET['action']) && $_GET['action'] === 'new_chat') {
    $newSessionId = 'chat_' . uniqid() . '_' . time();
    header("Location: userdashboard.php?crop_id=" . $selectedCropId . "&session_id=" . $newSessionId);
    exit();
}

// Active Chat Session ID
$sessionId = $_GET['session_id'] ?? null;

// Handle New Question Submission (REAL-TIME AI RESPONSE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['query'])) {
    $queryText = trim($_POST['query']);
    
    if (!$sessionId) {
        $sessionId = 'chat_' . uniqid() . '_' . time();
    }

    // 1. Generate response immediately using AIService
    $aiService = new AIService();
    $aiResponse = $aiService->generateAdvice($queryText, $activeCropName, $userRegion);

    // 2. Save both Question AND AI Response directly to Database
    $insertStmt = $pdo->prepare("
        INSERT INTO Query_Log (user_id, crop_id, session_id, query_text, response_text, language) 
        VALUES (?, ?, ?, ?, ?, 'Swahili')
    ");
    $insertStmt->execute([$userId, $selectedCropId, $sessionId, $queryText, $aiResponse]);

    // 3. Redirect back to display full thread instantly
    header("Location: userdashboard.php?crop_id=" . $selectedCropId . "&session_id=" . $sessionId);
    exit();
}

// Fetch all distinct chat sessions for sidebar
$threadsStmt = $pdo->prepare("
    SELECT session_id, query_text, created_at 
    FROM Query_Log 
    WHERE user_id = ? AND session_id IS NOT NULL 
    GROUP BY session_id 
    ORDER BY MAX(created_at) DESC
");
$threadsStmt->execute([$userId]);
$pastThreads = $threadsStmt->fetchAll(PDO::FETCH_ASSOC);

// Default to most recent thread if none selected
if (!$sessionId && !empty($pastThreads)) {
    $sessionId = $pastThreads[0]['session_id'];
}

// Fetch messages for current active thread
$messages = [];
if ($sessionId) {
    $msgStmt = $pdo->prepare("
        SELECT query_id, query_text, response_text, created_at 
        FROM Query_Log 
        WHERE user_id = ? AND session_id = ? 
        ORDER BY created_at ASC
    ");
    $msgStmt->execute([$userId, $sessionId]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../app/Views/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Sidebar: Chat Threads & Location -->
        <div class="col-md-3">
            <a href="userdashboard.php?action=new_chat&crop_id=<?= $selectedCropId ?>" class="btn btn-shamba w-100 py-2 fw-bold mb-3 border border-dark shadow-sm">
                + New Chat
            </a>

            <div class="card border-dark shadow-sm mb-4">
                <div class="card-header bg-white border-bottom border-dark py-2">
                    <h6 class="fw-bold mb-0 text-uppercase small">Recent Conversations</h6>
                </div>
                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php if (empty($pastThreads)): ?>
                            <p class="text-muted small p-2 mb-0">No past conversations.</p>
                        <?php else: ?>
                            <?php foreach ($pastThreads as $thread): ?>
                                <a href="userdashboard.php?crop_id=<?= $selectedCropId ?>&session_id=<?= $thread['session_id'] ?>" 
                                   class="list-group-item list-group-item-action rounded mb-1 py-2 px-2 text-truncate small <?= ($sessionId === $thread['session_id']) ? 'active bg-shamba text-white' : '' ?>">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= htmlspecialchars(mb_strimwidth($thread['query_text'], 0, 25, "...")) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card border-dark shadow-sm">
                <div class="card-header bg-white border-bottom border-dark py-2">
                    <h6 class="fw-bold mb-0 text-uppercase small">ENEO (LOCATION)</h6>
                </div>
                <div class="card-body p-3">
                    <div class="p-2 bg-light border border-dark rounded d-flex align-items-center">
                        <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
                        <span class="fw-semibold text-dark"><?= $userRegion ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-dark shadow-sm mb-4">
    <div class="card-header bg-white border-bottom border-dark py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-shamba">
            <i class="bi bi-envelope-paper-fill me-2"></i> Majibu na Ushauri Kutoka kwa Afisa wa Kilimo
        </h5>
        <?php if (!empty($farmerMessages)): ?>
            <span class="badge bg-shamba px-3 py-2 border border-dark">
                <?= count($farmerMessages) ?> Ujumbe
            </span>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-3" style="max-height: 350px; overflow-y: auto;">
        <?php if (!empty($farmerMessages)): ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($farmerMessages as $msg): ?>
                    <div class="p-3 rounded border border-dark bg-light shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-2">
                            <span class="fw-bold text-dark">
                                <i class="bi bi-person-badge me-1"></i> Afisa: <?= htmlspecialchars($msg['sender_name']) ?>
                            </span>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($msg['created_at'])) ?>
                            </small>
                        </div>
                        
                        <?php if (!empty($msg['crop_name'])): ?>
                            <div class="mb-2">
                                <span class="badge bg-success text-white border border-dark">
                                    Zao: <?= htmlspecialchars($msg['crop_name']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-0 text-dark" style="white-space: pre-line;">
                            <?= htmlspecialchars($msg['message_text']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-shamba"></i>
                <p class="mb-0 fw-semibold">Huna ujumbe mpya kutoka kwa Afisa wa Kilimo kwa sasa.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

        <!-- Main Chat Area -->
        <div class="col-md-9">
            <div class="card border-dark shadow-sm h-100 d-flex flex-column" style="min-height: 520px;">
                
                <div class="card-header bg-white border-bottom border-dark py-3">
                    <h5 class="fw-bold mb-0">Mtaalamu wa Kilimo (Swahili Assistant)</h5>
                </div>

                <div class="card-body p-4 flex-grow-1 overflow-auto chat-container" style="max-height: 420px;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted h-100 d-flex flex-column justify-content-center align-items-center my-5">
                            <i class="bi bi-chat-dots fs-1 d-block mb-2 text-shamba"></i>
                            <p class="mb-0 fw-semibold">Andika swali lako hapa chini ili kuanza mazungumzo.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <!-- Question -->
                            <div class="d-flex justify-content-end mb-3">
                                <div class="bg-success text-white p-3 rounded-3 col-md-8 border border-dark shadow-sm">
                                    <p class="mb-1"><?= htmlspecialchars($msg['query_text']) ?></p>
                                    <small class="text-white-50 d-block text-end"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></small>
                                </div>
                            </div>

                            <!-- AI Response -->
                            <?php if (!empty($msg['response_text'])): ?>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="bg-light p-3 rounded-3 col-md-9 border border-dark shadow-sm">
                                        <h6 class="fw-bold text-shamba mb-2">
                                            <i class="bi bi-robot me-1"></i> ShambaGuide AI:
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
                    <form method="POST" action="userdashboard.php?crop_id=<?= $selectedCropId ?><?= $sessionId ? '&session_id=' . $sessionId : '' ?>" class="d-flex gap-2" id="queryForm">
                        <input type="text" name="query" class="form-control border-dark" placeholder="Andika swali lako la kilimo hapa..." required>
                        <button type="submit" class="btn btn-shamba px-4 fw-bold" id="submitBtn">Tuma</button>
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

    // Disable button & show spinner on submit so user knows it's thinking
    var form = document.getElementById('queryForm');
    var btn = document.getElementById('submitBtn');
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