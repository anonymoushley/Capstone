<?php
// Include database connection
require_once '../config/database.php';

// Include PHPMailer for email notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../vendor/autoload.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add security headers to prevent caching and improve security
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Get chairperson information if logged in as chairperson
$chairId = $_SESSION['chair_id'] ?? null;
$chairProgram = $_SESSION['program'] ?? '';
$chairCampus = $_SESSION['campus'] ?? '';
$isChairperson = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'chairperson';

// Ensure chair_id column exists in schedules table
try {
    $check_col = $pdo->query("SHOW COLUMNS FROM schedules LIKE 'chair_id'");
    if ($check_col->rowCount() == 0) {
        // Add chair_id column to schedules table
        $pdo->exec("ALTER TABLE schedules ADD COLUMN chair_id int(11) DEFAULT NULL AFTER venue");
        
        // Add index for chair_id
        try {
            $pdo->exec("ALTER TABLE schedules ADD KEY idx_schedules_chair_id (chair_id)");
        } catch (PDOException $e) {
            // Index might already exist, ignore
        }
        
        // Add foreign key constraint
        try {
            $pdo->exec("ALTER TABLE schedules 
                        ADD CONSTRAINT fk_schedules_chair_id 
                        FOREIGN KEY (chair_id) REFERENCES chairperson_accounts(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (PDOException $e) {
            // Constraint might already exist, ignore
        }
    }
} catch (PDOException $e) {
    // Table might not exist yet, or column already exists - ignore error
    error_log("Note: Could not add chair_id to schedules table: " . $e->getMessage());
}

// Ensure chair_id column exists in interview_schedules table
try {
    // Check if interview_schedules table exists first
    $table_check = $pdo->query("SHOW TABLES LIKE 'interview_schedules'");
    if ($table_check->rowCount() > 0) {
        $check_col = $pdo->query("SHOW COLUMNS FROM interview_schedules LIKE 'chair_id'");
        if ($check_col->rowCount() == 0) {
            // Add chair_id column to interview_schedules table
            $pdo->exec("ALTER TABLE interview_schedules ADD COLUMN chair_id int(11) DEFAULT NULL AFTER venue");
            
            // Add index for chair_id
            try {
                $pdo->exec("ALTER TABLE interview_schedules ADD KEY idx_interview_schedules_chair_id (chair_id)");
            } catch (PDOException $e) {
                // Index might already exist, ignore
            }
            
            // Add foreign key constraint
            try {
                $pdo->exec("ALTER TABLE interview_schedules 
                            ADD CONSTRAINT fk_interview_schedules_chair_id 
                            FOREIGN KEY (chair_id) REFERENCES chairperson_accounts(id) 
                            ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (PDOException $e) {
                // Constraint might already exist, ignore
            }
        }
    }
} catch (PDOException $e) {
    // Table might not exist yet, or column already exists - ignore error
    error_log("Note: Could not add chair_id to interview_schedules table: " . $e->getMessage());
}

// Function to send schedule notification email
function sendScheduleNotificationEmail($pdo, $applicantIds, $scheduleType, $scheduleData) {
    try {
        // Get applicant emails and names
        $placeholders = str_repeat('?,', count($applicantIds) - 1) . '?';
        $sql = "SELECT r.id, r.email_address, pi.first_name, pi.last_name 
                FROM registration r 
                LEFT JOIN personal_info pi ON r.personal_info_id = pi.id 
                WHERE r.id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($applicantIds);
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($applicants)) {
            error_log("No applicants found for email notification");
            return false;
        }
        
        // Format schedule details
        $eventDate = date('F d, Y', strtotime($scheduleData['event_date']));
        $eventTime = date('g:i A', strtotime($scheduleData['event_time']));
        $venue = $scheduleData['venue'] ?? 'TBA';
        $scheduleTypeLabel = $scheduleType === 'exam' ? 'Exam' : 'Interview';
        
        // Send email to each applicant
        foreach ($applicants as $applicant) {
            if (empty($applicant['email_address'])) {
                error_log("No email address for applicant ID: " . $applicant['id']);
                continue;
            }
            
            $fullName = trim(($applicant['first_name'] ?? '') . ' ' . ($applicant['last_name'] ?? ''));
            if (empty($fullName)) {
                $fullName = 'Applicant';
            }
            
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'acregalado.chmsu@gmail.com';
                $mail->Password = 'vvekpeviojyyysfq';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->Timeout = 10;
                $mail->SMTPKeepAlive = false;
                
                $mail->setFrom('acregalado.chmsu@gmail.com', 'CHMSU Admissions');
                $mail->addAddress($applicant['email_address'], $fullName);
                
                $mail->isHTML(true);
                $mail->Subject = "CHMSU {$scheduleTypeLabel} Schedule Notification";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <div style='background-color: #00692a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                            <h2 style='margin: 0;'>Carlos Hilado Memorial State University</h2>
                            <p style='margin: 10px 0 0 0;'>Academic Program Application and Screening Management System</p>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px;'>
                            <p>Dear <strong>" . htmlspecialchars($fullName) . "</strong>,</p>
                            <p>You have been scheduled for your <strong>{$scheduleTypeLabel}</strong>.</p>
                            <div style='background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #00692a;'>
                                <h3 style='color: #00692a; margin-top: 0;'>Schedule Details</h3>
                                <p style='margin: 10px 0;'><strong>Date:</strong> {$eventDate}</p>
                                <p style='margin: 10px 0;'><strong>Time:</strong> {$eventTime}</p>
                                <p style='margin: 10px 0;'><strong>Venue:</strong> " . htmlspecialchars($venue) . "</p>
                            </div>
                            <p>Please arrive on time and bring all necessary documents.</p>
                            <p>If you have any questions or concerns, please contact the admissions office.</p>
                            <p style='margin-top: 30px;'>Best regards,<br><strong>CHMSU Admissions Team</strong></p>
                        </div>
                    </div>
                ";
                
                $mail->send();
                error_log("Schedule notification email sent successfully to: " . $applicant['email_address']);
            } catch (Exception $e) {
                error_log("Failed to send schedule notification email to {$applicant['email_address']}: " . $mail->ErrorInfo);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error in sendScheduleNotificationEmail: " . $e->getMessage());
        return false;
    }
}

// Get sub-page parameter
$sub_page = isset($_GET['sub']) ? $_GET['sub'] : 'exam';

// Get filter parameters
$filterStatus = $_GET['filter_status'] ?? '';

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_schedule') {
        $schedule_date = $_POST['schedule_date'] ?? '';
        $schedule_start_time = $_POST['schedule_start_time'] ?? '';
        $schedule_venues = $_POST['schedule_venues'] ?? [];
        
        // Validate date is not in the past
        if ($schedule_date) {
            $selected_date = new DateTime($schedule_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                $error_message = "Cannot schedule events for past dates. Please select today or a future date.";
            }
        }
        
        if ($schedule_date && $schedule_start_time && !empty($schedule_venues) && !isset($error_message)) {
            try {
                // Join all selected venues with comma
                $venues_string = implode(', ', $schedule_venues);
                
                // Check if chair_id column exists before including it in INSERT
                $has_chair_id = false;
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM schedules LIKE 'chair_id'");
                    $has_chair_id = $check_col->rowCount() > 0;
                } catch (PDOException $e) {
                    $has_chair_id = false;
                }
                
                // Create a single schedule record with all venues
                if ($has_chair_id) {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, chair_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$schedule_date, $schedule_start_time, $venues_string, $isChairperson ? $chairId : null]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$schedule_date, $schedule_start_time, $venues_string]);
                }
                
                $success_message = "Schedule created successfully with " . count($schedule_venues) . " venue(s).";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=schedule_created");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to create schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields and select at least one venue.";
        }
    } elseif ($action === 'add_interview_schedule') {
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_end_time = $_POST['interview_end_time'] ?? '';
        $interview_venue = $_POST['interview_venue'] ?? '';
        $interview_applicant = $_POST['interview_applicant'] ?? '';
        
        // Validate date is not in the past
        if ($interview_date) {
            $selected_date = new DateTime($interview_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                $error_message = "Cannot schedule interviews for past dates. Please select today or a future date.";
            }
        }
        
        if ($interview_date && $interview_start_time && $interview_end_time && $interview_venue && $interview_applicant && !isset($error_message)) {
            try {
                // Check if chair_id column exists before including it in INSERT
                $has_chair_id = false;
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM schedules LIKE 'chair_id'");
                    $has_chair_id = $check_col->rowCount() > 0;
                } catch (PDOException $e) {
                    $has_chair_id = false;
                }
                
                // Create a single schedule record for the interview
                if ($has_chair_id) {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, end_time, venue, chair_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $interview_end_time, $interview_venue, $isChairperson ? $chairId : null]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, end_time, venue, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $interview_end_time, $interview_venue]);
                }
                $schedule_id = $pdo->lastInsertId();
                
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link the applicant to this schedule
                $stmt = $pdo->prepare("INSERT INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$schedule_id, $interview_applicant]);
                
                // Send email notification to assigned applicant
                $schedule_data = [
                    'event_date' => $interview_date,
                    'event_time' => $interview_start_time,
                    'venue' => $interview_venue
                ];
                sendScheduleNotificationEmail($pdo, [$interview_applicant], 'interview', $schedule_data);
                
                $success_message = "Interview schedule added successfully.";
                // Redirect to prevent form resubmission - stay on scheduling page
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=interview_scheduled");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to add interview schedule: " . $e->getMessage();
                }
            } else {
                $error_message = "Please fill in all required fields.";
            }
    } elseif ($action === 'assign_to_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($applicant_ids && $schedule_id) {
            try {
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all applicants to the selected schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                $assigned_ids = [];
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        if ($stmt->rowCount() > 0) {
                            $count++;
                            $assigned_ids[] = $applicant_id;
                        }
                    }
                }
                
                // Send email notifications to assigned applicants
                if ($count > 0 && !empty($assigned_ids)) {
                    // Get schedule details
                    $schedule_stmt = $pdo->prepare("SELECT event_date, event_time, venue FROM schedules WHERE id = ?");
                    $schedule_stmt->execute([$schedule_id]);
                    $schedule_data = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($schedule_data) {
                        sendScheduleNotificationEmail($pdo, $assigned_ids, 'exam', $schedule_data);
                    }
                }
                
                $success_message = "Successfully assigned {$count} applicants to the schedule.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=applicants_assigned");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to assign applicants to schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please select applicants and a schedule.";
            }
    } elseif ($action === 'set_exam_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $exam_date = $_POST['exam_date'] ?? '';
        $exam_start_time = $_POST['exam_start_time'] ?? '';
        $exam_venue = $_POST['exam_venue'] ?? '';
        
        // Validate date is not in the past
        if ($exam_date) {
            $selected_date = new DateTime($exam_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                $error_message = "Cannot schedule exams for past dates. Please select today or a future date.";
            }
        }
        
        if ($applicant_ids && $exam_date && $exam_start_time && $exam_venue && !isset($error_message)) {
            try {
                // Check if chair_id column exists before including it in INSERT
                $has_chair_id = false;
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM schedules LIKE 'chair_id'");
                    $has_chair_id = $check_col->rowCount() > 0;
                } catch (PDOException $e) {
                    $has_chair_id = false;
                }
                
                // Create a single schedule record for the group
                if ($has_chair_id) {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, chair_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$exam_date, $exam_start_time, $exam_venue, $isChairperson ? $chairId : null]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$exam_date, $exam_start_time, $exam_venue]);
                }
                $schedule_id = $pdo->lastInsertId();
                
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all applicants to this single schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                $assigned_ids = [];
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                        $assigned_ids[] = $applicant_id;
                    }
                }
                
                // Send email notifications to assigned applicants
                if ($count > 0 && !empty($assigned_ids)) {
                    $schedule_data = [
                        'event_date' => $exam_date,
                        'event_time' => $exam_start_time,
                        'venue' => $exam_venue
                    ];
                    sendScheduleNotificationEmail($pdo, $assigned_ids, 'exam', $schedule_data);
                }
                
                $success_message = "Exam schedule created successfully for {$count} selected applicants.";
                // Redirect to prevent form resubmission - stay on scheduling page
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=exam_scheduled");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to set exam schedule: " . $e->getMessage();
                }
        } else {
            $error_message = "Please fill in all required fields (date, start time, venue) and select applicants.";
        }
    } elseif ($action === 'assign_to_interview_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($applicant_ids && $schedule_id) {
            try {
                // Create interview_schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`, `applicant_id`),
                    KEY `idx_interview_schedule_id` (`schedule_id`),
                    KEY `idx_interview_applicant_id` (`applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all selected applicants to this interview schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                $assigned_ids = [];
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO interview_schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                        $assigned_ids[] = $applicant_id;
                    }
                }
                
                // Send email notifications to assigned applicants
                if ($count > 0 && !empty($assigned_ids)) {
                    // Get interview schedule details
                    $schedule_stmt = $pdo->prepare("SELECT event_date, event_time, venue FROM interview_schedules WHERE id = ?");
                    $schedule_stmt->execute([$schedule_id]);
                    $schedule_data = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($schedule_data) {
                        sendScheduleNotificationEmail($pdo, $assigned_ids, 'interview', $schedule_data);
                    }
                }
                
                $success_message = "Successfully assigned {$count} applicants to interview schedule.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_assigned");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to assign applicants to interview schedule: " . $e->getMessage();
                }
        } else {
            $error_message = "Please select applicants and a schedule.";
        }
    } elseif ($action === 'delete_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($schedule_id) {
            try {
                // If chairperson, verify schedule belongs to them
                if ($isChairperson && $chairId) {
                    $check_owner = $pdo->prepare("SELECT chair_id FROM schedules WHERE id = ?");
                    $check_owner->execute([$schedule_id]);
                    $schedule = $check_owner->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$schedule || $schedule['chair_id'] != $chairId) {
                        $error_message = "You do not have permission to delete this schedule.";
                    } else {
                        // Check if schedule has applicants
                        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM schedule_applicants WHERE schedule_id = ?");
                        $check_stmt->execute([$schedule_id]);
                        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result && $result['count'] > 0) {
                            $error_message = "Cannot delete schedule with assigned applicants. Please remove applicants first.";
                        } else {
                            // Delete the schedule
                            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
                            $stmt->execute([$schedule_id]);
                            
                            $success_message = "Schedule deleted successfully.";
                            header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=schedule_deleted");
                            exit();
                        }
                    }
                } else {
                    // Check if schedule has applicants
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM schedule_applicants WHERE schedule_id = ?");
                    $check_stmt->execute([$schedule_id]);
                    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && $result['count'] > 0) {
                        $error_message = "Cannot delete schedule with assigned applicants. Please remove applicants first.";
                    } else {
                        // Delete the schedule
                        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
                        $stmt->execute([$schedule_id]);
                        
                        $success_message = "Schedule deleted successfully.";
                        header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=schedule_deleted");
                        exit();
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Failed to delete schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid schedule ID.";
        }
    } elseif ($action === 'delete_interview_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($schedule_id) {
            try {
                // If chairperson, verify interview schedule belongs to them
                if ($isChairperson && $chairId) {
                    $check_owner = $pdo->prepare("SELECT chair_id FROM interview_schedules WHERE id = ?");
                    $check_owner->execute([$schedule_id]);
                    $schedule = $check_owner->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$schedule || $schedule['chair_id'] != $chairId) {
                        $error_message = "You do not have permission to delete this interview schedule.";
                    } else {
                        // Check if schedule has applicants
                        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interview_schedule_applicants WHERE schedule_id = ?");
                        $check_stmt->execute([$schedule_id]);
                        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result && $result['count'] > 0) {
                            $error_message = "Cannot delete interview schedule with assigned applicants. Please remove applicants first.";
                        } else {
                            // Delete the interview schedule
                            $stmt = $pdo->prepare("DELETE FROM interview_schedules WHERE id = ?");
                            $stmt->execute([$schedule_id]);
                            
                            $success_message = "Interview schedule deleted successfully.";
                            header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_schedule_deleted");
                            exit();
                        }
                    }
                } else {
                    // Check if schedule has applicants
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interview_schedule_applicants WHERE schedule_id = ?");
                    $check_stmt->execute([$schedule_id]);
                    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && $result['count'] > 0) {
                        $error_message = "Cannot delete interview schedule with assigned applicants. Please remove applicants first.";
                    } else {
                        // Delete the interview schedule
                        $stmt = $pdo->prepare("DELETE FROM interview_schedules WHERE id = ?");
                        $stmt->execute([$schedule_id]);
                        
                        $success_message = "Interview schedule deleted successfully.";
                        header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_schedule_deleted");
                        exit();
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Failed to delete interview schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid schedule ID.";
        }
    } elseif ($action === 'create_interview_schedule') {
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_venues = $_POST['interview_venues'] ?? [];
        
        // Validate date is not in the past
        if ($interview_date) {
            $selected_date = new DateTime($interview_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                $error_message = "Cannot schedule interviews for past dates. Please select today or a future date.";
            }
        }
        
        if ($interview_date && $interview_start_time && !empty($interview_venues) && !isset($error_message)) {
            try {
                // Join all selected venues with comma
                $venues_string = implode(', ', $interview_venues);
                
                // Create interview_schedules table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedules` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `event_date` date NOT NULL,
                    `event_time` time NOT NULL,
                    `venue` varchar(255) NOT NULL,
                    `chair_id` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_interview_schedules_chair_id` (`chair_id`),
                    CONSTRAINT `fk_interview_schedules_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Check if chair_id column exists before including it in INSERT
                $has_chair_id = false;
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM interview_schedules LIKE 'chair_id'");
                    $has_chair_id = $check_col->rowCount() > 0;
                } catch (PDOException $e) {
                    $has_chair_id = false;
                }
                
                // Create a single interview schedule record with all venues
                if ($has_chair_id) {
                    $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, chair_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $venues_string, $isChairperson ? $chairId : null]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $venues_string]);
                }
                
                $success_message = "Interview schedule created successfully with " . count($interview_venues) . " venue(s).";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_schedule_created");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to create interview schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields and select at least one venue.";
        }
    } elseif ($action === 'set_interview_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_venue = $_POST['interview_venue'] ?? '';
        
        // Validate date is not in the past
        if ($interview_date) {
            $selected_date = new DateTime($interview_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                $error_message = "Cannot schedule interviews for past dates. Please select today or a future date.";
            }
        }
        
        if ($applicant_ids && $interview_date && $interview_start_time && $interview_venue && !isset($error_message)) {
            try {
                // Ensure dedicated interview tables exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedules` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `event_date` date NOT NULL,
                    `event_time` time NOT NULL,
                    `venue` varchar(255) DEFAULT NULL,
                    `chair_id` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_interview_schedules_chair_id` (`chair_id`),
                    CONSTRAINT `fk_interview_schedules_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`, `applicant_id`),
                    KEY `idx_interview_schedule_id` (`schedule_id`),
                    KEY `idx_interview_applicant_id` (`applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

                // Check if chair_id column exists before including it in INSERT
                $has_chair_id = false;
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM interview_schedules LIKE 'chair_id'");
                    $has_chair_id = $check_col->rowCount() > 0;
                } catch (PDOException $e) {
                    $has_chair_id = false;
                }
                
                // Create a single interview schedule record for the group
                if ($has_chair_id) {
                    $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, chair_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $interview_venue, $isChairperson ? $chairId : null]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$interview_date, $interview_start_time, $interview_venue]);
                }
                $schedule_id = $pdo->lastInsertId();

                // Link all selected applicants to this interview schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                $assigned_ids = [];
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO interview_schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                        $assigned_ids[] = $applicant_id;
                    }
                }

                // Send email notifications to assigned applicants
                if ($count > 0 && !empty($assigned_ids)) {
                    $schedule_data = [
                        'event_date' => $interview_date,
                        'event_time' => $interview_start_time,
                        'venue' => $interview_venue
                    ];
                    sendScheduleNotificationEmail($pdo, $assigned_ids, 'interview', $schedule_data);
                }

                $success_message = "Interview schedule created successfully for {$count} selected applicants.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_scheduled");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to set interview schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields (date, start time, end time, venue) and select applicants.";
        }
    }
}

