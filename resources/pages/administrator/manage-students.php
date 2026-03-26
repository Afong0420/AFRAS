<?php

if (isset($_POST['addStudent'])) {

    $firstName          = htmlspecialchars(trim($_POST['firstName']));
    $lastName           = htmlspecialchars(trim($_POST['lastName']));
    $email              = htmlspecialchars(trim($_POST['email']));
    $registrationNumber = htmlspecialchars(trim($_POST['registrationNumber']));
    $yearLevel          = $_POST['yearLevel'];
    $section            = $_POST['section'];
    $dateRegistered     = date("Y-m-d");

    $courseMap = [
        'BSHM'   => ['faculty' => 'FHMT', 'name' => 'Hospitality Management'],
        'BSIT'   => ['faculty' => 'FICT', 'name' => 'Information Technology'],
        'BSMT'   => ['faculty' => 'FMTE', 'name' => 'Marine Transportation'],
        'BSME'   => ['faculty' => 'FENG', 'name' => 'Mechanical Engineering'],
        'BSEE'   => ['faculty' => 'FENG', 'name' => 'Electrical Engineering'],
        'BSCrim' => ['faculty' => 'FCJS', 'name' => 'Criminology'],
        'BSMarE' => ['faculty' => 'FMTE', 'name' => 'Marine Engineering'],
    ];

    $courseCode = $_POST['course'];
    $faculty    = isset($courseMap[$courseCode]) ? $courseMap[$courseCode]['faculty'] : $_POST['faculty'];

    $imageFileNames = [];
    $folderPath = "resources/labels/{$registrationNumber}/";
    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    for ($i = 1; $i <= 5; $i++) {
        if (isset($_POST["capturedImage$i"]) && !empty($_POST["capturedImage$i"])) {
            $base64Data = explode(',', $_POST["capturedImage$i"])[1];
            $imageData  = base64_decode($base64Data);
            $labelName  = "{$i}.png";
            file_put_contents("{$folderPath}{$labelName}", $imageData);
            $imageFileNames[] = $registrationNumber . "_image{$i}.png";
        }
    }
    $imagesJson = json_encode($imageFileNames);

    $checkQuery = $pdo->prepare("SELECT COUNT(*) FROM students WHERE registrationNumber = :registrationNumber");
    $checkQuery->execute([':registrationNumber' => $registrationNumber]);
    $count = $checkQuery->fetchColumn();

    if ($count > 0) {
        $_SESSION['message'] = "Student with Registration No: $registrationNumber already exists!";
    } else {
        $insertQuery = $pdo->prepare("
            INSERT INTO students 
            (firstName, lastName, email, registrationNumber, faculty, courseCode, yearLevel, section, studentImage, dateRegistered) 
            VALUES 
            (:firstName, :lastName, :email, :registrationNumber, :faculty, :courseCode, :yearLevel, :section, :studentImage, :dateRegistered)
        ");
        $insertQuery->execute([
            ':firstName'          => $firstName,
            ':lastName'           => $lastName,
            ':email'              => $email,
            ':registrationNumber' => $registrationNumber,
            ':faculty'            => $faculty,
            ':courseCode'         => $courseCode,
            ':yearLevel'          => $yearLevel,
            ':section'            => $section,
            ':studentImage'       => $imagesJson,
            ':dateRegistered'     => $dateRegistered
        ]);
        $_SESSION['message'] = "Student $registrationNumber added successfully!";
    }
}

// Per-course student counts for overview cards
$definedCourses = [
    'BSHM'   => ['label' => 'Hospitality Management',  'icon' => 'ri-hotel-line',       'color' => '#F59E0B'],
    'BSIT'   => ['label' => 'Information Technology',   'icon' => 'ri-computer-line',     'color' => '#3B5BDB'],
    'BSMT'   => ['label' => 'Marine Transportation',    'icon' => 'ri-ship-line',         'color' => '#0EA5E9'],
    'BSME'   => ['label' => 'Mechanical Engineering',   'icon' => 'ri-settings-3-line',   'color' => '#10B981'],
    'BSEE'   => ['label' => 'Electrical Engineering',   'icon' => 'ri-flashlight-line',   'color' => '#8B5CF6'],
    'BSCrim' => ['label' => 'Criminology',              'icon' => 'ri-shield-line',       'color' => '#EF4444'],
    'BSMarE' => ['label' => 'Marine Engineering',       'icon' => 'ri-anchor-line',       'color' => '#06B6D4'],
];

$courseOverviewData = [];
foreach ($definedCourses as $code => $info) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM students WHERE courseCode = :code");
    $q->execute([':code' => $code]);
    $courseOverviewData[$code] = $q->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Manage Students — AFRAs</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
    <style>
        /* ── Course Overview Cards ─────────────────── */
        .course-overview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .course-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: var(--shadow);
            transition: transform 0.18s, box-shadow 0.18s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .course-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--course-color);
            border-radius: 16px 16px 0 0;
        }
        .course-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .course-card.active-course {
            outline: 2px solid var(--course-color);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--course-color) 15%, transparent);
        }
        .course-card-header { display: flex; align-items: center; gap: 10px; }
        .course-card-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            background: color-mix(in srgb, var(--course-color) 12%, transparent);
            color: var(--course-color);
            flex-shrink: 0;
        }
        .course-card-label {
            font-family: 'Syne', sans-serif;
            font-size: 12.5px; font-weight: 600;
            color: var(--text); line-height: 1.3;
        }
        .course-card-code {
            font-size: 11px; color: var(--text-muted);
            font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;
        }
        .course-card-count {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 700;
            color: var(--course-color); line-height: 1;
        }
        .course-card-sub { font-size: 11px; color: var(--text-muted); }

        /* ── Modal overlay ─────────────────── */
        #overlay {
            position: fixed; inset: 0;
            background: rgba(10, 15, 30, 0.55);
            backdrop-filter: blur(4px);
            z-index: 999; display: none;
        }
        .formDiv-- {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: var(--bg-white);
            border-radius: 20px;
            padding: 32px 36px;
            width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.18);
            border: 1px solid var(--border);
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .modal-header .form-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 700; color: var(--text);
        }
        .close {
            font-size: 22px; font-weight: 600; cursor: pointer;
            color: var(--text-muted); transition: color 0.15s;
            background: none; border: none; line-height: 1;
        }
        .close:hover { color: var(--danger); background: none; }
        .formDiv-- input[type="text"],
        .formDiv-- input[type="email"],
        .formDiv-- select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; color: var(--text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 12px;
            appearance: none;
        }
        .formDiv-- input:focus, .formDiv-- select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
            background: #fff;
        }

        /* ── Auto-assign badge ─────────────────── */
        .course-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            background: rgba(16,185,129,0.08);
            color: #059669;
            border: 1px solid rgba(16,185,129,0.25);
            margin-bottom: 12px; width: 100%;
        }
        .course-badge.hidden { display: none; }

        /* ── Camera Capture Section ─────────────────── */
        .camera-section {
            margin-bottom: 14px;
        }
        .camera-section-label {
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .camera-section-label span {
            font-weight: 400;
            color: var(--text-muted);
            font-size: 12px;
        }
        .btn-open-camera {
            width: 100%;
            padding: 11px 14px;
            background: linear-gradient(135deg, #3B5BDB, #6C63FF);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.15s;
            margin-bottom: 10px;
        }
        .btn-open-camera:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-open-camera.captured {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        /* Captured thumbnails */
        .captured-thumbs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .captured-thumbs img {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #10B981;
        }
        .captured-thumbs .thumb-empty {
            width: 58px;
            height: 58px;
            border-radius: 8px;
            border: 2px dashed var(--border);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 18px;
        }

        /* ── Camera Modal ─────────────────── */
        #cameraModal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px);
        }
        #cameraModal.active { display: flex; }
        .camera-modal-box {
            background: #0f172a;
            border-radius: 24px;
            padding: 28px 28px 24px;
            width: 500px;
            max-width: 95vw;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }
        .camera-modal-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .camera-modal-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .camera-modal-title i { color: #6C63FF; font-size: 20px; }
        .camera-close-btn {
            background: rgba(255,255,255,0.08);
            border: none;
            border-radius: 8px;
            color: #94a3b8;
            font-size: 18px;
            width: 34px; height: 34px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .camera-close-btn:hover { background: rgba(239,68,68,0.25); color: #f87171; }

        /* Video area */
        .video-wrapper {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            width: 100%;
            background: #000;
            aspect-ratio: 4/3;
        }
        #cameraVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        /* Countdown overlay */
        #countdownOverlay {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.45);
            z-index: 5;
        }
        #countdownOverlay.show { display: flex; }
        #countdownNumber {
            font-family: 'Syne', sans-serif;
            font-size: 96px;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 0 40px rgba(108,99,255,0.9), 0 4px 24px rgba(0,0,0,0.6);
            animation: pulse-scale 1s ease-in-out infinite;
            line-height: 1;
        }
        @keyframes pulse-scale {
            0%   { transform: scale(0.85); opacity: 0.6; }
            50%  { transform: scale(1.1);  opacity: 1; }
            100% { transform: scale(0.85); opacity: 0.6; }
        }
        /* Flash effect */
        #flashOverlay {
            position: absolute;
            inset: 0;
            background: #fff;
            opacity: 0;
            pointer-events: none;
            z-index: 6;
            transition: opacity 0.05s ease-in;
        }
        #flashOverlay.flash { opacity: 1; }

        /* Photo counter badge */
        .photo-counter {
            position: absolute;
            top: 10px; right: 10px;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            color: #f1f5f9;
            border-radius: 20px;
            padding: 4px 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            z-index: 4;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .photo-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            transition: background 0.3s;
        }
        .photo-dot.done { background: #10B981; }
        .photo-dot.active { background: #6C63FF; animation: dot-blink 0.6s ease-in-out infinite; }
        @keyframes dot-blink {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(0.75); }
        }

        /* Status text */
        #cameraStatus {
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            color: #94a3b8;
            text-align: center;
            min-height: 20px;
        }
        #cameraStatus.ready  { color: #6C63FF; font-weight: 600; }
        #cameraStatus.taking { color: #F59E0B; font-weight: 600; }
        #cameraStatus.done   { color: #10B981; font-weight: 600; }

        /* Preview strip */
        .preview-strip {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .preview-cell {
            width: 66px; height: 66px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .preview-cell img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: none;
        }
        .preview-cell .cell-num {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: rgba(255,255,255,0.2);
        }
        .preview-cell.captured {
            border-color: #10B981;
        }
        .preview-cell.captured img { display: block; }
        .preview-cell.captured .cell-num { display: none; }

        /* Capture & Action buttons */
        .camera-actions {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        #captureBtn {
            flex: 1;
            padding: 13px;
            background: linear-gradient(135deg, #6C63FF, #3B5BDB);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.15s;
        }
        #captureBtn:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }
        #captureBtn:disabled { opacity: 0.45; cursor: not-allowed; }
        #retakeBtn {
            padding: 13px 18px;
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: none;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        #retakeBtn:hover { background: rgba(239,68,68,0.22); }
        #confirmPhotosBtn {
            padding: 13px 18px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: none;
            align-items: center;
            gap: 6px;
            transition: opacity 0.2s;
        }
        #confirmPhotosBtn:hover { opacity: 0.88; }
        canvas#captureCanvas { display: none; }

        /* ── Face Photo Column ──────────────────────── */
        .face-td { text-align: center; }
        .face-avatar-wrap {
            position: relative;
            display: inline-block;
        }
        .face-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
            cursor: pointer;
            transition: transform 0.15s, border-color 0.15s;
            display: block;
        }
        .face-avatar:hover {
            transform: scale(1.12);
            border-color: var(--accent);
        }
        .face-initials {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3B5BDB, #6C63FF);
            color: #fff; font-size: 13px; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
        }

        /* Zoom popup — single large photo */
        .face-zoom-popup {
            display: none;
            position: fixed;
            z-index: 9999;
            pointer-events: none;
        }
        .face-avatar-wrap:hover .face-zoom-popup {
            display: block;
        }
        .face-zoom-popup img {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
            display: block;
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>

<section class="main">
<?php include "Includes/sidebar.php"; ?>

<div class="main--content">
    <div id="overlay"></div>
    <?php showMessage(); ?>

    <!-- ── Course Overview Cards ─────────────────────── -->
    <div class="title">
        <h2 class="section--title">Students by Course</h2>
        <button class="add" id="openAddStudent"><i class="ri-add-line"></i>Add Student</button>
    </div>

    <div class="course-overview">
        <?php foreach ($definedCourses as $code => $info):
            $count = $courseOverviewData[$code] ?? 0;
        ?>
        <div class="course-card" style="--course-color: <?= $info['color'] ?>"
             onclick="filterByCourse('<?= $code ?>', this)">
            <div class="course-card-header">
                <div class="course-card-icon"><i class="<?= $info['icon'] ?>"></i></div>
                <div>
                    <div class="course-card-label"><?= $info['label'] ?></div>
                    <div class="course-card-code"><?= $code ?></div>
                </div>
            </div>
            <div class="course-card-count"><?= $count ?></div>
            <div class="course-card-sub">enrolled students</div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Students Table ─────────────────────── -->
    <div class="table-container">
        <div class="title">
            <h2 class="section--title" id="tableTitle">All Students</h2>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <select id="yearFilterSelect" onchange="applyFilters()" style="
                    background:var(--bg-white); border:1px solid var(--border);
                    border-radius:10px; padding:8px 14px; font-size:13.5px;
                    color:var(--text); cursor:pointer; outline:none;">
                    <option value="">All Year Levels</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
                <button class="add" style="background:var(--text-muted); font-size:12px;" id="clearFilter" onclick="clearAllFilters()" style="display:none;">
                    <i class="ri-close-line"></i>Clear Filter
                </button>
            </div>
        </div>

        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>ID No</th>
                        <th>Face Photos</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Faculty</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                <?php
                $sql = "SELECT * FROM students ORDER BY courseCode, lastName";
                $result = fetch($sql);
                if ($result) {
                    foreach ($result as $row) {
                        $regNo    = $row["registrationNumber"];
                        $initials = strtoupper(substr($row["firstName"],0,1).substr($row["lastName"],0,1));

                        // Build face photo HTML — check which of the 5 exist
                        $faceHtml = '';
                        $photoCount = 0;
                        $firstImg = '';
                        $baseDir = dirname(dirname(dirname(dirname(__FILE__))));  // htdocs/afras4 root
                        for ($p = 1; $p <= 5; $p++) {
                            $imgPath = "resources/labels/{$regNo}/{$p}.png";
                            if (file_exists($baseDir . '/' . $imgPath)) {
                                $photoCount++;
                                if (!$firstImg) $firstImg = $imgPath;
                                $faceHtml .= "<img src='{$imgPath}' class='face-strip-img' onerror=\"this.style.display='none'\">";
                            }
                        }

                        // Avatar column: show first photo (zoomable) or initials placeholder
                        if ($firstImg) {
                            $avatarHtml = "
                                <div class='face-avatar-wrap'>
                                    <img src='{$firstImg}' class='face-avatar' onerror=\"this.style.display='none';this.nextElementSibling.style.display='inline-flex';\">
                                    <span class='face-initials' style='display:none'>{$initials}</span>
                                    <div class='face-zoom-popup'>
                                        <img src='{$firstImg}' onerror=\"this.style.display='none'\">
                                    </div>
                                </div>";
                        } else {
                            $avatarHtml = "<span class='face-initials'>{$initials}</span>";
                        }

                        echo "<tr id='rowstudents{$row["Id"]}' data-course='{$row["courseCode"]}' data-year='{$row["yearLevel"]}'>";
                        echo "<td>" . $regNo . "</td>";
                        echo "<td class='face-td'>" . $avatarHtml . "</td>";
                        echo "<td>" . $row["firstName"] . " " . $row["lastName"] . "</td>";
                        echo "<td>" . $row["courseCode"] . "</td>";
                        echo "<td>" . $row["faculty"] . "</td>";
                        echo "<td>" . $row["yearLevel"] . "</td>";
                        echo "<td>" . $row["section"] . "</td>";
                        echo "<td>" . $row["email"] . "</td>";
                        echo "<td>
                            <span><i class='ri-edit-line edit-student'
                                data-id='{$row["Id"]}'
                                data-firstname='" . htmlspecialchars($row["firstName"]) . "'
                                data-lastname='" . htmlspecialchars($row["lastName"]) . "'
                                data-email='" . htmlspecialchars($row["email"]) . "'
                                data-faculty='" . htmlspecialchars($row["faculty"]) . "'
                                data-course='" . htmlspecialchars($row["courseCode"]) . "'
                                data-year='" . htmlspecialchars($row["yearLevel"]) . "'
                                data-section='" . htmlspecialchars($row["section"]) . "'
                                style='cursor:pointer;'></i></span>
                            <span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='students'></i></span>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No records found</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Add Student Modal ─────────────────────── -->
    <div class="formDiv--" id="form" style="display:none;">
        <form method="post" id="addStudentForm">
            <div class="modal-header">
                <span class="form-title">Add New Student</span>
                <button type="button" class="close">&times;</button>
            </div>

            <input type="text" name="firstName" placeholder="First Name" required>
            <input type="text" name="lastName" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="registrationNumber" id="regNumInput" placeholder="Registration Number" required>

            <select required name="course" id="courseSelect">
                <option value="">Select Course</option>
                <option value="BSHM">Hospitality Management (BSHM)</option>
                <option value="BSIT">Information Technology (BSIT)</option>
                <option value="BSMT">Marine Transportation (BSMT)</option>
                <option value="BSME">Mechanical Engineering (BSME)</option>
                <option value="BSEE">Electrical Engineering (BSEE)</option>
                <option value="BSCrim">Criminology (BSCrim)</option>
                <option value="BSMarE">Marine Engineering (BSMarE)</option>
            </select>

            <!-- Auto-assigned faculty badge -->
            <div class="course-badge hidden" id="facultyBadge">
                <i class="ri-check-line"></i>
                <span id="facultyBadgeText">Faculty auto-assigned</span>
            </div>

            <!-- Hidden faculty — auto-filled by JS -->
            <input type="hidden" name="faculty" id="facultyHidden">

            <select required name="yearLevel">
                <option value="">Select Year Level</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
            </select>

            <select required name="section">
                <option value="">Select Section</option>
                <option value="A">Section A</option>
                <option value="B">Section B</option>
                <option value="C">Section C</option>
                <option value="D">Section D</option>
            </select>

            <!-- ── Camera Capture Section ── -->
            <div class="camera-section">
                <div class="camera-section-label">
                    <i class="ri-camera-line" style="color:#6C63FF;font-size:16px;"></i>
                    Face Photos
                    <span>(5 photos required)</span>
                </div>
                <button type="button" class="btn-open-camera" id="openCameraBtn">
                    <i class="ri-camera-line"></i>
                    <span id="openCameraBtnText">Open Camera & Capture Face</span>
                </button>
                <!-- Thumbnail preview of captured photos -->
                <div class="captured-thumbs" id="capturedThumbs">
                    <div class="thumb-empty"><i class="ri-user-line"></i></div>
                    <div class="thumb-empty"><i class="ri-user-line"></i></div>
                    <div class="thumb-empty"><i class="ri-user-line"></i></div>
                    <div class="thumb-empty"><i class="ri-user-line"></i></div>
                    <div class="thumb-empty"><i class="ri-user-line"></i></div>
                </div>
            </div>

            <!-- Hidden fields to carry image data to PHP -->
            <input type="hidden" name="capturedImage1" id="capturedImage1">
            <input type="hidden" name="capturedImage2" id="capturedImage2">
            <input type="hidden" name="capturedImage3" id="capturedImage3">
            <input type="hidden" name="capturedImage4" id="capturedImage4">
            <input type="hidden" name="capturedImage5" id="capturedImage5">

            <input type="submit" class="btn-submit" value="Save Student" name="addStudent">
        </form>
    </div>

    <!-- ════════════════════════════════════════════
         CAMERA CAPTURE MODAL
    ════════════════════════════════════════════ -->
    <div id="cameraModal">
        <div class="camera-modal-box">
            <div class="camera-modal-header">
                <div class="camera-modal-title">
                    <i class="ri-camera-3-line"></i>
                    Capture Student Face
                </div>
                <button class="camera-close-btn" id="closeCameraModal" title="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Video feed -->
            <div class="video-wrapper">
                <video id="cameraVideo" autoplay playsinline muted></video>
                <div id="countdownOverlay">
                    <div id="countdownNumber">3</div>
                </div>
                <div id="flashOverlay"></div>
                <!-- Dot progress -->
                <div class="photo-counter">
                    <div class="photo-dot" id="dot1"></div>
                    <div class="photo-dot" id="dot2"></div>
                    <div class="photo-dot" id="dot3"></div>
                    <div class="photo-dot" id="dot4"></div>
                    <div class="photo-dot" id="dot5"></div>
                </div>
            </div>

            <!-- Status -->
            <div id="cameraStatus" class="ready">Ready — Press "Start Capture" to begin</div>

            <!-- Photo preview strip -->
            <div class="preview-strip">
                <div class="preview-cell" id="prev1"><span class="cell-num">1</span><img id="prevImg1"></div>
                <div class="preview-cell" id="prev2"><span class="cell-num">2</span><img id="prevImg2"></div>
                <div class="preview-cell" id="prev3"><span class="cell-num">3</span><img id="prevImg3"></div>
                <div class="preview-cell" id="prev4"><span class="cell-num">4</span><img id="prevImg4"></div>
                <div class="preview-cell" id="prev5"><span class="cell-num">5</span><img id="prevImg5"></div>
            </div>

            <!-- Action buttons -->
            <div class="camera-actions">
                <button id="captureBtn">
                    <i class="ri-camera-2-line"></i> Start Capture
                </button>
                <button id="retakeBtn">
                    <i class="ri-refresh-line"></i> Retake
                </button>
                <button id="confirmPhotosBtn">
                    <i class="ri-check-double-line"></i> Use Photos
                </button>
            </div>

            <canvas id="captureCanvas"></canvas>
        </div>
    </div>

</div>
</section>

<script>
// ── Face photo popup: follow cursor ─────────────────────
document.querySelectorAll('.face-avatar-wrap').forEach(wrap => {
    const popup = wrap.querySelector('.face-zoom-popup');
    if (!popup) return;
    wrap.addEventListener('mousemove', e => {
        const offset = 16;
        let x = e.clientX + offset;
        let y = e.clientY + offset;
        if (x + 290 > window.innerWidth)  x = e.clientX - 290 - offset;
        if (y + 160 > window.innerHeight) y = e.clientY - 160 - offset;
        popup.style.left = x + 'px';
        popup.style.top  = y + 'px';
    });
});

// ── Course → Faculty auto-assign ─────────────────────────
const courseToFaculty = {
    'BSHM':   { code: 'FHMT', label: 'Faculty of Hospitality & Management Technology' },
    'BSIT':   { code: 'FICT', label: 'Faculty of Information & Communications Technology' },
    'BSMT':   { code: 'FMTE', label: 'Faculty of Marine Transportation Engineering' },
    'BSME':   { code: 'FENG', label: 'Faculty of Engineering' },
    'BSEE':   { code: 'FENG', label: 'Faculty of Engineering' },
    'BSCrim': { code: 'FCJS', label: 'Faculty of Criminal Justice Studies' },
    'BSMarE': { code: 'FMTE', label: 'Faculty of Marine Transportation Engineering' },
};

document.getElementById('courseSelect').addEventListener('change', function() {
    const sel = this.value;
    const badge     = document.getElementById('facultyBadge');
    const badgeTxt  = document.getElementById('facultyBadgeText');
    const hidden    = document.getElementById('facultyHidden');
    if (sel && courseToFaculty[sel]) {
        hidden.value   = courseToFaculty[sel].code;
        badgeTxt.textContent = '✓ Faculty auto-assigned: ' + courseToFaculty[sel].label;
        badge.classList.remove('hidden');
    } else {
        hidden.value = '';
        badge.classList.add('hidden');
    }
});

// ── Add Student Modal open / close ───────────────────────
const overlay = document.getElementById('overlay');
const modal   = document.getElementById('form');

document.getElementById('openAddStudent').addEventListener('click', () => {
    modal.style.display = 'block';
    overlay.style.display = 'block';
});
function closeModal() {
    modal.style.display = 'none';
    overlay.style.display = 'none';
}
document.querySelector('.close').addEventListener('click', closeModal);
overlay.addEventListener('click', closeModal);

// ── Course card + Year Level filter ─────────────────────
const courseNames = {
    'BSHM':   'Hospitality Management',
    'BSIT':   'Information Technology',
    'BSMT':   'Marine Transportation',
    'BSME':   'Mechanical Engineering',
    'BSEE':   'Electrical Engineering',
    'BSCrim': 'Criminology',
    'BSMarE': 'Marine Engineering',
};

let activeCourseFilter = '';

function filterByCourse(code, card) {
    if (card.classList.contains('active-course')) {
        // Toggle off
        activeCourseFilter = '';
        document.querySelectorAll('.course-card').forEach(c => c.classList.remove('active-course'));
    } else {
        activeCourseFilter = code;
        document.querySelectorAll('.course-card').forEach(c => c.classList.remove('active-course'));
        card.classList.add('active-course');
    }
    applyFilters();
}

function applyFilters() {
    const yearFilter = document.getElementById('yearFilterSelect').value;

    document.querySelectorAll('#studentTableBody tr').forEach(row => {
        const matchCourse = !activeCourseFilter || row.dataset.course === activeCourseFilter;
        const matchYear   = !yearFilter || row.dataset.year === yearFilter;
        row.style.display = (matchCourse && matchYear) ? '' : 'none';
    });

    // Update title
    let label = 'All Students';
    if (activeCourseFilter && yearFilter) {
        label = courseNames[activeCourseFilter] + ' — Year ' + yearFilter;
    } else if (activeCourseFilter) {
        label = courseNames[activeCourseFilter] + ' Students';
    } else if (yearFilter) {
        label = 'Year ' + yearFilter + ' Students';
    }
    document.getElementById('tableTitle').textContent = label;

    // Show/hide clear button
    document.getElementById('clearFilter').style.display =
        (activeCourseFilter || yearFilter) ? 'inline-flex' : 'none';
}

function clearAllFilters() {
    activeCourseFilter = '';
    document.querySelectorAll('.course-card').forEach(c => c.classList.remove('active-course'));
    document.getElementById('yearFilterSelect').value = '';
    applyFilters();
}

// ── Delete handlers ──────────────────────────────────────
document.querySelectorAll('.delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (confirm(`Are you sure you want to delete this student?`)) {
            fetch('delete_student.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    document.getElementById('rowstudents' + id).remove();
                }
            });
        }
    });
});

