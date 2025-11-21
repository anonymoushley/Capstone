<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['personal_info_id'])) {
            throw new Exception('Personal information not found. Please complete Step 1 first.');
        }

        $stmt = $pdo->prepare("INSERT INTO academic_background (
            personal_info_id, last_school_attended, strand_id, year_graduated,
            g11_1st_avg, g11_2nd_avg, g12_1st_avg, academic_award
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_SESSION['personal_info_id'],
            $_POST['last_school_attended'],
            $_POST['strand_id'],
            $_POST['year_graduated'],
            $_POST['g11_1st_avg'],
            $_POST['g11_2nd_avg'],
            $_POST['g12_1st_avg'],
            $_POST['academic_award']
        ]);

        echo json_encode(['success' => true, 'message' => 'Academic background saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>