// Handle success messages from redirects
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'schedule_created':
            $success_message = "Schedule created successfully!";
            break;
        case 'applicants_assigned':
            $success_message = "Applicants assigned to schedule successfully!";
            break;
        case 'exam_scheduled':
            $success_message = "Exam schedule created successfully!";
            break;
        case 'interview_scheduled':
            $success_message = "Interview schedule added successfully!";
            break;
        case 'interview_assigned':
            $success_message = "Applicants assigned to interview schedule successfully!";
            break;
        case 'interview_schedule_created':
            $success_message = "Interview schedule created successfully!";
            break;
        case 'schedule_deleted':
            $success_message = "Schedule deleted successfully!";
            break;
        case 'interview_schedule_deleted':
            $success_message = "Interview schedule deleted successfully!";
            break;
    }
}

// Get all schedules with applicant count (filtered by chair_id if chairperson)
try {
    if ($isChairperson && $chairId) {
        // Filter schedules by chair_id to ensure chairpersons only see their own schedules
        $stmt = $pdo->prepare("SELECT s.*, COUNT(sa.applicant_id) as applicant_count 
                            FROM schedules s 
                            LEFT JOIN schedule_applicants sa ON s.id = sa.schedule_id 
                            WHERE s.chair_id = ?
                            GROUP BY s.id 
                            ORDER BY s.event_date, s.event_time");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT s.*, COUNT(sa.applicant_id) as applicant_count 
                            FROM schedules s 
                            LEFT JOIN schedule_applicants sa ON s.id = sa.schedule_id 
                            GROUP BY s.id 
                            ORDER BY s.event_date, s.event_time");
    }
    $all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out schedules that already have applicants assigned (for dropdown)
    $schedules = array_filter($all_schedules, function($schedule) {
        return $schedule['applicant_count'] == 0;
    });
    
    // Keep all schedules for viewing (including assigned ones)
    $all_schedules_for_viewing = $all_schedules;
} catch (PDOException $e) {
    $schedules = [];
    $all_schedules_for_viewing = [];
    $error_message = "Failed to fetch schedules: " . $e->getMessage();
}

// Handle AJAX request for getting students for a specific schedule
if (isset($_GET['action']) && $_GET['action'] === 'get_schedule_students') {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    $schedule_id = $_GET['schedule_id'] ?? '';
    
    if ($schedule_id) {
        try {
            // If chairperson, verify schedule belongs to them
            if ($isChairperson && $chairId) {
                $check_stmt = $pdo->prepare("SELECT chair_id FROM schedules WHERE id = ?");
                $check_stmt->execute([$schedule_id]);
                $schedule = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$schedule || $schedule['chair_id'] != $chairId) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Access denied. This schedule does not belong to your account.']);
                    exit();
                }
            }
            
            $sql = "SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program
                                 FROM personal_info pi 
                                 LEFT JOIN registration r ON pi.id = r.personal_info_id
                                 LEFT JOIN program_application pa ON (pa.personal_info_id = pi.id OR pa.registration_id = r.id)
                                 INNER JOIN schedule_applicants sa ON r.id = sa.applicant_id
                                 WHERE sa.schedule_id = ?";
            $sql .= " ORDER BY pi.last_name, pi.first_name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$schedule_id]);
            $schedule_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($schedule_students);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch students: ' . $e->getMessage()]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Schedule ID is required']);
        exit();
    }
}

// Handle AJAX request for getting students for a specific interview schedule
if (isset($_GET['action']) && $_GET['action'] === 'get_interview_schedule_students') {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    $schedule_id = $_GET['schedule_id'] ?? '';
    
    if ($schedule_id) {
        try {
            // If chairperson, verify interview schedule belongs to them
            if ($isChairperson && $chairId) {
                $check_stmt = $pdo->prepare("SELECT chair_id FROM interview_schedules WHERE id = ?");
                $check_stmt->execute([$schedule_id]);
                $schedule = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$schedule || $schedule['chair_id'] != $chairId) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Access denied. This interview schedule does not belong to your account.']);
                    exit();
                }
            }
            
            $sql = "SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program
                                 FROM personal_info pi 
                                 LEFT JOIN registration r ON pi.id = r.personal_info_id
                                 LEFT JOIN program_application pa ON (pa.personal_info_id = pi.id OR pa.registration_id = r.id)
                                 INNER JOIN interview_schedule_applicants isa ON r.id = isa.applicant_id
                                 WHERE isa.schedule_id = ?";
            $sql .= " ORDER BY pi.last_name, pi.first_name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$schedule_id]);
            $schedule_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($schedule_students);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch interview students: ' . $e->getMessage()]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Schedule ID is required']);
        exit();
    }
}

// Get applicants with all required documents accepted and their exam status
try {
    // First, check if document status fields exist
    $status_fields_exist = false;
    try {
        $check_stmt = $pdo->query("SHOW COLUMNS FROM documents LIKE '%_status'");
        $status_fields_exist = $check_stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $status_fields_exist = false;
    }
    
    if ($status_fields_exist) {
        // Query with document status fields - only show applicants with all documents accepted
        // Filter by program/campus if chairperson
        $sql = "SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                            CASE WHEN sa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as exam_status,
                            s.event_date as scheduled_date
                            FROM personal_info pi 
                            LEFT JOIN registration r ON pi.id = r.personal_info_id
                            LEFT JOIN program_application pa ON (pa.personal_info_id = pi.id OR pa.registration_id = r.id)
                            LEFT JOIN documents d ON pi.id = d.personal_info_id
                            LEFT JOIN schedule_applicants sa ON r.id = sa.applicant_id
                            LEFT JOIN schedules s ON sa.schedule_id = s.id
                            WHERE d.g11_1st IS NOT NULL 
                            AND d.g11_2nd IS NOT NULL 
                            AND d.g12_1st IS NOT NULL 
                            AND (d.g11_1st_status = 'Accepted' OR d.g11_1st_status IS NULL)
                            AND (d.g11_2nd_status = 'Accepted' OR d.g11_2nd_status IS NULL)
                            AND (d.g12_1st_status = 'Accepted' OR d.g12_1st_status IS NULL)
                            AND NOT EXISTS (
                                SELECT 1 FROM documents d2 
                                WHERE d2.personal_info_id = pi.id 
                                AND (
                                    (d2.g11_1st_status = 'Rejected') OR
                                    (d2.g11_2nd_status = 'Rejected') OR
                                    (d2.g12_1st_status = 'Rejected')
                                )
                            )";
        if ($isChairperson && $chairProgram && $chairCampus) {
            $sql .= " AND LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)";
        }
        $sql .= " GROUP BY pi.id ORDER BY pi.last_name, pi.first_name";
        
        if ($isChairperson && $chairProgram && $chairCampus) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$chairProgram, $chairCampus]);
        } else {
            $stmt = $pdo->query($sql);
        }
    } else {
        // Fallback: Query without status fields - only show applicants with documents uploaded
        // Filter by program/campus if chairperson
        $sql = "SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                            CASE WHEN sa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as exam_status,
                            s.event_date as scheduled_date
                            FROM personal_info pi 
                            LEFT JOIN registration r ON pi.id = r.personal_info_id
                            LEFT JOIN program_application pa ON (pa.personal_info_id = pi.id OR pa.registration_id = r.id)
                            LEFT JOIN documents d ON pi.id = d.personal_info_id
                            LEFT JOIN schedule_applicants sa ON r.id = sa.applicant_id
                            LEFT JOIN schedules s ON sa.schedule_id = s.id
                            WHERE d.g11_1st IS NOT NULL 
                            AND d.g11_2nd IS NOT NULL 
                            AND d.g12_1st IS NOT NULL";
        if ($isChairperson && $chairProgram && $chairCampus) {
            $sql .= " AND LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)";
        }
        $sql .= " GROUP BY pi.id ORDER BY pi.last_name, pi.first_name";
        
        if ($isChairperson && $chairProgram && $chairCampus) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$chairProgram, $chairCampus]);
        } else {
            $stmt = $pdo->query($sql);
        }
    }
    
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply status filter if specified
    if (!empty($filterStatus)) {
        $applicants = array_filter($applicants, function($applicant) use ($filterStatus) {
            return $applicant['exam_status'] === $filterStatus;
        });
    }
    
    // Count scheduled applicants
    $scheduled_count = 0;
    $total_count = count($applicants);
    foreach($applicants as $applicant) {
        if($applicant['exam_status'] === 'Scheduled') {
            $scheduled_count++;
        }
    }
    
} catch (PDOException $e) {
    $applicants = [];
    $scheduled_count = 0;
    $total_count = 0;
    $error_message = "Failed to fetch applicants: " . $e->getMessage();
}

