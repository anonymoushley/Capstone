<?php
require_once __DIR__ . '/../config/security.php';
initSecureSession();
requireAuth('admin', '../admin/chair_login.php');

$conn = new mysqli("localhost", "root", "", "admission");
if ($conn->connect_error) {
    http_response_code(500);
    echo "Connection failed";
    exit;
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token']))) {
    http_response_code(403);
    echo "Invalid request";
    logSecurityEvent('CSRF token validation failed', ['file' => 'update_question.php']);
    exit;
}

$id = intval($_POST["id"] ?? 0);
$field = $_POST["field"] ?? '';
$value = $_POST["value"] ?? '';

// Validate ID
if ($id <= 0) {
    http_response_code(400);
    echo "Invalid ID";
    exit;
}

// Whitelist allowed fields to prevent SQL injection
$allowed = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'points'];
if (!in_array($field, $allowed)) {
    http_response_code(400);
    echo "Invalid field";
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("UPDATE questions SET `$field` = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo "Database error";
    exit;
}

$stmt->bind_param("si", $value, $id);
if ($stmt->execute()) {
    echo "Updated $field.";
} else {
    http_response_code(500);
    echo "Update failed";
}
$stmt->close();
$conn->close();
?>
