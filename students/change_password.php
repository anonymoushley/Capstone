<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Debug: Log received data
error_log('POST data received: ' . print_r($_POST, true));
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        $stmt = $pdo->prepare("SELECT password FROM registration WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        } elseif ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        } elseif (strlen($new_password) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE registration SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);

            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating your password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