// Get applicants eligible for interview (completed exam)
// Filter by program/campus if chairperson
// Fetch from exam_answers table only - if record exists, exam was completed
try {
    // Get the latest exam attempt per applicant
    $sql = "SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                         ea.points_earned,
                         ea.points_possible,
                         CASE WHEN isa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as interview_status,
                         isch.event_date as scheduled_date
                         FROM exam_answers ea
                         INNER JOIN registration r ON ea.applicant_id = r.id
                         INNER JOIN personal_info pi ON r.personal_info_id = pi.id
                         LEFT JOIN program_application pa ON (pa.personal_info_id = pi.id OR pa.registration_id = r.id)
                         LEFT JOIN interview_schedule_applicants isa ON r.id = isa.applicant_id
                         LEFT JOIN interview_schedules isch ON isa.schedule_id = isch.id
                         INNER JOIN (
                             SELECT applicant_id, MAX(submitted_at) as latest_submitted
                             FROM exam_answers
                             GROUP BY applicant_id
                         ) latest_ea ON ea.applicant_id = latest_ea.applicant_id AND ea.submitted_at = latest_ea.latest_submitted";
    if ($isChairperson && $chairProgram && $chairCampus) {
        $sql .= " WHERE (pa.id IS NULL OR (LOWER(TRIM(pa.program)) = LOWER(TRIM(?)) AND LOWER(TRIM(pa.campus)) = LOWER(TRIM(?))))";
    }
    $sql .= " ORDER BY pi.last_name, pi.first_name";
    
    if ($isChairperson && $chairProgram && $chairCampus) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$chairProgram, $chairCampus]);
    } else {
        $stmt = $pdo->query($sql);
    }
    $interview_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interview_applicants = [];
}

