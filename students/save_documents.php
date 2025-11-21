<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get personal_info_id from session or POST data
$personal_info_id = $_SESSION['personal_info_id'] ?? $_POST['personal_info_id'] ?? null;


if (!$personal_info_id) {
    echo json_encode(['success' => false, 'message' => 'Personal information not found']);
    exit;
}

try {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check if documents already exist
    $check_stmt = $pdo->prepare("SELECT * FROM documents WHERE personal_info_id = ?");
    $check_stmt->execute([$personal_info_id]);
    $existing_documents = $check_stmt->fetch();

    $g11_1st = '';
    $g11_2nd = '';
    $g12_1st = '';
    $ncii = '';
    $guidance_cert = '';
    $additional_file = '';

    // Handle Grade 11 1st Sem Report Card upload (multiple files) - REPLACE existing files
    if (isset($_FILES['g11_1st']) && is_array($_FILES['g11_1st']['name']) && !empty(array_filter($_FILES['g11_1st']['name']))) {
        $existing_g11_1st = ($existing_documents && isset($existing_documents['g11_1st'])) ? json_decode($existing_documents['g11_1st'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_g11_1st) && !empty($existing_g11_1st)) {
            deleteFiles($existing_g11_1st, $uploadDir);
        }
        $g11_1st = handleMultipleFileUpload($_FILES['g11_1st'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['g11_1st'])) {
        $g11_1st = $existing_documents['g11_1st'];
    }

    // Handle Grade 11 2nd Sem Report Card upload (multiple files) - REPLACE existing files
    if (isset($_FILES['g11_2nd']) && is_array($_FILES['g11_2nd']['name']) && !empty(array_filter($_FILES['g11_2nd']['name']))) {
        $existing_g11_2nd = ($existing_documents && isset($existing_documents['g11_2nd'])) ? json_decode($existing_documents['g11_2nd'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_g11_2nd) && !empty($existing_g11_2nd)) {
            deleteFiles($existing_g11_2nd, $uploadDir);
        }
        $g11_2nd = handleMultipleFileUpload($_FILES['g11_2nd'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['g11_2nd'])) {
        $g11_2nd = $existing_documents['g11_2nd'];
    }

    // Handle Grade 12 1st Sem Report Card upload (multiple files) - REPLACE existing files
    if (isset($_FILES['g12_1st']) && is_array($_FILES['g12_1st']['name']) && !empty(array_filter($_FILES['g12_1st']['name']))) {
        $existing_g12_1st = ($existing_documents && isset($existing_documents['g12_1st'])) ? json_decode($existing_documents['g12_1st'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_g12_1st) && !empty($existing_g12_1st)) {
            deleteFiles($existing_g12_1st, $uploadDir);
        }
        $g12_1st = handleMultipleFileUpload($_FILES['g12_1st'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['g12_1st'])) {
        $g12_1st = $existing_documents['g12_1st'];
    }

    // Handle NC II Certificate upload (multiple files) - REPLACE existing files
    if (isset($_FILES['ncii']) && is_array($_FILES['ncii']['name']) && !empty(array_filter($_FILES['ncii']['name']))) {
        $existing_ncii = ($existing_documents && isset($existing_documents['ncii'])) ? json_decode($existing_documents['ncii'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_ncii) && !empty($existing_ncii)) {
            deleteFiles($existing_ncii, $uploadDir);
        }
        $ncii = handleMultipleFileUpload($_FILES['ncii'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['ncii'])) {
        $ncii = $existing_documents['ncii'];
    }

    // Handle Guidance Certificate upload (multiple files) - REPLACE existing files
    if (isset($_FILES['guidance_cert']) && is_array($_FILES['guidance_cert']['name']) && !empty(array_filter($_FILES['guidance_cert']['name']))) {
        $existing_guidance_cert = ($existing_documents && isset($existing_documents['guidance_cert'])) ? json_decode($existing_documents['guidance_cert'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_guidance_cert) && !empty($existing_guidance_cert)) {
            deleteFiles($existing_guidance_cert, $uploadDir);
        }
        $guidance_cert = handleMultipleFileUpload($_FILES['guidance_cert'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['guidance_cert'])) {
        $guidance_cert = $existing_documents['guidance_cert'];
    }

    // Handle Additional File upload (multiple files) - REPLACE existing files
    if (isset($_FILES['additional_file']) && is_array($_FILES['additional_file']['name']) && !empty(array_filter($_FILES['additional_file']['name']))) {
        $existing_additional_file = ($existing_documents && isset($existing_documents['additional_file'])) ? json_decode($existing_documents['additional_file'], true) : [];
        // Delete existing files before uploading new ones
        if (is_array($existing_additional_file) && !empty($existing_additional_file)) {
            deleteFiles($existing_additional_file, $uploadDir);
        }
        $additional_file = handleMultipleFileUpload($_FILES['additional_file'], $uploadDir, []);
    } else if ($existing_documents && isset($existing_documents['additional_file'])) {
        $additional_file = $existing_documents['additional_file'];
    }

    if ($existing_documents) {
        // Update existing documents - only update fields that have new files, preserve others
        
        // Build dynamic UPDATE query - only update fields that have new files
        $updateFields = [];
        $updateValues = [];
        
        // Only update fields that actually have new files uploaded
        if (isset($_FILES['g11_1st']) && is_array($_FILES['g11_1st']['name']) && !empty(array_filter($_FILES['g11_1st']['name'])) && !empty($g11_1st)) {
            $updateFields[] = "g11_1st = ?";
            $updateValues[] = $g11_1st;
        }
        if (isset($_FILES['g11_2nd']) && is_array($_FILES['g11_2nd']['name']) && !empty(array_filter($_FILES['g11_2nd']['name'])) && !empty($g11_2nd)) {
            $updateFields[] = "g11_2nd = ?";
            $updateValues[] = $g11_2nd;
        }
        if (isset($_FILES['g12_1st']) && is_array($_FILES['g12_1st']['name']) && !empty(array_filter($_FILES['g12_1st']['name'])) && !empty($g12_1st)) {
            $updateFields[] = "g12_1st = ?";
            $updateValues[] = $g12_1st;
        }
        if (isset($_FILES['ncii']) && is_array($_FILES['ncii']['name']) && !empty(array_filter($_FILES['ncii']['name'])) && !empty($ncii)) {
            $updateFields[] = "ncii = ?";
            $updateValues[] = $ncii;
        }
        if (isset($_FILES['guidance_cert']) && is_array($_FILES['guidance_cert']['name']) && !empty(array_filter($_FILES['guidance_cert']['name'])) && !empty($guidance_cert)) {
            $updateFields[] = "guidance_cert = ?";
            $updateValues[] = $guidance_cert;
        }
        if (isset($_FILES['additional_file']) && is_array($_FILES['additional_file']['name']) && !empty(array_filter($_FILES['additional_file']['name'])) && !empty($additional_file)) {
            $updateFields[] = "additional_file = ?";
            $updateValues[] = $additional_file;
        }
        
        // Only update if there are fields to update
        if (!empty($updateFields)) {
            $updateValues[] = $personal_info_id;
            $sql = "UPDATE documents SET " . implode(", ", $updateFields) . " WHERE personal_info_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
        }
    } else {
        // Insert new documents
        
        $stmt = $pdo->prepare("INSERT INTO documents (
            personal_info_id, g11_1st, g11_2nd, g12_1st,
            ncii, guidance_cert, additional_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $personal_info_id,
            $g11_1st,
            $g11_2nd,
            $g12_1st,
            $ncii,
            $guidance_cert,
            $additional_file
        ]);
        
        error_log("Insert executed, new ID: " . $pdo->lastInsertId());
    }

    echo json_encode(['success' => true, 'message' => 'Documents saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleMultipleFileUpload($files, $uploadDir, $existingFiles = []) {
    // Start with empty array - new uploads replace existing files
    $uploadedFiles = [];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    
    if (!is_array($files['name'])) {
        // Single file - convert to array format
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $name,
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];
            
            // Validate file
            if ($file['size'] > $maxFileSize) {
                throw new Exception('File size exceeds 5MB: ' . $name);
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Invalid file format: ' . $name);
            }
            
            $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Invalid file extension: ' . $name);
            }
            
            // Generate unique filename
            $fileName = uniqid() . '_' . basename($name);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $uploadedFiles[] = $fileName;
            } else {
                throw new Exception('Failed to upload: ' . $name);
            }
        }
    }
    
    return json_encode($uploadedFiles);
}

function deleteFiles($fileNames, $uploadDir) {
    if (!is_array($fileNames)) {
        $fileNames = [$fileNames];
    }
    
    foreach ($fileNames as $fileName) {
        $filePath = $uploadDir . $fileName;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
?> 