// ════════════════════════════════════════════════════════
//  CAMERA CAPTURE LOGIC
// ════════════════════════════════════════════════════════
const TOTAL_PHOTOS  = 5;
const COUNTDOWN_SEC = 3;   // seconds countdown before each snap

let stream          = null;
let capturedPhotos  = [];   // array of base64 data URLs
let isCapturing     = false;

const cameraModal     = document.getElementById('cameraModal');
const video           = document.getElementById('cameraVideo');
const canvas          = document.getElementById('captureCanvas');
const ctx             = canvas.getContext('2d');
const captureBtn      = document.getElementById('captureBtn');
const retakeBtn       = document.getElementById('retakeBtn');
const confirmPhotosBtn= document.getElementById('confirmPhotosBtn');
const cameraStatus    = document.getElementById('cameraStatus');
const countdownOverlay= document.getElementById('countdownOverlay');
const countdownNumber = document.getElementById('countdownNumber');
const flashOverlay    = document.getElementById('flashOverlay');

// Open camera modal
document.getElementById('openCameraBtn').addEventListener('click', async () => {
    // Reset state
    resetCameraState();
    cameraModal.classList.add('active');
    await startCamera();
});

// Close camera modal
document.getElementById('closeCameraModal').addEventListener('click', () => {
    closeCameraModal();
});

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
            audio: false
        });
        video.srcObject = stream;
        setStatus('ready', 'Ready — Press "Start Capture" to begin');
        captureBtn.disabled = false;
    } catch (err) {
        setStatus('taking', '⚠ Camera access denied. Please allow camera permissions.');
        captureBtn.disabled = true;
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
    }
    video.srcObject = null;
}