// Fetch interview schedules and counts from dedicated tables if present
// Filtered by chair_id if chairperson
try {
    // Make sure tables exist before selecting (no-op if they don't)
    $pdo->query("SELECT 1 FROM interview_schedules LIMIT 1");
    if ($isChairperson && $chairId) {
        // Filter interview schedules by chair_id to ensure chairpersons only see their own schedules
        $stmt = $pdo->prepare("SELECT isch.*, 
                             COALESCE(COUNT(isa.applicant_id), 0) AS applicant_count
                             FROM interview_schedules isch
                             LEFT JOIN interview_schedule_applicants isa ON isch.id = isa.schedule_id
                             WHERE isch.chair_id = ?
                             GROUP BY isch.id
                             ORDER BY isch.event_date, isch.event_time");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT isch.*, 
                             COALESCE(COUNT(isa.applicant_id), 0) AS applicant_count
                             FROM interview_schedules isch
                             LEFT JOIN interview_schedule_applicants isa ON isch.id = isa.schedule_id
                             GROUP BY isch.id
                             ORDER BY isch.event_date, isch.event_time");
    }
    $interview_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interview_schedules = [];
}

// Get rooms for venue dropdown (filtered by chair_id if chairperson, and only active buildings)
try {
    if ($isChairperson && $chairId) {
        $stmt = $pdo->prepare("SELECT r.*, b.name as building_name 
                        FROM rooms r 
                        INNER JOIN buildings b ON r.building_id = b.id 
                        WHERE r.status = 'active' AND r.chair_id = ? AND b.status = 'active'
                        ORDER BY b.name, r.room_number");
        $stmt->execute([$chairId]);
    } else {
        $stmt = $pdo->query("SELECT r.*, b.name as building_name 
                        FROM rooms r 
                        INNER JOIN buildings b ON r.building_id = b.id 
                        WHERE r.status = 'active' AND b.status = 'active'
                        ORDER BY b.name, r.room_number");
    }
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rooms = [];
    $error_message = "Failed to fetch rooms: " . $e->getMessage();
}
?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- jsPDF for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- html2canvas for converting HTML to canvas for PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* Flatpickr Custom Styling - Green Theme */
    .flatpickr-calendar {
        font-family: inherit;
        border-radius: 8px;
        box-shadow: 0 3px 13px rgba(0, 0, 0, 0.2);
    }
    
    .flatpickr-months {
        background-color: rgb(0, 105, 42);
        border-radius: 8px 8px 0 0;
    }
    
    .flatpickr-current-month {
        color: white;
    }
    
    .flatpickr-month {
        color: white;
    }
    
    .flatpickr-prev-month,
    .flatpickr-next-month {
        color: white;
        fill: white;
    }
    
    .flatpickr-prev-month:hover,
    .flatpickr-next-month:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    .flatpickr-day.selected:hover,
    .flatpickr-day.startRange:hover,
    .flatpickr-day.endRange:hover {
        background: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
    
    .flatpickr-day.today {
        border-color: rgb(0, 105, 42);
        color: rgb(0, 105, 42);
    }
    
    .flatpickr-day.today:hover {
        background: rgba(0, 105, 42, 0.1);
    }
    
    .flatpickr-day:hover {
        background: rgba(0, 105, 42, 0.1);
    }
    
    .flatpickr-time input:hover,
    .flatpickr-time .flatpickr-am-pm:hover,
    .flatpickr-time input:focus,
    .flatpickr-time .flatpickr-am-pm:focus {
        background: rgba(0, 105, 42, 0.1);
    }
    
    .flatpickr-time .flatpickr-time-separator {
        color: rgb(0, 105, 42);
    }
    
    /* Date/Time Input Styling */
    .flatpickr-input {
        background: #ffffff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        color: #495057;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        cursor: pointer;
    }
    
    .flatpickr-input:focus {
        border-color: rgb(0, 105, 42);
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }
    
    .flatpickr-input[readonly] {
        background-color: #ffffff;
        cursor: pointer;
    }
    
    /* Form Control Overrides */
    .form-control {
        border-radius: 4px;
        border: 1px solid #ced4da;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
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

    .nav-pills .nav-link {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .nav-pills .nav-link.active {
        background-color: rgb(0, 105, 42) !important;
        color: white !important;
    }

    .nav-pills .nav-link {
        color: rgb(0, 105, 42) !important;
    }

    .nav-pills .nav-link:hover:not(.active) {
        background-color: rgba(25, 135, 84, 0.1);
        color: rgb(0, 105, 42) !important;
    }
    
    /* Green checkbox styling - Override Bootstrap defaults */
    input[type="checkbox"].applicant-checkbox {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        border: 2px solid rgb(0, 105, 42) !important;
        border-radius: 0.25rem !important;
        background-color: transparent !important;
        cursor: pointer !important;
        position: relative !important;
    }
    
    input[type="checkbox"].applicant-checkbox:checked {
        background-color: rgb(0, 105, 42) !important;
        border-color: rgb(0, 105, 42) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    input[type="checkbox"].applicant-checkbox:focus {
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        border-color: rgb(0, 105, 42) !important;
        outline: none !important;
    }
    
    input[type="checkbox"].applicant-checkbox:hover {
        border-color: rgb(0, 105, 42) !important;
    }
    
    input[type="checkbox"].applicant-checkbox:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
    
    /* Interview checkbox styling - Same as applicant checkbox */
    input[type="checkbox"].interview-applicant-checkbox {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        border: 2px solid rgb(0, 105, 42) !important;
        border-radius: 0.25rem !important;
        background-color: transparent !important;
        cursor: pointer !important;
        position: relative !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:checked {
        background-color: rgb(0, 105, 42) !important;
        border-color: rgb(0, 105, 42) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:focus {
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        border-color: rgb(0, 105, 42) !important;
        outline: none !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:hover {
        border-color: rgb(0, 105, 42) !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
    }
    
    /* Remove spinner arrows from number inputs */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    input[type="number"] {
        -moz-appearance: textfield;
    }
    
    /* Center text in range input boxes */
    #rangeInput, #rangeInputInterview {
        text-align: center;
        font-size: 16px !important;
        font-weight: 600;
    }
    
    /* Green styling for venue checkboxes */
    input[name="schedule_venues[]"],
    input[name="interview_venues[]"] {
        accent-color: rgb(0, 105, 42);
    }
    
    input[name="schedule_venues[]"]:checked,
    input[name="interview_venues[]"]:checked {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    /* Disabled button styling */
    #reviewConfirmScheduleBtn:disabled,
    #reviewConfirmInterviewScheduleBtn:disabled {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: #fff !important;
        opacity: 0.65;
        cursor: not-allowed;
    }
</style>

<!-- Add meta tags to prevent caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <!-- Toasts will be dynamically added here -->
    </div>
    
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="?page=<?= isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'chairperson' ? 'chair_dashboard' : (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'interviewer' ? 'interviewer_dashboard' : 'dashboard') ?>" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>SCHEDULING MANAGEMENT</h4>
                </div>
            </div>
        </div>
            </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
            <?php if (isset($success_message) && !empty($success_message)): ?>
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

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills" id="schedulingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $sub_page === 'exam' ? 'active' : '' ?>" id="exam-tab" data-bs-toggle="pill" data-bs-target="#exam" type="button" role="tab" aria-controls="exam" aria-selected="<?= $sub_page === 'exam' ? 'true' : 'false' ?>">
                        <i class="fas fa-clipboard-check me-2"></i>Exam Scheduling
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $sub_page === 'interview' ? 'active' : '' ?>" id="interview-tab" data-bs-toggle="pill" data-bs-target="#interview" type="button" role="tab" aria-controls="interview" aria-selected="<?= $sub_page === 'interview' ? 'true' : 'false' ?>">
                        <i class="fas fa-microphone me-2"></i>Interview Scheduling
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="schedulingTabContent">
        <!-- Exam Scheduling Tab -->
        <div class="tab-pane fade <?= $sub_page === 'exam' ? 'show active' : '' ?>" id="exam" role="tabpanel" aria-labelledby="exam-tab">
            <!-- Applicants Selection Section -->
            <div class="row mb-4">
                <div class="col-12">
            <div class="card">
                        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users"></i> Select Applicants for Exam (Documents Accepted)</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="color: white; font-weight: 500;">Range:</span>
                                        <input type="number" id="rangeInput" class="form-control" min="0" step="1" value="0" oninput="updateRangeFromInput()" onfocus="handleRangeInputFocus()" onblur="handleRangeInputBlur()" style="width: 40px; height: 31px; border-color: rgb(0, 105, 42); font-size: 12px; padding: 4px 6px; line-height: 1;">
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm" onclick="selectAllApplicants()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                </div>
                            </div>
                </div>
                <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Number of applicants:</span>
                                        <span class="badge" style="background-color: rgb(0, 105, 42);" id="unscheduledCount"><?= $total_count - $scheduled_count ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-danger me-2" onclick="createNewSchedule()">
                                            <i class="fas fa-plus"></i> Create Schedule
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="setExamScheduleBtn" onclick="setExamSchedule()" disabled>
                                            <i class="fas fa-calendar-plus"></i> Assign to Schedule
                                        </button>
                                        <button type="button" class="btn" id="viewExamSchedulesBtn" onclick="viewExamSchedules()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                                            <i class="fas fa-calendar-alt"></i> View Schedules
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50"></th>
                                            <th>Name</th>
                                            <th class="text-center">Campus</th>
                                            <th class="text-center">Program</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php if (empty($applicants)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                                    No Applicants Found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($applicants as $applicant): ?>
                                                <?php if ($applicant['exam_status'] !== 'Scheduled'): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="applicant-checkbox" value="<?= $applicant['applicant_id'] ?>" onchange="updateSelectedCount()">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['campus'] ?? 'N/A') ?></td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['program'] ?? 'N/A') ?></td>
                                            </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

</div>

        <!-- Interview Scheduling Tab -->
        <div class="tab-pane fade <?= $sub_page === 'interview' ? 'show active' : '' ?>" id="interview" role="tabpanel" aria-labelledby="interview-tab">
            <!-- Applicants Selection Section (Interview Eligible: Completed Exam) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users"></i> Select Applicants for Interview (Completed Exam)</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="color: white; font-weight: 500;">Range:</span>
                                        <input type="number" id="rangeInputInterview" class="form-control" min="0" step="1" value="0" oninput="updateRangeFromInputInterview()" onfocus="handleRangeInputFocusInterview()" onblur="handleRangeInputBlurInterview()" style="width: 40px; height: 31px; border-color: rgb(0, 105, 42); font-size: 12px; padding: 4px 6px; line-height: 1;">
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm" onclick="selectAllApplicantsInterview()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Number of applicants:</span>
                                        <span class="badge" style="background-color: rgb(0, 105, 42);" id="unscheduledCountInterview"><?= count(array_filter($interview_applicants, function($applicant) { return $applicant['interview_status'] === 'Not Scheduled'; })) ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-danger me-2" onclick="createNewInterviewSchedule()">
                                            <i class="fas fa-plus"></i> Create Schedule
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="setInterviewScheduleBtn" onclick="setInterviewSchedule()" disabled>
                                            <i class="fas fa-calendar-plus"></i> Assign to Schedule
                                        </button>
                                        <button type="button" class="btn" id="viewInterviewStudentListBtn" onclick="viewInterviewSchedules()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                                            <i class="fas fa-calendar-alt"></i> View Schedules
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50"></th>
                                            <th>Name</th>
                                            <th class="text-center">Campus</th>
                                            <th class="text-center">Program</th>
                                            <th class="text-center">Exam Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($interview_applicants)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                                    No Eligible Applicants Found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($interview_applicants as $applicant): ?>
                                                <?php if ($applicant['interview_status'] !== 'Scheduled'): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="interview-applicant-checkbox" value="<?= $applicant['applicant_id'] ?>" onchange="updateSelectedCountInterview()">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['campus'] ?? 'N/A') ?></td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['program'] ?? 'N/A') ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">
                                                        <?= (int)($applicant['points_earned'] ?? 0) ?>/<?= (int)($applicant['points_possible'] ?? 0) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Schedule Modal -->
<div class="modal fade" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="createScheduleModalLabel">
                    <i class="fas fa-plus"></i> Create New Schedule
                </h5>
            </div>
            <form method="POST" id="createScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_schedule">
                    
                    <div class="mb-3">
                        <label for="create_schedule_date" class="form-label">Schedule Date *</label>
                        <input type="text" class="form-control flatpickr-input" id="create_schedule_date" name="schedule_date" required readonly placeholder="Select date" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label for="create_schedule_start_time" class="form-label">Start Time *</label>
                        <input type="text" class="form-control flatpickr-input" id="create_schedule_start_time" name="schedule_start_time" required readonly placeholder="Select time" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venues *</label>
                        <?php if (empty($rooms)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>No rooms available.</strong> Please add rooms and buildings in the Maintenance section first.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="schedule_venues[]" value="<?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>" id="venue_<?= $room['id'] ?>">
                                            <label class="form-check-label" for="venue_<?= $room['id'] ?>">
                                                <?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Select one or more venues for this schedule.</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="reviewConfirmScheduleBtn" onclick="showCreateScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($rooms) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Review & Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Schedule Confirmation Modal -->
<div class="modal fade" id="createScheduleConfirmModal" tabindex="-1" aria-labelledby="createScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="createScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Schedule Creation
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the schedule details:</strong>
                </div>
                <div id="createScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to create this schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithCreateSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Create Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Assignment Confirmation Modal -->
<div class="modal fade" id="examScheduleConfirmModal" tabindex="-1" aria-labelledby="examScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="examScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Assignment
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the assignment details:</strong>
                </div>
                <div id="examScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to assign these applicants to the selected schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithExamSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Assign to Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign to Schedule Modal -->
<div class="modal fade" id="setExamScheduleModal" tabindex="-1" aria-labelledby="setExamScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="setExamScheduleModalLabel">
                    <i class="fas fa-calendar-plus"></i> Assign to Schedule
                </h5>
            </div>
            <form method="POST" id="examScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_to_schedule">
                    <input type="hidden" name="applicant_ids" id="selectedApplicantIds">
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> <strong>Select a schedule to assign the selected applicants to:</strong>
                    </div>
                    
                            <div class="mb-3">
                        <label for="modal_schedule_select" class="form-label">Available Schedules *</label>
                        <select class="form-control" id="modal_schedule_select" name="schedule_id" required>
                            <option value="">Select a schedule</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?= $schedule['id'] ?>">
                                    <?= date('M d, Y', strtotime($schedule['event_date'])) ?> - 
                                    <?= date('g:i A', strtotime($schedule['event_time'])) ?> - 
                                    <?= htmlspecialchars($schedule['venue'] ?? 'No venue') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-exclamation-triangle"></i> <strong>No schedules available.</strong> Please create a schedule first.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showAssignScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($schedules) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Assign to Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Exam Schedules Modal -->
<div class="modal fade" id="examSchedulesModal" tabindex="-1" aria-labelledby="examSchedulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="examSchedulesModalLabel">
                    <i class="fas fa-calendar-alt"></i> Exam Schedules
                </h5>
            </div>
            <div class="modal-body">
                <div id="examSchedulesContent">
                    <!-- Exam schedules table will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Set Interview Schedule Modal -->
<div class="modal fade" id="setInterviewScheduleModal" tabindex="-1" aria-labelledby="setInterviewScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="setInterviewScheduleModalLabel">
                    <i class="fas fa-calendar-plus"></i> Assign to Schedule
                </h5>
            </div>
            <form method="POST" id="interviewScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_to_interview_schedule">
                    <input type="hidden" name="applicant_ids" id="selectedInterviewApplicantIds">
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> <strong>Select a schedule to assign the selected applicants to:</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_interview_schedule_select" class="form-label">Available Interview Schedules *</label>
                        <select class="form-control" id="modal_interview_schedule_select" name="schedule_id" required>
                            <option value="">Select a schedule</option>
                            <?php 
                            // Get interview schedules for dropdown
                            $dropdown_schedules = [];
                            try {
                                if ($isChairperson && $chairId) {
                                    // Filter interview schedules by chair_id to ensure chairpersons only see their own schedules
                                    $stmt = $pdo->prepare("SELECT * FROM interview_schedules WHERE chair_id = ? ORDER BY event_date, event_time");
                                    $stmt->execute([$chairId]);
                                } else {
                                    $stmt = $pdo->prepare("SELECT * FROM interview_schedules ORDER BY event_date, event_time");
                                    $stmt->execute();
                                }
                                $dropdown_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                // Handle error silently
                            }
                            foreach ($dropdown_schedules as $schedule): ?>
                                <option value="<?= $schedule['id'] ?>">
                                    <?= date('M d, Y', strtotime($schedule['event_date'])) ?> - 
                                    <?= date('g:i A', strtotime($schedule['event_time'])) ?> - 
                                    <?= htmlspecialchars($schedule['venue'] ?? 'No venue') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (empty($dropdown_schedules)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-exclamation-triangle"></i> <strong>No interview schedules available.</strong> Please create a schedule first.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showAssignInterviewScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($dropdown_schedules) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Assign to Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Interview Schedule Confirmation Modal -->
<div class="modal fade" id="assignInterviewScheduleConfirmModal" tabindex="-1" aria-labelledby="assignInterviewScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="assignInterviewScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Interview Schedule Assignment
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the assignment details:</strong>
                </div>
                <div id="assignInterviewScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to assign these applicants to the selected interview schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithAssignInterviewSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Assign to Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Interview Schedules Modal -->
<div class="modal fade" id="interviewSchedulesModal" tabindex="-1" aria-labelledby="interviewSchedulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="interviewSchedulesModalLabel">
                    <i class="fas fa-calendar-alt"></i> Interview Schedules
                </h5>
            </div>
            <div class="modal-body">
                <div id="interviewSchedulesContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Interview Schedule Modal -->
<div class="modal fade" id="createInterviewScheduleModal" tabindex="-1" aria-labelledby="createInterviewScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="createInterviewScheduleModalLabel">
                    <i class="fas fa-plus"></i> Create New Interview Schedule
                </h5>
            </div>
            <form method="POST" id="createInterviewScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_interview_schedule">
                    
                    <div class="mb-3">
                        <label for="create_interview_date" class="form-label">Schedule Date *</label>
                        <input type="text" class="form-control flatpickr-input" id="create_interview_date" name="interview_date" required readonly placeholder="Select date" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label for="create_interview_start_time" class="form-label">Start Time *</label>
                        <input type="text" class="form-control flatpickr-input" id="create_interview_start_time" name="interview_start_time" required readonly placeholder="Select time" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venues *</label>
                        <?php if (empty($rooms)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>No rooms available.</strong> Please add rooms and buildings in the Maintenance section first.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="interview_venues[]" value="<?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>" id="interview_venue_<?= $room['id'] ?>">
                                            <label class="form-check-label" for="interview_venue_<?= $room['id'] ?>">
                                                <?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Select one or more venues for this interview schedule.</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="reviewConfirmInterviewScheduleBtn" onclick="showCreateInterviewScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($rooms) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Review & Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Interview Schedule Confirmation Modal -->
<div class="modal fade" id="createInterviewScheduleConfirmModal" tabindex="-1" aria-labelledby="createInterviewScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="createInterviewScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Interview Schedule Creation
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the interview schedule details:</strong>
                </div>
                <div id="createInterviewScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to create this interview schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithCreateInterviewSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Create Schedule
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Delete Schedule Confirmation Modal -->
<div class="modal fade" id="deleteScheduleConfirmModal" tabindex="-1" aria-labelledby="deleteScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #dc3545;">
                <h5 class="modal-title" id="deleteScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Schedule Deletion
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning: This action cannot be undone!</strong>
                </div>
                <div id="deleteScheduleDetails">
                    <!-- Schedule details will be populated by JavaScript -->
                </div>
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to delete this schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="proceedWithDeleteSchedule()">
                    <i class="fas fa-trash"></i> Yes, Delete Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Student List Modal -->
<div class="modal fade" id="viewStudentListModal" tabindex="-1" aria-labelledby="viewStudentListModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 900px; width: 900px;">
        <div class="modal-content" style="height: 600px; display: flex; flex-direction: column;">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white; flex-shrink: 0;">
                <h5 class="modal-title" id="viewStudentListModalLabel">
                    <i class="fas fa-users"></i> Student List
                </h5>
            </div>
            <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 15px;">
                <div class="mb-3" id="scheduleInfoContainer" style="display: none;">
                    <div id="scheduleInfo" style="background-color: rgb(0, 105, 42); color: white; padding: 15px; border-radius: 8px; min-height: 40px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        <!-- Schedule information will be populated here -->
                    </div>
                </div>
                <div id="studentListContent">
                    <!-- Student list will be populated here -->
                </div>
            </div>
            <div class="modal-footer" style="flex-shrink: 0;">
                <button type="button" class="btn" id="exportStudentListBtn" onclick="exportStudentList()" style="display: none; background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-download"></i> Export to CSV
                </button>
                <button type="button" class="btn" id="exportStudentListPdfBtn" onclick="exportStudentListToPDF()" style="display: none; background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-file-pdf"></i> Save as PDF
                </button>
                <button type="button" class="btn btn-secondary" id="backToSchedulesBtn" onclick="closeStudentListModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toast notification functions
function showToast(message, type = 'success', duration = 2000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    const headerBg = type === 'success' ? '#d4edda' : '#f8d7da';
    const headerBorder = type === 'success' ? '#c3e6cb' : '#f5c6cb';
    const bodyBg = type === 'success' ? '#d4edda' : '#f8d7da';
    const textColor = type === 'success' ? 'text-success' : 'text-danger';
    const typeLabel = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: ${headerBg}; border-color: ${headerBorder};">
                <i class="${iconClass} ${textColor} me-2"></i>
                <strong class="me-auto ${textColor}">${typeLabel}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: ${bodyBg};">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    
    // Auto-hide after specified duration
    setTimeout(() => {
        const toast = new bootstrap.Toast(toastElement);
        toast.hide();
    }, duration);
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Function to convert 12-hour format time to 24-hour format
function convertTo24Hour(time12h) {
    if (!time12h) return time12h;
    
    // Check if already in 24-hour format (no AM/PM)
    if (!time12h.match(/AM|PM/i)) {
        return time12h;
    }
    
    // Parse 12-hour format (e.g., "02:30 PM" or "2:30 PM")
    const time = time12h.trim();
    const [timePart, period] = time.split(/\s*(AM|PM)/i);
    const [hours, minutes] = timePart.split(':');
    
    let hour24 = parseInt(hours, 10);
    const min = minutes || '00';
    
    if (period && period.toUpperCase() === 'PM' && hour24 !== 12) {
        hour24 += 12;
    } else if (period && period.toUpperCase() === 'AM' && hour24 === 12) {
        hour24 = 0;
    }
    
    // Format as HH:mm
    return String(hour24).padStart(2, '0') + ':' + min.padStart(2, '0');
}

// Function to format time for display (handles both 12-hour and 24-hour formats)
function formatTimeForDisplay(timeValue) {
    if (!timeValue) return timeValue;
    
    // If already in 12-hour format with AM/PM, return as is
    if (timeValue.match(/AM|PM/i)) {
        return timeValue;
    }
    
    // If in 24-hour format, convert to 12-hour format for display
    try {
        const time24h = timeValue.includes(':') ? timeValue : timeValue + ':00';
        const [hours, minutes] = time24h.split(':');
        const hour24 = parseInt(hours, 10);
        const min = minutes || '00';
        
        let hour12 = hour24;
        let period = 'AM';
        
        if (hour24 === 0) {
            hour12 = 12;
        } else if (hour24 === 12) {
            hour12 = 12;
            period = 'PM';
        } else if (hour24 > 12) {
            hour12 = hour24 - 12;
            period = 'PM';
        }
        
        return String(hour12).padStart(2, '0') + ':' + min.padStart(2, '0') + ' ' + period;
    } catch (e) {
        return timeValue;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Clear URL parameters immediately to prevent success message from persisting
    if (window.location.search.includes('success=')) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
        
        // Also hide any empty success toasts
        const successToasts = document.querySelectorAll('.toast');
        successToasts.forEach(toast => {
            const toastBody = toast.querySelector('.toast-body');
            if (toastBody && (!toastBody.textContent.trim() || toastBody.textContent.trim() === '')) {
                toast.style.display = 'none';
            }
        });
    }
    
    // Auto-hide toasts after 3 seconds (exclude modal content)
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        // Skip toasts that are inside modals
        if (!toast.closest('.modal')) {
            setTimeout(() => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            }, 3000);
        }
    });
    
    // Hide success messages after 5 seconds (exclude modal content)
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        // Skip alerts that are inside modals
        if (!alert.closest('.modal')) {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    });
    
    // Global modal cleanup function
    function cleanupModalBackdrop() {
        // Remove any remaining backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remove modal-open class from body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Add cleanup to all modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', cleanupModalBackdrop);
    });
    
    // Prevent form resubmission and convert time formats
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Convert all time inputs from 12-hour to 24-hour format before submission
            const timeInputIds = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
            timeInputIds.forEach(function(id) {
                const timeInput = document.getElementById(id);
                if (timeInput && timeInput.value) {
                    const time24h = convertTo24Hour(timeInput.value);
                    timeInput.value = time24h;
                }
            });
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
    

    // Handle tab switching via URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const subPage = urlParams.get('sub');
    
    if (subPage === 'interview') {
        const interviewTab = new bootstrap.Tab(document.getElementById('interview-tab'));
        interviewTab.show();
    } else {
        const examTab = new bootstrap.Tab(document.getElementById('exam-tab'));
        examTab.show();
    }

    // Update URL when tabs are clicked
    document.getElementById('exam-tab').addEventListener('click', function() {
        const url = new URL(window.location);
        url.searchParams.set('sub', 'exam');
        window.history.pushState({}, '', url);
    });

    document.getElementById('interview-tab').addEventListener('click', function() {
        const url = new URL(window.location);
        url.searchParams.set('sub', 'interview');
        window.history.pushState({}, '', url);
    });
    
    // Show PHP messages as toasts
    <?php if (isset($success_message) && !empty($success_message)): ?>
        showToast('<?= addslashes($success_message) ?>', 'success', 2000);
    <?php endif; ?>
    
    <?php if (isset($error_message) && !empty($error_message)): ?>
        showToast('<?= addslashes($error_message) ?>', 'error', 2000);
    <?php endif; ?>
    
    // Add event listener to clear data when student list modal is hidden
    const studentListModal = document.getElementById('viewStudentListModal');
    if (studentListModal) {
        studentListModal.addEventListener('hidden.bs.modal', function() {
            // Clear global data when modal is hidden
            window.currentStudentData = null;
            window.currentScheduleInfo = null;
            
            // Clear the student list content
            document.getElementById('studentListContent').innerHTML = '';
            
            // Hide export buttons
            document.getElementById('exportStudentListBtn').style.display = 'none';
            document.getElementById('exportStudentListPdfBtn').style.display = 'none';
        });
    }
});

// Function to update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const count = checkboxes.length;
    const total = totalCheckboxes.length;
    
    console.log('=== updateSelectedCount DEBUG ===');
    console.log('Checked checkboxes:', count);
    console.log('Total available checkboxes:', total);
    console.log('Checkbox elements found:', checkboxes);
    
    // Update count displays with null checks
    const selectedCountEl = document.getElementById('selectedCount');
    const totalCountEl = document.getElementById('totalCount');
    
    console.log('selectedCount element found:', selectedCountEl);
    console.log('totalCount element found:', totalCountEl);
    
    if (selectedCountEl) {
        selectedCountEl.textContent = count;
        console.log('Updated selectedCount to:', count);
    } else {
        console.error('selectedCount element NOT FOUND!');
    }
    if (totalCountEl) {
        totalCountEl.textContent = total;
        console.log('Updated totalCount to:', total);
    } else {
        console.error('totalCount element NOT FOUND!');
    }
    
    // Enable/disable the Set Exam Schedule button
    const setExamBtn = document.getElementById('setExamScheduleBtn');
    const viewStudentBtn = document.getElementById('viewStudentListBtn');
    
    console.log('setExamBtn found:', setExamBtn);
    console.log('viewStudentBtn found:', viewStudentBtn);
    
    if (setExamBtn) {
        console.log('Before update - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        
        if (count > 0) {
            setExamBtn.disabled = false;
            setExamBtn.classList.remove('btn-secondary');
            setExamBtn.classList.add('btn-warning');
            setExamBtn.style.backgroundColor = '#ffc107';
            setExamBtn.style.color = '#000';
            setExamBtn.style.borderColor = '#ffc107';
            console.log('✅ Button ENABLED - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        } else {
            setExamBtn.disabled = true;
            setExamBtn.classList.remove('btn-warning');
            setExamBtn.classList.add('btn-secondary');
            setExamBtn.style.backgroundColor = '';
            setExamBtn.style.color = '';
            setExamBtn.style.borderColor = '';
            console.log('❌ Button DISABLED - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        }
    } else {
        console.error('❌ setExamBtn element NOT FOUND!');
    }
    
    // Exam Schedule button is always enabled
    if (viewStudentBtn) {
        viewStudentBtn.disabled = false;
        viewStudentBtn.classList.remove('btn-secondary');
        viewStudentBtn.classList.add('btn-success');
        viewStudentBtn.style.backgroundColor = 'rgb(0, 105, 42)';
        viewStudentBtn.style.color = 'white';
        viewStudentBtn.style.borderColor = 'rgb(0, 105, 42)';
        console.log('viewStudentBtn enabled');
    } else {
        console.error('viewStudentBtn element NOT FOUND!');
    }
    
    // Update select all button text based on current state
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicants()"]');
    if (selectAllBtn) {
        const totalCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
        if (count === totalCheckboxes && totalCheckboxes > 0) {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        }
    }
    
    console.log('=== END updateSelectedCount DEBUG ===');
}

// ================= Interview Tab JS (mirrors exam scheduling) =================
// Counters and range for interview selection
let currentRangeInterview = 0;

document.addEventListener('DOMContentLoaded', function() {
    const availableCount = document.querySelectorAll('.interview-applicant-checkbox').length;
    const totalCountEl = document.getElementById('totalCountInterview');
    if (totalCountEl) totalCountEl.textContent = availableCount;

    currentRangeInterview = 0;
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        rangeInput.value = currentRangeInterview;
    }
    selectCurrentRangeInterview();
});

function updateSelectedCountInterview() {
    const checkboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.interview-applicant-checkbox');
    const count = checkboxes.length;
    const total = totalCheckboxes.length;

    const selectedCountEl = document.getElementById('selectedCountInterview');
    const totalCountEl = document.getElementById('totalCountInterview');
    if (selectedCountEl) selectedCountEl.textContent = count;
    if (totalCountEl) totalCountEl.textContent = total;

    // Enable/disable the Set Interview Schedule button
    const setInterviewBtn = document.getElementById('setInterviewScheduleBtn');
    
    if (setInterviewBtn) {
        if (count > 0) {
            setInterviewBtn.disabled = false;
            setInterviewBtn.classList.remove('btn-secondary');
            setInterviewBtn.classList.add('btn-warning');
            setInterviewBtn.style.backgroundColor = '#ffc107';
            setInterviewBtn.style.color = '#000';
            setInterviewBtn.style.borderColor = '#ffc107';
        } else {
            setInterviewBtn.disabled = true;
            setInterviewBtn.classList.remove('btn-warning');
            setInterviewBtn.classList.add('btn-secondary');
            setInterviewBtn.style.backgroundColor = '';
            setInterviewBtn.style.color = '';
            setInterviewBtn.style.borderColor = '';
        }
    }
    
    // Update select all button text based on current state
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicantsInterview()"]');
    if (selectAllBtn) {
        if (count === total && total > 0) {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        }
    }
}

function selectAllApplicantsInterview() {
    const checkboxes = document.querySelectorAll('.interview-applicant-checkbox');
    const checkedCount = document.querySelectorAll('.interview-applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    checkboxes.forEach(cb => { 
        cb.checked = shouldSelectAll; 
    });
    
    // Update range input and currentRangeInterview
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        if (shouldSelectAll) {
            // Selecting all - set range to total count
            currentRangeInterview = checkboxes.length;
            rangeInput.value = checkboxes.length;
        } else {
            // Unselecting all - reset to 0
            currentRangeInterview = 0;
            rangeInput.value = 0;
        }
    }
    
    // Update button text
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicantsInterview()"]');
    if (selectAllBtn) {
        if (shouldSelectAll) {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        }
    }
    
    updateSelectedCountInterview();
}

// Function to update range from number input for interview
function updateRangeFromInputInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // Get the raw input value and clean it
        let inputValue = rangeInput.value.trim();
        
        // If input is empty or just whitespace, treat as 0
        if (inputValue === '' || inputValue === '0') {
            inputValue = 0;
        } else {
            inputValue = parseInt(inputValue) || 0;
        }
        
        const unscheduledCount = document.querySelectorAll('.interview-applicant-checkbox').length;
        
        console.log('Interview raw input value:', rangeInput.value);
        console.log('Interview processed input value:', inputValue);
        console.log('Interview unscheduled applicants count:', unscheduledCount);
        
        // Validate input against unscheduled applicants
        if (inputValue > unscheduledCount && unscheduledCount > 0) {
            showToast(`Input exceeds unscheduled applicants. Available: ${unscheduledCount}`, 'error', 3000);
            // Keep the input value but don't select any checkboxes
            currentRangeInterview = 0;
            // Uncheck all checkboxes
            const allCheckboxes = document.querySelectorAll('.interview-applicant-checkbox');
            allCheckboxes.forEach(cb => { cb.checked = false; });
            updateSelectedCountInterview();
            return; // Don't proceed to selectCurrentRangeInterview()
        } else if (inputValue < 0) {
            showToast('Input cannot be negative', 'error', 3000);
            rangeInput.value = 0;
            currentRangeInterview = 0;
        } else {
            currentRangeInterview = inputValue;
        }
        
        console.log('Interview range updated from input:', currentRangeInterview);
        selectCurrentRangeInterview();
    }
}

function selectCurrentRangeInterview() {
    const allCheckboxes = document.querySelectorAll('.interview-applicant-checkbox');
    allCheckboxes.forEach(cb => { cb.checked = false; });
    const availableCheckboxes = Array.from(allCheckboxes);
    const toSelect = Math.min(currentRangeInterview, availableCheckboxes.length);
    for (let i = 0; i < toSelect; i++) {
        if (availableCheckboxes[i]) availableCheckboxes[i].checked = true;
    }
    
    updateSelectedCountInterview();
}

// Function to handle interview range input focus and typing
function handleRangeInputFocusInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // Clear the input when user focuses and it contains only "0"
        if (rangeInput.value === '0') {
            rangeInput.value = '';
        }
    }
}

