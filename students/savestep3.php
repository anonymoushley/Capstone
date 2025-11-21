<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['personal_info_id'])) {
    echo json_encode(['success' => false, 'message' => 'Personal information not found']);
    exit;
}

try {
    // Get registration_id from session (user_id) or from database
    $registration_id = null;
    if (isset($_SESSION['user_id'])) {
        $registration_id = $_SESSION['user_id'];
    } else {
        // Try to get registration_id from database using personal_info_id
        $reg_stmt = $pdo->prepare("SELECT id FROM registration WHERE personal_info_id = ? LIMIT 1");
        $reg_stmt->execute([$_SESSION['personal_info_id']]);
        $reg_result = $reg_stmt->fetch();
        if ($reg_result) {
            $registration_id = $reg_result['id'];
        }
    }

    // Check if program_application already exists
    $check_stmt = $pdo->prepare("SELECT id FROM program_application WHERE personal_info_id = ?");
    $check_stmt->execute([$_SESSION['personal_info_id']]);
    $existing_program = $check_stmt->fetch();

    if ($existing_program) {
        // Update existing program_application
        $stmt = $pdo->prepare("UPDATE program_application SET 
            campus = ?, college = ?, program = ?, registration_id = ?
            WHERE personal_info_id = ?");
        
        $stmt->execute([
            $_POST['selected_campus'],
            $_POST['selected_college'],
            $_POST['program'],
            $registration_id,
            $_SESSION['personal_info_id']
        ]);
    } else {
        // Insert new program_application
        $stmt = $pdo->prepare("INSERT INTO program_application (
            personal_info_id, campus, college, program, registration_id
        ) VALUES (?, ?, ?, ?, ?)");

        $stmt->execute([
            $_SESSION['personal_info_id'],
            $_POST['selected_campus'],
            $_POST['selected_college'],
            $_POST['program'],
            $registration_id
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Program application saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>