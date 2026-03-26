<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $faculty = $_POST['faculty'];
    $courseCode = $_POST['course'];
    $yearLevel = $_POST['yearLevel'];
    $section = $_POST['section'];

    $stmt = $pdo->prepare("UPDATE students SET 
        firstName = :firstName,
        lastName = :lastName,
        email = :email,
        faculty = :faculty,
        courseCode = :courseCode,
        yearLevel = :yearLevel,
        section = :section
        WHERE Id = :id
    ");

    $stmt->execute([
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':email' => $email,
        ':faculty' => $faculty,
        ':courseCode' => $courseCode,
        ':yearLevel' => $yearLevel,
        ':section' => $section,
        ':id' => $id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Student updated successfully!']);
}
?>