<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as interviewer
if (!isset($_SESSION['interviewer_id']) || $_SESSION['user_type'] !== 'interviewer') {
    header("Location: chair_login.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$applicant_id = $_GET['applicant_id'] ?? null;
$applicant_data = null;

// Get applicant data if ID is provided
$profiling_data = null;
if ($applicant_id) {
    $sql = "SELECT 
                r.id AS registration_id,
                r.applicant_status,
                pi.id AS personal_info_id,
                pi.last_name,
                pi.first_name,
                pi.middle_name,
                pi.contact_number,
                sr.interview_total_score,
                sr.exam_total_score
            FROM registration r
            INNER JOIN personal_info pi ON r.personal_info_id = pi.id
            INNER JOIN screening_results sr ON sr.personal_info_id = pi.id
            WHERE r.id = ? 
            AND sr.exam_total_score >= 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicant_data = $result->fetch_assoc();
    
    // Fetch profiling data if applicant data exists
    if ($applicant_data && isset($applicant_data['personal_info_id'])) {
        $personal_info_id = $applicant_data['personal_info_id'];
        
        // Fetch personal info
        $personal_sql = "SELECT * FROM personal_info WHERE id = ?";
        $personal_stmt = $conn->prepare($personal_sql);
        $personal_stmt->bind_param("i", $personal_info_id);
        $personal_stmt->execute();
        $profiling_data['personal'] = $personal_stmt->get_result()->fetch_assoc();
        
        // Fetch socio demographic
        $socio_sql = "SELECT * FROM socio_demographic WHERE personal_info_id = ?";
        $socio_stmt = $conn->prepare($socio_sql);
        $socio_stmt->bind_param("i", $personal_info_id);
        $socio_stmt->execute();
        $profiling_data['socio'] = $socio_stmt->get_result()->fetch_assoc();
        
        // Fetch academic background
        $academic_sql = "SELECT ab.*, s.name as strand_name FROM academic_background ab LEFT JOIN strands s ON ab.strand_id = s.id WHERE ab.personal_info_id = ?";
        $academic_stmt = $conn->prepare($academic_sql);
        $academic_stmt->bind_param("i", $personal_info_id);
        $academic_stmt->execute();
        $profiling_data['academic'] = $academic_stmt->get_result()->fetch_assoc();
        
        // Fetch program application
        $program_sql = "SELECT * FROM program_application WHERE personal_info_id = ?";
        $program_stmt = $conn->prepare($program_sql);
        $program_stmt->bind_param("i", $personal_info_id);
        $program_stmt->execute();
        $profiling_data['program'] = $program_stmt->get_result()->fetch_assoc();
        
        // Fetch documents
        $documents_sql = "SELECT * FROM documents WHERE personal_info_id = ?";
        $documents_stmt = $conn->prepare($documents_sql);
        $documents_stmt->bind_param("i", $personal_info_id);
        $documents_stmt->execute();
        $profiling_data['documents'] = $documents_stmt->get_result()->fetch_assoc();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_interview'])) {
    // Get applicant_id from POST
    $applicant_id = $_POST['applicant_id'] ?? null;
    
    if (!$applicant_id) {
        $_SESSION['error_message'] = "Applicant ID is missing.";
        header("Location: interviewer_main.php?page=interviewer_applicants");
        exit;
    }
    
    // Calculate scores from radio buttons
    $section1_total = 0;
    $section2_total = 0;
    $section3_total = 0;
    $writing_score = 0;
    $reading_score = intval($_POST['reading_score'] ?? 0);
    
    // Section 1: Preparedness (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section1_total += intval($_POST["section1_item$i"] ?? 0);
    }
    
    // Section 2: Communication Skills (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section2_total += intval($_POST["section2_item$i"] ?? 0);
    }
    
    // Section 3: Personal/Physical/Social Traits (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section3_total += intval($_POST["section3_item$i"] ?? 0);
    }
    
    // Writing Skills (max 20 points)
    $writing_score = intval($_POST['writing_score'] ?? 0);
    
    // Total Score (out of 100)
    $total_score = $section1_total + $section2_total + $section3_total + $writing_score + $reading_score;
    
    // Final Interview Score = (TS / 100) × 50 + 50
    $final_score = (($total_score / 100) * 50) + 50;
    
    // Calculate interview percentage: (interview_total_score / 100) × 35
    $interview_percentage = ($final_score / 100) * 35;
    
    // Get personal_info_id from registration
    $reg_sql = "SELECT personal_info_id FROM registration WHERE id = ?";
    $reg_stmt = $conn->prepare($reg_sql);
    $reg_stmt->bind_param("i", $applicant_id);
    $reg_stmt->execute();
    $reg_result = $reg_stmt->get_result();
    $reg_data = $reg_result->fetch_assoc();
    
    if ($reg_data) {
        $personal_info_id = $reg_data['personal_info_id'];
        
        // Update or insert interview results
        $update_sql = "INSERT INTO screening_results (personal_info_id, interview_total_score, interview_percentage)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE 
                       interview_total_score = VALUES(interview_total_score),
                       interview_percentage = VALUES(interview_percentage)";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("idd", $personal_info_id, $final_score, $interview_percentage);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Interview evaluation submitted successfully!";
            // Redirect to prevent form resubmission
            header("Location: interviewer_main.php?page=interviewer_applicants");
            exit;
        } else {
            $_SESSION['error_message'] = "Error saving interview evaluation.";
        }
    } else {
        $_SESSION['error_message'] = "Applicant not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Interview Form - Interviewer Portal</title>
  <link rel="icon" href="images/chmsu.png" type="image/png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: url('images/chmsubg.jpg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    .overlay {
      background-color: rgba(255, 255, 255, 0.85);
      min-height: 100vh;
      padding-top: 120px;
    }
    .header-bar {
      background-color: rgb(0, 105, 42);
      color: white;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }
    .header-bar img {
      width: 65px;
      margin-right: 10px;
    }
    .interview-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
        overflow: hidden;
    }
    
    .applicant-info {
        background: linear-gradient(135deg, rgb(0, 105, 42), rgb(0, 85, 34));
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
    }
    
    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }
    
    .table {
        margin-bottom: 0;
        width: 100%;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
    }
    
    .score-input {
        width: 100px;
        text-align: center;
    }
    
    .btn-save {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
    }
    
    .btn-save:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
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
    
    .form-check-input:checked {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    .form-check-input:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.25rem rgba(0, 105, 42, 0.25);
    }
    
    .btn-confirm:hover {
        background-color: rgb(0, 85, 34) !important;
        border-color: rgb(0, 85, 34) !important;
        color: white !important;
    }
    
    .score-display {
        font-size: 1.2em;
        font-weight: bold;
        color: rgb(0, 105, 42);
    }
    
    .btn-info {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
        color: white;
    }
    .btn-info:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }
    .btn-info:focus,
    .btn-info:active,
    .btn-info:focus-visible,
    .btn-info.active,
    .btn-info:active:focus {
        background-color: rgb(0, 85, 34) !important;
        border-color: rgb(0, 85, 34) !important;
        color: white !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    
    /* Profiling Modal Styles - Matching my_account.php format */
    .resume-section { 
        margin-bottom: 2rem;
        position: relative;
    }
    .resume-section h5 { 
        border-bottom: 1px solid #ccc; 
        padding: 0.75rem 1rem; 
        margin-bottom: 1rem; 
        background-color: rgb(0, 105, 42); 
        color: white;
        border-radius: 4px; 
        font-weight: 600;
    }
    .resume-label { 
        font-weight: bold; 
        color: #333; 
    }
    .id-picture {
        border-radius: 5px; 
        width: 2in;
        height: 2in;
        object-fit: cover;
        border: 2px solid rgb(0, 105, 42);
        display: block;
        margin-left: auto;
        margin-right: 0;
        margin-top: 0 !important;
    }
    .personal-info-row {
        position: relative;
    }
    .personal-info-row .picture-col {
        position: absolute;
        top: 0;
        right: 15px;
        width: auto;
        padding: 0;
    }
    .personal-info-row .info-col {
        padding-right: 220px !important;
    }
    @media (max-width: 768px) {
        .personal-info-row {
            display: flex;
            flex-direction: column;
        }
        .personal-info-row .picture-col {
            position: relative;
            width: 100%;
            text-align: center;
            margin-top: 0;
            margin-bottom: 1rem;
            order: -1;
        }
        .personal-info-row .info-col {
            padding-right: 15px !important;
            order: 1;
        }
        .id-picture {
            width: 150px !important;
            height: 150px !important;
            margin: 0 auto !important;
        }
    }
  </style>
</head>
<body>
<div class="overlay">
  <div class="header-bar d-flex align-items-center">
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
      <p class="mb-0">Interviewer Portal - Interview Form</p>
    </div>
  </div>

  <!-- Toast Container -->
  <?php 
    // Store messages in variables and clear session immediately
    $success_message = $_SESSION['success_message'] ?? null;
    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    
  ?>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <?php if ($success_message): ?>
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
          <i class="fas fa-check-circle text-success me-2"></i>
          <strong class="me-auto text-success">Success</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #d4edda;">
          <?= htmlspecialchars($success_message) ?>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
          <i class="fas fa-exclamation-circle text-danger me-2"></i>
          <strong class="me-auto text-danger">Error</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #f8d7da;">
          <?= htmlspecialchars($error_message) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
          <h5 class="modal-title" id="confirmationModalLabel">
            <i class="fas fa-question-circle me-2"></i>Confirm Interview Evaluation
          </h5>
        </div>
        <div class="modal-body">
          <div class="text-center mb-3">
            <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
            <h6>Please review your evaluation scores:</h6>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="card border-success">
                <div class="card-body text-center">
                  <h6 class="card-title text-success">Total Score</h6>
                  <h4 class="text-success" id="modalTotalScore">0</h4>
                  <small class="text-muted">out of 100</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="card border-success">
                <div class="card-body text-center">
                  <h6 class="card-title text-success">Interview Score</h6>
                  <h4 class="text-success" id="modalFinalScore">0</h4>
                  <small class="text-muted">out of 100</small>
                </div>
              </div>
            </div>
          </div>
          <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> Once submitted, this evaluation cannot be modified.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Cancel
          </button>
          <button type="button" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" id="printEvaluationBtn" onclick="printEvaluation()">
            <i class="fas fa-print me-2"></i>Print
          </button>
          <button type="button" class="btn btn-confirm" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" id="confirmSubmitBtn">
            <i class="fas fa-check me-2"></i>Confirm & Submit
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid px-4 py-3">
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="interviewer_main.php?page=interviewer_applicants" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Applicants
                    </a>
                    <h4 class="mb-0"><i class="fas fa-microphone me-2"></i>Interview Form</h4>
                </div>
            </div>
        </div>
    </div>

    <?php if ($applicant_data): ?>
        <div class="row">
            <div class="col-12">
                <div class="interview-card">
                    <!-- Applicant Information -->
                    <div class="applicant-info">
                        <div class="row align-items-center">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-2">
                                            <i class="fas fa-user me-2"></i>
                                            <?= htmlspecialchars(ucwords(strtolower($applicant_data['first_name'] . ' ' . $applicant_data['middle_name'] . ' ' . $applicant_data['last_name']))) ?>
                                        </h5>
                                        <p class="mb-0">
                                            <i class="fas fa-hashtag me-2"></i>
                                            Registration ID: <strong><?= $applicant_data['registration_id'] ?></strong>
                                        </p>
                                    </div>
                                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#profilingModal" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid rgb(0, 105, 42);">
                                        <i class="fas fa-eye me-2"></i>View Profiling Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Form -->
                    <div class="p-4">
                        <form method="POST" action="" id="interviewForm">
                            <input type="hidden" name="applicant_id" value="<?= $applicant_id ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">I. PREPAREDNESS FOR COLLEGE EDUCATION (20 points)</th>
                                    </tr>
                                    <tr>
                                        <th style="width: 40%;">Indicators</th>
                                        <th class="text-center">5<br><small>Excellent</small></th>
                                        <th class="text-center">4<br><small>Above Average</small></th>
                                        <th class="text-center">3<br><small>Average</small></th>
                                        <th class="text-center">2<br><small>Below Avg</small></th>
                                        <th class="text-center">1<br><small>Poor</small></th>
                                    </tr>

                                    <!-- Preparedness Rows -->
                                    <?php
                                        $section1 = [
                                          'Foundation in math, science, and other requisite skills/knowledge',
                                          'Study habits (as discussed during the interview)',
                                          'Display of interest in the applied program',
                                          'Academic and extra-curricular achievements/awards'
                                        ];
                                        foreach ($section1 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section1_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">II. ORAL COMMUNICATION SKILLS (20 points)</th>
                                    </tr>
                                    <?php
                                        $section2 = [
                                          'Content of the responses to interview questions',
                                          'Manner and delivery of the responses',
                                          'Mechanics, use of words/terms, grammar, and pronunciation',
                                          'Gestures and facial expressions'
                                        ];
                                        foreach ($section2 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section2_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">III. PERSONAL/PHYSICAL/SOCIAL TRAITS (20 points)</th>
                                    </tr>
                                    <?php
                                        $section3 = [
                                          'Personal traits (professionalism, confidence, enthusiasm)',
                                          'Social traits (courtesy, attentiveness, rapport with interviewer)',
                                          'Physical appearance (hygiene, grooming, dress/clothes)',
                                          'Body language, eye contact, and posture'
                                        ];
                                        foreach ($section3 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section3_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">IV. WRITING SKILLS (20 points)</th>
                                    </tr>
                                    <tr>
                                        <td class="text-start">1. Score on Writing Skills Test</td>
                                        <td colspan="6"><input type="number" name="writing_score" min="0" max="20" maxlength="2" class="form-control" required></td>
                                    </tr>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">V. READING AND COMPREHENSION (20 points)</th>
                                    </tr>
                                    <tr>
                                        <td class="text-start">1. Score on Reading and Comprehension Test</td>
                                        <td colspan="6"><input type="number" name="reading_score" min="0" max="20" maxlength="2" class="form-control" required></td>
                                    </tr>

                                    <tr style="background-color: rgba(0, 105, 42, 0.1);">
                                        <th colspan="7">TOTAL SCORE (TS): <input type="number" name="total_score" id="totalScore" readonly class="form-control d-inline-block" style="width: 100px;"></th>
                                    </tr>
                                    <tr style="background-color: rgba(0, 105, 42, 0.2);">
                                        <th colspan="7">
                                          INTERVIEW SCORE = (TS / 100) × 50 + 50 = 
                                          <input type="number" name="final_score" id="finalScore" readonly class="form-control d-inline-block" style="width: 100px;">
                                        </th>
                                    </tr>
                                </table>
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" id="submitBtn" class="btn btn-save btn-lg">
                                    <i class="fas fa-save me-2"></i>Submit Evaluation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>No Applicant Selected</h5>
                        <p class="text-muted">Please select an applicant from the applicants list to conduct an interview.</p>
                        <a href="interviewer_main.php?page=interviewer_applicants" class="btn btn-outline-success">
                            <i class="fas fa-users me-2"></i>View Applicants List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Profiling Data Modal -->
<?php if ($applicant_data && $profiling_data): ?>
<div class="modal fade" id="profilingModal" tabindex="-1" aria-labelledby="profilingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="profilingModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Applicant Profiling Data
                </h5>
            </div>
            <div class="modal-body">
                <!-- Personal Information -->
                <div class="resume-section">
                    <h5><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
                    <?php if ($profiling_data['personal']): ?>
                        <div class="row align-items-start personal-info-row">
                            <div class="col-md-3 text-end picture-col">
                                <?php if (!empty($profiling_data['personal']['id_picture'])): ?>
                                    <img src="../uploads/id_pictures/<?= htmlspecialchars($profiling_data['personal']['id_picture']) ?>" class="id-picture" alt="ID Picture">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9 info-col">
                                <p><span class="resume-label">Name:</span> <?= htmlspecialchars(ucfirst(strtolower($profiling_data['personal']['last_name'])) . ', ' . ucfirst(strtolower($profiling_data['personal']['first_name'])) . ' ' . ucfirst(strtolower($profiling_data['personal']['middle_name'] ?? ''))) ?></p>
                                <p><span class="resume-label">Date of Birth:</span> <?= htmlspecialchars($profiling_data['personal']['date_of_birth'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Age:</span> <?= htmlspecialchars($profiling_data['personal']['age'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Sex:</span> <?= htmlspecialchars($profiling_data['personal']['sex'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Contact Number:</span> <?= htmlspecialchars($profiling_data['personal']['contact_number'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Address:</span> <?= htmlspecialchars($profiling_data['personal']['street_purok'] ?? '') ?>, Brgy. <?= htmlspecialchars($profiling_data['personal']['barangay'] ?? '') ?>, <?= htmlspecialchars($profiling_data['personal']['city'] ?? '') ?>, <?= htmlspecialchars($profiling_data['personal']['province'] ?? '') ?>, <?= htmlspecialchars($profiling_data['personal']['region'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>No personal information found.</p>
                    <?php endif; ?>
                </div>

                <!-- Academic Background -->
                <div class="resume-section">
                    <h5><i class="fas fa-graduation-cap me-2"></i>Academic Background</h5>
                    <?php if ($profiling_data['academic']): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><span class="resume-label">Last School Attended:</span> <?= htmlspecialchars($profiling_data['academic']['last_school_attended'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Strand:</span> <?= htmlspecialchars($profiling_data['academic']['strand_name'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Year Graduated:</span> <?= htmlspecialchars($profiling_data['academic']['year_graduated'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="resume-label">G11 1st Sem Avg:</span> <?= htmlspecialchars($profiling_data['academic']['g11_1st_avg'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">G11 2nd Sem Avg:</span> <?= htmlspecialchars($profiling_data['academic']['g11_2nd_avg'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">G12 1st Sem Avg:</span> <?= htmlspecialchars($profiling_data['academic']['g12_1st_avg'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Academic Award:</span> <?= htmlspecialchars($profiling_data['academic']['academic_award'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>No academic background found.</p>
                    <?php endif; ?>
                </div>

                <!-- Program Application -->
                <div class="resume-section">
                    <h5><i class="fas fa-book me-2"></i>Program Application</h5>
                    <?php if ($profiling_data['program']): ?>
                        <p><span class="resume-label">Campus Choice:</span> <?= htmlspecialchars($profiling_data['program']['campus'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">College:</span> <?= htmlspecialchars($profiling_data['program']['college'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">Program Choice:</span> <?= htmlspecialchars($profiling_data['program']['program'] ?? 'N/A') ?></p>
                    <?php else: ?>
                        <p>No program application found.</p>
                    <?php endif; ?>
                </div>

                <!-- Socio-Demographic -->
                <div class="resume-section">
                    <h5><i class="fas fa-users me-2"></i>Socio-Demographic Profile</h5>
                    <?php if ($profiling_data['socio']): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><span class="resume-label">Marital Status:</span> <?= htmlspecialchars($profiling_data['socio']['marital_status'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Religion:</span> <?= htmlspecialchars($profiling_data['socio']['religion'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Orientation:</span> <?= htmlspecialchars($profiling_data['socio']['orientation'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Father Status:</span> <?= htmlspecialchars($profiling_data['socio']['father_status'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Father Education:</span> <?= htmlspecialchars($profiling_data['socio']['father_education'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Father Employment:</span> <?= htmlspecialchars($profiling_data['socio']['father_employment'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="resume-label">Mother Status:</span> <?= htmlspecialchars($profiling_data['socio']['mother_status'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Mother Education:</span> <?= htmlspecialchars($profiling_data['socio']['mother_education'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Mother Employment:</span> <?= htmlspecialchars($profiling_data['socio']['mother_employment'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Siblings:</span> <?= htmlspecialchars($profiling_data['socio']['siblings'] ?? 'N/A') ?></p>
                                <p><span class="resume-label">Living With:</span> <?= htmlspecialchars($profiling_data['socio']['living_with'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        <h5 style="margin-top: 1.5rem;"><i class="fas fa-laptop me-2"></i>Technology Access</h5>
                        <p><span class="resume-label">The student applicant has access to a personal computer at home:</span> <?= htmlspecialchars($profiling_data['socio']['access_computer'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant has internet access at home:</span> <?= htmlspecialchars($profiling_data['socio']['access_internet'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant has access to mobile device(s):</span> <?= htmlspecialchars($profiling_data['socio']['access_mobile'] ?? 'N/A') ?></p>
                        
                        <h5 style="margin-top: 1.5rem;"><i class="fas fa-info-circle me-2"></i>Other Details</h5>
                        <p><span class="resume-label">The student applicant is part of an indigenous group in the Philippines:</span> <?= htmlspecialchars($profiling_data['socio']['indigenous_group'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant is the first in their family to attend college:</span> <?= htmlspecialchars($profiling_data['socio']['first_gen_college'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant was a scholar:</span> <?= htmlspecialchars($profiling_data['socio']['was_scholar'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant received any academic honors in high school:</span> <?= htmlspecialchars($profiling_data['socio']['received_honors'] ?? 'N/A') ?></p>
                        <p><span class="resume-label">The student applicant has a disability:</span> <?= htmlspecialchars($profiling_data['socio']['has_disability'] ?? 'N/A') ?></p>
                        <?php if (!empty($profiling_data['socio']['has_disability']) && $profiling_data['socio']['has_disability'] != 'No' && !empty($profiling_data['socio']['disability_detail'])): ?>
                            <p><span class="resume-label">Disability Detail:</span> <?= htmlspecialchars($profiling_data['socio']['disability_detail']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No socio-demographic information found.</p>
                    <?php endif; ?>
                </div>

                <!-- Documents -->
                <div class="resume-section">
                    <h5><i class="fas fa-file-upload me-2"></i>Uploaded Documents</h5>
                    <?php 
                    $doc_fields = [
                        'g11_1st' => 'G11 1st Sem Report Card',
                        'g11_2nd' => 'G11 2nd Sem Report Card',
                        'g12_1st' => 'G12 1st Sem Report Card',
                        'ncii' => 'NCII Certificate',
                        'guidance_cert' => 'Guidance Certificate',
                        'additional_file' => 'Additional File'
                    ];
                    if ($profiling_data['documents']): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-center">Document</th>
                                    <th class="text-center">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($doc_fields as $field => $label):
                                    // Get file data from documents array if it exists
                                    $file_data = $profiling_data['documents'][$field] ?? null;
                                    
                                    // Handle JSON-encoded file arrays (multiple files) or single filename
                                    $files = [];
                                    if (!empty($file_data)) {
                                        if (is_string($file_data)) {
                                            $decoded = json_decode($file_data, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                // Multiple files stored as JSON array
                                                $files = array_filter($decoded, function($f) { return !empty($f); });
                                            } else {
                                                // Single filename (not JSON)
                                                $files = [$file_data];
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?= htmlspecialchars($label) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($files)): ?>
                                            <button type="button"
                                                class="btn btn-sm btn-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewDocumentModal"
                                                data-files="<?php echo htmlspecialchars(json_encode(array_map(function($f) { return '../uploads/' . $f; }, $files))); ?>"
                                                data-label="<?= htmlspecialchars($label) ?>">
                                                View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No documents uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Document View Modal with Carousel -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #00692a; color: white;">
                <h5 class="modal-title" id="viewDocumentModalLabel">
                    <i class="fas fa-file-alt me-2"></i>View Document
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="documentModalBody" style="padding: 0; margin: 0;">
                <!-- Bootstrap Carousel -->
                <div id="documentCarousel" class="carousel slide">
                    <!-- Carousel Indicators -->
                    <div class="carousel-indicators" id="carouselIndicators">
                        <!-- Indicators will be generated by JavaScript -->
                    </div>
                    
                    <!-- Carousel Inner -->
                    <div class="carousel-inner" id="carouselInner">
                        <!-- Carousel items will be generated by JavaScript -->
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px; padding: 20px;">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carousel Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#documentCarousel" data-bs-slide="prev" id="carouselPrevBtn" style="display: none;">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#documentCarousel" data-bs-slide="next" id="carouselNextBtn" style="display: none;">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer" style="display: none;">
            </div>
        </div>
    </div>
</div>

<style>
  /* Document Modal Styles */
  #viewDocumentModal .modal-dialog {
    max-width: 800px;
  }
  
  #viewDocumentModal .modal-body {
    position: relative;
    padding: 0;
    margin: 0;
  }
  
  /* Carousel Styles */
  #documentCarousel {
    width: 100%;
    position: relative;
  }
  
  #documentCarousel .carousel-inner {
    width: 100%;
    position: relative;
    padding: 0;
    margin: 0;
  }
  
  #documentCarousel .carousel-item {
    text-align: center;
    width: 100%;
    display: flex !important;
    align-items: flex-start !important;
    justify-content: center !important;
    padding: 0;
    margin: 0;
    transition: none !important;
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
  }
  
  #documentCarousel .carousel-item.active {
    position: relative;
    opacity: 1;
    display: flex !important;
  }
  
  /* Remove carousel transition animation */
  #documentCarousel.carousel {
    transition: none !important;
  }
  
  #documentCarousel .carousel-inner {
    transition: none !important;
  }
  
  #documentCarousel .carousel-item-next:not(.carousel-item-start),
  #documentCarousel .active.carousel-item-end,
  #documentCarousel .carousel-item-prev:not(.carousel-item-end),
  #documentCarousel .active.carousel-item-start {
    transform: translateX(0) !important;
    transition: none !important;
  }
  
  #documentCarousel .carousel-item-next,
  #documentCarousel .carousel-item-prev,
  #documentCarousel .carousel-item.active {
    transform: translateX(0) !important;
    transition: none !important;
  }
  
  #documentCarousel .carousel-item img {
    width: 100%;
    max-width: 100%;
    height: auto;
    max-height: 80vh;
    object-fit: contain;
    margin: 0;
    padding: 0;
    display: block;
  }
  
  #documentCarousel .carousel-item embed {
    width: 100%;
    max-width: 100%;
    height: auto;
    max-height: 80vh;
    min-height: 400px;
    margin: 0;
    padding: 0;
    display: block;
  }
  
  /* Mobile responsive styles */
  @media (max-width: 768px) {
    #viewDocumentModal .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem);
    }
    #viewDocumentModal .modal-content {
      border-radius: 0.5rem;
    }
    #documentCarousel .carousel-item img {
      max-height: 70vh;
    }
  }
  
  @media (max-width: 576px) {
    #viewDocumentModal .modal-dialog {
      margin: 0.25rem;
      max-width: calc(100% - 0.5rem);
    }
    #documentCarousel .carousel-item img {
      max-height: 65vh;
    }
  }
  
  /* Carousel Controls */
  #documentCarousel .carousel-control-prev,
  #documentCarousel .carousel-control-next {
    width: 50px;
    height: 50px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.8;
    transition: opacity 0.3s ease;
    z-index: 10;
    background-color: transparent;
    border: none;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  #documentCarousel .carousel-control-prev:hover,
  #documentCarousel .carousel-control-next:hover {
    opacity: 1;
  }
  
  #documentCarousel .carousel-control-prev {
    left: 15px;
  }
  
  #documentCarousel .carousel-control-next {
    right: 15px;
  }
  
  #documentCarousel .carousel-control-prev-icon,
  #documentCarousel .carousel-control-next-icon {
    background-color: #00692a;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    background-size: 60%;
    opacity: 1;
  }
  
  /* Carousel Indicators */
  #documentCarousel .carousel-indicators {
    margin-bottom: 15px;
  }
  
  #documentCarousel .carousel-indicators button {
    background-color: #00692a;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin: 0 4px;
    opacity: 0.5;
    transition: all 0.3s ease;
  }
  
  #documentCarousel .carousel-indicators button.active {
    opacity: 1;
    transform: scale(1.2);
  }
  
  #documentCarousel .carousel-indicators button:hover {
    opacity: 0.8;
  }
</style>

<script>
// Document View Modal with Bootstrap Carousel functionality
var documentCarousel = null;

document.addEventListener('DOMContentLoaded', function() {
    const viewDocumentModal = document.getElementById('viewDocumentModal');
    const profilingModal = document.getElementById('profilingModal');
    
    if (viewDocumentModal) {
        viewDocumentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) {
                console.error('Modal opened without related target');
                return;
            }
            
            const filesData = button.getAttribute('data-files');
            const singleFile = button.getAttribute('data-file');
            const label = button.getAttribute('data-label') || 'Document';
            const modalTitle = viewDocumentModal.querySelector('.modal-title');
            const carouselInner = document.getElementById('carouselInner');
            const carouselIndicators = document.getElementById('carouselIndicators');
            const carouselPrevBtn = document.getElementById('carouselPrevBtn');
            const carouselNextBtn = document.getElementById('carouselNextBtn');
            
            // Parse files - can be single file or JSON array
            let files = [];
            if (filesData) {
                try {
                    const parsed = JSON.parse(filesData);
                    if (Array.isArray(parsed)) {
                        files = parsed;
                    } else if (typeof parsed === 'string') {
                        files = [parsed];
                    } else {
                        files = [];
                    }
                } catch(e) {
                    console.warn('Failed to parse filesData as JSON, treating as string:', e);
                    if (filesData.trim() !== '') {
                        files = [filesData];
                    }
                }
            } else if (singleFile) {
                files = [singleFile];
            }
            
            // Filter out empty file paths
            files = files.filter(function(file) {
                return file && file.trim() !== '';
            });
            
            // Update modal title
            modalTitle.innerHTML = '<i class="fas fa-file-alt me-2"></i>View: ' + label;
            
            // Clear existing carousel content
            carouselInner.innerHTML = '';
            carouselIndicators.innerHTML = '';
            
            if (files.length === 0) {
                carouselInner.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No files to display. The document may not have been uploaded correctly.</div>';
                carouselPrevBtn.style.display = 'none';
                carouselNextBtn.style.display = 'none';
                return;
            }
            
            // Show/hide navigation buttons and indicators
            if (files.length > 1) {
                carouselPrevBtn.style.display = 'block';
                carouselNextBtn.style.display = 'block';
            } else {
                carouselPrevBtn.style.display = 'none';
                carouselNextBtn.style.display = 'none';
            }
            
            // Initialize or refresh Bootstrap carousel
            const carouselElement = document.getElementById('documentCarousel');
            if (documentCarousel) {
                const bsCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
            }
            
            // Preload all images
            const preloadImages = [];
            files.forEach(function(file) {
                const filePath = file.split('?')[0];
                const ext = filePath.split('.').pop().toLowerCase();
                if(['jpg','jpeg','png','gif','bmp','webp','svg'].includes(ext)) {
                    const preloadImg = new Image();
                    preloadImg.src = file;
                    preloadImages.push(preloadImg);
                }
            });
            
            // Create carousel items and indicators
            files.forEach(function(file, index) {
                // Improved file extension extraction
                let ext = '';
                const filePath = file.split('?')[0]; // Remove query parameters
                const parts = filePath.split('.');
                if (parts.length > 1) {
                    ext = parts.pop().toLowerCase().trim();
                }
                
                // Create indicator
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#documentCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.setAttribute('aria-label', 'Slide ' + (index + 1));
                if (index === 0) {
                    indicator.classList.add('active');
                    indicator.setAttribute('aria-current', 'true');
                }
                carouselIndicators.appendChild(indicator);
                
                // Create carousel item
                const carouselItem = document.createElement('div');
                carouselItem.className = 'carousel-item' + (index === 0 ? ' active' : '');
                
                // Load content - try image first if extension suggests it, or if no extension, try as image
                const imageExtensions = ['jpg','jpeg','png','gif','bmp','webp','svg'];
                const isImage = ext && imageExtensions.includes(ext);
                const isPdf = ext === 'pdf';
                
                // If no extension or unknown extension, try to load as image first (browser will handle it)
                if (isImage || (!ext && !isPdf)) {
                    const img = document.createElement('img');
                    img.className = 'img-fluid';
                    img.alt = 'Document Image';
                    img.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; object-fit: contain; margin: 0; padding: 0; display: block;';
                    
                    const preloadedImg = preloadImages[index];
                    if (preloadedImg && preloadedImg.complete) {
                        carouselItem.appendChild(img);
                        img.src = file;
                    } else {
                        carouselItem.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="width: 100%; min-height: 200px; padding: 20px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        img.onload = function() {
                            carouselItem.innerHTML = '';
                            carouselItem.appendChild(img);
                        };
                        
                        img.onerror = function() {
                            carouselItem.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load image. <a href="' + file + '" target="_blank" class="btn btn-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                        };
                        
                        img.src = file;
                    }
                    carouselInner.appendChild(carouselItem);
                } else if (isPdf) {
                    const embed = document.createElement('embed');
                    embed.src = file;
                    embed.type = 'application/pdf';
                    embed.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; min-height: 400px; margin: 0; padding: 0; display: block;';
                    carouselItem.appendChild(embed);
                    carouselInner.appendChild(carouselItem);
                } else {
                    // For unknown file types, try to load as iframe or provide download link
                    // First, try to detect if it might be an image by checking the file path
                    const lowerFile = file.toLowerCase();
                    if (lowerFile.includes('image') || lowerFile.includes('photo') || lowerFile.includes('picture') || lowerFile.includes('img')) {
                        // Try loading as image even without extension
                        const img = document.createElement('img');
                        img.className = 'img-fluid';
                        img.alt = 'Document Image';
                        img.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; object-fit: contain; margin: 0; padding: 0; display: block;';
                        carouselItem.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="width: 100%; min-height: 200px; padding: 20px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        img.onload = function() {
                            carouselItem.innerHTML = '';
                            carouselItem.appendChild(img);
                        };
                        
                        img.onerror = function() {
                            carouselItem.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Preview not available for this file type. <a href="' + file + '" target="_blank" class="btn btn-success ms-2" download><i class="fas fa-download me-1"></i>Download File</a></div>';
                        };
                        
                        img.src = file;
                        carouselInner.appendChild(carouselItem);
                    } else {
                        // For other file types, provide download option
                        carouselItem.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Preview not available for this file type. <a href="' + file + '" target="_blank" class="btn btn-success ms-2" download><i class="fas fa-download me-1"></i>Download File</a> or <a href="' + file + '" target="_blank" class="btn btn-outline-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                        carouselInner.appendChild(carouselItem);
                    }
                }
            });
            
            // Initialize carousel after items are added
            documentCarousel = new bootstrap.Carousel(carouselElement, {
                interval: false,
                wrap: false,
                ride: false
            });
        });
        
        // Clean up carousel instance when modal is hidden
        viewDocumentModal.addEventListener('hidden.bs.modal', function() {
            if (documentCarousel) {
                const carouselElement = document.getElementById('documentCarousel');
                const bsCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
                documentCarousel = null;
            }
            
            // When document modal is hidden, reopen profiling modal (preserve original behavior)
            if (profilingModal) {
                setTimeout(function() {
                    const bsProfilingModal = new bootstrap.Modal(profilingModal);
                    bsProfilingModal.show();
                }, 100);
            }
        });
    }
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-calculate total score
document.addEventListener('DOMContentLoaded', function() {
    const radioInputs = document.querySelectorAll('input[type="radio"]');
    const readingInput = document.querySelector('input[name="reading_score"]');
    const writingInput = document.querySelector('input[name="writing_score"]');
    const totalScoreInput = document.getElementById('totalScore');
    const finalScoreInput = document.getElementById('finalScore');
    
    function calculateScores() {
        let section1Total = 0;
        let section2Total = 0;
        let section3Total = 0;
        let writingScore = parseInt(writingInput.value) || 0;
        let readingScore = parseInt(readingInput.value) || 0;
        
        // Calculate Section 1: Preparedness (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section1_item${i}"]:checked`);
            if (checked) {
                section1Total += parseInt(checked.value);
            }
        }
        
        // Calculate Section 2: Communication Skills (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section2_item${i}"]:checked`);
            if (checked) {
                section2Total += parseInt(checked.value);
            }
        }
        
        // Calculate Section 3: Personal/Physical/Social Traits (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section3_item${i}"]:checked`);
            if (checked) {
                section3Total += parseInt(checked.value);
            }
        }
        
        // Total Score (out of 100)
        const totalScore = section1Total + section2Total + section3Total + writingScore + readingScore;
        
        // Final Interview Score = (TS / 100) × 50 + 50
        const finalScore = ((totalScore / 100) * 50) + 50;
        
        // Update display
        totalScoreInput.value = totalScore;
        finalScoreInput.value = finalScore.toFixed(2);
    }
    
    // Add event listeners
    radioInputs.forEach(input => {
        input.addEventListener('change', calculateScores);
    });
    
    readingInput.addEventListener('input', function() {
        // Limit to 2 digits only
        if (this.value.length > 2) {
            this.value = this.value.slice(0, 2);
        }
        // Ensure value is within range
        if (parseInt(this.value) > 20) {
            this.value = 20;
        }
        calculateScores();
    });
    
    writingInput.addEventListener('input', function() {
        // Limit to 2 digits only
        if (this.value.length > 2) {
            this.value = this.value.slice(0, 2);
        }
        // Ensure value is within range
        if (parseInt(this.value) > 20) {
            this.value = 20;
        }
        calculateScores();
    });
    
    // Initial calculation
    calculateScores();
    
    // Show toast notifications if they exist
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
    
    // Function to show error toast
    function showErrorToast(message) {
        let toastContainer = document.querySelector('.toast-container');
        
        // Create toast container if it doesn't exist
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1060';
            document.body.appendChild(toastContainer);
        }
        
        const errorToast = document.createElement('div');
        errorToast.className = 'toast align-items-center text-bg-danger border-0';
        errorToast.setAttribute('role', 'alert');
        errorToast.setAttribute('aria-live', 'assertive');
        errorToast.setAttribute('aria-atomic', 'true');
        errorToast.setAttribute('data-bs-delay', '5000');
        
        errorToast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(errorToast);
        const toast = new bootstrap.Toast(errorToast);
        toast.show();
        
        // Remove the toast element after it's hidden
        errorToast.addEventListener('hidden.bs.toast', function() {
            errorToast.remove();
        });
    }
    
    // Add confirmation modal for form submission
    const submitBtn = document.getElementById('submitBtn');
    const interviewForm = document.getElementById('interviewForm');
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    
    submitBtn.addEventListener('click', function() {
        // Check if all required fields are filled
        const allRadios = document.querySelectorAll('input[type="radio"]:required');
        const readingScore = document.querySelector('input[name="reading_score"]');
        
        let allFilled = true;
        allRadios.forEach(radio => {
            const name = radio.name;
            const checked = document.querySelector(`input[name="${name}"]:checked`);
            if (!checked) {
                allFilled = false;
            }
        });
        
        if (!readingScore.value || readingScore.value < 0 || readingScore.value > 20) {
            allFilled = false;
        }
        
        const writingScore = document.querySelector('input[name="writing_score"]');
        if (!writingScore.value || writingScore.value < 0 || writingScore.value > 20) {
            allFilled = false;
        }
        
        if (!allFilled) {
            showErrorToast('Please complete all evaluation fields before submitting.');
            return;
        }
        
        // Get the calculated scores for confirmation
        const totalScore = document.getElementById('totalScore').value;
        const finalScore = document.getElementById('finalScore').value;
        
        // Update modal with current scores
        document.getElementById('modalTotalScore').textContent = totalScore;
        document.getElementById('modalFinalScore').textContent = finalScore;
        
        // Show the confirmation modal
        confirmationModal.show();
    });
    
    // Handle confirmation modal submit
    confirmSubmitBtn.addEventListener('click', function() {
        // Create a hidden submit button and trigger it
        const submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'submit_interview';
        submitInput.value = '1';
        interviewForm.appendChild(submitInput);
        interviewForm.submit();
    });
    
    // Print evaluation function
    window.printEvaluation = function() {
        // Get applicant information
        const applicantName = document.querySelector('.applicant-info h5')?.textContent?.trim() || 'Applicant';
        const registrationId = document.querySelector('.applicant-info p strong')?.textContent || '';
        const totalScore = document.getElementById('modalTotalScore')?.textContent || '0';
        const finalScore = document.getElementById('modalFinalScore')?.textContent || '0';
        const today = new Date();
        const interviewDate = today.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        
        // Get all section scores
        const getSectionScores = function(sectionNum, itemCount) {
            let scores = [];
            for (let i = 0; i < itemCount; i++) {
                const checked = document.querySelector(`input[name="section${sectionNum}_item${i}"]:checked`);
                scores.push(checked ? checked.value : '');
            }
            return scores;
        };
        
        const section1Scores = getSectionScores(1, 4);
        const section2Scores = getSectionScores(2, 4);
        const section3Scores = getSectionScores(3, 4);
        const writingScore = document.querySelector('input[name="writing_score"]')?.value || '';
        const readingScore = document.querySelector('input[name="reading_score"]')?.value || '';
        
        // Get applicant data from profiling modal if available
        const applicantData = {
            name: applicantName,
            registrationId: registrationId,
            dateOfBirth: '',
            age: '',
            sex: '',
            address: '',
            contact: '',
            school: '',
            strand: '',
            yearGraduated: '',
            program: '',
            campus: ''
        };
        
        // Try to get data from profiling modal
        const profilingModal = document.getElementById('profilingModal');
        if (profilingModal) {
            const personalInfo = profilingModal.querySelector('.card-body');
            if (personalInfo) {
                const nameMatch = personalInfo.textContent.match(/Name:\s*([^\n]+)/);
                const dobMatch = personalInfo.textContent.match(/Date of Birth:\s*([^\n]+)/);
                const ageMatch = personalInfo.textContent.match(/Age:\s*([^\n]+)/);
                const sexMatch = personalInfo.textContent.match(/Sex:\s*([^\n]+)/);
                const contactMatch = personalInfo.textContent.match(/Contact Number:\s*([^\n]+)/);
                const addressMatch = personalInfo.textContent.match(/Address:\s*([^\n]+)/);
                
                if (nameMatch) applicantData.name = nameMatch[1].trim();
                if (dobMatch) applicantData.dateOfBirth = dobMatch[1].trim();
                if (ageMatch) applicantData.age = ageMatch[1].trim();
                if (sexMatch) applicantData.sex = sexMatch[1].trim();
                if (contactMatch) applicantData.contact = contactMatch[1].trim();
                if (addressMatch) applicantData.address = addressMatch[1].trim();
            }
            
            const academicInfo = profilingModal.querySelectorAll('.card-body')[1];
            if (academicInfo) {
                const schoolMatch = academicInfo.textContent.match(/Last School Attended:\s*([^\n]+)/);
                const strandMatch = academicInfo.textContent.match(/Strand:\s*([^\n]+)/);
                const yearMatch = academicInfo.textContent.match(/Year Graduated:\s*([^\n]+)/);
                
                if (schoolMatch) applicantData.school = schoolMatch[1].trim();
                if (strandMatch) applicantData.strand = strandMatch[1].trim();
                if (yearMatch) applicantData.yearGraduated = yearMatch[1].trim();
            }
            
            const programInfo = profilingModal.querySelectorAll('.card-body')[2];
            if (programInfo) {
                const programMatch = programInfo.textContent.match(/Program:\s*([^\n]+)/);
                const campusMatch = programInfo.textContent.match(/Campus:\s*([^\n]+)/);
                
                if (programMatch) applicantData.program = programMatch[1].trim();
                if (campusMatch) applicantData.campus = campusMatch[1].trim();
            }
        }
        
        // Parse name
        const nameParts = applicantData.name.split(',').map(s => s.trim());
        const lastName = nameParts[0] || '';
        const firstMiddle = nameParts[1] || applicantData.name;
        const firstMiddleParts = firstMiddle.split(' ');
        const firstName = firstMiddleParts[0] || '';
        const middleName = firstMiddleParts.slice(1).join(' ') || '';
        
        // Create print window content matching the form layout
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>INTERVIEW FORM - ${applicantData.name}</title>
                <style>
                    @page { size: letter; margin: 0.5in; }
                    body { 
                        font-family: Arial, sans-serif; 
                        font-size: 11pt;
                        line-height: 1.3;
                        margin: 0;
                        padding: 0;
                    }
                    .form-container {
                        width: 100%;
                        max-width: 8.5in;
                        margin: 0 auto;
                        position: relative;
                    }
                    .header-section {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                        margin-bottom: 15px;
                    }
                    .logo-section {
                        width: 80px;
                        height: 80px;
                        border: 2px solid #000;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background-color: rgb(0, 105, 42);
                        color: white;
                        font-weight: bold;
                        font-size: 10pt;
                        text-align: center;
                        padding: 5px;
                    }
                    .title-section {
                        text-align: center;
                        flex-grow: 1;
                        margin: 0 20px;
                    }
                    .title-section h1 {
                        font-size: 18pt;
                        font-weight: bold;
                        margin: 0;
                        letter-spacing: 2px;
                    }
                    .doc-info {
                        border: 1px solid #000;
                        padding: 8px;
                        font-size: 9pt;
                        width: 180px;
                    }
                    .doc-info p {
                        margin: 2px 0;
                        line-height: 1.2;
                    }
                    .section-title {
                        font-weight: bold;
                        font-size: 12pt;
                        margin: 15px 0 10px 0;
                        text-decoration: underline;
                    }
                    .form-field {
                        margin-bottom: 8px;
                        display: flex;
                        align-items: baseline;
                    }
                    .form-label {
                        font-weight: bold;
                        min-width: 150px;
                        display: inline-block;
                    }
                    .form-line {
                        border-bottom: 1px solid #000;
                        flex-grow: 1;
                        min-height: 18px;
                        margin-left: 10px;
                        display: inline-block;
                    }
                    .name-group {
                        display: flex;
                        gap: 20px;
                    }
                    .name-field {
                        flex: 1;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 10px 0;
                        font-size: 10pt;
                    }
                    table th, table td {
                        border: 1px solid #000;
                        padding: 6px;
                        text-align: left;
                    }
                    table th {
                        background-color: rgb(0, 105, 42);
                        color: white;
                        font-weight: bold;
                        text-align: center;
                    }
                    .score-header {
                        text-align: center;
                        font-weight: bold;
                    }
                    .score-cell {
                        text-align: center;
                        width: 60px;
                    }
                    .checkbox-section {
                        margin: 15px 0;
                    }
                    .checkbox-item {
                        margin: 5px 0;
                        display: flex;
                        align-items: center;
                    }
                    .checkbox-item input[type="checkbox"] {
                        margin-right: 8px;
                        width: 15px;
                        height: 15px;
                    }
                    .signature-section {
                        display: flex;
                        justify-content: space-between;
                        margin-top: 30px;
                    }
                    .signature-box {
                        width: 45%;
                    }
                    .signature-line {
                        border-bottom: 1px solid #000;
                        margin-top: 40px;
                        min-height: 20px;
                    }
                    .status-stamp {
                        position: absolute;
                        bottom: 20px;
                        right: 20px;
                        border: 2px double #000;
                        padding: 10px;
                        text-align: center;
                        width: 120px;
                    }
                    .status-stamp .status-label {
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .status-stamp .stamp-circle {
                        border: 2px solid #000;
                        border-radius: 50%;
                        width: 80px;
                        height: 80px;
                        margin: 0 auto;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 8pt;
                        text-align: center;
                        padding: 5px;
                    }
                    @media print {
                        body { margin: 0; padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="form-container">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="logo-section">CHMSU</div>
                        <div class="title-section">
                            <h1>INTERVIEW FORM</h1>
                        </div>
                        <div class="doc-info">
                            <p><strong>Document Code:</strong> F.01-PC-CHMSU</p>
                            <p><strong>Revision No.:</strong> 0</p>
                            <p><strong>Effective Date:</strong> April 26, 2024</p>
                            <p><strong>Page:</strong> 1 of 1</p>
                        </div>
                    </div>
                    
                    <!-- Applicant's Section -->
                    <div class="section-title">APPLICANT'S SECTION</div>
                    <div class="form-field">
                        <span class="form-label">Date of Interview:</span>
                        <span class="form-line">${interviewDate}</span>
                    </div>
                    <div class="form-field name-group">
                        <div class="name-field">
                            <span class="form-label">NAME:</span>
                        </div>
                    </div>
                    <div class="form-field name-group">
                        <div class="name-field">
                            <span class="form-label">Last Name:</span>
                            <span class="form-line">${lastName}</span>
                        </div>
                        <div class="name-field">
                            <span class="form-label">First Name:</span>
                            <span class="form-line">${firstName}</span>
                        </div>
                        <div class="name-field">
                            <span class="form-label">Middle Name:</span>
                            <span class="form-line">${middleName}</span>
                        </div>
                    </div>
                    <div class="form-field name-group">
                        <div class="name-field">
                            <span class="form-label">AGE:</span>
                            <span class="form-line">${applicantData.age || '_____'}</span>
                        </div>
                        <div class="name-field">
                            <span class="form-label">SEX:</span>
                            <span class="form-line">${applicantData.sex || '_____'}</span>
                        </div>
                    </div>
                    <div class="form-field">
                        <span class="form-label">PERMANENT ADDRESS:</span>
                        <span class="form-line">${applicantData.address || '________________________________________________________________'}</span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">CONTACT NO:</span>
                        <span class="form-line">${applicantData.contact || '_____'}</span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">SCHOOL LAST ATTENDED/ADDRESS:</span>
                        <span class="form-line">${applicantData.school || '________________________________________________________________'}</span>
                    </div>
                    <div class="form-field name-group">
                        <div class="name-field">
                            <span class="form-label">SHS STRAND/DEGREE PROGRAM:</span>
                            <span class="form-line">${applicantData.strand || '_____'}</span>
                        </div>
                        <div class="name-field">
                            <span class="form-label">YEAR GRADUATED/LAST ATTENDED:</span>
                            <span class="form-line">${applicantData.yearGraduated || '_____'}</span>
                        </div>
                    </div>
                    <div class="form-field name-group">
                        <div class="name-field">
                            <span class="form-label">PROGRAM APPLIED:</span>
                            <span class="form-line">${applicantData.program || '_____'}</span>
                        </div>
                        <div class="name-field">
                            <span class="form-label">CAMPUS:</span>
                            <span class="form-line">${applicantData.campus || '_____'}</span>
                        </div>
                    </div>
                    
                    <!-- Interviewer's Section -->
                    <div class="section-title" style="margin-top: 20px;">INTERVIEWER'S SECTION</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">INDICATORS</th>
                                <th class="score-header">Excellent<br>5</th>
                                <th class="score-header">Above Average<br>4</th>
                                <th class="score-header">Average<br>3</th>
                                <th class="score-header">Below Average<br>2</th>
                                <th class="score-header">Poor<br>1</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th colspan="6" style="background-color: rgb(0, 105, 42); color: white;">I. PREPAREDNESS FOR COLLEGE EDUCATION (20 points)</th>
                            </tr>
                            <tr>
                                <td>1. Foundation in math, science, and other requisite skills/knowledge</td>
                                <td class="score-cell">${section1Scores[0] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[0] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[0] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[0] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[0] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>2. Study habits (as discussed during the interview)</td>
                                <td class="score-cell">${section1Scores[1] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[1] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[1] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[1] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[1] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>3. Display of interest in the applied program</td>
                                <td class="score-cell">${section1Scores[2] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[2] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[2] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[2] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[2] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>4. Academic and extra-curricular achievements/awards</td>
                                <td class="score-cell">${section1Scores[3] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[3] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[3] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[3] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section1Scores[3] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <th colspan="6" style="background-color: rgb(0, 105, 42); color: white;">II. ORAL COMMUNICATION SKILLS (20 points)</th>
                            </tr>
                            <tr>
                                <td>1. Content of the responses to interview questions</td>
                                <td class="score-cell">${section2Scores[0] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[0] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[0] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[0] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[0] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>2. Manner and delivery of the responses</td>
                                <td class="score-cell">${section2Scores[1] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[1] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[1] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[1] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[1] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>3. Mechanics, use of words/terms, grammar, and pronunciation</td>
                                <td class="score-cell">${section2Scores[2] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[2] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[2] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[2] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[2] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>4. Gestures and facial expressions</td>
                                <td class="score-cell">${section2Scores[3] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[3] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[3] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[3] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section2Scores[3] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <th colspan="6" style="background-color: rgb(0, 105, 42); color: white;">III. PERSONAL/PHYSICAL/SOCIAL TRAITS (20 points)</th>
                            </tr>
                            <tr>
                                <td>1. Personal traits (professionalism, confidence, enthusiasm)</td>
                                <td class="score-cell">${section3Scores[0] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[0] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[0] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[0] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[0] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>2. Social traits (courtesy, attentiveness, rapport with interviewer)</td>
                                <td class="score-cell">${section3Scores[1] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[1] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[1] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[1] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[1] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>3. Physical appearance (hygiene, grooming, dress/clothes)</td>
                                <td class="score-cell">${section3Scores[2] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[2] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[2] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[2] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[2] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <td>4. Body language, eye contact, and posture</td>
                                <td class="score-cell">${section3Scores[3] == 5 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[3] == 4 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[3] == 3 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[3] == 2 ? '✓' : ''}</td>
                                <td class="score-cell">${section3Scores[3] == 1 ? '✓' : ''}</td>
                            </tr>
                            <tr>
                                <th colspan="6" style="background-color: rgb(0, 105, 42); color: white;">IV. WRITING SKILLS (20 points)</th>
                            </tr>
                            <tr>
                                <td>1. Score on Writing Skills Test</td>
                                <td colspan="5" style="text-align: center; font-weight: bold;">${writingScore || '_____'}</td>
                            </tr>
                            <tr>
                                <th colspan="6" style="background-color: rgb(0, 105, 42); color: white;">V. READING AND COMPREHENSION (20 points)</th>
                            </tr>
                            <tr>
                                <td>1. Score on Reading and Comprehension Test</td>
                                <td colspan="5" style="text-align: center; font-weight: bold;">${readingScore || '_____'}</td>
                            </tr>
                            <tr style="background-color: #f0f0f0;">
                                <td colspan="5" style="font-weight: bold;">TOTAL SCORE (TS):</td>
                                <td style="text-align: center; font-weight: bold;">${totalScore}</td>
                            </tr>
                            <tr style="background-color: #e0e0e0;">
                                <td colspan="5" style="font-weight: bold;">INTERVIEW SCORE = (TS / 100) × 50 + 50 =</td>
                                <td style="text-align: center; font-weight: bold;">${finalScore}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Special Applicant Categories -->
                    <div class="checkbox-section">
                        <p style="font-weight: bold; margin-bottom: 10px;">Please check (if applicable to the applicant):</p>
                        <div class="checkbox-item">
                            <input type="checkbox" disabled>
                            <span>Member of the Indigenous People (IP)</span>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" disabled>
                            <span>Person with Disability (PWD)</span>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" disabled>
                            <span>First Member in the Family to enter college education (First Gen)</span>
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="signature-box">
                            <p><strong>Interviewed by:</strong></p>
                            <div class="signature-line"></div>
                            <p style="margin-top: 5px;">Printed Name and Signature</p>
                        </div>
                        <div class="signature-box">
                            <p><strong>Computed by:</strong></p>
                            <div class="signature-line"></div>
                            <p style="margin-top: 5px;">Printed Name and Signature</p>
                        </div>
                    </div>
                    
                    <!-- Status Stamp -->
                    <div class="status-stamp">
                        <div class="status-label">STATUS</div>
                        <div class="stamp-circle">
                            CONTROLLED<br>
                            CARLOS HILADO<br>
                            MEMORIAL STATE<br>
                            UNIVERSITY<br>
                            TALISAY CITY,<br>
                            NEGROS OCCIDENTAL
                        </div>
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };
});
</script>
</body>
</html>
