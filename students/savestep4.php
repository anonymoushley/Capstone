<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['personal_info_id'])) {
            throw new Exception('Personal information not found. Please complete Step 1 first.');
        }

        $upload_dir = '../uploads/documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $documents = [];
        $required_files = ['g11_1st', 'g11_2nd', 'g12_1st'];
        $optional_files = ['ncii', 'guidance_cert', 'additional_file'];

        // Handle required files
        foreach ($required_files as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== 0) {
                throw new Exception("Required file $file is missing");
            }
            $file_extension = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . $file . '.' . $file_extension;
            move_uploaded_file($_FILES[$file]['tmp_name'], $upload_dir . $new_filename);
            $documents[$file] = $new_filename;
        }

        // Handle optional files
        foreach ($optional_files as $file) {
            if (isset($_FILES[$file]) && $_FILES[$file]['error'] === 0) {
                $file_extension = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . $file . '.' . $file_extension;
                move_uploaded_file($_FILES[$file]['tmp_name'], $upload_dir . $new_filename);
                $documents[$file] = $new_filename;
            }
        }

        // Save document information to database
        $stmt = $pdo->prepare("INSERT INTO documents (
            personal_info_id, g11_1st, g11_2nd, g12_1st,
            ncii, guidance_cert, additional_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_SESSION['personal_info_id'],
            $documents['g11_1st'],
            $documents['g11_2nd'],
            $documents['g12_1st'],
            $documents['ncii'] ?? null,
            $documents['guidance_cert'] ?? null,
            $documents['additional_file'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Documents uploaded successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>