function closeCameraModal() {
    stopCamera();
    cameraModal.classList.remove('active');
}

function resetCameraState() {
    capturedPhotos = [];
    isCapturing    = false;

    // Reset preview cells
    for (let i = 1; i <= TOTAL_PHOTOS; i++) {
        document.getElementById('prev' + i).classList.remove('captured');
        document.getElementById('prevImg' + i).src = '';
        document.getElementById('dot' + i).className = 'photo-dot';
    }

    captureBtn.style.display       = 'flex';
    captureBtn.disabled            = false;
    captureBtn.innerHTML           = '<i class="ri-camera-2-line"></i> Start Capture';
    retakeBtn.style.display        = 'none';
    confirmPhotosBtn.style.display = 'none';
    countdownOverlay.classList.remove('show');
    setStatus('ready', 'Ready — Press "Start Capture" to begin');
}

function setStatus(cls, msg) {
    cameraStatus.className = cls;
    cameraStatus.textContent = msg;
}

// Countdown promise: counts down N seconds, returns when done
function countdown(seconds) {
    return new Promise(resolve => {
        countdownOverlay.classList.add('show');
        let remaining = seconds;
        countdownNumber.textContent = remaining;

        const tick = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(tick);
                countdownOverlay.classList.remove('show');
                resolve();
            } else {
                countdownNumber.textContent = remaining;
            }
        }, 1000);
    });
}

