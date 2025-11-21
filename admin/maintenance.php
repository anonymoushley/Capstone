<?php
// Include database connection
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get chairperson information if logged in as chairperson
$chairId = $_SESSION['chair_id'] ?? null;
$chairCampus = $_SESSION['campus'] ?? '';
$isChairperson = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'chairperson';

// Ensure chair_id column exists in buildings table
try {
    $check_col = $pdo->query("SHOW COLUMNS FROM buildings LIKE 'chair_id'");
    if ($check_col->rowCount() == 0) {
        // Add chair_id column to buildings table
        $pdo->exec("ALTER TABLE buildings ADD COLUMN chair_id int(11) DEFAULT NULL AFTER name");
        
        // Add index for chair_id
        try {
            $pdo->exec("ALTER TABLE buildings ADD KEY idx_buildings_chair_id (chair_id)");
        } catch (PDOException $e) {
            // Index might already exist, ignore
        }
        
        // Add foreign key constraint
        try {
            $pdo->exec("ALTER TABLE buildings 
                        ADD CONSTRAINT fk_buildings_chair_id 
                        FOREIGN KEY (chair_id) REFERENCES chairperson_accounts(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (PDOException $e) {
            // Constraint might already exist, ignore
        }
    }
} catch (PDOException $e) {
    // Table might not exist yet, or column already exists - ignore error
    error_log("Note: Could not add chair_id to buildings table: " . $e->getMessage());
}

// Ensure chair_id column exists in rooms table
try {
    $check_col = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'chair_id'");
    if ($check_col->rowCount() == 0) {
        // Add chair_id column to rooms table
        $pdo->exec("ALTER TABLE rooms ADD COLUMN chair_id int(11) DEFAULT NULL AFTER building_id");
        
        // Add index for chair_id
        try {
            $pdo->exec("ALTER TABLE rooms ADD KEY idx_rooms_chair_id (chair_id)");
        } catch (PDOException $e) {
            // Index might already exist, ignore
        }
        
        // Add foreign key constraint
        try {
            $pdo->exec("ALTER TABLE rooms 
                        ADD CONSTRAINT fk_rooms_chair_id 
                        FOREIGN KEY (chair_id) REFERENCES chairperson_accounts(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (PDOException $e) {
            // Constraint might already exist, ignore
        }
    }
} catch (PDOException $e) {
    // Table might not exist yet, or column already exists - ignore error
    error_log("Note: Could not add chair_id to rooms table: " . $e->getMessage());
}

// Add security headers to prevent caching and improve security
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Handle strand management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Enhanced duplicate submission prevention with operation tracking
    $form_token = $_POST['form_token'] ?? '';
    $operation_id = $_POST['operation_id'] ?? '';
    $session_token = $_SESSION['form_token'] ?? '';
    $last_submission_time = $_SESSION['last_maintenance_submission'] ?? 0;
    $processed_operations = $_SESSION['processed_operations'] ?? [];
    $current_time = time();
    
    // Check for rapid successive submissions (within 2 seconds)
    if (($current_time - $last_submission_time) < 2) {
        $error_message = "Please wait before submitting again. Too many requests.";
    }
    // Check if this operation has already been processed
    elseif (!empty($operation_id) && in_array($operation_id, $processed_operations)) {
        $error_message = "This operation has already been processed. Please refresh the page and try again.";
    }
    // Check for duplicate submission using session token
    elseif ($form_token !== $session_token || empty($form_token)) {
        $error_message = "Form has already been submitted or token is invalid. Please refresh the page and try again.";
    }
    // Check for empty or invalid action
    elseif (empty($action) || !in_array($action, ['add_strand', 'archive_strand', 'restore_strand', 'add_building', 'archive_building', 'restore_building', 'add_room', 'archive_room', 'restore_room'])) {
        $error_message = "Invalid action specified.";
    } else {
        switch ($action) {
            case 'add_strand':
                $strand_name = trim($_POST['strand_name'] ?? '');
                if ($strand_name) {
                    try {
                        // Enhanced duplicate check with case-insensitive comparison
                        $check_stmt = $pdo->prepare("SELECT id, name FROM strands WHERE LOWER(name) = LOWER(?)");
                        $check_stmt->execute([$strand_name]);
                        $existing = $check_stmt->fetch();
                        
                        if ($existing) {
                            $error_message = "Strand '$strand_name' already exists (case-insensitive match with '{$existing['name']}').";
                        } else {
                            // Additional validation for strand name
                            if (strlen($strand_name) < 2 || strlen($strand_name) > 100) {
                                $error_message = "Strand name must be between 2 and 100 characters.";
                            } elseif (!preg_match('/^[a-zA-Z0-9\s\-&]+$/', $strand_name)) {
                                $error_message = "Strand name contains invalid characters. Only letters, numbers, spaces, hyphens, and ampersands are allowed.";
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO strands (name, status, created_at) VALUES (?, 'active', NOW())");
                                $stmt->execute([$strand_name]);
                                $success_message = "Strand '$strand_name' added successfully.";
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to add strand: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please enter a strand name.";
                }
                break;
                
            case 'archive_strand':
                $strand_id = $_POST['strand_id'] ?? '';
                if ($strand_id && is_numeric($strand_id)) {
                    try {
                        // First check if strand exists and is currently active
                        $check_stmt = $pdo->prepare("SELECT id, name, status FROM strands WHERE id = ?");
                        $check_stmt->execute([$strand_id]);
                        $strand = $check_stmt->fetch();
                        
                        if (!$strand) {
                            $error_message = "Strand not found.";
                        } elseif ($strand['status'] !== 'active') {
                            $error_message = "Strand '{$strand['name']}' is already archived.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE strands SET status = 'archived', updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$strand_id]);
                            $success_message = "Strand '{$strand['name']}' archived successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to archive strand: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid strand ID.";
                }
                break;
                
            case 'restore_strand':
                $strand_id = $_POST['strand_id'] ?? '';
                if ($strand_id && is_numeric($strand_id)) {
                    try {
                        // First check if strand exists and is currently archived
                        $check_stmt = $pdo->prepare("SELECT id, name, status FROM strands WHERE id = ?");
                        $check_stmt->execute([$strand_id]);
                        $strand = $check_stmt->fetch();
                        
                        if (!$strand) {
                            $error_message = "Strand not found.";
                        } elseif ($strand['status'] !== 'archived') {
                            $error_message = "Strand '{$strand['name']}' is already active.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE strands SET status = 'active', updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$strand_id]);
                            $success_message = "Strand '{$strand['name']}' restored successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to restore strand: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid strand ID.";
                }
                break;
                
            case 'add_building':
                $building_name = trim($_POST['building_name'] ?? '');
                if ($building_name) {
                    try {
                        // Enhanced duplicate check with case-insensitive comparison
                        $check_stmt = $pdo->prepare("SELECT id, name FROM buildings WHERE LOWER(name) = LOWER(?)");
                        $check_stmt->execute([$building_name]);
                        $existing = $check_stmt->fetch();
                        
                        if ($existing) {
                            $error_message = "Building '$building_name' already exists (case-insensitive match with '{$existing['name']}').";
                        } else {
                            // Additional validation for building name
                            if (strlen($building_name) < 2 || strlen($building_name) > 100) {
                                $error_message = "Building name must be between 2 and 100 characters.";
                            } elseif (!preg_match('/^[a-zA-Z0-9\s\-&]+$/', $building_name)) {
                                $error_message = "Building name contains invalid characters. Only letters, numbers, spaces, hyphens, and ampersands are allowed.";
                            } else {
                                // Check if chair_id column exists
                                $has_chair_id = false;
                                try {
                                    $check_chair_col = $pdo->query("SHOW COLUMNS FROM buildings LIKE 'chair_id'");
                                    $has_chair_id = $check_chair_col->rowCount() > 0;
                                } catch (PDOException $e) {
                                    $has_chair_id = false;
                                }
                                
                                if ($has_chair_id) {
                                    $stmt = $pdo->prepare("INSERT INTO buildings (name, chair_id, status, created_at) VALUES (?, ?, 'active', NOW())");
                                    $stmt->execute([$building_name, $isChairperson ? $chairId : null]);
                                } else {
                                    $stmt = $pdo->prepare("INSERT INTO buildings (name, status, created_at) VALUES (?, 'active', NOW())");
                                    $stmt->execute([$building_name]);
                                }
                                $success_message = "Building '$building_name' added successfully.";
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to add building: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please enter a building name.";
                }
                break;
                
            case 'archive_building':
                $building_id = $_POST['building_id'] ?? '';
                if ($building_id && is_numeric($building_id)) {
                    try {
                        // First check if building exists and is currently active
                        $check_stmt = $pdo->prepare("SELECT id, name, status, chair_id FROM buildings WHERE id = ?");
                        $check_stmt->execute([$building_id]);
                        $building = $check_stmt->fetch();
                        
                        if (!$building) {
                            $error_message = "Building not found.";
                        } elseif ($isChairperson && $chairId && $building['chair_id'] != $chairId) {
                            $error_message = "You do not have permission to archive this building.";
                        } elseif ($building['status'] !== 'active') {
                            $error_message = "Building '{$building['name']}' is already archived.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE buildings SET status = 'archived' WHERE id = ?");
                            $stmt->execute([$building_id]);
                            $success_message = "Building '{$building['name']}' archived successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to archive building: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid building ID.";
                }
                break;
                
            case 'restore_building':
                $building_id = $_POST['building_id'] ?? '';
                if ($building_id && is_numeric($building_id)) {
                    try {
                        // First check if building exists and is currently archived
                        $check_stmt = $pdo->prepare("SELECT id, name, status, chair_id FROM buildings WHERE id = ?");
                        $check_stmt->execute([$building_id]);
                        $building = $check_stmt->fetch();
                        
                        if (!$building) {
                            $error_message = "Building not found.";
                        } elseif ($isChairperson && $chairId && $building['chair_id'] != $chairId) {
                            $error_message = "You do not have permission to restore this building.";
                        } elseif ($building['status'] !== 'archived') {
                            $error_message = "Building '{$building['name']}' is already active.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE buildings SET status = 'active' WHERE id = ?");
                            $stmt->execute([$building_id]);
                            $success_message = "Building '{$building['name']}' restored successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to restore building: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid building ID.";
                }
                break;
                
            case 'add_room':
                $building_id = $_POST['building_id'] ?? '';
                $room_number = trim($_POST['room_number'] ?? '');
                
                if ($building_id && $room_number) {
                    try {
                        // Check if building exists and is active (and belongs to chairperson if applicable)
                        if ($isChairperson && $chairId) {
                            $building_stmt = $pdo->prepare("SELECT id, name FROM buildings WHERE id = ? AND status = 'active' AND chair_id = ?");
                            $building_stmt->execute([$building_id, $chairId]);
                        } else {
                            $building_stmt = $pdo->prepare("SELECT id, name FROM buildings WHERE id = ? AND status = 'active'");
                            $building_stmt->execute([$building_id]);
                        }
                        $building = $building_stmt->fetch();
                        
                        if (!$building) {
                            $error_message = "Selected building not found or is not active.";
                        } else {
                            // Check for duplicate room number in the same building
                            $check_stmt = $pdo->prepare("SELECT id, room_number FROM rooms WHERE building_id = ? AND room_number = ?");
                            $check_stmt->execute([$building_id, $room_number]);
                            $existing = $check_stmt->fetch();
                            
                            if ($existing) {
                                $error_message = "Room number '$room_number' already exists in building '{$building['name']}'.";
                            } else {
                                // Validation
                                if (strlen($room_number) < 1 || strlen($room_number) > 50) {
                                    $error_message = "Room number must be between 1 and 50 characters.";
                                } else {
                                    // Check if chair_id column exists
                                    $has_chair_id = false;
                                    try {
                                        $check_chair_col = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'chair_id'");
                                        $has_chair_id = $check_chair_col->rowCount() > 0;
                                    } catch (PDOException $e) {
                                        $has_chair_id = false;
                                    }
                                    
                                    if ($has_chair_id) {
                                        $stmt = $pdo->prepare("INSERT INTO rooms (building_id, chair_id, room_number, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                                        $stmt->execute([$building_id, $isChairperson ? $chairId : null, $room_number]);
                                    } else {
                                        $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, status, created_at) VALUES (?, ?, 'active', NOW())");
                                        $stmt->execute([$building_id, $room_number]);
                                    }
                                    $success_message = "Room '$room_number' added successfully to building '{$building['name']}'.";
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to add room: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please select a building and enter a room number.";
                }
                break;
                
            case 'archive_room':
                $room_id = $_POST['room_id'] ?? '';
                if ($room_id && is_numeric($room_id)) {
                    try {
                        // First check if room exists and is currently active
                        $check_stmt = $pdo->prepare("SELECT r.id, r.room_number, r.status, r.chair_id, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
                        $check_stmt->execute([$room_id]);
                        $room = $check_stmt->fetch();
                        
                        if (!$room) {
                            $error_message = "Room not found.";
                        } elseif ($isChairperson && $chairId && $room['chair_id'] != $chairId) {
                            $error_message = "You do not have permission to archive this room.";
                        } elseif ($room['status'] !== 'active') {
                            $error_message = "Room '{$room['room_number']}' in building '{$room['building_name']}' is already archived.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE rooms SET status = 'archived' WHERE id = ?");
                            $stmt->execute([$room_id]);
                            $success_message = "Room '{$room['room_number']}' in building '{$room['building_name']}' archived successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to archive room: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid room ID.";
                }
                break;
                
            case 'restore_room':
                $room_id = $_POST['room_id'] ?? '';
                if ($room_id && is_numeric($room_id)) {
                    try {
                        // First check if room exists and is currently archived
                        $check_stmt = $pdo->prepare("SELECT r.id, r.room_number, r.status, r.chair_id, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
                        $check_stmt->execute([$room_id]);
                        $room = $check_stmt->fetch();
                        
                        if (!$room) {
                            $error_message = "Room not found.";
                        } elseif ($isChairperson && $chairId && $room['chair_id'] != $chairId) {
                            $error_message = "You do not have permission to restore this room.";
                        } elseif ($room['status'] !== 'archived') {
                            $error_message = "Room '{$room['room_number']}' in building '{$room['building_name']}' is already active.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE rooms SET status = 'active' WHERE id = ?");
                            $stmt->execute([$room_id]);
                            $success_message = "Room '{$room['room_number']}' in building '{$room['building_name']}' restored successfully.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Failed to restore room: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid room ID.";
                }
                break;
        }
        
        // Generate unique operation ID for this submission
        $operation_id = bin2hex(random_bytes(16));
        
        // Update submission timestamp, regenerate form token, and track operation
        $_SESSION['last_maintenance_submission'] = $current_time;
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
        $_SESSION['processed_operations'][] = $operation_id;
        
        // Keep only last 50 operations to prevent session bloat
        if (count($_SESSION['processed_operations']) > 50) {
            $_SESSION['processed_operations'] = array_slice($_SESSION['processed_operations'], -50);
        }
        
        // Store messages in session instead of redirecting
        if (!empty($error_message)) {
            $_SESSION['maintenance_error'] = $error_message;
        } elseif (!empty($success_message)) {
            $_SESSION['maintenance_success'] = $success_message;
        }
        
        // Use JavaScript redirect to stay on the same page
        echo "<script>window.location.href = window.location.href.split('?')[0] + '?page=maintenance';</script>";
        exit;
    }
}

// Generate form token if not exists
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Handle success/error messages from session (primary) and URL (fallback)
if (isset($_SESSION['maintenance_success'])) {
    $success_message = $_SESSION['maintenance_success'];
    unset($_SESSION['maintenance_success']); // Clear after displaying
} elseif (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8');
    // Limit message length to prevent abuse
    if (strlen($success_message) > 500) {
        $success_message = substr($success_message, 0, 500) . '...';
    }
}

if (isset($_SESSION['maintenance_error'])) {
    $error_message = $_SESSION['maintenance_error'];
    unset($_SESSION['maintenance_error']); // Clear after displaying
} elseif (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    // Limit message length to prevent abuse
    if (strlen($error_message) > 500) {
        $error_message = substr($error_message, 0, 500) . '...';
    }
}


// Get all strands
try {
    $stmt = $pdo->query("SELECT * FROM strands ORDER BY status, name");
    $strands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $strands = [];
    $error_message = "Failed to fetch strands: " . $e->getMessage();
}

// Get all buildings (filtered by chair_id if chairperson)
try {
    if ($isChairperson && $chairId) {
        $stmt = $pdo->prepare("SELECT * FROM buildings WHERE chair_id = ? ORDER BY status, name");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM buildings ORDER BY status, name");
    }
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $buildings = [];
    $error_message = "Failed to fetch buildings: " . $e->getMessage();
}

// Get all rooms with building information (filtered by chair_id if chairperson)
try {
    if ($isChairperson && $chairId) {
        $stmt = $pdo->prepare("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.chair_id = ? ORDER BY r.status, b.name, r.room_number");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id ORDER BY r.status, b.name, r.room_number");
    }
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rooms = [];
    $error_message = "Failed to fetch rooms: " . $e->getMessage();
}

// Get active buildings for room form dropdown (filtered by chair_id if chairperson)
try {
    if ($isChairperson && $chairId) {
        $stmt = $pdo->prepare("SELECT id, name FROM buildings WHERE status = 'active' AND chair_id = ? ORDER BY name");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT id, name FROM buildings WHERE status = 'active' ORDER BY name");
    }
    $active_buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $active_buildings = [];
}
?>

<style>
    /* Back Button Theme Styling - Matching Header Color */
    .btn-outline-success {
        border-color: rgb(0, 105, 42);
        color: rgb(0, 105, 42);
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-outline-success:hover {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
        color: white;
    }

    .btn-outline-success:active {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }

    /* Custom green color overrides to match header */
    .bg-success {
        background-color: rgb(0, 105, 42) !important;
    }
    
    .btn-success {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    .btn-success:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
    
    .btn-success:active {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
    
    .text-success {
        color: rgb(0, 105, 42) !important;
    }
    
    .badge.bg-success {
        background-color: rgb(0, 105, 42) !important;
    }
</style>

<!-- Add meta tags to prevent caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="?page=<?= isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'chairperson' ? 'chair_dashboard' : (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'interviewer' ? 'interviewer_dashboard' : 'dashboard') ?>" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-tools me-2"></i>SYSTEM MAINTENANCE</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <?php if (isset($success_message)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong class="me-auto text-success">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" style="background-color: #d4edda;">
                    <?= $success_message ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                    <strong class="me-auto text-danger">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" style="background-color: #f8d7da;">
                    <?= $error_message ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Academic Management Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Management</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Strands Management</h5>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStrandFormModal">
                            <i class="fas fa-plus"></i> Add New Strand
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Strand Name</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($strands as $strand): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($strand['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $strand['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($strand['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($strand['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#archiveStrandModal" onclick="setStrandToArchive(<?= $strand['id'] ?>, '<?= htmlspecialchars($strand['name']) ?>')">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#restoreStrandModal" onclick="setStrandToRestore(<?= $strand['id'] ?>, '<?= htmlspecialchars($strand['name']) ?>')">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facility Management Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-building me-2"></i>Facility Management</h4>
                </div>
                <div class="card-body">
                    <!-- Buildings Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Buildings</h5>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBuildingFormModal">
                                <i class="fas fa-plus"></i> Add New Building
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Building Name</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buildings as $building): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($building['name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $building['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($building['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($building['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#archiveBuildingModal" onclick="setBuildingToArchive(<?= $building['id'] ?>, '<?= htmlspecialchars($building['name']) ?>')">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#restoreBuildingModal" onclick="setBuildingToRestore(<?= $building['id'] ?>, '<?= htmlspecialchars($building['name']) ?>')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rooms Section -->
                    <div class="border-top pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Rooms</h5>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomFormModal">
                                <i class="fas fa-plus"></i> Add New Room
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Building</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rooms as $room): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($room['room_number']) ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-building text-muted me-1"></i>
                                                <?= htmlspecialchars($room['building_name']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $room['status'] === 'active' ? 'success' : ($room['status'] === 'maintenance' ? 'danger' : 'secondary') ?>">
                                                    <?= ucfirst($room['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($room['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#archiveRoomModal" onclick="setRoomToArchive(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>', '<?= htmlspecialchars($room['building_name']) ?>')">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#restoreRoomModal" onclick="setRoomToRestore(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>', '<?= htmlspecialchars($room['building_name']) ?>')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Strand Form Modal -->
<div class="modal fade" id="addStrandFormModal" tabindex="-1" aria-labelledby="addStrandFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addStrandFormModalLabel">
                    <i class="fas fa-plus"></i> Add New Strand
                </h5>
            </div>
            <form id="addStrandForm" onsubmit="return false;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="strand_name_input" class="form-label">Strand Name</label>
                        <input type="text" class="form-control" id="strand_name_input" name="strand_name" required placeholder="Enter strand name" onkeypress="handleEnterKey(event)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="showAddConfirmation()">
                        <i class="fas fa-plus"></i> Add Strand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Strand Confirmation Modal -->
<div class="modal fade" id="addStrandConfirmModal" tabindex="-1" aria-labelledby="addStrandConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addStrandConfirmModalLabel">
                    <i class="fas fa-check-circle"></i> Confirm Add Strand
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_strand">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="strand_name" id="confirm_strand_name">
                    <p>Are you sure you want to add the strand <strong id="confirm_strand_display"></strong>?</p>
                    <p class="text-muted">This will make the strand available in the student profiling dropdown.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirm Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Archive Strand Modal -->
<div class="modal fade" id="archiveStrandModal" tabindex="-1" aria-labelledby="archiveStrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: #fff;">
                <h5 class="modal-title" id="archiveStrandModalLabel">
                    <i class="fas fa-archive"></i> Archive Strand
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="archive_strand">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="strand_id" id="archive_strand_id">
                    <p>Are you sure you want to archive the strand <strong id="archive_strand_name"></strong>?</p>
                    <p class="text-muted">This will hide the strand from the student profiling dropdown, but it can be restored later.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Archive Strand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Strand Modal -->
<div class="modal fade" id="restoreStrandModal" tabindex="-1" aria-labelledby="restoreStrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="restoreStrandModalLabel">
                    <i class="fas fa-undo"></i> Restore Strand
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="restore_strand">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="strand_id" id="restore_strand_id">
                    <p>Are you sure you want to restore the strand <strong id="restore_strand_name"></strong>?</p>
                    <p class="text-muted">This will make the strand available again in the student profiling dropdown.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-undo"></i> Restore Strand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Building Form Modal -->
<div class="modal fade" id="addBuildingFormModal" tabindex="-1" aria-labelledby="addBuildingFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addBuildingFormModalLabel">
                    <i class="fas fa-building"></i> Add New Building
                </h5>
            </div>
            <form id="addBuildingForm" onsubmit="return false;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="building_name_input" class="form-label">Building Name *</label>
                        <input type="text" class="form-control" id="building_name_input" name="building_name" required placeholder="Enter building name" onkeypress="handleEnterKey(event)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="showAddBuildingConfirmation()">
                        <i class="fas fa-plus"></i> Add Building
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Building Confirmation Modal -->
<div class="modal fade" id="addBuildingConfirmModal" tabindex="-1" aria-labelledby="addBuildingConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addBuildingConfirmModalLabel">
                    <i class="fas fa-check-circle"></i> Confirm Add Building
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_building">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="building_name" id="confirm_building_name">
                    <p>Are you sure you want to add the building <strong id="confirm_building_display"></strong>?</p>
                    <p class="text-muted">This will make the building available for room management.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirm Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Archive Building Modal -->
<div class="modal fade" id="archiveBuildingModal" tabindex="-1" aria-labelledby="archiveBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: #fff;">
                <h5 class="modal-title" id="archiveBuildingModalLabel">
                    <i class="fas fa-archive"></i> Archive Building
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="archive_building">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="building_id" id="archive_building_id">
                    <p>Are you sure you want to archive the building <strong id="archive_building_name"></strong>?</p>
                    <p class="text-muted">This will hide the building from the room management dropdown, but it can be restored later.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Archive Building
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Building Modal -->
<div class="modal fade" id="restoreBuildingModal" tabindex="-1" aria-labelledby="restoreBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="restoreBuildingModalLabel">
                    <i class="fas fa-undo"></i> Restore Building
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="restore_building">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="building_id" id="restore_building_id">
                    <p>Are you sure you want to restore the building <strong id="restore_building_name"></strong>?</p>
                    <p class="text-muted">This will make the building available again for room management.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-undo"></i> Restore Building
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Room Form Modal -->
<div class="modal fade" id="addRoomFormModal" tabindex="-1" aria-labelledby="addRoomFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addRoomFormModalLabel">
                    <i class="fas fa-door-open"></i> Add New Room
                </h5>
            </div>
            <form id="addRoomForm" onsubmit="return false;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="building_select" class="form-label">Building *</label>
                        <select class="form-control" id="building_select" name="building_id" required>
                            <option value="">Select a building</option>
                            <?php foreach ($active_buildings as $building): ?>
                                <option value="<?= $building['id'] ?>"><?= htmlspecialchars($building['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="room_number_input" class="form-label">Room Number *</label>
                        <input type="text" class="form-control" id="room_number_input" name="room_number" required placeholder="Enter room number" onkeypress="handleEnterKey(event)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="showAddRoomConfirmation()">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Room Confirmation Modal -->
<div class="modal fade" id="addRoomConfirmModal" tabindex="-1" aria-labelledby="addRoomConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="addRoomConfirmModalLabel">
                    <i class="fas fa-check-circle"></i> Confirm Add Room
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="building_id" id="confirm_room_building_id">
                    <input type="hidden" name="room_number" id="confirm_room_number">
                    <p>Are you sure you want to add the room <strong id="confirm_room_display"></strong>?</p>
                    <p class="text-muted">This will make the room available for scheduling and management.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirm Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Archive Room Modal -->
<div class="modal fade" id="archiveRoomModal" tabindex="-1" aria-labelledby="archiveRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: #fff;">
                <h5 class="modal-title" id="archiveRoomModalLabel">
                    <i class="fas fa-archive"></i> Archive Room
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="archive_room">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="room_id" id="archive_room_id">
                    <p>Are you sure you want to archive the room <strong id="archive_room_display"></strong>?</p>
                    <p class="text-muted">This will hide the room from scheduling, but it can be restored later.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Archive Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Room Modal -->
<div class="modal fade" id="restoreRoomModal" tabindex="-1" aria-labelledby="restoreRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="restoreRoomModalLabel">
                    <i class="fas fa-undo"></i> Restore Room
                </h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="restore_room">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                    <input type="hidden" name="operation_id" value="<?= bin2hex(random_bytes(16)) ?>">
                    <input type="hidden" name="room_id" id="restore_room_id">
                    <p>Are you sure you want to restore the room <strong id="restore_room_display"></strong>?</p>
                    <p class="text-muted">This will make the room available again for scheduling.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-undo"></i> Restore Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Function to handle Enter key press
function handleEnterKey(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        // Determine which form is being used based on the input field
        if (event.target.id === 'strand_name_input') {
            showAddConfirmation();
        } else if (event.target.id === 'building_name_input') {
            showAddBuildingConfirmation();
        } else if (event.target.id === 'room_number_input') {
            showAddRoomConfirmation();
        }
    }
}

// Function to show add strand confirmation
function showAddConfirmation() {
    const strandName = document.getElementById('strand_name_input').value.trim();
    
    if (!strandName) {
        alert('Please enter a strand name.');
        return;
    }
    
    // Set the strand name in the confirmation modal
    document.getElementById('confirm_strand_name').value = strandName;
    document.getElementById('confirm_strand_display').textContent = strandName;
    
    // Hide the form modal and show confirmation modal
    const formModal = bootstrap.Modal.getInstance(document.getElementById('addStrandFormModal'));
    formModal.hide();
    
    // Show confirmation modal after a short delay
    setTimeout(() => {
        const confirmModal = new bootstrap.Modal(document.getElementById('addStrandConfirmModal'));
        confirmModal.show();
    }, 300);
}

// Function to set strand data for archiving
function setStrandToArchive(strandId, strandName) {
    document.getElementById('archive_strand_id').value = strandId;
    document.getElementById('archive_strand_name').textContent = strandName;
}

// Function to set strand data for restoring
function setStrandToRestore(strandId, strandName) {
    document.getElementById('restore_strand_id').value = strandId;
    document.getElementById('restore_strand_name').textContent = strandName;
}

// Function to show add building confirmation
function showAddBuildingConfirmation() {
    const buildingName = document.getElementById('building_name_input').value.trim();
    
    if (!buildingName) {
        alert('Please enter a building name.');
        return;
    }
    
    // Set the building data in the confirmation modal
    document.getElementById('confirm_building_name').value = buildingName;
    document.getElementById('confirm_building_display').textContent = buildingName;
    
    // Hide the form modal and show confirmation modal
    const formModal = bootstrap.Modal.getInstance(document.getElementById('addBuildingFormModal'));
    formModal.hide();
    
    // Show confirmation modal after a short delay
    setTimeout(() => {
        const confirmModal = new bootstrap.Modal(document.getElementById('addBuildingConfirmModal'));
        confirmModal.show();
    }, 300);
}

// Function to set building data for archiving
function setBuildingToArchive(buildingId, buildingName) {
    document.getElementById('archive_building_id').value = buildingId;
    document.getElementById('archive_building_name').textContent = buildingName;
}

// Function to set building data for restoring
function setBuildingToRestore(buildingId, buildingName) {
    document.getElementById('restore_building_id').value = buildingId;
    document.getElementById('restore_building_name').textContent = buildingName;
}

// Function to show add room confirmation
function showAddRoomConfirmation() {
    const buildingId = document.getElementById('building_select').value;
    const roomNumber = document.getElementById('room_number_input').value.trim();
    
    if (!buildingId || !roomNumber) {
        alert('Please select a building and enter a room number.');
        return;
    }
    
    // Get building name for display
    const buildingSelect = document.getElementById('building_select');
    const buildingName = buildingSelect.options[buildingSelect.selectedIndex].text;
    
    // Set the room data in the confirmation modal
    document.getElementById('confirm_room_building_id').value = buildingId;
    document.getElementById('confirm_room_number').value = roomNumber;
    document.getElementById('confirm_room_display').textContent = roomNumber + ' in ' + buildingName;
    
    // Hide the form modal and show confirmation modal
    const formModal = bootstrap.Modal.getInstance(document.getElementById('addRoomFormModal'));
    formModal.hide();
    
    // Show confirmation modal after a short delay
    setTimeout(() => {
        const confirmModal = new bootstrap.Modal(document.getElementById('addRoomConfirmModal'));
        confirmModal.show();
    }, 300);
}

// Function to set room data for archiving
function setRoomToArchive(roomId, roomNumber, buildingName) {
    document.getElementById('archive_room_id').value = roomId;
    document.getElementById('archive_room_display').textContent = roomNumber + ' in ' + buildingName;
}

// Function to set room data for restoring
function setRoomToRestore(roomId, roomNumber, buildingName) {
    document.getElementById('restore_room_id').value = roomId;
    document.getElementById('restore_room_display').textContent = roomNumber + ' in ' + buildingName;
}

// Enhanced form resubmission prevention
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
    
    // Enhanced form submission prevention
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            // Prevent multiple submissions
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            
            // Disable all submit buttons and show loading state
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                button.dataset.originalText = originalText;
            });
            
            // Disable all form inputs to prevent changes during submission
            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            
            // Add a timeout to re-enable form if submission takes too long
            setTimeout(() => {
                if (isSubmitting) {
                    isSubmitting = false;
                    submitButtons.forEach(button => {
                        button.disabled = false;
                        button.innerHTML = button.dataset.originalText || 'Submit';
                    });
                    inputs.forEach(input => {
                        input.disabled = false;
                    });
                }
            }, 10000); // 10 second timeout
        });
    });
    
    // Prevent browser back/forward navigation from resubmitting forms
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page was loaded from cache, refresh to get fresh state
            window.location.reload();
        }
    });
    
    // Clear form data on page load to prevent refresh resubmission
    window.addEventListener('load', function() {
        // Clear any cached form data
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Clear any form data that might be cached
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.reset();
        });
        
        // Ensure we're on the maintenance page
        const currentUrl = window.location.href;
        if (!currentUrl.includes('page=maintenance')) {
            const baseUrl = currentUrl.split('?')[0];
            window.history.replaceState(null, null, baseUrl + '?page=maintenance');
        }
    });
    
    // Prevent double-click on submit buttons
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.disabled) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Handle Enter key in input fields to prevent unwanted form submissions
    const inputFields = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    inputFields.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Special handling for strand name input
                if (this.id === 'strand_name_input') {
                    showAddConfirmation();
                } else {
                    // Find the associated button and click it
                    const form = this.closest('form');
                    if (form) {
                        const submitButton = form.querySelector('button[type="submit"], button:not([type="button"])');
                        if (submitButton && !submitButton.disabled) {
                            submitButton.click();
                        }
                    }
                }
            }
        });
    });
});
</script>