// Function to handle interview range input blur
function handleRangeInputBlurInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // If input is empty on blur, set it to 0
        if (rangeInput.value.trim() === '') {
            rangeInput.value = '0';
            currentRangeInterview = 0;
            selectCurrentRangeInterview();
        }
    }
}

function setInterviewSchedule() {
    console.log('setInterviewSchedule called');
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    console.log('Selected checkboxes:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    console.log('Selected IDs:', selectedIds);
    
    // Check if modal element exists
    const modalElement = document.getElementById('setInterviewScheduleModal');
    console.log('Modal element:', modalElement);
    
    if (!modalElement) {
        console.error('Modal element not found!');
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    // Show assign to schedule modal
    const interviewModal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedInterviewApplicantIds').value = selectedIds.join(',');
    console.log('Showing modal...');
    interviewModal.show();
}

function proceedWithInterviewSchedule() {
    // Submit the form
    document.getElementById('interviewScheduleForm').submit();
}

// Function to show interview schedule confirmation
function showInterviewScheduleConfirmation() {
    const date = document.getElementById('modal_interview_date').value;
    const start = document.getElementById('modal_interview_start_time').value;
    const end = document.getElementById('modal_interview_end_time').value;
    const venue = document.getElementById('modal_interview_venue').value;
    const selectedIds = document.getElementById('selectedInterviewApplicantIds').value;
    
    // Validate form
    if (!date || !start || !end || !venue) {
        showToast('Please fill in all required fields.', 'error', 2000);
        return;
    }
    
    // Validate that date is not in the past
    if (date) {
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        selectedDate.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showToast('Cannot select a past date. Please choose today or a future date.', 'error', 3000);
            const dateInput = document.getElementById('modal_interview_date');
            if (dateInput) {
                dateInput.value = '';
                dateInput.focus();
            }
            return;
        }
    }
    
    if (!selectedIds) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Format date and time for display
    const eventDate = new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
    
    // Format times (handle 12-hour format with AM/PM)
    const startTimeStr = formatTimeForDisplay(start);
    const endTimeStr = formatTimeForDisplay(end);
    
    // Populate confirmation modal
    document.getElementById('interviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Interview Date:</strong> ${eventDate}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Time:</strong> ${startTimeStr} - ${endTimeStr}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Venue:</strong> ${venue}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide interview schedule modal and show confirmation
    const interviewModal = bootstrap.Modal.getInstance(document.getElementById('setInterviewScheduleModal'));
    interviewModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('interviewScheduleConfirmModal'));
    confirmModal.show();
}

function viewInterviewSchedules() {
    const modal = new bootstrap.Modal(document.getElementById('interviewSchedulesModal'));
    
    // Get all schedules from PHP data (including assigned ones for viewing)
    const allSchedules = <?= json_encode($interview_schedules ?? []) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        // Since schedules are now already grouped (one schedule per group), we can use them directly
        const groupedSchedules = allSchedules.map(schedule => ({
            id: schedule.id,
            event_date: schedule.event_date,
            event_time: schedule.event_time,
            end_time: schedule.end_time,
            venue: schedule.venue,
            applicant_count: schedule.applicant_count !== undefined ? schedule.applicant_count : 0
        }));
        
        console.log('Grouped interview schedules:', groupedSchedules);
        console.log('First interview schedule applicant_count:', groupedSchedules[0]?.applicant_count, 'Type:', typeof groupedSchedules[0]?.applicant_count);
        
        // Display schedules directly since they're already grouped
        groupedSchedules.forEach((schedule, index) => {
            console.log(`Interview Schedule ${index}:`, schedule);
            console.log(`Interview Schedule ${index} applicant_count:`, schedule.applicant_count, 'Type:', typeof schedule.applicant_count);
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'short', day: 'numeric' 
            });
            
            const startTimeStr = formatTimeForDisplay(schedule.event_time);
            
            let actionButtons = '';
            if (schedule.applicant_count === 0 || schedule.applicant_count === '0') {
                // Show delete button for schedules without applicants
                actionButtons = `
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id}, 'interview')" style="border-radius: 4px; padding: 6px 12px; font-size: 12px; margin-right: 5px;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            } else {
                // Show view button for schedules with applicants
                const buttonText = 'View Student List (' + schedule.applicant_count + ')';
                actionButtons = `
                    <button type="button" class="btn btn-sm" onclick="viewStudentsForInterviewSchedule(${schedule.id}, 'Interview Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-users"></i> ${buttonText}
                    </button>
                `;
            }
            
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        ${actionButtons}
                    </td>
                </tr>
            `;
        });
    } else {
        // Update schedule info to show no schedules message
        document.getElementById('scheduleInfo').innerHTML = '<strong style="color: white; font-size: 16px;">No Interview Schedules Found</strong>';
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No interview schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('interviewSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    modal.show();
}

// Function to view students for a specific interview schedule
function viewStudentsForInterviewSchedule(scheduleId, eventName, eventDate, eventTime, venue) {
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data and content immediately to prevent showing cached data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content immediately
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading students...</p>
        </div>
    `;
    
    // Hide export buttons initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    document.getElementById('exportStudentListPdfBtn').style.display = 'none';
    
    // Reset the back button
    document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-times"></i> Close';
    document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'closeStudentListModal()');
    
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
        <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
        <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
            <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
            <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    
    console.log('Interview Schedule ID:', scheduleId);
    
    modal.show();
    
    // Fetch students for this specific interview schedule via AJAX with cache-busting
    fetch(`scheduling.php?action=get_interview_schedule_students&schedule_id=${scheduleId}&t=${Date.now()}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5 class="text-muted">Error</h5>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            const scheduledApplicants = data;
            
            console.log('Scheduled interview applicants found:', scheduledApplicants);
            
            if (scheduledApplicants.length > 0) {
                let studentRows = '';
                scheduledApplicants.forEach((applicant, index) => {
                    // Format name properly
                    const fullName = `${applicant.last_name}, ${applicant.first_name} ${applicant.middle_name || ''}`.trim();
                    const formattedName = fullName.split(' ').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                    
                    studentRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${formattedName}</strong></td>
                            <td class="text-center">${applicant.program || 'N/A'}</td>
                            <td class="text-center"><span class="badge" style="background-color: rgb(0, 105, 42);">Scheduled</span></td>
                        </tr>
                    `;
                });
                
                document.getElementById('studentListContent').innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentListTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${studentRows}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Show export buttons and store data for export
                document.getElementById('exportStudentListBtn').style.display = 'inline-block';
                document.getElementById('exportStudentListPdfBtn').style.display = 'inline-block';
                window.currentStudentData = scheduledApplicants;
                window.currentScheduleInfo = {
                    eventName: eventName,
                    eventDate: eventDate,
                    eventTime: eventTime,
                    venue: venue,
                    scheduleType: 'interview'
                };
                
                // Update back button to return to interview schedules
                document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-arrow-left"></i> Back to Interview Schedules';
                document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'backToInterviewSchedules()');
                
            } else {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="text-muted">No Students Found</h5>
                        <p>No students are scheduled for this interview.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching interview schedule students:', error);
            document.getElementById('studentListContent').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5 class="text-muted">Error</h5>
                    <p>Failed to load student data. Please try again.</p>
                </div>
            `;
        });
}

// Function to go back to interview schedules table view
function backToInterviewSchedules() {
    // Close the student list modal
    const studentListModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (studentListModal) {
        studentListModal.hide();
    }
    
    // Open the interview schedules modal
    const interviewSchedulesModal = new bootstrap.Modal(document.getElementById('interviewSchedulesModal'));
    
    // Get all schedules from PHP data and display the table (including assigned ones)
    const allSchedules = <?= json_encode($interview_schedules ?? []) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        allSchedules.forEach(schedule => {
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
            
            const startTime = new Date(`2000-01-01T${schedule.event_time}`);
            const startTimeStr = startTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const buttonText = 'View Student List (' + schedule.applicant_count + ')';
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForInterviewSchedule(${schedule.id}, 'Interview Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> ${buttonText}
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No interview schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('interviewSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    interviewSchedulesModal.show();
}

// Function to create new interview schedule
function createNewInterviewSchedule() {
    const modal = new bootstrap.Modal(document.getElementById('createInterviewScheduleModal'));
    modal.show();
}

// Function to show create interview schedule confirmation
function showCreateInterviewScheduleConfirmation() {
    const date = document.getElementById('create_interview_date').value;
    const start = document.getElementById('create_interview_start_time').value;
    const venueCheckboxes = document.querySelectorAll('input[name="interview_venues[]"]:checked');
    
    // Validate form
    if (!date || !start) {
        showToast('Please fill in all required fields.', 'error', 2000);
        return;
    }
    
    // Validate that date is not in the past
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showToast('Cannot select a past date. Please choose today or a future date.', 'error', 3000);
        document.getElementById('create_interview_date').value = '';
        document.getElementById('create_interview_date').focus();
        return;
    }
    
    if (venueCheckboxes.length === 0) {
        showToast('Please select at least one venue.', 'error', 2000);
        return;
    }
    
    // Get selected venues
    const selectedVenues = Array.from(venueCheckboxes).map(cb => cb.value);
    
    // Format date and time for display
    const eventDate = new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
    
    // Format time (handle 12-hour format with AM/PM)
    const startTimeStr = formatTimeForDisplay(start);
    
    // Populate confirmation modal
    document.getElementById('createInterviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Interview Date:</strong> ${eventDate}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Start Time:</strong> ${startTimeStr}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Venues:</strong> ${selectedVenues.length} venue(s)
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <small class="text-muted">${selectedVenues.join(', ')}</small>
            </div>
        </div>
    `;
    
    // Hide create schedule modal and show confirmation
    const createModal = bootstrap.Modal.getInstance(document.getElementById('createInterviewScheduleModal'));
    createModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('createInterviewScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with create interview schedule
function proceedWithCreateInterviewSchedule() {
    // Convert time to 24-hour format before submission
    const timeInput = document.getElementById('create_interview_start_time');
    if (timeInput && timeInput.value) {
        const time24h = convertTo24Hour(timeInput.value);
        timeInput.value = time24h;
    }
    // Submit the form
    document.getElementById('createInterviewScheduleForm').submit();
}

// Function to assign applicants to interview schedule
function assignToInterviewSchedule() {
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    const modalElement = document.getElementById('assignToInterviewScheduleModal');
    
    if (!modalElement) {
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedInterviewApplicantIds').value = selectedIds.join(',');
    modal.show();
}

// Function to show assign interview schedule confirmation
function showAssignInterviewScheduleConfirmation() {
    const scheduleSelect = document.getElementById('modal_interview_schedule_select');
    const selectedIds = document.getElementById('selectedInterviewApplicantIds').value;
    
    // Validate form
    if (!scheduleSelect.value) {
        showToast('Please select a schedule.', 'error', 2000);
        return;
    }
    
    // Get selected schedule details
    const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
    const scheduleText = selectedOption.text;
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Populate confirmation modal
    document.getElementById('assignInterviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Selected Schedule:</strong> ${scheduleText}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide assign schedule modal and show confirmation
    const assignModal = bootstrap.Modal.getInstance(document.getElementById('setInterviewScheduleModal'));
    assignModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('assignInterviewScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with assign interview schedule
function proceedWithAssignInterviewSchedule() {
    // Submit the form
    document.getElementById('interviewScheduleForm').submit();
}

// Function to view student list from selected interview applicants
function viewStudentListFromInterviewSelection() {
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading selected applicants...</p>
        </div>
    `;
    
    // Hide export buttons initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    document.getElementById('exportStudentListPdfBtn').style.display = 'none';
    
    // Reset the back button
    setBackButtonForCurrentSection();
    
    // Show the schedule info container
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong style="color: white; font-size: 16px;">Selected Interview Applicants</strong><br>
        <small style="color: rgba(255, 255, 255, 0.9);">${selectedNames.length} applicants selected</small>
    `;
    
    modal.show();
    
    // Display selected applicants
    if (selectedNames.length > 0) {
        let studentRows = '';
        selectedNames.forEach((name, index) => {
            studentRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${name}</strong></td>
                    <td class="text-center">N/A</td>
                    <td class="text-center"><span class="badge bg-warning">Selected</span></td>
                </tr>
            `;
        });
        
        document.getElementById('studentListContent').innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover" id="studentListTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Program</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${studentRows}
                    </tbody>
                </table>
            </div>
        `;
        
        // Store data for potential export
        window.currentStudentData = selectedNames.map((name, index) => ({
            last_name: name.split(',')[0] || '',
            first_name: name.split(',')[1]?.trim().split(' ')[0] || '',
            middle_name: name.split(',')[1]?.trim().split(' ').slice(1).join(' ') || '',
            program: 'N/A'
        }));
        
        window.currentScheduleInfo = {
            eventName: 'Selected Interview Applicants',
            eventDate: 'N/A',
            eventTime: 'N/A',
            venue: 'N/A',
            scheduleType: 'interview'
        };
        
        // Show export buttons
        document.getElementById('exportStudentListBtn').style.display = 'inline-block';
        document.getElementById('exportStudentListPdfBtn').style.display = 'inline-block';
        
    } else {
        document.getElementById('studentListContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5 class="text-muted">No Applicants Selected</h5>
                <p>Please select applicants to view their details.</p>
            </div>
        `;
    }
}

// Function to update unscheduled count display for interview
function updateUnscheduledCountInterview() {
    const unscheduledCount = document.querySelectorAll('.interview-applicant-checkbox:not([disabled])').length;
    
    const unscheduledCountEl = document.getElementById('unscheduledCountInterview');
    
    if (unscheduledCountEl) {
        unscheduledCountEl.textContent = unscheduledCount;
    }
}

// Function to show success toast for interview
function showSuccessToastInterview(message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1065';
        document.body.appendChild(toastContainer);
    } else {
        // Ensure z-index is high enough to appear above modals
        toastContainer.style.zIndex = '1065';
    }
    
    const toastId = 'exportToastInterview_' + Date.now();
    const iconClass = 'fas fa-check-circle';
    const headerBg = '#d4edda';
    const headerBorder = '#c3e6cb';
    const bodyBg = '#d4edda';
    const textColor = 'text-success';
    const typeLabel = 'Success';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 1065;">
            <div class="toast-header" style="background-color: ${headerBg}; border-color: ${headerBorder};">
                <i class="${iconClass} ${textColor} me-2"></i>
                <strong class="me-auto ${textColor}">${typeLabel}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: ${bodyBg};">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        const toast = new bootstrap.Toast(toastElement);
        toast.hide();
    }, 3000);
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
        }
    });
}

