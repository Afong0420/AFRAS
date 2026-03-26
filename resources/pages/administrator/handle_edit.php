<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['type'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data.']);
    exit;
}

try {
    switch ($input['type']) {

        case 'lecture':
            $stmt = $pdo->prepare("UPDATE lecture SET 
                firstName   = :firstName,
                lastName    = :lastName,
                emailAddress= :email,
                phoneNo     = :phoneNo,
                facultyCode = :faculty
                WHERE Id = :id");
            $stmt->execute([
                ':firstName' => htmlspecialchars(trim($input['firstName'])),
                ':lastName'  => htmlspecialchars(trim($input['lastName'])),
                ':email'     => filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL),
                ':phoneNo'   => htmlspecialchars(trim($input['phoneNo'])),
                ':faculty'   => htmlspecialchars(trim($input['faculty'])),
                ':id'        => (int)$input['id'],
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'course':
            $stmt = $pdo->prepare("UPDATE course SET 
                name       = :name,
                courseCode = :courseCode,
                facultyID  = :facultyID
                WHERE Id = :id");
            $stmt->execute([
                ':name'       => htmlspecialchars(trim($input['name'])),
                ':courseCode' => htmlspecialchars(trim($input['courseCode'])),
                ':facultyID'  => (int)$input['facultyID'],
                ':id'         => (int)$input['id'],
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'unit':
            $allowedDays = ['Mon','Tue','Wed','Thu','Fri'];
            // Sanitize scheduleDays
            $scheduleDays = null;
            if (!empty($input['scheduleDays'])) {
                $rawDays = explode(',', $input['scheduleDays']);
                $cleanDays = array_filter($rawDays, fn($d) => in_array(trim($d), $allowedDays));
                $scheduleDays = !empty($cleanDays) ? implode(',', array_map('trim', $cleanDays)) : null;
            }
            // Legacy startTime/endTime (first day)
            $startTime = !empty($input['startTime']) ? $input['startTime'] : null;
            $endTime   = !empty($input['endTime'])   ? $input['endTime']   : null;

            $stmt = $pdo->prepare("UPDATE unit SET 
                name         = :name,
                unitCode     = :unitCode,
                courseID     = :courseID,
                startTime    = :startTime,
                endTime      = :endTime,
                venueID      = :venueID,
                scheduleDays = :scheduleDays,
                lectureID    = :lectureID
                WHERE Id = :id");
            $stmt->execute([
                ':name'         => htmlspecialchars(trim($input['name'])),
                ':unitCode'     => htmlspecialchars(trim($input['unitCode'])),
                ':courseID'     => (int)$input['courseID'],
                ':startTime'    => $startTime,
                ':endTime'      => $endTime,
                ':venueID'      => !empty($input['venue']) ? (int)$input['venue'] : null,
                ':scheduleDays' => $scheduleDays,
                ':lectureID'    => !empty($input['lectureID']) ? (int)$input['lectureID'] : null,
                ':id'           => (int)$input['id'],
            ]);

            // Save per-day, per-session schedules into unit_schedule table
            $unitId = (int)$input['id'];
            if (!empty($input['daySchedules']) && is_array($input['daySchedules'])) {
                $allowedSessions = ['morning','afternoon'];
                // Remove old schedules for this unit
                $delStmt = $pdo->prepare("DELETE FROM unit_schedule WHERE unitId = :unitId");
                $delStmt->execute([':unitId' => $unitId]);
                // Insert new per-day, per-session schedules
                $insStmt = $pdo->prepare("INSERT INTO unit_schedule (unitId, day, session, startTime, endTime, venueID)
                                           VALUES (:unitId, :day, :session, :startTime, :endTime, :venueID)");
                $dayVenues = isset($input['dayVenues']) && is_array($input['dayVenues']) ? $input['dayVenues'] : [];
                foreach ($input['daySchedules'] as $day => $sessions) {
                    if (!in_array($day, $allowedDays)) continue;
                    $dayVenueId = !empty($dayVenues[$day]) ? (int)$dayVenues[$day] : null;
                    foreach ($sessions as $sess => $times) {
                        if (!in_array($sess, $allowedSessions)) continue;
                        $s = $times['start'] ?? null;
                        $e = $times['end']   ?? null;
                        if (!$s || !$e || $e <= $s) continue;
                        $insStmt->execute([
                            ':unitId'    => $unitId,
                            ':day'       => $day,
                            ':session'   => $sess,
                            ':startTime' => $s,
                            ':endTime'   => $e,
                            ':venueID'   => $dayVenueId,
                        ]);
                    }
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'faculty':
            $stmt = $pdo->prepare("UPDATE faculty SET 
                facultyName = :facultyName,
                facultyCode = :facultyCode
                WHERE Id = :id");
            $stmt->execute([
                ':facultyName' => htmlspecialchars(trim($input['facultyName'])),
                ':facultyCode' => htmlspecialchars(trim($input['facultyCode'])),
                ':id'          => (int)$input['id'],
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'student':
            $stmt = $pdo->prepare("UPDATE students SET 
                firstName  = :firstName,
                lastName   = :lastName,
                email      = :email,
                faculty    = :faculty,
                courseCode = :courseCode,
                yearLevel  = :yearLevel,
                section    = :section
                WHERE Id = :id");
            $stmt->execute([
                ':firstName'  => htmlspecialchars(trim($input['firstName'])),
                ':lastName'   => htmlspecialchars(trim($input['lastName'])),
                ':email'      => filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL),
                ':faculty'    => htmlspecialchars(trim($input['faculty'])),
                ':courseCode' => htmlspecialchars(trim($input['courseCode'])),
                ':yearLevel'  => htmlspecialchars(trim($input['yearLevel'])),
                ':section'    => htmlspecialchars(trim($input['section'])),
                ':id'         => (int)$input['id'],
            ]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown type.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}