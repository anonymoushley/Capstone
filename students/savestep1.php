<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Create a log file
$log_file = '../debug_log.txt';
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

write_log("Starting savestep1.php");
write_log("POST data: " . print_r($_POST, true));
write_log("FILES data: " . print_r($_FILES, true));

// Check if the request is actually POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    write_log("Error: Not a POST request");
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Check if required fields are present
$required_fields = ['last_name', 'first_name', 'birth_date', 'age', 'sex', 'contact_number', 
                   'region_name', 'province_name', 'city_name', 'barangay_name', 'street'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    write_log("Error: Missing required fields: " . implode(', ', $missing_fields));
    die(json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]));
}

require_once '../config/database.php';

// Test database connection
try {
    $pdo->query("SELECT 1");
    write_log("Database connection successful");
} catch (PDOException $e) {
    write_log("Database connection failed: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

try {
    $pdo->beginTransaction();

    // Handle file upload for ID picture
    $id_picture = null;
    if (isset($_FILES['id_picture']) && $_FILES['id_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/id_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['id_picture']['tmp_name'], $upload_path)) {
            $id_picture = $new_filename;
        }
    }

    // Check if personal_info already exists
    $check_stmt = $pdo->prepare("SELECT pi.id FROM personal_info pi JOIN registration r ON pi.id = r.personal_info_id WHERE r.id = ?");
    $check_stmt->execute([$_SESSION['user_id']]);
    $existing_personal_info = $check_stmt->fetch();

    if ($existing_personal_info) {
        // Update existing personal_info - only update id_picture if new file is uploaded
        $personal_info_id = $existing_personal_info['id'];
        
        // Build dynamic UPDATE query - only update id_picture if new file is uploaded
        if ($id_picture !== null) {
            // New picture uploaded, include it in the update
            $stmt = $pdo->prepare("UPDATE personal_info SET 
                last_name = ?, first_name = ?, middle_name = ?, date_of_birth = ?, age = ?, sex = ?, 
                contact_number = ?, region = ?, province = ?, city = ?, barangay = ?, street_purok = ?, id_picture = ?
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['last_name'],
                $_POST['first_name'],
                $_POST['middle_name'],
                $_POST['birth_date'],
                $_POST['age'],
                $_POST['sex'],
                '0' . $_POST['contact_number'], // Add 0 prefix to contact number
                $_POST['region_name'],
                $_POST['province_name'],
                $_POST['city_name'],
                $_POST['barangay_name'],
                $_POST['street'],
                $id_picture,
                $personal_info_id
            ]);
        } else {
            // No new picture uploaded, update all fields except id_picture
            $stmt = $pdo->prepare("UPDATE personal_info SET 
                last_name = ?, first_name = ?, middle_name = ?, date_of_birth = ?, age = ?, sex = ?, 
                contact_number = ?, region = ?, province = ?, city = ?, barangay = ?, street_purok = ?
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['last_name'],
                $_POST['first_name'],
                $_POST['middle_name'],
                $_POST['birth_date'],
                $_POST['age'],
                $_POST['sex'],
                '0' . $_POST['contact_number'], // Add 0 prefix to contact number
                $_POST['region_name'],
                $_POST['province_name'],
                $_POST['city_name'],
                $_POST['barangay_name'],
                $_POST['street'],
                $personal_info_id
            ]);
        }
    } else {
        // Insert new personal_info
        $stmt = $pdo->prepare("INSERT INTO personal_info (
            last_name, first_name, middle_name, date_of_birth, age, sex, 
            contact_number, region, province, city, barangay, street_purok, id_picture
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['last_name'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['birth_date'],
            $_POST['age'],
            $_POST['sex'],
            '0' . $_POST['contact_number'], // Add 0 prefix to contact number
            $_POST['region_name'],
            $_POST['province_name'],
            $_POST['city_name'],
            $_POST['barangay_name'],
            $_POST['street'],
            $id_picture
        ]);

        $personal_info_id = $pdo->lastInsertId();
    }
    write_log("Personal info ID: " . $personal_info_id);

    // Link personal_info_id to registration
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE registration SET personal_info_id = ? WHERE id = ?");
        $stmt->execute([$personal_info_id, $_SESSION['user_id']]);
        write_log("Linked personal_info_id $personal_info_id to registration id " . $_SESSION['user_id']);
    }

