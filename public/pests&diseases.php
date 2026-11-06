<?php
session_start();

// 1. Session Auth Check
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? null;

if (!$userId) {
    header("Location: index.php");
    exit();
}

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// 2. Fetch Pests & Diseases from database filtered by the 4 Scope Crops
$pestsAndDiseases = [];

try {
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
    } else {
        $db = $conn ?? $pdo ?? null;
    }

    if ($db) {
        $sql = "
            SELECT pd.*, c.crop_name, c.swahili_name 
            FROM Pests_Diseases pd
            JOIN Crops c ON pd.crop_id = c.crop_id
            WHERE c.crop_name IN ('Maize', 'Beans', 'Potatoes', 'Tomatoes')
               OR c.swahili_name IN ('Mahindi', 'Maharagwe', 'Viazi', 'Nyanya')
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $pestsAndDiseases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Quietly catch to allow official KALRO fallback rendering below
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
    <title>Wadudu na Magonjwa (KALRO Guide) - ShambaGuide</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .directory-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .header-card {
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
        .header-card h2 {
            margin: 0;
            color: #2e7d32;
            font-size: 1.4rem;
        }
        .crop-filter-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #2e7d32;
            border-radius: 20px;
            background: #fff;
            color: #2e7d32;
            font-weight: bold;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            outline: none;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #2e7d32;
            color: #fff;
        }
        .grid-directory {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .pest-card {
            background: #ffffff;
            border-radius: 10px;
            border-top: 4px solid #c62828;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            padding: 20px;
            transition: opacity 0.3s ease, transform 0.2s ease;
        }
        .pest-card.disease {
            border-top-color: #d84315;
        }
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .pest-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
        }
        .badge-maize { background-color: #f39c12; }
        .badge-beans { background-color: #8e44ad; }
        .badge-potatoes { background-color: #d35400; }
        .badge-tomatoes { background-color: #e74c3c; }
        .badge-default { background-color: #27ae60; }
        
        .type-tag {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: #ffebee;
            color: #c62828;
            font-weight: bold;
            display: inline-block;
            margin-top: 4px;
        }
        .section-block { margin-bottom: 12px; }
        .section-title { font-weight: bold; font-size: 0.85rem; color: #555; text-transform: uppercase; margin-bottom: 3px; }
        .section-desc { font-size: 0.92rem; color: #333; line-height: 1.4; }
        .kalro-box {
            background: #f4fbf7;
            border-left: 3px solid #2e7d32;
            padding: 10px;
            border-radius: 0 6px 6px 0;
            margin-top: 10px;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <?php 
    $headerPath = __DIR__ . '/../app/Views/header.php';
    if (file_exists($headerPath)) {
        include_once $headerPath;
    } else { ?>
        <header class="navbar">
            <div class="nav-brand">ShambaGuide</div>
            <nav class="nav-links">
                <a href="userdashboard.php">Dashboard</a>
                <a href="calendar.php">Kalenda</a>
                <a href="crops&disease.php" class="active">Wadudu na Magonjwa</a>
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

    <div class="directory-container">
        <!-- Header & Filter Card -->
        <div class="header-card">
            <div>
                <h2>🐛 Mwongozo wa Wadudu na Magonjwa</h2>
                <p style="margin: 5px 0 0 0; color: #666;">Tambua dalili na njia za kupambana na wadudu na magonjwa kwa mazao makuu .</p>
            </div>

            <!-- In-Page Tab Buttons -->
            <div class="crop-filter-btns">
                <button type="button" class="filter-btn active" onclick="filterCrop('all', this)">Zote (All)</button>
                <button type="button" class="filter-btn" onclick="filterCrop('maize', this)">🌽 Mahindi</button>
                <button type="button" class="filter-btn" onclick="filterCrop('beans', this)">🫘 Maharagwe</button>
                <button type="button" class="filter-btn" onclick="filterCrop('potatoes', this)">🥔 Viazi</button>
                <button type="button" class="filter-btn" onclick="filterCrop('tomatoes', this)">🍅 Nyanya</button>
            </div>
        </div>

        <!-- Directory Content Grid -->
        <div class="grid-directory">
            <?php if (!empty($pestsAndDiseases)): ?>
                <?php foreach ($pestsAndDiseases as $item): 
                    $cropCategory = strtolower($item['crop_name'] ?? '');
                    if (strpos($cropCategory, 'maize') !== false || strpos($cropCategory, 'mahindi') !== false) { $cropCategory = 'maize'; }
                    elseif (strpos($cropCategory, 'bean') !== false || strpos($cropCategory, 'maharagwe') !== false) { $cropCategory = 'beans'; }
                    elseif (strpos($cropCategory, 'potato') !== false || strpos($cropCategory, 'viazi') !== false) { $cropCategory = 'potatoes'; }
                    elseif (strpos($cropCategory, 'tomato') !== false || strpos($cropCategory, 'nyanya') !== false) { $cropCategory = 'tomatoes'; }
                ?>
                    <div class="pest-card <?php echo strtolower($item['type'] ?? 'pest') === 'disease' ? 'disease' : ''; ?>" data-crop="<?php echo $cropCategory; ?>">
                        <div class="card-top">
                            <div>
                                <h3 class="pest-title"><?php echo htmlspecialchars($item['name_swahili'] ?? $item['name']); ?></h3>
                                <span class="type-tag"><?php echo htmlspecialchars($item['type'] ?? 'Kipengele'); ?></span>
                            </div>
                            <span class="badge <?php echo getCropBadgeClass($item['crop_name']); ?>">
                                <?php echo htmlspecialchars($item['swahili_name'] ?? $item['crop_name']); ?>
                            </span>
                        </div>

                        <div class="section-block">
                            <div class="section-title">🔍 Dalili (Symptoms):</div>
                            <div class="section-desc"><?php echo htmlspecialchars($item['symptoms']); ?></div>
                        </div>

                        <div class="section-block">
                            <div class="section-title">🛡️ Kuzuia & Tiba (Control Measures):</div>
                            <div class="section-desc"><?php echo htmlspecialchars($item['control_measures']); ?></div>
                        </div>

                        <?php if (!empty($item['kalro_recommendation'])): ?>
                            <div class="kalro-box">
                                🌿 <strong>Ushauri wa KALRO:</strong><br>
                                <?php echo htmlspecialchars($item['kalro_recommendation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- KALRO Directory Items with Data Attributes -->
                <?php
                $kalroDirectory = [
                    ['crop_cat' => 'maize', 'crop_sw' => 'Mahindi', 'badge' => 'badge-maize', 'name' => 'Kiwavi Jeshi Vamizi (Fall Armyworm)', 'type' => 'Kududu (Pest)', 'symptoms' => 'Majani kuwa na matundu madogo madogo na mashimo makubwa, kinyesi cha wadudu kuonekana kwenye silinda ya jani.', 'control' => 'Panda mapema. Tumia dawa zilizosajiliwa kama Emamectin Benzoate au Spinotoram. Tumia mbinu ya Push-Pull.', 'kalro' => 'Kagua shamba mara 2 kwa wiki wakati mmea unachipua.'],
                    ['crop_cat' => 'maize', 'crop_sw' => 'Mahindi', 'badge' => 'badge-maize', 'name' => 'Ugonjwa wa Mnyauko wa MLN (Maize Lethal Necrosis)', 'type' => 'Ugonjwa (Disease)', 'symptoms' => 'Majani kuwa ya manjano kutoka kingoni, mmea kukauka kabla ya muda na mahindi kukosa mbegu.', 'control' => 'Badilisha mazao (Crop rotation). Tumia mbegu zinazovumilia MLN zilizothibitishwa na KALRO.', 'kalro' => 'Ng’oa na uteketeze mimea iliyoathirika mara moja.'],
                    ['crop_cat' => 'beans', 'crop_sw' => 'Maharagwe', 'badge' => 'badge-beans', 'name' => 'Inzi wa Maharagwe (Bean Fly)', 'type' => 'Kududu (Pest)', 'symptoms' => 'Shina chini ya udongo kuvimba na kupasuka, majani kugeuka manjano na mmea kunyauka.', 'control' => 'Weka dawa ya mbegu kabla ya kupanda (Seed dressing). Pandia mbolea vizuri ili kuongeza nguvu ya mmea.', 'kalro' => 'Panda mara tu mvua zinapoanza ili kuzuia mashambulizi.'],
                    ['crop_cat' => 'beans', 'crop_sw' => 'Maharagwe', 'badge' => 'badge-beans', 'name' => 'Ugonjwa wa Kimeta / Anthracnose', 'type' => 'Ugonjwa (Disease)', 'symptoms' => 'Mabaka meusi au ya kahawia chini ya majani, kwenye shina, na kwenye maganda ya maharagwe.', 'control' => 'Tumia mbegu safi zilizoidhinishwa. Nyunyizia dawa za Fungicide zenye shaba (Copper-based).', 'kalro' => 'Kukataza kuingia shambani wakati majani yapo mbichi/mabichi.'],
                    ['crop_cat' => 'potatoes', 'crop_sw' => 'Viazi', 'badge' => 'badge-potatoes', 'name' => 'Blight ya Chelewa (Late Blight)', 'type' => 'Ugonjwa (Disease)', 'symptoms' => 'Mabaka meusi na yenye maji kwenye majani na shina. Pamba nyeupe chini ya jani wakati wa baridi.', 'control' => 'Nyunyizia Fungicides (Mancozeb, Metalaxyl) kabla na baada ya ugonjwa kuonekana.', 'kalro' => 'Tumia aina za viazi kama Shangi au Dutch Roby zinazovumilia.'],
                    ['crop_cat' => 'potatoes', 'crop_sw' => 'Viazi', 'badge' => 'badge-potatoes', 'name' => 'Nematodi wa Viazi (Potato Cyst Nematode)', 'type' => 'Kududu (Pest)', 'symptoms' => 'Viazi kuwa vidogo, mmea kudumaa na majani kugeuka manjano bila sababu ya wazi.', 'control' => 'Zungusha mazao na mazao yasiyo ya jamaa ya solanaceae. Tumia mbegu safi kutoka KALRO Tigoni.', 'kalro' => 'Zingatia usafi wa vifaa vya shamba kuzuia kueneza udongo ulioathirika.'],
                    ['crop_cat' => 'tomatoes', 'crop_sw' => 'Nyanya', 'badge' => 'badge-tomatoes', 'name' => 'Tuta Absoluta (Tomato Leafminer)', 'type' => 'Kududu (Pest)', 'symptoms' => 'Migodi au mifereji myeupe kwenye majani, matundu kwenye matunda na shina kuanza kuoza.', 'control' => 'Weka mitego ya feromoni (Pheromone traps). Nyunyizia dawa zenye Bacillus thuringiensis au Chlorantraniliprole.', 'kalro' => 'Safisha shamba kikamilifu baada ya mavuno.'],
                    ['crop_cat' => 'tomatoes', 'crop_sw' => 'Nyanya', 'badge' => 'badge-tomatoes', 'name' => 'Mnyauko wa Bakteria (Bacterial Wilt)', 'type' => 'Ugonjwa (Disease)', 'symptoms' => 'Mmea kunyauka ghafla ukiwa bado wa kijani kibichi. Shina likikatwa linaonyesha majimaji ya maziwa.', 'control' => 'Hakuna tiba ya kemikali. Zungusha mazao kwa miaka 3-5 na mazao ya nafaka.', 'kalro' => 'Hakikisha mtiririko mzuri wa maji na epuka kuhamisha udongo wenye ugonjwa.']
                ];
                foreach ($kalroDirectory as $item): ?>
                    <div class="pest-card <?php echo strpos($item['type'], 'Ugonjwa') !== false ? 'disease' : ''; ?>" data-crop="<?php echo $item['crop_cat']; ?>">
                        <div class="card-top">
                            <div>
                                <h3 class="pest-title"><?php echo $item['name']; ?></h3>
                                <span class="type-tag"><?php echo $item['type']; ?></span>
                            </div>
                            <span class="badge <?php echo $item['badge']; ?>">
                                <?php echo $item['crop_sw']; ?>
                            </span>
                        </div>

                        <div class="section-block">
                            <div class="section-title">🔍 Dalili (Symptoms):</div>
                            <div class="section-desc"><?php echo $item['symptoms']; ?></div>
                        </div>

                        <div class="section-block">
                            <div class="section-title">🛡️ Kuzuia & Tiba (Control Measures):</div>
                            <div class="section-desc"><?php echo $item['control']; ?></div>
                        </div>

                        <div class="kalro-box">
                            🌿 <strong>Ushauri wa KALRO:</strong><br>
                            <?php echo $item['kalro']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Client-side Tab Filtering Script -->
    <script>
        function filterCrop(cropName, btnElement) {
            // Update active state on buttons
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            // Show/Hide cards on the same page instantly
            const cards = document.querySelectorAll('.pest-card');
            cards.forEach(card => {
                const cardCrop = card.getAttribute('data-crop');
                if (cropName === 'all' || cardCrop === cropName) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>