// Function to close student list modal
function closeStudentListModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (modal) {
        modal.hide();
    }
}

// Function to toggle select all
function toggleSelectAll() {
    console.log('toggleSelectAll called');
    const checkboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const checkedCount = document.querySelectorAll('.applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    console.log('Current checked count:', checkedCount, 'Total checkboxes:', checkboxes.length, 'Should select all:', shouldSelectAll);
    
    checkboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = shouldSelectAll;
        }
    });
    
    updateSelectedCount();
    console.log('Toggle complete - all selected:', shouldSelectAll);
}

// Function to toggle select all applicants
function selectAllApplicants() {
    console.log('selectAllApplicants called');
    const checkboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const checkedCount = document.querySelectorAll('.applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    console.log('Current checked count:', checkedCount, 'Total checkboxes:', checkboxes.length, 'Should select all:', shouldSelectAll);
    
    checkboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = shouldSelectAll;
        }
    });
    
    // Update range input and currentRange
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        if (shouldSelectAll) {
            // Selecting all - set range to total count
            currentRange = checkboxes.length;
            rangeInput.value = checkboxes.length;
        } else {
            // Unselecting all - reset to 0
            currentRange = 0;
            rangeInput.value = 0;
        }
    }
    
    // Update button text
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicants()"]');
    if (selectAllBtn) {
        if (shouldSelectAll) {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        }
    }
    
    updateSelectedCount();
    console.log('Toggle complete - all selected:', shouldSelectAll);
}

// Current range value
let currentRange = 0;

// Initialize range display on page load
document.addEventListener('DOMContentLoaded', function() {
    const availableCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
    console.log('Available checkboxes on load:', availableCount);
    
    // Always reset to 0 on page load/refresh
    currentRange = 0;
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        rangeInput.value = currentRange;
    }
    console.log('Range initialized to:', currentRange);
    
    // Automatically select the initial range (0 = no selection)
    selectCurrentRange();
    updateUnscheduledCount();
});

// Function to update range from number input
function updateRangeFromInput() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // Get the raw input value and clean it
        let inputValue = rangeInput.value.trim();
        
        // If input is empty or just whitespace, treat as 0
        if (inputValue === '' || inputValue === '0') {
            inputValue = 0;
        } else {
            inputValue = parseInt(inputValue) || 0;
        }
        
        const unscheduledCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
        
        console.log('=== RANGE INPUT DEBUG ===');
        console.log('Raw input value:', rangeInput.value);
        console.log('Processed input value:', inputValue);
        console.log('Unscheduled applicants count:', unscheduledCount);
        console.log('All checkboxes:', document.querySelectorAll('.applicant-checkbox').length);
        console.log('Disabled checkboxes (scheduled):', document.querySelectorAll('.applicant-checkbox[disabled]').length);
        
        // Validate input against unscheduled applicants
        if (inputValue > unscheduledCount && unscheduledCount > 0) {
            showToast(`Input exceeds unscheduled applicants. Available: ${unscheduledCount}`, 'error', 3000);
            // Keep the input value but don't select any checkboxes
            currentRange = 0;
            // Uncheck all checkboxes
            const allCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
            allCheckboxes.forEach(cb => { cb.checked = false; });
            updateSelectedCount();
            return; // Don't proceed to selectCurrentRange()
        } else if (inputValue < 0) {
            showToast('Input cannot be negative', 'error', 3000);
            rangeInput.value = 0;
            currentRange = 0;
        } else {
            currentRange = inputValue;
        }
        
        console.log('Final currentRange:', currentRange);
        console.log('=== END RANGE INPUT DEBUG ===');
        selectCurrentRange();
    }
}

// Function to handle range input focus and typing
function handleRangeInputFocus() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // Clear the input when user focuses and it contains only "0"
        if (rangeInput.value === '0') {
            rangeInput.value = '';
        }
    }
}

// Function to handle range input blur
function handleRangeInputBlur() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // If input is empty on blur, set it to 0
        if (rangeInput.value.trim() === '') {
            rangeInput.value = '0';
            currentRange = 0;
            selectCurrentRange();
        }
    }
}

// Function to select the current range
function selectCurrentRange() {
    console.log('selectCurrentRange called with range:', currentRange);
    
    // First, uncheck all
    const allCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    console.log('Found checkboxes:', allCheckboxes.length);
    allCheckboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = false;
        }
    });
    
    // Then select the first N available applicants
    const availableCheckboxes = Array.from(document.querySelectorAll('.applicant-checkbox:not([disabled])'));
    const toSelect = Math.min(currentRange, availableCheckboxes.length);
    console.log('Will select:', toSelect, 'checkboxes');
    
    for (let i = 0; i < toSelect; i++) {
        if (availableCheckboxes[i]) {
            availableCheckboxes[i].checked = true;
            console.log('Checked checkbox', i);
        }
    }
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = (toSelect === availableCheckboxes.length);
    }
    
    updateSelectedCount();
    updateUnscheduledCount();
    console.log('Selection complete');
}

// Function to create new schedule
function createNewSchedule() {
    const modal = new bootstrap.Modal(document.getElementById('createScheduleModal'));
    modal.show();
}