// Normalize Yes/No values
$yes_no_fields = [
    'access_computer', 'access_internet', 'access_mobile',
    'first_gen_college', 'was_scholar', 'received_honors', 'has_disability'
];

foreach ($yes_no_fields as $field) {
    if (isset($_POST[$field])) {
        $_POST[$field] = ($_POST[$field] === '1' || strtolower($_POST[$field]) === 'yes') ? 'Yes' : 'No';
    } else {
        $_POST[$field] = 'No'; // Default to 'No' if not set
    }
}


    // Check if socio_demographic already exists
    $check_socio = $pdo->prepare("SELECT id FROM socio_demographic WHERE personal_info_id = ?");
    $check_socio->execute([$personal_info_id]);
    $existing_socio = $check_socio->fetch();

    if ($existing_socio) {
        // Update existing socio_demographic
        $stmt = $pdo->prepare("UPDATE socio_demographic SET 
            marital_status = ?, religion = ?, orientation = ?,
            father_status = ?, father_education = ?, father_employment = ?,
            mother_status = ?, mother_education = ?, mother_employment = ?,
            siblings = ?, living_with = ?, access_computer = ?, access_internet = ?,
            access_mobile = ?, indigenous_group = ?, first_gen_college = ?,
            was_scholar = ?, received_honors = ?, has_disability = ?, disability_detail = ?
            WHERE personal_info_id = ?");
        
        $stmt->execute([
            $_POST['marital_status'],
            $_POST['religion'],
            $_POST['orientation'],
            $_POST['father_status'],
            $_POST['father_education'],
            $_POST['father_employment'],
            $_POST['mother_status'],
            $_POST['mother_education'],
            $_POST['mother_employment'],
            $_POST['siblings'],
            $_POST['living_with'],
            $_POST['access_computer'],
            $_POST['access_internet'],
            $_POST['access_mobile'],
            $_POST['indigenous_group'],
            $_POST['first_gen_college'],
            $_POST['was_scholar'],
            $_POST['received_honors'],
            $_POST['has_disability'],
            $_POST['disability_detail'] ?? null,
            $personal_info_id
        ]);
    } else {
        // Insert new socio_demographic
        $stmt = $pdo->prepare("INSERT INTO socio_demographic (
            personal_info_id, marital_status, religion, orientation,
            father_status, father_education, father_employment,
            mother_status, mother_education, mother_employment,
            siblings, living_with, access_computer, access_internet,
            access_mobile, indigenous_group, first_gen_college,
            was_scholar, received_honors, has_disability, disability_detail
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $personal_info_id,
            $_POST['marital_status'],
            $_POST['religion'],
            $_POST['orientation'],
            $_POST['father_status'],
            $_POST['father_education'],
            $_POST['father_employment'],
            $_POST['mother_status'],
            $_POST['mother_education'],
            $_POST['mother_employment'],
            $_POST['siblings'],
            $_POST['living_with'],
            $_POST['access_computer'],
            $_POST['access_internet'],
            $_POST['access_mobile'],
            $_POST['indigenous_group'],
            $_POST['first_gen_college'],
            $_POST['was_scholar'],
            $_POST['received_honors'],
            $_POST['has_disability'],
            $_POST['disability_detail'] ?? null
        ]);
    }

    $pdo->commit();
    
    // Store personal_info_id in session
    $_SESSION['personal_info_id'] = $personal_info_id;
    write_log("Session data after save: " . print_r($_SESSION, true));
    
    echo json_encode(['success' => true, 'message' => 'Step 1 data saved successfully', 'personal_info_id' => $personal_info_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    write_log("Error occurred: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>