<?php
session_start();

// 1. Flexible Session Auth Check
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? null;

if (!$userId) {
    header("Location: index.php");
    exit();
}

// Database Connection
require_once __DIR__ . '/../config/db.php';

// 2. Region Switcher (User selection > Session > Default 'Nakuru')
if (isset($_GET['region']) && !empty($_GET['region'])) {
    $selectedRegion = trim($_GET['region']);
    $_SESSION['region'] = $selectedRegion;
} elseif (isset($_SESSION['region']) && !empty($_SESSION['region'])) {
    $selectedRegion = $_SESSION['region'];
} else {
    $selectedRegion = 'Nakuru';
}

$kenyaRegions = [
    'Nakuru', 'Uasin Gishu', 'Trans Nzoia', 'Kiambu', 
    'Meru', 'Nyandarua', 'Machakos', 'Narok', 'Kakamega', 'Bungoma'
];

// 3. Query Planting Calendar for the 4 Scope Crops only
$calendarData = [];

try {
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
    } else {
        $db = $conn ?? $pdo ?? null;
    }

    if ($db) {
        $stmt = $db->prepare("
            SELECT pc.*, c.crop_name, c.swahili_name 
            FROM Planting_Calendar pc
            JOIN Crops c ON pc.crop_id = c.crop_id
            WHERE (pc.region = :region OR pc.region = 'All' OR pc.region IS NULL)
              AND (
                  c.crop_name IN ('Maize', 'Beans', 'Potatoes', 'Tomatoes') OR 
                  c.swahili_name IN ('Mahindi', 'Maharagwe', 'Viazi', 'Nyanya')
              )
            ORDER BY FIELD(c.crop_name, 'Maize', 'Beans', 'Potatoes', 'Tomatoes')
        ");
        $stmt->execute([':region' => $selectedRegion]);
        $calendarData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Soft fallback if database setup is pending
}

function getCropBadgeClass($cropName) {
    switch (strtolower($cropName)) {
        case 'maize': case 'mahindi': return 'badge-maize';
        case 'beans': case 'maharagwe': return 'badge-beans';
        case 'potatoes': case 'viazi': return 'badge-potatoes';
        case 'tomatoes': case 'nyanya': return 'badge-tomatoes';
        default: return 'badge-default';
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalenda ya Kilimo - ShambaGuide</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .calendar-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .filter-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filter-card h2 {
            margin: 0;
            color: #2e7d32;
            font-size: 1.4rem;
        }
        .region-select-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .region-select-form select {
            padding: 8px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            outline: none;
        }
        .region-select-form button {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 20px;
        }
        .crop-calendar-card {
            background: #ffffff;
            border-radius: 10px;
            border-top: 4px solid #2e7d32;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            padding: 20px;
        }
        .crop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .crop-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }
        .badge-maize { background-color: #f39c12; }
        .badge-beans { background-color: #8e44ad; }
        .badge-potatoes { background-color: #d35400; }
        .badge-tomatoes { background-color: #e74c3c; }
        .badge-default { background-color: #27ae60; }
        
        .timeline-item { margin-bottom: 12px; }
        .timeline-label { font-size: 0.85rem; color: #666; text-transform: uppercase; }
        .timeline-value { font-weight: 600; color: #1b5e20; font-size: 1rem; }
        .notes-box {
            background: #f9f9f9;
            border-left: 3px solid #2e7d32;
            padding: 10px;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #444;
        }
    </style>
</head>
<body>

    <!-- Modular Header Inclusion (or standard template navbar) -->
    <?php 
    $headerPath = __DIR__ . '/../app/Views/header.php';
    if (file_exists($headerPath)) {
        include_once $headerPath;
    } else { ?>
        <header class="navbar">
            <div class="nav-brand">
                <a href="index.php">ShambaGuide</div>
            <nav class="nav-links">
                <a href="userdashboard.php">Dashboard</a>
                <a href="calendar.php" class="active">Kalenda</a>
                <a href="crops&disease.php">Wadudu na Magonjwa</a>
                <div class="user-dropdown">
                    <button class="dropbtn"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Mkulima'); ?> ▼</button>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Log Out</a>
                    </div>
                </div>
            </nav>
        </header>
    <?php } ?>

    <div class="calendar-container">
        <!-- Region Filter Card -->
        <div class="filter-card">
            <div>
                <h2>📅 Kalenda ya Upandaji (Planting Calendar)</h2>
                <p style="margin: 5px 0 0 0; color: #666;">Inaonyesha tarehe za msimu kwa eneo la: <strong><?php echo htmlspecialchars($selectedRegion); ?></strong></p>
            </div>
            
            <form action="calendar.php" method="GET" class="region-select-form">
                <label for="regionSelect">Badili Eneo (Region):</label>
                <select name="region" id="regionSelect">
                    <?php foreach ($kenyaRegions as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo ($r === $selectedRegion) ? 'selected' : ''; ?>>
                            <?php echo $r; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Hifadhi Eneo</button>
            </form>
        </div>

        <!-- 4 Scope Crop Cards -->
        <div class="calendar-grid">
            <?php if (!empty($calendarData)): ?>
                <?php foreach ($calendarData as $item): ?>
                    <div class="crop-calendar-card">
                        <div class="crop-header">
                            <span class="crop-title">
                                <?php echo htmlspecialchars($item['swahili_name'] ?? $item['crop_name']); ?>
                            </span>
                            <span class="badge <?php echo getCropBadgeClass($item['crop_name']); ?>">
                            </span>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-label">🌱 Msimu wa Kuandaa / Kupanda:</div>
                            <div class="timeline-value"><?php echo htmlspecialchars($item['planting_season'] ?? $item['planting_months'] ?? 'Machia - Aprili'); ?></div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-label">🌽 Msimu wa Kuvuna:</div>
                            <div class="timeline-value"><?php echo htmlspecialchars($item['harvest_season'] ?? $item['harvest_months'] ?? 'Agosti - September'); ?></div>
                        </div>

                        <?php if (!empty($item['notes'])): ?>
                            <div class="notes-box">
                                💡 <strong>Ushauri:</strong><br>
                                <?php echo htmlspecialchars($item['notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default 4 Scope Crops Fallback Cards -->
                <?php 
                $defaultScopeCrops = [
                    ['name' => 'Mahindi (Maize)', 'badge' => 'badge-maize', 'plant' => 'Machia - Aprili', 'harvest' => 'Agosti - September', 'note' => 'Panda na mbolea ya DAP wakati wa mvua za kwanza.'],
                    ['name' => 'Maharagwe (Beans)', 'badge' => 'badge-beans', 'plant' => 'Machia - Aprili', 'harvest' => 'Juni - Julai', 'note' => 'Palilia mapema kuzuia magonjwa ya mizizi.'],
                    ['name' => 'Viazi (Potatoes)', 'badge' => 'badge-potatoes', 'plant' => 'Aprili - Mei', 'harvest' => 'Julai - Agosti', 'note' => 'Tumia mbegu zilizothibitishwa zizizo na magonjwa.'],
                    ['name' => 'Nyanya (Tomatoes)', 'badge' => 'badge-tomatoes', 'plant' => 'Kituo cha Mche / Mwaka mzima', 'harvest' => 'Miezi 3 baada ya kupanda', 'note' => 'Hakikisha maji ya kutosha na dawa ya kuzuia blight.']
                ];
                foreach ($defaultScopeCrops as $crop): ?>
                    <div class="crop-calendar-card">
                        <div class="crop-header">
                            <span class="crop-title"><?php echo $crop['name']; ?></span>
                            <span class="badge <?php echo $crop['badge']; ?>"></span>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-label">🌱 Msimu wa Kupanda (Eneo: <?php echo htmlspecialchars($selectedRegion); ?>):</div>
                            <div class="timeline-value"><?php echo $crop['plant']; ?></div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-label">🌽 Msimu wa Kuvuna:</div>
                            <div class="timeline-value"><?php echo $crop['harvest']; ?></div>
                        </div>
                        <div class="notes-box">
                            💡 <strong>Ushauri:</strong><br>
                            <?php echo $crop['note']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>