// Function to show create schedule confirmation
function showCreateScheduleConfirmation() {
    // Get form data
    const scheduleDate = document.getElementById('create_schedule_date').value;
    const startTime = document.getElementById('create_schedule_start_time').value;
    const venueCheckboxes = document.querySelectorAll('input[name="schedule_venues[]"]:checked');
    
    // Validate form
    if (!scheduleDate || !startTime || venueCheckboxes.length === 0) {
        showToast('Please fill in all required fields and select at least one venue.', 'error', 2000);
        return;
    }
    
    // Validate that date is not in the past
    const selectedDate = new Date(scheduleDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showToast('Cannot select a past date. Please choose today or a future date.', 'error', 3000);
        document.getElementById('create_schedule_date').value = '';
        document.getElementById('create_schedule_date').focus();
        return;
    }
    
    // Format date
    const formattedDate = new Date(scheduleDate).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    
    // Format time (handle 12-hour format with AM/PM)
    const startTimeFormatted = formatTimeForDisplay(startTime);
    
    // Get selected venues
    const selectedVenues = Array.from(venueCheckboxes).map(cb => cb.value);
    
    // Populate confirmation modal
    document.getElementById('createScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Date:</strong> ${formattedDate}
            </div>
            <div class="col-md-6">
                <strong>Time:</strong> ${startTimeFormatted}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Venues (${selectedVenues.length}):</strong><br>
                ${selectedVenues.map(venue => `• ${venue}`).join('<br>')}
            </div>
        </div>
    `;
    
    // Hide create schedule modal and show confirmation
    const createModal = bootstrap.Modal.getInstance(document.getElementById('createScheduleModal'));
    createModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('createScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with create schedule
function proceedWithCreateSchedule() {
    // Convert time to 24-hour format before submission
    const timeInput = document.getElementById('create_schedule_start_time');
    if (timeInput && timeInput.value) {
        const time24h = convertTo24Hour(timeInput.value);
        timeInput.value = time24h;
    }
    // Submit the form
    document.getElementById('createScheduleForm').submit();
}

// Function to set exam schedule for selected applicants
function setExamSchedule() {
    console.log('setExamSchedule called');
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    console.log('Selected checkboxes:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    console.log('Selected IDs:', selectedIds);
    
    // Check if modal element exists
    const modalElement = document.getElementById('setExamScheduleModal');
    console.log('Modal element:', modalElement);
    
    if (!modalElement) {
        console.error('Modal element not found!');
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    // Show assign to schedule modal
    const examModal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedApplicantIds').value = selectedIds.join(',');
    console.log('Showing modal...');
    examModal.show();
}

// Function to show assign schedule confirmation
function showAssignScheduleConfirmation() {
    const scheduleSelect = document.getElementById('modal_schedule_select');
    const selectedIds = document.getElementById('selectedApplicantIds').value;
    
    // Validate form
    if (!scheduleSelect.value) {
        showToast('Please select a schedule.', 'error', 2000);
        return;
    }
    
    // Get selected schedule details
    const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
    const scheduleText = selectedOption.text;
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Populate confirmation modal
    document.getElementById('examScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Selected Schedule:</strong> ${scheduleText}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide assign schedule modal and show confirmation
    const assignModal = bootstrap.Modal.getInstance(document.getElementById('setExamScheduleModal'));
    assignModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('examScheduleConfirmModal'));
    confirmModal.show();
}

// Function to show exam schedule confirmation (legacy - kept for compatibility)
function showExamScheduleConfirmation() {
    // This function is now replaced by showAssignScheduleConfirmation
    showAssignScheduleConfirmation();
}

// Function to proceed with exam schedule creation
function proceedWithExamSchedule() {
    // Submit the form
    document.getElementById('examScheduleForm').submit();
}

// Function to get current section (exam or interview)
function getCurrentSection() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('sub') || 'exam';
}

// Function to set appropriate back button based on current section
function setBackButtonForCurrentSection() {
    const currentSection = getCurrentSection();
    const backBtn = document.querySelector('.modal-footer .btn-secondary');
    
    if (backBtn) {
        if (currentSection === 'interview') {
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Interview Schedules';
            backBtn.setAttribute('onclick', 'backToInterviewSchedules()');
        } else {
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Exam Schedules';
            backBtn.setAttribute('onclick', 'backToExamSchedules()');
        }
    }
}

// Function to view student list from selected applicants
function viewStudentListFromSelection() {
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading selected applicants...</p>
        </div>
    `;
    
    // Hide export buttons initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    document.getElementById('exportStudentListPdfBtn').style.display = 'none';
    
    // Reset the back button
    setBackButtonForCurrentSection();
    
    // Show the schedule info container
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong style="color: white; font-size: 16px;">Selected Exam Applicants</strong><br>
        <small style="color: rgba(255, 255, 255, 0.9);">${selectedNames.length} applicants selected</small>
    `;
    
    modal.show();
    
    // Display selected applicants
    if (selectedNames.length > 0) {
        let studentRows = '';
        selectedNames.forEach((name, index) => {
            studentRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${name}</strong></td>
                    <td class="text-center">N/A</td>
                    <td class="text-center"><span class="badge bg-warning">Selected</span></td>
                </tr>
            `;
        });
        
        document.getElementById('studentListContent').innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover" id="studentListTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Program</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${studentRows}
                    </tbody>
                </table>
            </div>
        `;
        
        // Store data for potential export
        window.currentStudentData = selectedNames.map((name, index) => ({
            last_name: name.split(',')[0] || '',
            first_name: name.split(',')[1]?.trim().split(' ')[0] || '',
            middle_name: name.split(',')[1]?.trim().split(' ').slice(1).join(' ') || '',
            program: 'N/A'
        }));
        
        window.currentScheduleInfo = {
            eventName: 'Selected Exam Applicants',
            eventDate: 'N/A',
            eventTime: 'N/A',
            venue: 'N/A',
            scheduleType: 'exam'
        };
        
        // Show export buttons
        document.getElementById('exportStudentListBtn').style.display = 'inline-block';
        document.getElementById('exportStudentListPdfBtn').style.display = 'inline-block';
        
    } else {
        document.getElementById('studentListContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5 class="text-muted">No Applicants Selected</h5>
                <p>Please select applicants to view their details.</p>
            </div>
        `;
    }
}

// Store schedule info for deletion
let scheduleToDelete = {
    id: null,
    type: null
};

// Function to delete a schedule
function deleteSchedule(scheduleId, type) {
    // Store schedule info
    scheduleToDelete.id = scheduleId;
    scheduleToDelete.type = type;
    
    // Get schedule details from the table
    const allSchedules = type === 'exam' 
        ? <?= json_encode($all_schedules_for_viewing) ?>
        : <?= json_encode($interview_schedules ?? []) ?>;
    
    const schedule = allSchedules.find(s => s.id == scheduleId);
    
    if (!schedule) {
        showToast('Schedule not found.', 'error', 2000);
        return;
    }
    
    // Format date and time
    const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    const startTimeStr = formatTimeForDisplay(schedule.event_time);
    
    // Populate confirmation modal
    document.getElementById('deleteScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Date:</strong> ${eventDate}
            </div>
            <div class="col-md-6">
                <strong>Time:</strong> ${startTimeStr}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Venue:</strong> ${schedule.venue}
            </div>
        </div>
    `;
    
    // Show confirmation modal
    const confirmModal = new bootstrap.Modal(document.getElementById('deleteScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with schedule deletion
function proceedWithDeleteSchedule() {
    if (!scheduleToDelete.id || !scheduleToDelete.type) {
        showToast('Error: Schedule information not found.', 'error', 2000);
        return;
    }
    
    // Create a form to submit the delete request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = scheduleToDelete.type === 'exam' ? 'delete_schedule' : 'delete_interview_schedule';
    form.appendChild(actionInput);
    
    const scheduleIdInput = document.createElement('input');
    scheduleIdInput.type = 'hidden';
    scheduleIdInput.name = 'schedule_id';
    scheduleIdInput.value = scheduleToDelete.id;
    form.appendChild(scheduleIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Function to view exam schedules
function viewExamSchedules() {
    const modal = new bootstrap.Modal(document.getElementById('examSchedulesModal'));
    
    // Get all schedules from PHP data (including assigned ones for viewing)
    const allSchedules = <?= json_encode($all_schedules_for_viewing) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        // Since schedules are now already grouped (one schedule per group), we can use them directly
        const groupedSchedules = allSchedules.map(schedule => ({
            id: schedule.id,
            event_date: schedule.event_date,
            event_time: schedule.event_time,
            end_time: schedule.end_time,
            venue: schedule.venue,
            applicant_count: schedule.applicant_count !== undefined ? schedule.applicant_count : 0
        }));
        
        console.log('Grouped exam schedules:', groupedSchedules);
        console.log('First schedule applicant_count:', groupedSchedules[0]?.applicant_count, 'Type:', typeof groupedSchedules[0]?.applicant_count);
        
        // Display schedules directly since they're already grouped
        groupedSchedules.forEach((schedule, index) => {
            console.log(`Exam Schedule ${index}:`, schedule);
            console.log(`Exam Schedule ${index} applicant_count:`, schedule.applicant_count, 'Type:', typeof schedule.applicant_count);
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'short', day: 'numeric' 
            });
            
            const startTimeStr = formatTimeForDisplay(schedule.event_time);
            
            let actionButtons = '';
            if (schedule.applicant_count === 0 || schedule.applicant_count === '0') {
                // Show delete button for schedules without applicants
                actionButtons = `
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id}, 'exam')" style="border-radius: 4px; padding: 6px 12px; font-size: 12px; margin-right: 5px;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            } else {
                // Show view button for schedules with applicants
                actionButtons = `
                    <button type="button" class="btn btn-sm" onclick="viewStudentsForSchedule(${schedule.id}, 'Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-users"></i> View Student List (${schedule.applicant_count})
                    </button>
                `;
            }
            
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        ${actionButtons}
                    </td>
                </tr>
            `;
        });
    } else {
        // Update schedule info to show no schedules message
        document.getElementById('scheduleInfo').innerHTML = '<strong style="color: white; font-size: 16px;">No Exam Schedules Found</strong>';
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No exam schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('examSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    modal.show();
}

// Function to view students for a specific schedule
function viewStudentsForSchedule(scheduleId, eventName, eventDate, eventTime, venue) {
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data and content immediately to prevent showing cached data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content immediately
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading students...</p>
        </div>
    `;
    
    // Hide export buttons initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    document.getElementById('exportStudentListPdfBtn').style.display = 'none';
    
    // Reset the back button
    document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-times"></i> Close';
    document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'closeStudentListModal()');
    
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
        <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
        <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
            <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
            <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    
    console.log('Schedule ID:', scheduleId);
    
    modal.show();
    
    // Fetch students for this specific schedule via AJAX with cache-busting
    fetch(`scheduling.php?action=get_schedule_students&schedule_id=${scheduleId}&t=${Date.now()}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5 class="text-muted">Error</h5>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            const scheduledApplicants = data;
            
            console.log('Scheduled applicants found:', scheduledApplicants);
            
            if (scheduledApplicants.length > 0) {
                let studentRows = '';
                scheduledApplicants.forEach((applicant, index) => {
                    // Format name properly
                    const fullName = `${applicant.last_name}, ${applicant.first_name} ${applicant.middle_name || ''}`.trim();
                    const formattedName = fullName.split(' ').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                    
                    studentRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${formattedName}</strong></td>
                            <td class="text-center">${applicant.program || 'N/A'}</td>
                            <td class="text-center"><span class="badge" style="background-color: rgb(0, 105, 42);">Scheduled</span></td>
                        </tr>
                    `;
                });
                
                document.getElementById('studentListContent').innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentListTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${studentRows}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Show export buttons and store data for export
                document.getElementById('exportStudentListBtn').style.display = 'inline-block';
                document.getElementById('exportStudentListPdfBtn').style.display = 'inline-block';
                document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-arrow-left"></i> Back to Schedules';
                document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'backToExamSchedules()');
                window.currentStudentData = scheduledApplicants;
                window.currentScheduleInfo = {
                    eventName: eventName,
                    eventDate: eventDate,
                    eventTime: eventTime,
                    venue: venue,
                    scheduleType: 'exam'
                };
            } else {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="text-muted">No Student Found</h5>
                        <p>No students are currently enrolled for this schedule.</p>
                    </div>
                `;
                
                // Hide export buttons when no students
                document.getElementById('exportStudentListBtn').style.display = 'none';
                document.getElementById('exportStudentListPdfBtn').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            
            document.getElementById('studentListContent').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5 class="text-muted">Error</h5>
                    <p>Failed to load students. Please try again.</p>
                    <small class="text-muted">Error: ${error.message}</small>
                    <br><small class="text-muted">Check browser console for more details.</small>
                </div>
            `;
        });
}

