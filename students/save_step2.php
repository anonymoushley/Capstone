<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get personal_info_id from either session or POST data
$personal_info_id = $_SESSION['personal_info_id'] ?? $_POST['personal_info_id'] ?? null;

if (!$personal_info_id) {
    echo json_encode(['success' => false, 'message' => 'Personal information not found. Please complete Step 1 first.']);
    exit;
}

try {
    // Validate grade inputs
    $g11_1st_avg = floatval($_POST['g11_1st_avg']);
    $g11_2nd_avg = floatval($_POST['g11_2nd_avg']);
    $g12_1st_avg = floatval($_POST['g12_1st_avg']);
    
    // Check if grades are within valid range (0-100)
    if ($g11_1st_avg < 0 || $g11_1st_avg > 100 || 
        $g11_2nd_avg < 0 || $g11_2nd_avg > 100 || 
        $g12_1st_avg < 0 || $g12_1st_avg > 100) {
        throw new Exception('Grades must be between 0 and 100');
    }

    // Check if academic_background already exists
    $check_stmt = $pdo->prepare("SELECT id FROM academic_background WHERE personal_info_id = ?");
    $check_stmt->execute([$personal_info_id]);
    $existing_academic = $check_stmt->fetch();

    if ($existing_academic) {
        // Update existing academic_background
        $stmt = $pdo->prepare("UPDATE academic_background SET 
            last_school_attended = ?, strand_id = ?, year_graduated = ?,
            g11_1st_avg = ?, g11_2nd_avg = ?, g12_1st_avg = ?, academic_award = ?
            WHERE personal_info_id = ?");
        
        $stmt->execute([
            $_POST['last_school_attended'],
            $_POST['strand_id'],
            $_POST['year_graduated'],
            $g11_1st_avg,
            $g11_2nd_avg,
            $g12_1st_avg,
            $_POST['academic_award'],
            $personal_info_id
        ]);
    } else {
        // Insert new academic_background
        $stmt = $pdo->prepare("INSERT INTO academic_background (
            personal_info_id, last_school_attended, strand_id, year_graduated,
            g11_1st_avg, g11_2nd_avg, g12_1st_avg, academic_award
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $personal_info_id,
            $_POST['last_school_attended'],
            $_POST['strand_id'],
            $_POST['year_graduated'],
            $g11_1st_avg,
            $g11_2nd_avg,
            $g12_1st_avg,
            $_POST['academic_award']
        ]);
    }

    // Store personal_info_id in session if it came from POST
    if (isset($_POST['personal_info_id'])) {
        $_SESSION['personal_info_id'] = $_POST['personal_info_id'];
    }

    echo json_encode(['success' => true, 'message' => 'Academic background saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>