<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['step'])) {
    $step = 'step' . $input['step'];
    if (isset($_SESSION['form_data'][$step])) {
        unset($_SESSION['form_data'][$step]);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid step']);
}
?> 