// Flash effect
function flash() {
    return new Promise(resolve => {
        flashOverlay.classList.add('flash');
        setTimeout(() => {
            flashOverlay.style.transition = 'opacity 0.35s ease-out';
            flashOverlay.style.opacity = '0';
            setTimeout(() => {
                flashOverlay.classList.remove('flash');
                flashOverlay.style.transition = '';
                flashOverlay.style.opacity    = '';
                resolve();
            }, 350);
        }, 60);
    });
}

// Capture a single frame from the video
function captureFrame() {
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/png');
}

// Main capture sequence: 5 photos with countdown between each
captureBtn.addEventListener('click', async () => {
    if (isCapturing) return;
    isCapturing = true;
    captureBtn.disabled = true;
    capturedPhotos = [];

    for (let i = 1; i <= TOTAL_PHOTOS; i++) {
        // Mark dot as active
        setDot(i, 'active');
        setStatus('taking', `Photo ${i} of ${TOTAL_PHOTOS} — Get ready...`);

        // Countdown
        await countdown(COUNTDOWN_SEC);

        // Take the photo
        setStatus('taking', `📸 Capturing photo ${i}...`);
        const dataUrl = captureFrame();
        capturedPhotos.push(dataUrl);
        await flash();

        // Update preview
        const prevCell = document.getElementById('prev' + i);
        const prevImg  = document.getElementById('prevImg' + i);
        prevImg.src = dataUrl;
        prevCell.classList.add('captured');
        setDot(i, 'done');

        setStatus('taking', `Photo ${i} saved ✓`);
        await sleep(400);
    }

    // All done
    isCapturing = false;
    captureBtn.style.display        = 'none';
    retakeBtn.style.display         = 'flex';
    confirmPhotosBtn.style.display  = 'flex';
    setStatus('done', '✅ All 5 photos captured! Review & confirm.');
});

