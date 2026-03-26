<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendanceData = json_decode(file_get_contents("php://input"), true);
    if ($attendanceData) {
        try {
            $sql = "INSERT INTO attendance (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked)  
                VALUES (:studentID, :course, :unit, :attendanceStatus, :date)";

            $stmt = $pdo->prepare($sql);

            foreach ($attendanceData as $data) {
                $studentID = $data['studentID'];
                $attendanceStatus = $data['attendanceStatus'];
                $course = $data['course'];
                $unit = $data['unit'];
                $date = date("Y-m-d");

                $stmt->execute([
                    ':studentID' => $studentID,
                    ':course' => $course,
                    ':unit' => $unit,
                    ':attendanceStatus' => $attendanceStatus,
                    ':date' => $date
                ]);
            }

            $_SESSION['message'] = "Attendance recorded successfully for all entries.";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error inserting attendance data: " . $e->getMessage();
        }
    } else {
        $_SESSION['message'] = "No attendance data received.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">

    <!-- Load face-api first, then script.js — ORDER MATTERS -->
    <script defer src="resources/assets/javascript/face_logics/face-api.min.js"></script>
    <script defer src="resources/assets/javascript/face_logics/script.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
</head>

<body>

    <?php include 'includes/topbar.php'; ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">

            <div id="messageDiv" class="messageDiv" style="display:none;"></div>

            

            <p style="font-size:16px; font-weight:400; color:blue; text-align:center; padding-top:2px;">
                Please select course, unit, and venue first before launching Facial Recognition.
            </p>

            <form class="lecture-options" id="selectForm">
                <select required name="course" id="courseSelect" onChange="updateTable()">
                    <option value="" selected>Select Course</option>
                    <?php
                    $courseNames = getCourseNames();
                    foreach ($courseNames as $course) {
                        echo '<option value="' . $course["courseCode"] . '">' . $course["name"] . '</option>';
                    }
                    ?>
                </select>

                <select required name="unit" id="unitSelect" onChange="onUnitChange()">
                    <option value="" selected>Select Unit</option>
                    <?php
                    $unitNames = getUnitNames();
                    foreach ($unitNames as $unit) {
                        $start       = htmlspecialchars($unit["startTime"]   ?? '');
                        $end         = htmlspecialchars($unit["endTime"]     ?? '');
                        $hasMorning  = intval($unit["has_morning"]   ?? 0);
                        $hasAfternoon= intval($unit["has_afternoon"] ?? 0);
                        $daySched    = htmlspecialchars($unit["day_schedule"] ?? '{}');
                        echo '<option value="' . $unit["unitCode"] . '"'
                            . ' data-start="'       . $start        . '"'
                            . ' data-end="'         . $end          . '"'
                            . ' data-has-morning="' . $hasMorning   . '"'
                            . ' data-has-afternoon="'. $hasAfternoon . '"'
                            . ' data-daysched=\''   . $daySched     . '\''
                            . '>'
                            . $unit["name"] . '</option>';
                    }
                    ?>
                </select>

                <!-- Session selector — shown only when a subject has both morning & afternoon classes -->
                <div id="sessionSelectorWrap" style="display:none; margin-top:6px;">
                    <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Class Session</label>
                    <div style="display:flex; gap:10px;">
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;
                               border:1.5px solid var(--border,#e5e7eb); border-radius:8px;
                               padding:7px 14px; font-size:13px; font-weight:600; color:#3B5BDB;
                               background:#EEF2FF; transition:box-shadow .15s;" id="morningLabel">
                            <input type="radio" name="classSession" id="sessionMorning" value="morning"
                                   style="accent-color:#3B5BDB;" onchange="onSessionChange()">
                            ☀️ Morning (Lecture)
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;
                               border:1.5px solid var(--border,#e5e7eb); border-radius:8px;
                               padding:7px 14px; font-size:13px; font-weight:600; color:#0EA5E9;
                               background:#E0F2FE; transition:box-shadow .15s;" id="afternoonLabel">
                            <input type="radio" name="classSession" id="sessionAfternoon" value="afternoon"
                                   style="accent-color:#0EA5E9;" onchange="onSessionChange()">
                            🌤 Afternoon (Laboratory)
                        </label>
                    </div>
                    <p id="sessionTimeHint" style="font-size:11px; color:var(--text-muted); margin-top:6px;"></p>
                </div>

                <select required name="venue" id="venueSelect" onChange="updateTable()">
                    <option value="" selected>Select Venue</option>
                    <?php
                    $venueNames = getVenueNames();
                    foreach ($venueNames as $venue) {
                        echo '<option value="' . $venue["className"] . '">' . $venue["className"] . '</option>';
                    }
                    ?>
                </select>
            </form>

            <div class="attendance-button">
                <button id="startButton" class="add">Launch Facial Recognition</button>
                <button id="endButton" class="add" style="display:none;">End Attendance Process</button>
                <button id="endAttendance" class="add">END Attendance Taking</button>
            </div>

            <!-- 30-minute re-check indicator (hidden until FR starts) -->
            <div id="reCheckPanel" style="display:none;" class="recheck-panel">
                <div class="recheck-inner">
                    <div class="recheck-icon">🔄</div>
                    <div class="recheck-info">
                        <span class="recheck-label">Next Auto Re-check in</span>
                        <span id="reCheckCountdown" class="recheck-countdown">30:00</span>
                    </div>
                    <div id="reCheckStatus" class="recheck-status idle">Waiting…</div>
                </div>
                <div class="recheck-bar-track">
                    <div id="reCheckBar" class="recheck-bar"></div>
                </div>
            </div>

            <!-- id="videoContainer" is required by script.js -->
            <div id="videoContainer" style="display:none; position:relative;">
                <video id="video" autoplay muted playsinline style="display:block; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,0.18);"></video>
                <canvas id="overlay" style="position:absolute; top:0; left:0; z-index:10; pointer-events:none;"></canvas>
            </div>

            <div class="table-container">
                <div id="studentTableContainer"></div>
            </div>

        </div>
    </section>

    <script src="resources/assets/javascript/active_link.js"></script>


</html>