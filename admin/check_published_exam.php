<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

if (!isset($_SESSION['chair_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
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