// Retake
retakeBtn.addEventListener('click', async () => {
    resetCameraState();
    await startCamera();
});

// Confirm — transfer photos to hidden inputs & close
confirmPhotosBtn.addEventListener('click', () => {
    for (let i = 0; i < capturedPhotos.length; i++) {
        document.getElementById('capturedImage' + (i + 1)).value = capturedPhotos[i];
    }
    // Update thumbnail strip in the form
    updateFormThumbs();
    // Update open-camera button text
    document.getElementById('openCameraBtnText').textContent = '✓ 5 Photos Captured — Retake?';
    document.getElementById('openCameraBtn').classList.add('captured');
    closeCameraModal();
});

function updateFormThumbs() {
    const thumbsEl = document.getElementById('capturedThumbs');
    thumbsEl.innerHTML = '';
    capturedPhotos.forEach(src => {
        const img = document.createElement('img');
        img.src = src;
        thumbsEl.appendChild(img);
    });
}

function setDot(n, state) {
    const dot = document.getElementById('dot' + n);
    dot.className = 'photo-dot' + (state ? ' ' + state : '');
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}
</script>

<?php js_asset(["active_link"]) ?>

<!-- ── Edit Student Modal ─────────────────────── -->
<div class="formDiv--" id="editStudentForm" style="display:none;">
    <div class="modal-header">
        <span class="form-title">Edit Student</span>
        <button type="button" class="close" id="closeEditStudent">&times;</button>
    </div>
    <input type="hidden" id="editStudentId">
    <input type="text" id="editStudentFirst" placeholder="First Name" required>
    <input type="text" id="editStudentLast" placeholder="Last Name" required>
    <input type="email" id="editStudentEmail" placeholder="Email Address" required>
    <select id="editStudentCourse">
        <option value="">Select Course</option>
        <option value="BSHM">Hospitality Management (BSHM)</option>
        <option value="BSIT">Information Technology (BSIT)</option>
        <option value="BSMT">Marine Transportation (BSMT)</option>
        <option value="BSME">Mechanical Engineering (BSME)</option>
        <option value="BSEE">Electrical Engineering (BSEE)</option>
        <option value="BSCrim">Criminology (BSCrim)</option>
        <option value="BSMarE">Marine Engineering (BSMarE)</option>
    </select>
    <select id="editStudentYear">
        <option value="">Select Year Level</option>
        <option value="1">1st Year</option>
        <option value="2">2nd Year</option>
        <option value="3">3rd Year</option>
        <option value="4">4th Year</option>
    </select>
    <select id="editStudentSection">
        <option value="">Select Section</option>
        <option value="A">Section A</option>
        <option value="B">Section B</option>
        <option value="C">Section C</option>
        <option value="D">Section D</option>
    </select>
    <button class="btn-submit" id="saveStudentEdit" style="margin-top:8px;">Save Changes</button>
