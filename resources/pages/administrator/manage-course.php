<?php

if (isset($_POST["addCourse"])) {
    $courseName = htmlspecialchars(trim($_POST["courseName"]));
    $courseCode = htmlspecialchars(trim($_POST["courseCode"]));
    $facultyID = filter_var($_POST["faculty"], FILTER_VALIDATE_INT);
    $dateRegistered = date("Y-m-d");

    if ($courseName && $courseCode && $facultyID) {
        $query = $pdo->prepare("SELECT * FROM course WHERE courseCode = :courseCode");
        $query->bindParam(':courseCode', $courseCode);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['message'] = "Course Already Exists";
        } else {
            $query = $pdo->prepare("INSERT INTO course (name, courseCode, facultyID, dateCreated) 
                                     VALUES (:name, :courseCode, :facultyID, :dateCreated)");
            $query->bindParam(':name', $courseName);
            $query->bindParam(':courseCode', $courseCode);
            $query->bindParam(':facultyID', $facultyID);
            $query->bindParam(':dateCreated', $dateRegistered);
            $query->execute();
            $_SESSION['message'] = "Course Inserted Successfully";
        }
    } else {
        $_SESSION['message'] = "Invalid input for course";
    }
}

if (isset($_POST["addUnit"])) {
    $unitName  = htmlspecialchars(trim($_POST["unitName"]));
    $unitCode  = htmlspecialchars(trim($_POST["unitCode"]));
    $courseID  = filter_var($_POST["course"], FILTER_VALIDATE_INT);
    $lectureID = !empty($_POST["lecturer"]) ? filter_var($_POST["lecturer"], FILTER_VALIDATE_INT) : null;
    $dateRegistered = date("Y-m-d");

    // Collect per-day schedules (morning and afternoon sessions) with per-day venue
    $allowedDays     = ['Mon','Tue','Wed','Thu','Fri'];
    $allowedSessions = ['morning','afternoon'];
    $daySchedules    = []; // [day][session] = ['start'=>..,'end'=>..]
    $dayVenues       = []; // [day] = venueID
    foreach ($allowedDays as $d) {
        $venueKey   = "venue_{$d}";
        $dayVenueId = !empty($_POST[$venueKey]) ? filter_var($_POST[$venueKey], FILTER_VALIDATE_INT) : null;
        foreach ($allowedSessions as $sess) {
            $startKey = "startTime_{$d}_{$sess}";
            $endKey   = "endTime_{$d}_{$sess}";
            if (!empty($_POST[$startKey]) && !empty($_POST[$endKey])) {
                $s = htmlspecialchars(trim($_POST[$startKey]));
                $e = htmlspecialchars(trim($_POST[$endKey]));
                if ($e > $s) {
                    $daySchedules[$d][$sess] = ['start' => $s, 'end' => $e];
                    $dayVenues[$d] = $dayVenueId;
                }
            }
        }
    }
    // Use the first active day's venue as the legacy unit venueID
    $venue = null;
    foreach ($dayVenues as $dv) { if ($dv) { $venue = $dv; break; } }
    // Days that have at least one session
    $activeDaysList = array_keys(array_filter($daySchedules));
    $scheduleDays   = !empty($activeDaysList) ? implode(',', $activeDaysList) : null;
    // Legacy startTime/endTime: first morning slot found
    $startTime = null; $endTime = null;
    foreach ($daySchedules as $d => $sessions) {
        if (!empty($sessions['morning'])) {
            $startTime = $sessions['morning']['start'];
            $endTime   = $sessions['morning']['end'];
            break;
        }
    }

    if ($unitName && $unitCode && $courseID && $venue && !empty($daySchedules)) {
        $query = $pdo->prepare("SELECT * FROM unit WHERE unitCode = :unitCode");
        $query->bindParam(':unitCode', $unitCode);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['message'] = "Unit Already Exists";
        } else {
            $query = $pdo->prepare("INSERT INTO unit (name, unitCode, courseID, dateCreated, startTime, endTime, venueID, scheduleDays, lectureID)
                                     VALUES (:name, :unitCode, :courseID, :dateCreated, :startTime, :endTime, :venueID, :scheduleDays, :lectureID)");
            $query->bindParam(':name', $unitName);
            $query->bindParam(':unitCode', $unitCode);
            $query->bindParam(':courseID', $courseID);
            $query->bindParam(':dateCreated', $dateRegistered);
            $query->bindParam(':startTime', $startTime);
            $query->bindParam(':endTime', $endTime);
            $query->bindParam(':venueID', $venue);
            $query->bindParam(':scheduleDays', $scheduleDays);
            $query->bindParam(':lectureID', $lectureID);
            $query->execute();
            $newUnitId = $pdo->lastInsertId();

            // Insert per-day, per-session schedules into unit_schedule
            $schedStmt = $pdo->prepare("INSERT INTO unit_schedule (unitId, day, session, startTime, endTime, venueID)
                                         VALUES (:unitId, :day, :session, :startTime, :endTime, :venueID)
                                         ON DUPLICATE KEY UPDATE startTime=:startTime2, endTime=:endTime2, venueID=:venueID2");
            foreach ($daySchedules as $day => $sessions) {
                $dayVenueId = $dayVenues[$day] ?? $venue;
                foreach ($sessions as $sess => $times) {
                    $schedStmt->execute([
                        ':unitId'     => $newUnitId,
                        ':day'        => $day,
                        ':session'    => $sess,
                        ':startTime'  => $times['start'],
                        ':endTime'    => $times['end'],
                        ':venueID'    => $dayVenueId,
                        ':startTime2' => $times['start'],
                        ':endTime2'   => $times['end'],
                        ':venueID2'   => $dayVenueId,
                    ]);
                }
            }
            $_SESSION['message'] = "Unit Inserted Successfully";
        }
    } else {
        $_SESSION['message'] = "All fields are required. Please set at least one day with start/end times.";
    }
}

if (isset($_POST["addFaculty"])) {
    $facultyName = htmlspecialchars(trim($_POST["facultyName"]));
    $facultyCode = htmlspecialchars(trim($_POST["facultyCode"]));
    $dateRegistered = date("Y-m-d");

    if ($facultyName && $facultyCode) {
        $query = $pdo->prepare("SELECT * FROM faculty WHERE facultyCode = :facultyCode");
        $query->bindParam(':facultyCode', $facultyCode);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['message'] = "Faculty Already Exists";
        } else {
            $query = $pdo->prepare("INSERT INTO faculty (facultyName, facultyCode, dateRegistered) 
                                     VALUES (:facultyName, :facultyCode, :dateRegistered)");
            $query->bindParam(':facultyName', $facultyName);
            $query->bindParam(':facultyCode', $facultyCode);
            $query->bindParam(':dateRegistered', $dateRegistered);
            $query->execute();
            $_SESSION['message'] = "Faculty Inserted Successfully";
        }
    } else {
        $_SESSION['message'] = "Invalid input for faculty";
    }
}

// ── Course overview cards ─────────────────────────────
$cardColors = ['#3B5BDB','#0EA5E9','#10B981','#F59E0B','#8B5CF6','#EF4444','#06B6D4','#EC4899','#F97316'];
$cardIcons  = ['ri-book-2-line','ri-computer-line','ri-ship-line','ri-settings-3-line','ri-flashlight-line','ri-shield-line','ri-anchor-line','ri-hotel-line','ri-microscope-line'];

$courseCardSql = "SELECT 
    c.Id,
    c.name AS course_name,
    c.courseCode,
    f.facultyName AS faculty_name,
    COUNT(DISTINCT u.Id) AS total_units
    FROM course c
    LEFT JOIN unit u ON c.Id = u.courseID
    LEFT JOIN faculty f ON c.facultyID = f.Id
    GROUP BY c.Id";
$courseCardResult = fetch($courseCardSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Manage Courses — AFRAs</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
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
    </style>
</head>

<body>
    <?php include 'includes/topbar.php' ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">
            <div id="overlay"></div>

            <!-- ── Summary Cards (Add buttons) ─────────────────────── -->
            <div class="overview">
                <div class="title">
                    <h2 class="section--title">Overview</h2>
                    <select name="date" id="date" class="dropdown">
                        <option value="today">Today</option>
                        <option value="lastweek">Last Week</option>
                        <option value="lastmonth">Last Month</option>
                        <option value="lastyear">Last Year</option>
                        <option value="alltime">All Time</option>
                    </select>
                </div>
                <div class="cards">
                    <div id="addCourse" class="card card-1">
                        <div class="card--data">
                            <div class="card--content">
                                <button class="add"><i class="ri-add-line"></i>Add Course</button>
                                <h1><?php total_rows('course') ?> Courses</h1>
                            </div>
                            <i class="ri-user-2-line card--icon--lg"></i>
                        </div>
                    </div>
                    <div class="card card-1" id="addUnit">
                        <div class="card--data">
                            <div class="card--content">
                                <button class="add"><i class="ri-add-line"></i>Add Subjects</button>
                                <h1><?php total_rows('unit') ?> Subject</h1>
                            </div>
                            <i class="ri-file-text-line card--icon--lg"></i>
                        </div>
                    </div>
                    <div class="card card-1" id="addFaculty">
                        <div class="card--data">
                            <div class="card--content">
                                <button class="add"><i class="ri-add-line"></i>Add Faculty</button>
                                <h1><?php total_rows("faculty") ?> faculties</h1>
                            </div>
                            <i class="ri-user-line card--icon--lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php showMessage() ?>

            <!-- ── Course Overview Cards ─────────────────────── -->
            <div class="title">
                <h2 class="section--title">Courses by Subject</h2>
            </div>

            <div class="course-overview">
                <?php
                if ($courseCardResult):
                    foreach ($courseCardResult as $idx => $cRow):
                        $color = $cardColors[$idx % count($cardColors)];
                        $icon  = $cardIcons[$idx % count($cardIcons)];
                ?>
                <div class="course-card"
                     style="--course-color: <?= $color ?>"
                     onclick="filterByCourse('<?= htmlspecialchars($cRow['courseCode']) ?>', this)">
                    <div class="course-card-header">
                        <div class="course-card-icon"><i class="<?= $icon ?>"></i></div>
                        <div>
                            <div class="course-card-label"><?= htmlspecialchars($cRow['course_name']) ?></div>
                            <div class="course-card-code"><?= htmlspecialchars($cRow['courseCode']) ?></div>
                        </div>
                    </div>
                    <div class="course-card-count"><?= $cRow['total_units'] ?></div>
                    <div class="course-card-sub">subjects / units</div>
                </div>
                <?php endforeach; else: ?>
                <p style="color:var(--text-muted); font-size:13px;">No courses found.</p>
                <?php endif; ?>
            </div>

            <!-- ── Course Table ─────────────────────── -->
            <div class="table-container">
                <div class="title">
                    <h2 class="section--title">Course</h2>
                </div>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Total Subjects</th>
                                <th>Total Students</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT 
                        c.name AS course_name,
                        c.facultyID AS faculty,
                        f.facultyName AS faculty_name,
                        c.Id AS Id,
                        c.courseCode,
                        COUNT(u.Id) AS total_units,
                        COUNT(DISTINCT s.Id) AS total_students,
                        c.dateCreated AS date_created
                        FROM course c
                        LEFT JOIN unit u ON c.Id = u.courseID
                        LEFT JOIN students s ON c.courseCode = s.courseCode
                        LEFT JOIN faculty f on c.facultyID=f.Id
                        GROUP BY c.Id";
                            $result = fetch($sql);
                            if ($result) {
                                foreach ($result as $row) {
                                    echo "<tr id='rowcourse{$row["Id"]}' data-course='{$row["courseCode"]}'>";
                                    echo "<td>" . $row["course_name"] . "</td>";
                                    echo "<td>" . $row["faculty_name"] . "</td>";
                                    echo "<td>" . $row["total_units"] . "</td>";
                                    echo "<td>" . $row["total_students"] . "</td>";
                                    echo "<td>" . $row["date_created"] . "</td>";
                                    echo "<td>
                                        <span><i class='ri-edit-line edit-course'
                                            data-id='{$row["Id"]}'
                                            data-name='" . htmlspecialchars($row["course_name"]) . "'
                                            data-code='{$row["courseCode"]}'
                                            data-faculty='{$row["faculty"]}'
                                            style='cursor:pointer; margin-right:8px;'></i></span>
                                        <span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='course'></i></span>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Units/Subjects Table ─────────────────────── -->
            <div class="table-container">
                <div class="title">
                    <h2 class="section--title" id="unitTableTitle">All Subjects</h2>
                    <button class="add" id="clearFilter"
                            style="background:var(--text-muted); font-size:12px; display:none;"
                            onclick="clearCourseFilter()">
                        <i class="ri-close-line"></i>Clear Filter
                    </button>
                </div>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Lecturer / Instructor</th>
                                <th>Schedule per Day</th>
                                <th>Venue</th>
                                <th>Total Students</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="unitTableBody">
                            <?php
                            $sql = "SELECT 
                            c.name AS course_name,
                            c.courseCode AS course_code,
                            c.Id AS course_int_id,
                            u.unitCode AS unit_code,
                            u.name AS unit_name, u.Id as Id,
                            u.dateCreated AS date_created,
                            u.startTime AS start_time,
                            u.endTime AS end_time,
                            u.venueID AS venue_id,
                            u.scheduleDays AS schedule_days,
                            u.lectureID AS lecture_id,
                            v.className AS venue_name,
                            CONCAT(l.firstName, ' ', l.lastName) AS lecturer_name,
                            COUNT(s.Id) AS total_students
                            FROM unit u
                            LEFT JOIN course c ON u.courseID = c.Id
                            LEFT JOIN students s ON c.courseCode = s.courseCode
                            LEFT JOIN venue v ON u.venueID = v.Id
                            LEFT JOIN lecture l ON u.lectureID = l.Id
                            GROUP BY u.Id";
                            // Fetch per-day, per-session schedules for all units
                            $unitSchedulesAll = [];
                            $schedRows = fetch("SELECT unitId, day, session, startTime, endTime, venueID FROM unit_schedule ORDER BY FIELD(day,'Mon','Tue','Wed','Thu','Fri'), FIELD(session,'morning','afternoon')");
                            if ($schedRows) {
                                foreach ($schedRows as $sr) {
                                    $unitSchedulesAll[$sr['unitId']][$sr['day']][$sr['session']] = $sr;
                                    // Store per-day venueID (same for all sessions of a day)
                                    if (!empty($sr['venueID'])) {
                                        $unitSchedulesAll[$sr['unitId']][$sr['day']]['venueID'] = $sr['venueID'];
                                    }
                                }
                            }
                            // Build venue lookup map: venueId => className
                            $venueMap = [];
                            foreach (getVenueNames() as $vn) { $venueMap[$vn['Id']] = $vn['className']; }

                            $result = fetch($sql);
                            if ($result) {
                                foreach ($result as $row) {
                                    // Build per-day schedule + venue display
                                    $allDays = ['Mon','Tue','Wed','Thu','Fri'];
                                    $unitDaySched = $unitSchedulesAll[$row['Id']] ?? [];
                                    $activeDays = $row['schedule_days'] ? explode(',', $row['schedule_days']) : array_keys($unitDaySched);
                                    $daySchedHtml = '';
                                    $venueDisplay = '';
                                    $sessionLabels = ['morning' => '☀️ Lecture', 'afternoon' => '🌤 Lab'];
                                    $sessionColors = ['morning' => '#3B5BDB', 'afternoon' => '#0EA5E9'];
                                    $seenDayVenue = []; // track which days we already added a venue badge for
                                    foreach ($allDays as $d) {
                                        if (!in_array($d, $activeDays)) continue;
                                        $daySessions = $unitDaySched[$d] ?? [];
                                        if (empty($daySessions)) continue;
                                        $dayVenueId   = $unitDaySched[$d]['venueID'] ?? null;
                                        $dayVenueName = $dayVenueId ? ($venueMap[$dayVenueId] ?? null) : null;
                                        foreach (['morning','afternoon'] as $sess) {
                                            if (!isset($daySessions[$sess])) continue;
                                            $s = date('h:i A', strtotime($daySessions[$sess]['startTime']));
                                            $e = date('h:i A', strtotime($daySessions[$sess]['endTime']));
                                            $color = $sessionColors[$sess];
                                            $label = $sessionLabels[$sess];
                                            $daySchedHtml .= "<div style='display:flex;align-items:center;gap:6px;margin-bottom:3px;'>"
                                                . "<span style='background:{$color};color:#fff;padding:2px 6px;border-radius:5px;font-size:10px;font-weight:600;min-width:30px;text-align:center;'>{$d}</span>"
                                                . "<span style='background:color-mix(in srgb,{$color} 12%,transparent);color:{$color};padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;'>{$label}</span>"
                                                . "<span style='font-size:11px;color:var(--text);'>{$s} – {$e}</span></div>";
                                        }
                                        // Per-day venue badge (once per day)
                                        if (!isset($seenDayVenue[$d])) {
                                            $seenDayVenue[$d] = true;
                                            $vLabel = $dayVenueName ? htmlspecialchars($dayVenueName) : '<span style="color:var(--text-muted);font-size:11px;">—</span>';
                                            $venueDisplay .= "<div style='display:flex;align-items:center;gap:6px;margin-bottom:3px;'>"
                                                . "<span style='background:#6c757d;color:#fff;padding:2px 6px;border-radius:5px;font-size:10px;font-weight:600;min-width:30px;text-align:center;'>{$d}</span>"
                                                . "<span style='font-size:11px;color:var(--text);'>{$vLabel}</span></div>";
                                        }
                                    }
                                    $daysDisplay   = $daySchedHtml ?: '<span style="color:var(--text-muted);font-size:12px;">No schedule set</span>';
                                    $venueDisplay  = $venueDisplay  ?: '<span style="color:var(--text-muted);font-size:12px;">No venue set</span>';
                                    // Build JSON of per-day, per-session schedules for edit modal
                                    $daySchedJson = htmlspecialchars(json_encode($unitDaySched));
                                    $lecturerDisplay = $row['lecturer_name']
                                        ? htmlspecialchars($row['lecturer_name'])
                                        : '<span style="color:var(--text-muted);font-size:12px;">No lecturer assigned</span>';
                                    echo "<tr id='rowunit{$row["Id"]}' data-course='{$row["course_code"]}'>";
                                    echo "<td>" . $row["unit_code"] . "</td>";
                                    echo "<td>" . $row["unit_name"] . "</td>";
                                    echo "<td>" . $row["course_name"] . "</td>";
                                    echo "<td>" . $lecturerDisplay . "</td>";
                                    echo "<td>" . $daysDisplay . "</td>";
                                    echo "<td>" . $venueDisplay . "</td>";
                                    echo "<td>" . $row["total_students"] . "</td>";
                                    echo "<td>" . $row["date_created"] . "</td>";
                                    echo "<td>
                                        <span><i class='ri-edit-line edit-unit'
                                            data-id='{$row["Id"]}'
                                            data-name='" . htmlspecialchars($row["unit_name"]) . "'
                                            data-code='" . htmlspecialchars($row["unit_code"]) . "'
                                            data-courseid='" . intval($row["course_int_id"]) . "'
                                            data-venue='" . ($row["venue_id"] ?? '') . "'
                                            data-lectureid='" . ($row["lecture_id"] ?? '') . "'
                                            data-days='" . htmlspecialchars($row["schedule_days"] ?? '') . "'
                                            data-daysched='{$daySchedJson}'
                                            style='cursor:pointer; margin-right:8px;'></i></span>
                                        <span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='unit'></i></span>
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

            <!-- ── Faculty Table ─────────────────────── -->
            <div class="table-container">
                <div class="title">
                    <h2 class="section--title">Faculty</h2>
                </div>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Total Courses</th>
                                <th>Total Students</th>
                                <th>Total Lectures</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT 
                           f.facultyName AS faculty_name,
                           f.facultyCode AS faculty_code,
                           f.Id as Id,
                           f.dateRegistered AS date_created,
                           COUNT(DISTINCT c.Id) AS total_courses,
                           COUNT(DISTINCT s.Id) AS total_students,
                           COUNT(DISTINCT l.Id) AS total_lectures
                       FROM faculty f
                       LEFT JOIN course c ON f.Id = c.facultyID
                       LEFT JOIN students s ON f.facultyCode = s.faculty
                       LEFT JOIN lecture l ON f.facultyCode = l.facultyCode
                       GROUP BY f.Id";
                            $result = fetch($sql);
                            if ($result) {
                                foreach ($result as $row) {
                                    echo "<tr id='rowfaculty{$row["Id"]}'>";
                                    echo "<td>" . $row["faculty_code"] . "</td>";
                                    echo "<td>" . $row["faculty_name"] . "</td>";
                                    echo "<td>" . $row["total_courses"] . "</td>";
                                    echo "<td>" . $row["total_students"] . "</td>";
                                    echo "<td>" . $row["total_lectures"] . "</td>";
                                    echo "<td>" . $row["date_created"] . "</td>";
                                    echo "<td>
                                        <span><i class='ri-edit-line edit-faculty'
                                            data-id='{$row["Id"]}'
                                            data-name='" . htmlspecialchars($row["faculty_name"]) . "'
                                            data-code='" . htmlspecialchars($row["faculty_code"]) . "'
                                            style='cursor:pointer; margin-right:8px;'></i></span>
                                        <span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='faculty'></i></span>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── Add Course Modal ─────────────────────── -->
        <div class="formDiv" id="addCourseForm" style="display:none;">
            <form method="POST" action="" name="addCourse" enctype="multipart/form-data">
                <div style="display:flex; justify-content:space-around;">
                    <div class="form-title"><p>Add Course</p></div>
                    <div><span class="close">&times;</span></div>
                </div>
                <input type="text" name="courseName" placeholder="Course Name" required>
                <input type="text" name="courseCode" placeholder="Course Code" required>
                <select required name="faculty">
                    <option value="" selected>Select Faculty</option>
                    <?php
                    $facultyNames = getFacultyNames();
                    foreach ($facultyNames as $faculty) {
                        echo '<option value="' . $faculty["Id"] . '">' . $faculty["facultyName"] . '</option>';
                    }
                    ?>
                </select>
                <input type="submit" class="submit" value="Save Course" name="addCourse">
            </form>
        </div>

        <!-- ── Add Unit Modal ─────────────────────── -->
        <div class="formDiv" id="addUnitForm" style="display:none; max-height:90vh; overflow-y:auto;">
            <form method="POST" action="" name="addUnit" enctype="multipart/form-data">
                <div style="display:flex; justify-content:space-around;">
                    <div class="form-title"><p>Add Subjects</p></div>
                    <div><span class="close">&times;</span></div>
                </div>
                <input type="text" name="unitName" placeholder="Subject Name" required>
                <input type="text" name="unitCode" placeholder="Subject Code" required>
                <select required name="course">
                    <option value="" selected>Select Course</option>
                    <?php
                    $courseNames = getCourseNames();
                    foreach ($courseNames as $course) {
                        echo '<option value="' . $course["Id"] . '">' . $course["name"] . '</option>';
                    }
                    ?>
                </select>

                <select name="lecturer" style="margin-top:6px;">
                    <option value="" selected>Select Lecturer / Instructor</option>
                    <?php
                    $lectureNames = getLectureNames();
                    foreach ($lectureNames as $lec) {
                        echo '<option value="' . $lec["Id"] . '">' . htmlspecialchars($lec["firstName"] . ' ' . $lec["lastName"]) . '</option>';
                    }
                    ?>
                </select>

                <!-- Per-day schedule (morning + afternoon) -->
                <label style="font-size:13px; font-weight:600; color:var(--text); margin:10px 0 8px; display:block;">Class Schedule per Day</label>
                <p style="font-size:11px; color:var(--text-muted); margin-bottom:10px;">Check a day, then set times for the session(s) you need. Leave a session blank to skip it.</p>
                <?php foreach (['Mon','Tue','Wed','Thu','Fri'] as $day): ?>
                <div class="add-day-row" id="addDayRow_<?= $day ?>" style="border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:10px 12px; margin-bottom:8px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:6px;">
                        <input type="checkbox" class="add-day-toggle" data-day="<?= $day ?>"
                               style="accent-color:var(--primary,#3B5BDB); width:15px; height:15px;"
                               onchange="toggleAddDayTime('<?= $day ?>', this.checked)">
                        <span style="font-size:13px; font-weight:700; color:var(--text); min-width:34px;"><?= $day ?></span>
                    </label>
                    <div id="addDayTime_<?= $day ?>" style="display:none; padding-left:23px;">
                        <!-- Morning / Lecture -->
                        <div style="margin-bottom:8px;">
                            <div style="font-size:11px; font-weight:700; color:#3B5BDB; margin-bottom:4px;">☀️ Morning (Lecture)</div>
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                <div>
                                    <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">Start</label>
                                    <input type="time" name="startTime_<?= $day ?>_morning" id="addStart_<?= $day ?>_morning"
                                           style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">End</label>
                                    <input type="time" name="endTime_<?= $day ?>_morning" id="addEnd_<?= $day ?>_morning"
                                           style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                                </div>
                            </div>
                        </div>
                        <!-- Afternoon / Laboratory -->
                        <div style="margin-bottom:8px;">
                            <div style="font-size:11px; font-weight:700; color:#0EA5E9; margin-bottom:4px;">🌤 Afternoon (Laboratory)</div>
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                <div>
                                    <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">Start</label>
                                    <input type="time" name="startTime_<?= $day ?>_afternoon" id="addStart_<?= $day ?>_afternoon"
                                           style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">End</label>
                                    <input type="time" name="endTime_<?= $day ?>_afternoon" id="addEnd_<?= $day ?>_afternoon"
                                           style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                                </div>
                            </div>
                        </div>
                        <!-- Per-day Venue -->
                        <div style="margin-top:4px;">
                            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">📍 Venue for <?= $day ?></label>
                            <select name="venue_<?= $day ?>" style="padding:6px 10px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px; width:100%; max-width:260px;">
                                <option value="">Select Venue</option>
                                <?php
                                $venueNames = getVenueNames();
                                foreach ($venueNames as $v) {
                                    echo '<option value="' . $v["Id"] . '">' . htmlspecialchars($v["className"]) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <input type="submit" class="submit" value="Save Unit" name="addUnit">
            </form>
        </div>

        <!-- ── Add Faculty Modal ─────────────────────── -->
        <div class="formDiv" id="addFacultyForm" style="display:none;">
            <form method="POST" action="" name="addFaculty" enctype="multipart/form-data">
                <div style="display:flex; justify-content:space-around;">
                    <div class="form-title"><p>Add Faculty</p></div>
                    <div><span class="close">&times;</span></div>
                </div>
                <input type="text" name="facultyName" placeholder="Faculty Name" required>
                <input type="text" name="facultyCode" placeholder="Faculty Code" required>
                <input type="submit" class="submit" value="Save Faculty" name="addFaculty">
            </form>
        </div>

    </section>

    <script>
    // ── Course card filter ───────────────────────────────────
    function filterByCourse(code, card) {
        // Toggle: click same active card = clear filter
        if (card.classList.contains('active-course')) {
            clearCourseFilter();
            return;
        }
        document.querySelectorAll('.course-card').forEach(c => c.classList.remove('active-course'));
        card.classList.add('active-course');

        // Filter subjects/units table
        document.querySelectorAll('#unitTableBody tr').forEach(row => {
            row.style.display = (row.dataset.course === code) ? '' : 'none';
        });

        const label = card.querySelector('.course-card-label').textContent;
        document.getElementById('unitTableTitle').textContent = label + ' — Subjects / Units';
        document.getElementById('clearFilter').style.display = 'inline-flex';
    }

    function clearCourseFilter() {
        document.querySelectorAll('.course-card').forEach(c => c.classList.remove('active-course'));
        document.querySelectorAll('#unitTableBody tr').forEach(row => row.style.display = '');
        document.getElementById('unitTableTitle').textContent = 'All Subjects / Units';
        document.getElementById('clearFilter').style.display = 'none';
    }
    </script>

    <?php js_asset(["delete_request", "addCourse", "active_link"]) ?>

    <!-- ══════════════════════════════════════════════
         EDIT MODALS
    ══════════════════════════════════════════════ -->

    <!-- Edit Course Modal -->
    <div class="formDiv" id="editCourseForm" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="form-title"><p>Edit Course</p></div>
            <span class="close" id="closeEditCourse">&times;</span>
        </div>
        <input type="hidden" id="editCourseId">
        <input type="text" id="editCourseName" placeholder="Course Name" required>
        <input type="text" id="editCourseCode" placeholder="Course Code" required>
        <select id="editCourseFaculty">
            <option value="">Select Faculty</option>
            <?php foreach (getFacultyNames() as $f): ?>
            <option value="<?= $f['Id'] ?>"><?= htmlspecialchars($f['facultyName']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="submit" id="saveCourseEdit" style="width:100%; margin-top:8px;">Save Changes</button>
    </div>

    <!-- Edit Unit Modal -->
    <div class="formDiv" id="editUnitForm" style="display:none; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="form-title"><p>Edit Subject</p></div>
            <span class="close" id="closeEditUnit">&times;</span>
        </div>
        <input type="hidden" id="editUnitId">
        <input type="text" id="editUnitName" placeholder="Subject Name" required>
        <input type="text" id="editUnitCode" placeholder="Subject Code" required>
        <select id="editUnitCourse">
            <option value="">Select Course</option>
            <?php foreach (getCourseNames() as $c): ?>
            <option value="<?= $c['Id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="editUnitLecturer" style="margin-top:6px;">
            <option value="">Select Lecturer / Instructor</option>
            <?php foreach (getLectureNames() as $lec): ?>
            <option value="<?= $lec['Id'] ?>"><?= htmlspecialchars($lec['firstName'] . ' ' . $lec['lastName']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Per-day schedule for edit (morning + afternoon) -->
        <label style="font-size:13px; font-weight:600; color:var(--text); margin:10px 0 8px; display:block;">Class Schedule per Day</label>
        <p style="font-size:11px; color:var(--text-muted); margin-bottom:10px;">Check a day, then set times for the session(s) you need. Leave a session blank to skip it.</p>
        <?php foreach (['Mon','Tue','Wed','Thu','Fri'] as $day): ?>
        <div class="edit-day-row" id="editDayRow_<?= $day ?>" style="border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:10px 12px; margin-bottom:8px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:6px;">
                <input type="checkbox" class="edit-day-cb" value="<?= $day ?>"
                       style="accent-color:var(--primary,#3B5BDB); width:15px; height:15px;"
                       onchange="toggleEditDayTime('<?= $day ?>', this.checked)">
                <span style="font-size:13px; font-weight:700; color:var(--text); min-width:34px;"><?= $day ?></span>
            </label>
            <div id="editDayTime_<?= $day ?>" style="display:none; padding-left:23px;">
                <!-- Morning / Lecture -->
                <div style="margin-bottom:8px;">
                    <div style="font-size:11px; font-weight:700; color:#3B5BDB; margin-bottom:4px;">☀️ Morning (Lecture)</div>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <div>
                            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">Start</label>
                            <input type="time" id="editStart_<?= $day ?>_morning"
                                   style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                        </div>
                        <div>
                            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">End</label>
                            <input type="time" id="editEnd_<?= $day ?>_morning"
                                   style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                        </div>
                    </div>
                </div>
                <!-- Afternoon / Laboratory -->
                <div style="margin-bottom:8px;">
                    <div style="font-size:11px; font-weight:700; color:#0EA5E9; margin-bottom:4px;">🌤 Afternoon (Laboratory)</div>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <div>
                            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">Start</label>
                            <input type="time" id="editStart_<?= $day ?>_afternoon"
                                   style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                        </div>
                        <div>
                            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">End</label>
                            <input type="time" id="editEnd_<?= $day ?>_afternoon"
                                   style="padding:6px 8px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px;">
                        </div>
                    </div>
                </div>
                <!-- Per-day Venue -->
                <div style="margin-top:4px;">
                    <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">📍 Venue for <?= $day ?></label>
                    <select id="editVenue_<?= $day ?>" style="padding:6px 10px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:13px; width:100%; max-width:260px;">
                        <option value="">Select Venue</option>
                        <?php foreach (getVenueNames() as $v): ?>
                        <option value="<?= $v['Id'] ?>"><?= htmlspecialchars($v['className']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <button class="submit" id="saveUnitEdit" style="width:100%; margin-top:8px;">Save Changes</button>
    </div>

    <!-- Edit Faculty Modal -->
    <div class="formDiv" id="editFacultyForm" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="form-title"><p>Edit Faculty</p></div>
            <span class="close" id="closeEditFaculty">&times;</span>
        </div>
        <input type="hidden" id="editFacultyId">
        <input type="text" id="editFacultyName" placeholder="Faculty Name" required>
        <input type="text" id="editFacultyCode" placeholder="Faculty Code" required>
        <button class="submit" id="saveFacultyEdit" style="width:100%; margin-top:8px;">Save Changes</button>
    </div>

    <script>
    // ── Shared helpers ───────────────────────────────────
    const overlay2 = document.getElementById('overlay');

    function openEditModal(formId) {
        document.getElementById(formId).style.display = 'block';
        overlay2.style.display = 'block';
    }
    function closeEditModal(formId) {
        document.getElementById(formId).style.display = 'none';
        overlay2.style.display = 'none';
    }

    ['closeEditCourse','closeEditUnit','closeEditFaculty'].forEach(id => {
        document.getElementById(id).addEventListener('click', () => {
            document.querySelectorAll('#editCourseForm,#editUnitForm,#editFacultyForm').forEach(f => f.style.display='none');
            overlay2.style.display = 'none';
        });
    });
    overlay2.addEventListener('click', () => {
        document.querySelectorAll('#editCourseForm,#editUnitForm,#editFacultyForm').forEach(f => f.style.display='none');
    });

    function editPost(payload, successCb) {
        fetch('handle_edit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { successCb(); }
            else { alert('Error: ' + (data.error || 'Could not save.')); }
        });
    }

    // ── Course edit ──────────────────────────────────────
    document.querySelectorAll('.edit-course').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editCourseId').value    = this.dataset.id;
            document.getElementById('editCourseName').value  = this.dataset.name;
            document.getElementById('editCourseCode').value  = this.dataset.code;
            document.getElementById('editCourseFaculty').value = this.dataset.faculty;
            openEditModal('editCourseForm');
        });
    });
    document.getElementById('saveCourseEdit').addEventListener('click', () => {
        const id         = document.getElementById('editCourseId').value;
        const name       = document.getElementById('editCourseName').value.trim();
        const courseCode = document.getElementById('editCourseCode').value.trim();
        const facultyID  = document.getElementById('editCourseFaculty').value;
        if (!name || !courseCode || !facultyID) { alert('All fields are required.'); return; }
        editPost({ type:'course', id, name, courseCode, facultyID }, () => {
            const row = document.getElementById('rowcourse' + id);
            row.cells[0].textContent = name;
            // faculty name: get selected option text
            const sel = document.getElementById('editCourseFaculty');
            row.cells[1].textContent = sel.options[sel.selectedIndex].text;
            // update edit btn data attrs
            const icon = row.querySelector('.edit-course');
            icon.dataset.name    = name;
            icon.dataset.code    = courseCode;
            icon.dataset.faculty = facultyID;
            closeEditModal('editCourseForm');
        });
    });

    const DAYS = ['Mon','Tue','Wed','Thu','Fri'];
    const SESSIONS = ['morning','afternoon'];

    // ── Add Unit: toggle day time row ───────────────────
    function toggleAddDayTime(day, show) {
        const el = document.getElementById('addDayTime_' + day);
        if (el) el.style.display = show ? 'block' : 'none';
        if (!show) {
            SESSIONS.forEach(sess => {
                const s = document.getElementById(`addStart_${day}_${sess}`);
                const e = document.getElementById(`addEnd_${day}_${sess}`);
                if (s) s.value = '';
                if (e) e.value = '';
            });
        }
    }

    // ── Edit Unit: toggle day time row ──────────────────
    function toggleEditDayTime(day, show) {
        const el = document.getElementById('editDayTime_' + day);
        if (el) el.style.display = show ? 'block' : 'none';
        if (!show) {
            SESSIONS.forEach(sess => {
                const s = document.getElementById(`editStart_${day}_${sess}`);
                const e = document.getElementById(`editEnd_${day}_${sess}`);
                if (s) s.value = '';
                if (e) e.value = '';
            });
            const v = document.getElementById(`editVenue_${day}`);
            if (v) v.value = '';
        }
    }

    // Format "13:30:00" → "1:30 PM"
    function fmt24to12(t) {
        if (!t) return '';
        const parts = t.split(':');
        let h = parseInt(parts[0]), m = parts[1] || '00';
        const ampm = h < 12 ? 'AM' : 'PM';
        if (h === 0) h = 12;
        else if (h > 12) h -= 12;
        return `${h}:${m} ${ampm}`;
    }

    // ── Unit edit: open modal ────────────────────────────
    document.querySelectorAll('.edit-unit').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editUnitId').value    = this.dataset.id;
            document.getElementById('editUnitName').value  = this.dataset.name;
            document.getElementById('editUnitCode').value  = this.dataset.code;
            document.getElementById('editUnitCourse').value = this.dataset.courseid || '';
            document.getElementById('editUnitLecturer').value = this.dataset.lectureid || '';

            // Parse per-day, per-session schedules: { day: { session: { startTime, endTime }, venueID } }
            let daySched = {};
            try { daySched = JSON.parse(this.dataset.daysched || '{}'); } catch(e) {}

            const activeDays = (this.dataset.days || '').split(',').map(d => d.trim()).filter(Boolean);

            // Reset all
            DAYS.forEach(d => {
                const cb = document.querySelector(`#editDayRow_${d} .edit-day-cb`);
                const timeEl = document.getElementById('editDayTime_' + d);
                if (cb) cb.checked = false;
                if (timeEl) timeEl.style.display = 'none';
                SESSIONS.forEach(sess => {
                    const s = document.getElementById(`editStart_${d}_${sess}`);
                    const e = document.getElementById(`editEnd_${d}_${sess}`);
                    if (s) s.value = '';
                    if (e) e.value = '';
                });
                const v = document.getElementById(`editVenue_${d}`);
                if (v) v.value = '';
            });

            activeDays.forEach(d => {
                const cb = document.querySelector(`#editDayRow_${d} .edit-day-cb`);
                const timeEl = document.getElementById('editDayTime_' + d);
                if (cb) cb.checked = true;
                if (timeEl) timeEl.style.display = 'block';
                if (daySched[d]) {
                    SESSIONS.forEach(sess => {
                        const sessData = daySched[d][sess];
                        if (sessData) {
                            const s = document.getElementById(`editStart_${d}_${sess}`);
                            const e = document.getElementById(`editEnd_${d}_${sess}`);
                            if (s) s.value = (sessData.startTime || '').substring(0,5);
                            if (e) e.value = (sessData.endTime   || '').substring(0,5);
                        }
                    });
                    // Restore per-day venue
                    const venueId = daySched[d].venueID || '';
                    const v = document.getElementById(`editVenue_${d}`);
                    if (v) v.value = venueId;
                }
            });

            openEditModal('editUnitForm');
        });
    });

    document.getElementById('saveUnitEdit').addEventListener('click', () => {
        const id        = document.getElementById('editUnitId').value;
        const name      = document.getElementById('editUnitName').value.trim();
        const unitCode  = document.getElementById('editUnitCode').value.trim();
        const courseID  = document.getElementById('editUnitCourse').value;
        const lectureID = document.getElementById('editUnitLecturer').value;
        // Collect per-day venues
        const dayVenues = {};
        DAYS.forEach(d => {
            const v = document.getElementById(`editVenue_${d}`);
            if (v && v.value) dayVenues[d] = v.value;
        });

        // Collect per-day, per-session schedules
        // daySchedules: { day: { session: { start, end } } }
        const daySchedules = {};
        let hasAnyDay  = false;
        let hasTimeErr = false;

        DAYS.forEach(d => {
            const cb = document.querySelector(`#editDayRow_${d} .edit-day-cb`);
            if (!cb || !cb.checked) return;
            const sessData = {};
            SESSIONS.forEach(sess => {
                const s = (document.getElementById(`editStart_${d}_${sess}`)?.value || '').trim();
                const e = (document.getElementById(`editEnd_${d}_${sess}`)?.value   || '').trim();
                if (!s && !e) return; // blank session = not used
                if (!s || !e) { hasTimeErr = true; alert(`Please set both start and end time for ${d} ${sess} session.`); return; }
                if (e <= s)   { hasTimeErr = true; alert(`End time must be after start time for ${d} ${sess} session.`); return; }
                sessData[sess] = { start: s + ':00', end: e + ':00' };
            });
            if (Object.keys(sessData).length > 0) {
                daySchedules[d] = sessData;
                hasAnyDay = true;
            }
        });

        if (hasTimeErr) return;
        if (!hasAnyDay) { alert('Please select at least one day with a schedule.'); return; }
        if (!name || !unitCode || !courseID) { alert('Name, code and course are required.'); return; }

        const scheduleDays = Object.keys(daySchedules).join(',');
        // Legacy startTime/endTime: first morning session found
        let legacyStart = null, legacyEnd = null;
        for (const d of Object.keys(daySchedules)) {
            if (daySchedules[d].morning) { legacyStart = daySchedules[d].morning.start; legacyEnd = daySchedules[d].morning.end; break; }
            if (daySchedules[d].afternoon) { legacyStart = daySchedules[d].afternoon.start; legacyEnd = daySchedules[d].afternoon.end; break; }
        }

        // First active day's venue as legacy venueID
        let venue = null;
        for (const d of Object.keys(daySchedules)) { if (dayVenues[d]) { venue = dayVenues[d]; break; } }

        editPost({ type:'unit', id, name, unitCode, courseID, venue, lectureID, scheduleDays, daySchedules, dayVenues,
                   startTime: legacyStart, endTime: legacyEnd }, () => {
            const row = document.getElementById('rowunit' + id);
            row.cells[0].textContent = unitCode;
            row.cells[1].textContent = name;

            // Lecturer cell (index 3)
            const lecSel  = document.getElementById('editUnitLecturer');
            const lecText = lecSel.options[lecSel.selectedIndex]?.text || '';
            row.cells[3].innerHTML = lectureID && lecText
                ? lecText
                : '<span style="color:var(--text-muted);font-size:12px;">No lecturer assigned</span>';

            // Schedule cell (index 4)
            const sessColors = { morning:'#3B5BDB', afternoon:'#0EA5E9' };
            const sessLabels = { morning:'☀️ Lecture', afternoon:'🌤 Lab' };
            let dayHtml = '';
            DAYS.forEach(d => {
                if (!daySchedules[d]) return;
                SESSIONS.forEach(sess => {
                    if (!daySchedules[d][sess]) return;
                    const s = fmt24to12(daySchedules[d][sess].start);
                    const e = fmt24to12(daySchedules[d][sess].end);
                    const c = sessColors[sess];
                    dayHtml += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                        <span style="background:${c};color:#fff;padding:2px 6px;border-radius:5px;font-size:10px;font-weight:600;min-width:30px;text-align:center;">${d}</span>
                        <span style="background:color-mix(in srgb,${c} 12%,transparent);color:${c};padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;">${sessLabels[sess]}</span>
                        <span style="font-size:11px;">${s} – ${e}</span></div>`;
                });
            });
            row.cells[4].innerHTML = dayHtml || '<span style="color:var(--text-muted);font-size:12px;">No schedule set</span>';

            // Venue cell (index 5) — show per-day venue badges matching PHP output
            let venueCellHtml = '';
            DAYS.forEach(d => {
                if (!daySchedules[d]) return;
                const vSel = document.getElementById(`editVenue_${d}`);
                const vText = vSel && vSel.value ? (vSel.options[vSel.selectedIndex]?.text || '') : '';
                const vLabel = vText
                    ? `<span style="font-size:11px;color:var(--text);">${vText}</span>`
                    : `<span style="color:var(--text-muted);font-size:11px;">—</span>`;
                venueCellHtml += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                    <span style="background:#6c757d;color:#fff;padding:2px 6px;border-radius:5px;font-size:10px;font-weight:600;min-width:30px;text-align:center;">${d}</span>
                    ${vLabel}</div>`;
            });
            row.cells[5].innerHTML = venueCellHtml || '<span style="color:var(--text-muted);font-size:12px;">No venue set</span>';

            const icon = row.querySelector('.edit-unit');
            icon.dataset.name      = name;
            icon.dataset.code      = unitCode;
            icon.dataset.venue     = venue;
            icon.dataset.lectureid = lectureID;
            icon.dataset.days      = scheduleDays;
            // Store as { day: { session: { startTime, endTime }, venueID } }
            icon.dataset.daysched  = JSON.stringify(
                Object.fromEntries(Object.entries(daySchedules).map(([d, sessions]) => [
                    d,
                    Object.assign(
                        Object.fromEntries(Object.entries(sessions).map(([sess, v]) => [
                            sess, { startTime: v.start, endTime: v.end }
                        ])),
                        { venueID: dayVenues[d] || '' }
                    )
                ]))
            );
            icon.dataset.venue = venue || '';
            closeEditModal('editUnitForm');
        });
    });

    // ── Faculty edit ─────────────────────────────────────
    document.querySelectorAll('.edit-faculty').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editFacultyId').value   = this.dataset.id;
            document.getElementById('editFacultyName').value = this.dataset.name;
            document.getElementById('editFacultyCode').value = this.dataset.code;
            openEditModal('editFacultyForm');
        });
    });
    document.getElementById('saveFacultyEdit').addEventListener('click', () => {
        const id          = document.getElementById('editFacultyId').value;
        const facultyName = document.getElementById('editFacultyName').value.trim();
        const facultyCode = document.getElementById('editFacultyCode').value.trim();
        if (!facultyName || !facultyCode) { alert('All fields are required.'); return; }
        editPost({ type:'faculty', id, facultyName, facultyCode }, () => {
            const row = document.getElementById('rowfaculty' + id);
            row.cells[0].textContent = facultyCode;
            row.cells[1].textContent = facultyName;
            const icon = row.querySelector('.edit-faculty');
            icon.dataset.name = facultyName;
            icon.dataset.code = facultyCode;
            closeEditModal('editFacultyForm');
        });
    });
    </script>
</body>

</html>