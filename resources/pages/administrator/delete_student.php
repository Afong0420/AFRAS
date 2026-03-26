<?php
session_start();
require_once 'database_connection.php'; // adjust path if needed

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Get student registration number and folder to delete images
    $stmt = $pdo->prepare("SELECT registrationNumber FROM students WHERE Id = :id");
    $stmt->execute([':id' => $id]);
    $student = $stmt->fetch();

    if ($student) {
        $registrationNumber = $student['registrationNumber'];
        $folderPath = "resources/labels/{$registrationNumber}/";

        // Delete folder with images
        if (file_exists($folderPath)) {
            $files = glob($folderPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($folderPath);
        }

        // Delete student record from database
        $delete = $pdo->prepare("DELETE FROM students WHERE Id = :id");
        $delete->execute([':id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'Student deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>