// Function to view student list for a specific schedule
function viewStudentList(scheduleId, eventName, eventDate, eventTime, venue = 'N/A') {
    // For now, show a placeholder modal - you can implement actual student list fetching
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
        <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
        <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong style="color: white; font-size: 16px;">${eventName}</strong><br>
            <small style="color: rgba(255, 255, 255, 0.9);">${eventDate} at ${eventTime}</small><br>
            <small style="color: rgba(255, 255, 255, 0.9);"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center text-muted">
            <i class="fas fa-users fa-3x mb-3"></i>
            <p>Student list for this schedule will be displayed here.</p>
            <p><small>Schedule ID: ${scheduleId}</small></p>
        </div>
    `;
    modal.show();
}

// Function to go back to exam schedules table view
function backToExamSchedules() {
    // Close the student list modal
    const studentListModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (studentListModal) {
        studentListModal.hide();
    }
    
    // Open the exam schedules modal
    const examSchedulesModal = new bootstrap.Modal(document.getElementById('examSchedulesModal'));
    
    // Get all schedules from PHP data and display the table (including assigned ones)
    const allSchedules = <?= json_encode($all_schedules_for_viewing) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        allSchedules.forEach(schedule => {
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
            
            const startTime = new Date(`2000-01-01T${schedule.event_time}`);
            const startTimeStr = startTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForSchedule(${schedule.id}, 'Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> View Student List (${schedule.applicant_count})
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No exam schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('examSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    examSchedulesModal.show();
}

// Function to close modal
function closeModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (modal) {
        modal.hide();
    }
}

// Function to export student list to CSV
function exportStudentList() {
    if (!window.currentStudentData || window.currentStudentData.length === 0) {
        showToast('No student data to export.', 'error', 2000);
        return;
    }
    
    const scheduleInfo = window.currentScheduleInfo;
    const students = window.currentStudentData;
    
    // Determine event type for header
    const eventType = scheduleInfo.scheduleType === 'interview' ? 'Interview Schedule' : 'Exam Schedule';
    
    // Create CSV content with specific event type header
    let csvContent = `"${eventType}"\n`;
    csvContent += `"Date: ${scheduleInfo.eventDate}"\n`;
    csvContent += `"Time: ${scheduleInfo.eventTime}"\n`;
    csvContent += `"Venue: ${scheduleInfo.venue}"\n\n`;
    
    csvContent += `"#","Name","Program"\n`;
    
    students.forEach((student, index) => {
        // Format name properly
        const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
        const formattedName = fullName.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
        
        const program = student.program || 'N/A';
        
        csvContent += `"${index + 1}","${formattedName}","${program}"\n`;
    });
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    // Generate filename with date and time
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
    const eventTypeLabel = scheduleInfo.scheduleType === 'interview' ? 'interview' : 'exam';
    const filename = `${eventTypeLabel}_student_list_${dateStr}_${timeStr}.csv`;
    
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    showSuccessToast('Student list exported successfully!');
}

// Function to export student list to PDF
function exportStudentListToPDF() {
    if (!window.currentStudentData || window.currentStudentData.length === 0) {
        showToast('No student data to export.', 'error', 2000);
        return;
    }
    
    const scheduleInfo = window.currentScheduleInfo;
    const students = window.currentStudentData;
    
    try {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('portrait', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        let yPos = 20;
        const margin = 15;
        const lineHeight = 7;
        const maxWidth = pageWidth - (margin * 2);
        
        // Determine event type for header
        const eventType = scheduleInfo.scheduleType === 'interview' ? 'Interview Schedule' : 'Exam Schedule';
        
        // Load logo and create header
        const logoPath = 'images/chmsu.png';
        let headerHeight = 50;
        yPos = 15;
        
        // Add logo (if available)
        try {
            pdf.addImage(logoPath, 'PNG', margin, yPos, 20, 20);
            yPos += 25;
        } catch (e) {
            // Logo not found, continue without it
            console.log('Logo not found, continuing without logo');
        }
        
        // Reset yPos for text if logo was added
        if (yPos > 15) {
            yPos = 20;
        } else {
            yPos = 15;
        }
        
        // University name
        pdf.setTextColor(0, 0, 0);
        pdf.setFontSize(14);
        pdf.setFont(undefined, 'bold');
        pdf.text('Carlos Hilado Memorial State University', margin + 25, yPos);
        yPos += 6;
        
        // System name
        pdf.setFontSize(10);
        pdf.setFont(undefined, 'normal');
        pdf.text('Academic Program Application and Screening Management System', margin + 25, yPos);
        yPos += 6;
        
        // Event type title
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'bold');
        pdf.text(eventType, margin + 25, yPos);
        yPos += 8;
        
        // Draw a line under header
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.5);
        pdf.line(margin, yPos, pageWidth - margin, yPos);
        yPos += 5;
        
        // Add schedule details
        pdf.setFontSize(10);
        pdf.text(`Date: ${scheduleInfo.eventDate || 'N/A'}`, margin, yPos);
        yPos += lineHeight;
        pdf.text(`Time: ${scheduleInfo.eventTime || 'N/A'}`, margin, yPos);
        yPos += lineHeight;
        pdf.text(`Venue: ${scheduleInfo.venue || 'N/A'}`, margin, yPos);
        yPos += lineHeight * 1.5;
        
        // Draw a line
        pdf.setDrawColor(0, 105, 42);
        pdf.line(margin, yPos, pageWidth - margin, yPos);
        yPos += lineHeight;
        
        // Table header
        pdf.setFillColor(240, 240, 240);
        pdf.rect(margin, yPos - 5, pageWidth - (margin * 2), lineHeight + 2, 'F');
        
        pdf.setFontSize(10);
        pdf.setFont(undefined, 'bold');
        pdf.text('#', margin + 5, yPos);
        pdf.text('Name', margin + 15, yPos);
        pdf.text('Program', margin + 100, yPos);
        
        yPos += lineHeight + 3;
        pdf.setFont(undefined, 'normal');
        
        // Add student rows
        students.forEach((student, index) => {
            // Check if we need a new page
            if (yPos > pageHeight - 20) {
                pdf.addPage();
                yPos = 20;
            }
            
            // Format name properly
            const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
            const formattedName = fullName.split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
            
            const program = student.program || 'N/A';
            
            // Truncate long names to fit
            const maxNameWidth = 80;
            const maxProgramWidth = 60;
            let displayName = formattedName;
            let displayProgram = program;
            
            if (pdf.getTextWidth(displayName) > maxNameWidth) {
                while (pdf.getTextWidth(displayName + '...') > maxNameWidth && displayName.length > 0) {
                    displayName = displayName.slice(0, -1);
                }
                displayName += '...';
            }
            
            if (pdf.getTextWidth(displayProgram) > maxProgramWidth) {
                while (pdf.getTextWidth(displayProgram + '...') > maxProgramWidth && displayProgram.length > 0) {
                    displayProgram = displayProgram.slice(0, -1);
                }
                displayProgram += '...';
            }
            
            pdf.setFontSize(9);
            pdf.text(`${index + 1}`, margin + 5, yPos);
            pdf.text(displayName, margin + 15, yPos);
            pdf.text(displayProgram, margin + 100, yPos);
            
            yPos += lineHeight + 1;
        });
        
        // Generate filename with date and time
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
        const eventTypeLabel = scheduleInfo.scheduleType === 'interview' ? 'interview' : 'exam';
        const filename = `${eventTypeLabel}_student_list_${dateStr}_${timeStr}.pdf`;
        
        // Save the PDF
        pdf.save(filename);
        
        // Show success message
        showSuccessToast('Student list exported to PDF successfully!');
    } catch (error) {
        console.error('Error generating PDF:', error);
        showToast('Error generating PDF. Please try again.', 'error', 3000);
    }
}

// Function to update unscheduled count display
function updateUnscheduledCount() {
    const unscheduledCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
    
    const unscheduledCountEl = document.getElementById('unscheduledCount');
    
    if (unscheduledCountEl) {
        unscheduledCountEl.textContent = unscheduledCount;
    }
}

// Function to show success toast
function showSuccessToast(message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1065';
        document.body.appendChild(toastContainer);
    } else {
        // Ensure z-index is high enough to appear above modals
        toastContainer.style.zIndex = '1065';
    }
    
    const toastId = 'exportToast_' + Date.now();
    const iconClass = 'fas fa-check-circle';
    const headerBg = '#d4edda';
    const headerBorder = '#c3e6cb';
    const bodyBg = '#d4edda';
    const textColor = 'text-success';
    const typeLabel = 'Success';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 1065;">
            <div class="toast-header" style="background-color: ${headerBg}; border-color: ${headerBorder};">
                <i class="${iconClass} ${textColor} me-2"></i>
                <strong class="me-auto ${textColor}">${typeLabel}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: ${bodyBg};">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        const toast = new bootstrap.Toast(toastElement);
        toast.hide();
    }, 3000);
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Initialize Flatpickr for date and time inputs
function initializeFlatpickr() {
    // Wait for Flatpickr to be available
    if (typeof flatpickr === 'undefined') {
        setTimeout(initializeFlatpickr, 100);
        return;
    }
    
    // Helper function to close all date pickers
    function closeAllDatePickers() {
        const dateInputs = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        dateInputs.forEach(function(id) {
            const input = document.getElementById(id);
            if (input && input._flatpickr) {
                input._flatpickr.close();
            }
        });
    }
    
    // Track time inputs to prevent date picker conflicts
    const timeInputIds = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
    
    // Helper function to check if an element is a time input
    function isTimeInput(element) {
        if (!element) return false;
        return timeInputIds.includes(element.id) || 
               (element.classList && element.classList.contains('flatpickr-input') && 
               element.id && (element.id.includes('time') || element.id.includes('Time')));
    }
    
    // Helper function is no longer needed - using global handler instead
    
    // Date picker for schedule date
    if (document.getElementById('create_schedule_date') && !document.getElementById('create_schedule_date')._flatpickr) {
        const dateInput = document.getElementById('create_schedule_date');
        flatpickr('#create_schedule_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            allowInput: false,
            clickOpens: true,
            onOpen: function(selectedDates, dateStr, instance) {
                // Check if a time input was just clicked or time picker is open - if so, close this date picker
                // But allow if the click came from this date input itself
                const dateInputId = instance.input.id;
                const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
                if ((window.timeInputClicked || window.isAnyTimePickerOpen()) && 
                    window.lastClickedElement && 
                    !dateInputIds.includes(window.lastClickedElement.id)) {
                    instance.close();
                    return;
                }
                // Close time pickers when date picker opens
                const timeInputs = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
                timeInputs.forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input && input._flatpickr) {
                        input._flatpickr.close();
                    }
                });
            },
        });
    }
    
    // Time picker for schedule start time
    if (document.getElementById('create_schedule_start_time') && !document.getElementById('create_schedule_start_time')._flatpickr) {
        const timeInput = document.getElementById('create_schedule_start_time');
        flatpickr('#create_schedule_start_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            allowInput: false,
            clickOpens: true,
            onOpen: function() {
                // Close date pickers when time picker opens
                closeAllDatePickers();
            }
        });
    }
    
    // Date picker for interview date
    if (document.getElementById('create_interview_date') && !document.getElementById('create_interview_date')._flatpickr) {
        const dateInput = document.getElementById('create_interview_date');
        flatpickr('#create_interview_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            allowInput: false,
            clickOpens: true,
            onOpen: function(selectedDates, dateStr, instance) {
                // Check if a time input was just clicked or time picker is open - if so, close this date picker
                // But allow if the click came from this date input itself
                const dateInputId = instance.input.id;
                const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
                if ((window.timeInputClicked || window.isAnyTimePickerOpen()) && 
                    window.lastClickedElement && 
                    !dateInputIds.includes(window.lastClickedElement.id)) {
                    instance.close();
                    return;
                }
                // Close time pickers when date picker opens
                const timeInputs = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
                timeInputs.forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input && input._flatpickr) {
                        input._flatpickr.close();
                    }
                });
            },
        });
    }
    
    // Time picker for interview start time
    if (document.getElementById('create_interview_start_time') && !document.getElementById('create_interview_start_time')._flatpickr) {
        const timeInput = document.getElementById('create_interview_start_time');
        flatpickr('#create_interview_start_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            allowInput: false,
            clickOpens: true,
            onOpen: function() {
                // Close date pickers when time picker opens
                closeAllDatePickers();
            },
        });
    }
    
    // Initialize for dynamically created modals (if they exist)
    if (document.getElementById('modal_interview_date') && !document.getElementById('modal_interview_date')._flatpickr) {
        const dateInput = document.getElementById('modal_interview_date');
        flatpickr('#modal_interview_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            allowInput: false,
            clickOpens: true,
            onOpen: function(selectedDates, dateStr, instance) {
                // Check if a time input was just clicked or time picker is open - if so, close this date picker
                // But allow if the click came from this date input itself
                const dateInputId = instance.input.id;
                const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
                if ((window.timeInputClicked || window.isAnyTimePickerOpen()) && 
                    window.lastClickedElement && 
                    !dateInputIds.includes(window.lastClickedElement.id)) {
                    instance.close();
                    return;
                }
                // Close time pickers when date picker opens
                const timeInputs = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
                timeInputs.forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input && input._flatpickr) {
                        input._flatpickr.close();
                    }
                });
            },
        });
    }
    
    if (document.getElementById('modal_interview_start_time') && !document.getElementById('modal_interview_start_time')._flatpickr) {
        const timeInput = document.getElementById('modal_interview_start_time');
        flatpickr('#modal_interview_start_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            allowInput: false,
            clickOpens: true,
            onOpen: function() {
                // Close date pickers when time picker opens
                closeAllDatePickers();
            },
        });
    }
    
    if (document.getElementById('modal_interview_end_time') && !document.getElementById('modal_interview_end_time')._flatpickr) {
        const timeInput = document.getElementById('modal_interview_end_time');
        flatpickr('#modal_interview_end_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            allowInput: false,
            clickOpens: true,
            onOpen: function() {
                // Close date pickers when time picker opens
                closeAllDatePickers();
            },
        });
    }
}

// Global handler to prevent date pickers from opening when time inputs are clicked
// This is set up once and applies to all time inputs
(function() {
    const timeInputIds = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
    window.timeInputClicked = false;
    
    function isTimeInput(element) {
        if (!element) return false;
        return timeInputIds.includes(element.id) || 
               (element.classList && element.classList.contains('flatpickr-input') && 
               element.id && (element.id.includes('time') || element.id.includes('Time')));
    }
    
    function closeAllDatePickers() {
        const dateInputs = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        dateInputs.forEach(function(id) {
            const input = document.getElementById(id);
            if (input && input._flatpickr) {
                input._flatpickr.close();
            }
        });
    }
    
    // Check if any time picker is currently open
    window.isAnyTimePickerOpen = function() {
        const timeInputIds = ['create_schedule_start_time', 'create_interview_start_time', 'modal_interview_start_time', 'modal_interview_end_time'];
        return timeInputIds.some(function(id) {
            const input = document.getElementById(id);
            return input && input._flatpickr && input._flatpickr.isOpen;
        });
    };
    
    // Track the last clicked element to determine if date picker should open
    window.lastClickedElement = null;
    
    // Function to temporarily disable date pickers (only when time input was clicked)
    function disableDatePickers() {
        const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        dateInputIds.forEach(function(id) {
            const input = document.getElementById(id);
            if (input && input._flatpickr) {
                // Store original open method if not already stored
                if (!input._flatpickr._originalOpen) {
                    input._flatpickr._originalOpen = input._flatpickr.open;
                }
                // Override open method to prevent opening only if time input was clicked
                input._flatpickr.open = function() {
                    // Allow opening if the click was on this date input itself
                    if (window.lastClickedElement && window.lastClickedElement.id === id) {
                        return input._flatpickr._originalOpen.apply(this, arguments);
                    }
                    // Don't open if time input was just clicked or time picker is open
                    if (window.timeInputClicked || window.isAnyTimePickerOpen()) {
                        return;
                    }
                    // Otherwise use original open method
                    return input._flatpickr._originalOpen.apply(this, arguments);
                };
            }
        });
    }
    
    // Function to re-enable date pickers
    function enableDatePickers() {
        const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        dateInputIds.forEach(function(id) {
            const input = document.getElementById(id);
            if (input && input._flatpickr && input._flatpickr._originalOpen) {
                input._flatpickr.open = input._flatpickr._originalOpen;
            }
        });
    }
    
    // Track all mousedown events to know what was clicked
    document.addEventListener('mousedown', function(e) {
        window.lastClickedElement = e.target;
    }, true);
    
    // Track when time inputs are clicked
    document.addEventListener('mousedown', function(e) {
        const target = e.target;
        if (isTimeInput(target)) {
            window.timeInputClicked = true;
            closeAllDatePickers();
            disableDatePickers(); // Temporarily disable date pickers
            
            // Keep flag active while time picker is open, reset when closed
            const checkTimePicker = setInterval(function() {
                if (!window.isAnyTimePickerOpen()) {
                    window.timeInputClicked = false;
                    enableDatePickers();
                    clearInterval(checkTimePicker);
                }
            }, 100);
            
            // Also reset after a longer delay as fallback
            setTimeout(function() {
                clearInterval(checkTimePicker);
                window.timeInputClicked = false;
                enableDatePickers();
            }, 5000); // 5 second fallback
        }
    }, true); // Use capture phase to set flag early
    
    // Only prevent date picker opening if click didn't originate from the date input itself
    document.addEventListener('click', function(e) {
        const target = e.target;
        const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        // Only block if the click is on a date input AND it wasn't the date input that was clicked
        // (i.e., the click came from somewhere else like a time input)
        if (dateInputIds.includes(target.id) && 
            (window.timeInputClicked || window.isAnyTimePickerOpen()) && 
            window.lastClickedElement && 
            !dateInputIds.includes(window.lastClickedElement.id)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            target.blur();
            if (target._flatpickr && target._flatpickr.isOpen) {
                target._flatpickr.close();
            }
        }
    }, true); // Use capture phase
    
    // Prevent date pickers from opening if time input was just clicked or time picker is open
    // But allow if the focus came from clicking the date input itself
    document.addEventListener('focus', function(e) {
        const target = e.target;
        const dateInputIds = ['create_schedule_date', 'create_interview_date', 'modal_interview_date'];
        // Only block if focus is on date input AND the click didn't come from the date input itself
        if (dateInputIds.includes(target.id) && 
            (window.timeInputClicked || window.isAnyTimePickerOpen()) && 
            window.lastClickedElement && 
            !dateInputIds.includes(window.lastClickedElement.id)) {
            // Prevent date picker from opening
            e.preventDefault();
            e.stopImmediatePropagation();
            if (target._flatpickr && target._flatpickr.isOpen) {
                target._flatpickr.close();
            }
            // Blur the date input
            setTimeout(function() {
                target.blur();
            }, 10);
        }
        if (isTimeInput(target)) {
            closeAllDatePickers();
        }
    }, true); // Use capture phase
})();

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initializeFlatpickr();
    
    // Watch for dynamically added modals
    const observer = new MutationObserver(function() {
        initializeFlatpickr();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Also initialize when modals are shown
    const modals = ['createScheduleModal', 'createInterviewScheduleModal', 'setInterviewScheduleModal'];
    modals.forEach(function(modalId) {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('shown.bs.modal', function() {
                setTimeout(initializeFlatpickr, 100);
            });
        }
    });
});
</script>