</div>

<script>
// ── Edit Student ─────────────────────────────────────
const editStudentCourseToFaculty = {
    'BSHM':   'FHMT', 'BSIT': 'FICT', 'BSMT': 'FMTE',
    'BSME':   'FENG', 'BSEE': 'FENG', 'BSCrim': 'FCJS', 'BSMarE': 'FMTE',
};

document.querySelectorAll('.edit-student').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('editStudentId').value      = this.dataset.id;
        document.getElementById('editStudentFirst').value   = this.dataset.firstname;
        document.getElementById('editStudentLast').value    = this.dataset.lastname;
        document.getElementById('editStudentEmail').value   = this.dataset.email;
        document.getElementById('editStudentCourse').value  = this.dataset.course;
        document.getElementById('editStudentYear').value    = this.dataset.year;
        document.getElementById('editStudentSection').value = this.dataset.section;

        document.getElementById('editStudentForm').style.display = 'block';
        overlay.style.display = 'block';
    });
});

document.getElementById('closeEditStudent').addEventListener('click', () => {
    document.getElementById('editStudentForm').style.display = 'none';
    overlay.style.display = 'none';
});

document.getElementById('saveStudentEdit').addEventListener('click', () => {
    const id         = document.getElementById('editStudentId').value;
    const firstName  = document.getElementById('editStudentFirst').value.trim();
    const lastName   = document.getElementById('editStudentLast').value.trim();
    const email      = document.getElementById('editStudentEmail').value.trim();
    const courseCode = document.getElementById('editStudentCourse').value;
    const yearLevel  = document.getElementById('editStudentYear').value;
    const section    = document.getElementById('editStudentSection').value;
    const faculty    = editStudentCourseToFaculty[courseCode] || '';

    if (!firstName || !lastName || !email || !courseCode || !yearLevel || !section) {
        alert('Please fill in all fields.'); return;
    }

    fetch('handle_edit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type:'student', id, firstName, lastName, email, faculty, courseCode, yearLevel, section })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('rowstudents' + id);
            row.cells[1].textContent = firstName + ' ' + lastName;
            row.cells[2].textContent = courseCode;
            row.cells[3].textContent = faculty;
            row.cells[4].textContent = yearLevel;
            row.cells[5].textContent = section;
            row.cells[6].textContent = email;
            // Update data attrs on the edit icon
            const icon = row.querySelector('.edit-student');
            icon.dataset.firstname = firstName;
            icon.dataset.lastname  = lastName;
            icon.dataset.email     = email;
            icon.dataset.faculty   = faculty;
            icon.dataset.course    = courseCode;
            icon.dataset.year      = yearLevel;
            icon.dataset.section   = section;

            document.getElementById('editStudentForm').style.display = 'none';
            overlay.style.display = 'none';
        } else {
            alert('Error: ' + (data.error || 'Could not save changes.'));
        }
    });
});
</script>
</body>
</html>