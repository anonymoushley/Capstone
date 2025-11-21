<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$step = $_POST['step'] ?? null;
if (!$step) {
    echo json_encode(['success' => false, 'message' => 'No step specified']);
    exit;
}

require_once __DIR__ . '/../config/functions.php';

$personal_info_id = $_SESSION['personal_info_id'] ?? $_POST['personal_info_id'] ?? null;
if (!$personal_info_id && $step !== 'step5') {
    // For step5, we only need user_id, no personal_info_id required here
    echo json_encode(['success' => false, 'message' => 'Personal information not found. Please complete Step 1 first.']);
    exit;
}

try {
    switch ($step) {
        case 'step1':
            $_SESSION['profiling']['step1'] = [
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'applicant_type' => sanitize($_POST['applicant_type'] ?? '')
            ];
            echo json_encode(['success' => true, 'message' => 'Step 1 saved in session']);
            break;

        case 'step2':
            $applicant_type = $_SESSION['profiling']['step1']['applicant_type'] ?? '';
            $year_graduated = $applicant_type === 'New Applicant - Same Academic Year' ? 2025 : ($_POST['year_graduated'] ?? null);

            if (!$year_graduated) {
                throw new Exception('Year graduated is required');
            }

            $data = [
                'last_school_attended' => sanitize($_POST['last_school_attended'] ?? ''),
                'strand' => sanitize($_POST['strand'] ?? ''),
                'year_graduated' => $year_graduated,
                'g11_1st_avg' => sanitize($_POST['g11_1st_avg'] ?? ''),
                'g11_2nd_avg' => sanitize($_POST['g11_2nd_avg'] ?? ''),
                'g12_1st_avg' => sanitize($_POST['g12_1st_avg'] ?? ''),
                'academic_award' => sanitize($_POST['academic_award'] ?? '')
            ];

            $_SESSION['profiling']['step2'] = $data;

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
                    $data['last_school_attended'],
                    $data['strand_id'],
                    $data['year_graduated'],
                    $data['g11_1st_avg'],
                    $data['g11_2nd_avg'],
                    $data['g12_1st_avg'],
                    $data['academic_award'],
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
                    $data['last_school_attended'],
                    $data['strand_id'],
                    $data['year_graduated'],
                    $data['g11_1st_avg'],
                    $data['g11_2nd_avg'],
                    $data['g12_1st_avg'],
                    $data['academic_award']
                ]);
            }

            $_SESSION['profiling']['step2_completed'] = true;
            echo json_encode(['success' => true, 'message' => 'Academic background saved']);
            break;

        case 'step3':
            // Get registration_id from session (user_id) or from database
            $registration_id = null;
            if (isset($_SESSION['user_id'])) {
                $registration_id = $_SESSION['user_id'];
            } else {
                // Try to get registration_id from database using personal_info_id
                $reg_stmt = $pdo->prepare("SELECT id FROM registration WHERE personal_info_id = ? LIMIT 1");
                $reg_stmt->execute([$personal_info_id]);
                $reg_result = $reg_stmt->fetch();
                if ($reg_result) {
                    $registration_id = $reg_result['id'];
                }
            }

            // Check if program_application already exists
            $check_stmt = $pdo->prepare("SELECT id FROM program_application WHERE personal_info_id = ?");
            $check_stmt->execute([$personal_info_id]);
            $existing_program = $check_stmt->fetch();

            if ($existing_program) {
                // Update existing program_application
                $stmt = $pdo->prepare("UPDATE program_application SET 
                    campus = ?, college = ?, program = ?, registration_id = ?
                    WHERE personal_info_id = ?");
                
                $stmt->execute([
                    sanitize($_POST['selected_campus']),
                    sanitize($_POST['selected_college']),
                    sanitize($_POST['program']),
                    $registration_id,
                    $personal_info_id
                ]);
            } else {
                // Insert new program_application
                $stmt = $pdo->prepare("INSERT INTO program_application (
                    personal_info_id, campus, college, program, registration_id
                ) VALUES (?, ?, ?, ?, ?)");

                $stmt->execute([
                    $personal_info_id,
                    sanitize($_POST['selected_campus']),
                    sanitize($_POST['selected_college']),
                    sanitize($_POST['program']),
                    $registration_id
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Program application saved']);
            break;

        case 'step4':
            $upload_dir = '../uploads/documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $documents = [];
            $required = ['g11_1st', 'g11_2nd', 'g12_1st'];
            $optional = ['ncii', 'guidance_cert', 'additional_file'];

            foreach ($required as $field) {
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== 0) {
                    throw new Exception("Required file $field is missing");
                }
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . $field . '.' . $ext;
                move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $filename);
                $documents[$field] = $filename;
            }

            foreach ($optional as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . $field . '.' . $ext;
                    move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $filename);
                    $documents[$field] = $filename;
                }
            }

            // Check if documents already exist
            $check_stmt = $pdo->prepare("SELECT id FROM documents WHERE personal_info_id = ?");
            $check_stmt->execute([$personal_info_id]);
            $existing_documents = $check_stmt->fetch();

            if ($existing_documents) {
                // Update existing documents - only update fields that have new files
                $updateFields = [];
                $updateValues = [];
                
                if (!empty($documents['g11_1st'])) {
                    $updateFields[] = "g11_1st = ?";
                    $updateValues[] = $documents['g11_1st'];
                }
                if (!empty($documents['g11_2nd'])) {
                    $updateFields[] = "g11_2nd = ?";
                    $updateValues[] = $documents['g11_2nd'];
                }
                if (!empty($documents['g12_1st'])) {
                    $updateFields[] = "g12_1st = ?";
                    $updateValues[] = $documents['g12_1st'];
                }
                if (!empty($documents['ncii'])) {
                    $updateFields[] = "ncii = ?";
                    $updateValues[] = $documents['ncii'];
                }
                if (!empty($documents['guidance_cert'])) {
                    $updateFields[] = "guidance_cert = ?";
                    $updateValues[] = $documents['guidance_cert'];
                }
                if (!empty($documents['additional_file'])) {
                    $updateFields[] = "additional_file = ?";
                    $updateValues[] = $documents['additional_file'];
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
                    $documents['g11_1st'],
                    $documents['g11_2nd'],
                    $documents['g12_1st'],
                    $documents['ncii'] ?? null,
                    $documents['guidance_cert'] ?? null,
                    $documents['additional_file'] ?? null
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Documents uploaded']);
            break;

        case 'step5':
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User session not found.');
            }

            if (!isset($_POST['certify']) || $_POST['certify'] !== 'true') {
                throw new Exception('You must certify the information before submitting.');
            }

            $user_id = $_SESSION['user_id'];

            // Always update the application_submitted status to 1
            $stmt = $pdo->prepare("UPDATE registration SET application_submitted = 1 WHERE id = ?");
            $stmt->execute([$user_id]);

            // Check if the user exists (not if rows were changed)
            $check_stmt = $pdo->prepare("SELECT id FROM registration WHERE id = ?");
            $check_stmt->execute([$user_id]);
            $user_exists = $check_stmt->fetch();

            if ($user_exists) {
                echo json_encode(['success' => true, 'message' => 'Application updated successfully.']);
            } else {
                throw new Exception('User not found.');
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid step']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
