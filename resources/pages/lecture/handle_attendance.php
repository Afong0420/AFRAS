<?php
/*
 * handle_attendance.php
 * FIX: Added require_once for database_connection.php — $pdo was undefined before.
 * Receives JSON array of attendance records and inserts each into attendance.
 */

require_once __DIR__ . "/../../lib/php_functions.php";
require_once __DIR__ . "/../../../database/database_connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$attendanceData = json_decode(file_get_contents("php://input"), true);
$response = [];

if (!$attendanceData || !is_array($attendanceData)) {
    echo json_encode(['status' => 'error', 'message' => 'No attendance data received.']);
    exit;
}

try {
    // Use session column if it exists (added by migration); fall back gracefully if not
    $hasSessionCol = false;
    try {
        $pdo->query("SELECT session FROM attendance LIMIT 1");
        $hasSessionCol = true;
    } catch (PDOException $e) { /* column not yet added — skip */ }

    if ($hasSessionCol) {
        $sql = "INSERT INTO attendance
                    (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked, session)
                VALUES
                    (:studentID, :course, :unit, :attendanceStatus, :date, :session)";
    } else {
        $sql = "INSERT INTO attendance
                    (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked)
                VALUES
                    (:studentID, :course, :unit, :attendanceStatus, :date)";
    }

    $stmt = $pdo->prepare($sql);
    $date = date("Y-m-d");

    foreach ($attendanceData as $data) {
        $params = [
            ':studentID'        => $data['studentID']        ?? '',
            ':course'           => $data['course']           ?? '',
            ':unit'             => $data['unit']             ?? '',
            ':attendanceStatus' => $data['attendanceStatus'] ?? 'Absent',
            ':date'             => $date,
        ];
        if ($hasSessionCol) {
            $params[':session'] = $data['session'] ?? '';
        }
        $stmt->execute($params);
    }

    $response['status']  = 'success';
    $response['message'] = 'Attendance recorded successfully for all ' . count($attendanceData) . ' student(s).';
} catch (PDOException $e) {
    $response['status']  = 'error';
    $response['message'] = 'Error inserting attendance data: ' . $e->getMessage();
}

echo json_encode($response);
