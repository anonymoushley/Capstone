<?php
session_start();
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['step']) && isset($input['data'])) {
    $_SESSION['form_data']['step' . $input['step']] = $input['data'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?> 