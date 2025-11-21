<?php
session_start();
header('Content-Type: application/json');

if (isset($_GET['step'])) {
    $step = 'step' . $_GET['step'];
    $data = isset($_SESSION['form_data'][$step]) ? $_SESSION['form_data'][$step] : [];
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?> 