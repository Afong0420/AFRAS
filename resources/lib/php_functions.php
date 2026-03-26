<?php
function user()
{
    if (isset($_SESSION['user'])) {
        return (object) $_SESSION['user'];
    }
    return null;
}

function getFacultyNames()
{
    global $pdo;
    $sql = "SELECT * FROM faculty";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $facultyNames = array();
    if ($result) {
        foreach ($result as $row) {
            $facultyNames[] = $row;
        }
    }

    return $facultyNames;
}
function getLectureNames()
{
    global $pdo;
    $sql = "SELECT Id, firstName, lastName FROM lecture";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lectureNames = array();
    if ($result) {
        foreach ($result as $row) {
            $lectureNames[] = $row;
        }
    }

    return $lectureNames;
}
function getCourseNames()
{
    global $pdo;
    $sql = "SELECT * FROM course";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courseNames = array();
    if ($result) {
        foreach ($result as $row) {
            $courseNames[] = $row;
        }
    }

    return $courseNames;
}
function getVenueNames()
{
    $sql = "SELECT Id, className FROM venue";
    $result =  fetch($sql);

    $venueNames = array();
    if ($result) {
        foreach ($result as $row) {
            $venueNames[] = $row;
        }
    }

    return $venueNames;
}
function getUnitNames()
{
    // Fetch units with per-day schedule and venue info from unit_schedule + venue
    $sql = "SELECT u.unitCode, u.name, u.startTime, u.endTime, u.scheduleDays,
                   GROUP_CONCAT(DISTINCT us.day ORDER BY FIELD(us.day,'Mon','Tue','Wed','Thu','Fri') SEPARATOR ',') AS sched_days
            FROM unit u
            LEFT JOIN unit_schedule us ON us.unitId = u.Id
            GROUP BY u.Id";
    $units = fetch($sql);
    if (!$units) return [];

    // Fetch all unit_schedule rows with venue names
    $schedSql = "SELECT us.unitId, us.day, us.session, us.startTime, us.endTime,
                        us.venueID, v.className AS venueName
                 FROM unit_schedule us
                 LEFT JOIN venue v ON v.Id = us.venueID
                 ORDER BY FIELD(us.day,'Mon','Tue','Wed','Thu','Fri'),
                          FIELD(us.session,'morning','afternoon')";
    $schedRows = fetch($schedSql);
    $unitSchedMap = []; // unitId not available directly — key by unitCode via a second lookup
    $unitIdMap    = []; // unitCode => unitId

    // Fetch unit Ids
    $idRows = fetch("SELECT Id, unitCode FROM unit");
    foreach (($idRows ?: []) as $ir) { $unitIdMap[$ir['unitCode']] = $ir['Id']; }

    foreach (($schedRows ?: []) as $sr) {
        $uid = $sr['unitId'];
        $d   = $sr['day'];
        if (!isset($unitSchedMap[$uid][$d])) {
            $unitSchedMap[$uid][$d] = ['venueID' => $sr['venueID'], 'venueName' => $sr['venueName'], 'sessions' => []];
        }
        $unitSchedMap[$uid][$d]['sessions'][$sr['session']] = [
            'startTime' => $sr['startTime'], 'endTime' => $sr['endTime']
        ];
    }

    $unitNames = [];
    foreach ($units as $row) {
        $uid      = $unitIdMap[$row['unitCode']] ?? null;
        $dayData  = $uid ? ($unitSchedMap[$uid] ?? []) : [];
        $row['day_schedule'] = json_encode($dayData); // { Mon: {venueID, venueName, sessions:{morning:{...}}} }
        $row['has_morning']   = 0;
        $row['has_afternoon'] = 0;
        foreach ($dayData as $d => $dd) {
            if (isset($dd['sessions']['morning']))   $row['has_morning']   = 1;
            if (isset($dd['sessions']['afternoon'])) $row['has_afternoon'] = 1;
        }
        $unitNames[] = $row;
    }
    return $unitNames;
}

function showMessage(): void
{
    if (isset($_SESSION['message'])) {
        echo " <div id='messageDiv' class='messageDiv' >{$_SESSION['message']}</div>";
        echo `<script>
        
         var messageDiv = document.getElementById('messageDiv');
    messageDiv.style.opacity = 1;
    setTimeout(function() {
      messageDiv.style.opacity = 0;
    }, 5000);
        </script>`;

        unset($_SESSION['message']);
    }
}


function total_rows($tablename)
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM {$tablename}");
    $total_rows = $stmt->rowCount();
    echo $total_rows;
}

function fetch($sql)
{
    global $pdo;
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}


function fetchStudentRecordsFromDatabase($courseCode, $unitCode)
{
    $studentRows = array();

    $query = "SELECT * FROM attendance WHERE course = '$courseCode' AND unit = '$unitCode'";
    $result = fetch($query);

    if ($result) {
        foreach ($result as $row) {
            $studentRows[] = $row;
        }
    }

    return $studentRows;
}

function js_asset($links = [])
{
    if ($links) {
        foreach ($links as $link) {
            echo "<script src='resources/assets/javascript/{$link}.js'>
        </script>";
        }
    }
}