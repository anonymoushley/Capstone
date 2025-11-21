<?php
/**
 * Check Published Exam API
 * 
 * Returns JSON response indicating if there's a published exam for the chairperson
 * 
 * @package Admin
 */

require_once __DIR__ . '/../config/error_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Authorization check
if (!isset($_SESSION['chair_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$chairperson_id = $_SESSION['chair_id'];

// Check if there's a published exam
$stmt = $conn->prepare("SELECT version_name FROM exam_versions WHERE is_published = 1 AND is_archived = 0 AND chair_id = ?");
$stmt->bind_param("i", $chairperson_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $published_exam = $result->fetch_assoc();
    echo json_encode([
        'hasPublished' => true,
        'publishedName' => $published_exam['version_name']
    ]);
} else {
    echo json_encode([
        'hasPublished' => false
    ]);
}

$stmt->close();
$conn->close();
?>