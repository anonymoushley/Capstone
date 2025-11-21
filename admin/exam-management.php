<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in as chairperson
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'chairperson') {
    echo "<div class='alert alert-danger'>Access denied. Please login as chairperson.</div>";
    exit;
}

// Connect to DB
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admission";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get chairperson's assigned campus and program from session
$chair_program = $_SESSION['program'] ?? '';
$chair_campus = $_SESSION['campus'] ?? '';

// Safety fallback if session variables are not set
if (empty($chair_program) || empty($chair_campus)) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}


$version_id = isset($_GET['version_id']) ? intval($_GET['version_id']) : 0;

// Fetch the exam version name
// Validate that this exam version is assigned to at least one applicant under this chairperson
$version_query = $conn->prepare("
    SELECT ev.* 
    FROM exam_versions ev
    JOIN chairperson_accounts ca ON ev.chair_id = ca.id
    WHERE ev.id = ? 
      AND ev.is_archived = 0 
      AND ca.program = ? 
      AND ca.campus = ?
    LIMIT 1
");

$version_query->bind_param("iss", $version_id, $chair_program, $chair_campus);
$version_query->execute();
$version_result = $version_query->get_result();
$version = $version_result->fetch_assoc();


if (!$version) {
    die("<div class='alert alert-danger'>You are not authorized to view or edit this exam version.</div>");
}

if (!$version) {
    die("<div class='alert alert-danger'>Invalid or archived exam version</div>");
}

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/exam_images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle saving exam information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_exam'])) {
    $exam_title = $_POST['exam_title'];
    $time_limit = intval($_POST['time_limit']);
    $instructions = $_POST['instructions'];
    
    // Validate inputs
    if (empty($exam_title)) {
        $error_message = "Exam title is required.";
    } elseif ($time_limit < 1) {
        $error_message = "Time limit must be at least 1 minute.";
    } else {
        // Update exam version information
        $update_stmt = $conn->prepare("UPDATE exam_versions SET version_name = ?, time_limit = ?, instructions = ? WHERE id = ?");
        $update_stmt->bind_param("sisi", $exam_title, $time_limit, $instructions, $version_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Exam information saved successfully!";
        } else {
            $error_message = "Error saving exam information: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Handle saving questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_questions'])) {
    $success_count = 0;
    $error_count = 0;
    
    // First, update exam title and instructions
    $exam_title = $_POST['exam_title'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    
    if (!empty($exam_title)) {
        // Get current exam data to preserve published_at and chair_id
        $current_exam = $conn->query("SELECT published_at, chair_id FROM exam_versions WHERE id = $version_id")->fetch_assoc();
        
        $update_exam_stmt = $conn->prepare("UPDATE exam_versions SET version_name = ?, instructions = ?, published_at = ?, chair_id = ? WHERE id = ?");
        $update_exam_stmt->bind_param("sssii", $exam_title, $instructions, $current_exam['published_at'], $current_exam['chair_id'], $version_id);
        $update_exam_stmt->execute();
        $update_exam_stmt->close();
    }
    
    // Get all question data from POST (nested array)
    $questions = [];
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
        foreach ($_POST['questions'] as $q) {
            if (!empty($q['question_text'])) {
                $questions[] = [
                    'id' => $q['id'] ?? null,
                    'text' => $q['question_text'],
                    'type' => $q['question_type'],
                    'option_a' => $q['option_a'] ?? '',
                    'option_b' => $q['option_b'] ?? '',
                    'option_c' => $q['option_c'] ?? '',
                    'option_d' => $q['option_d'] ?? '',
                    'answer' => $q['answer'] ?? '',
                    'points' => intval($q['points'] ?? 1)
                ];
            }
        }
    }
    
    // Validate that there is at least one question
    if (empty($questions)) {
        $error_message = "Please add at least one question.";
    } else {
        // Process each question
        foreach ($questions as $question) {
            // Handle image upload if present
            $image_url = null;
            if (isset($_FILES['question_image_' . $question['id']]) && $_FILES['question_image_' . $question['id']]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/exam_images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['question_image_' . $question['id']]['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error_message = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                    continue;
                }
                
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['question_image_' . $question['id']]['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/exam_images/' . $new_filename;
                } else {
                    $error_message = "Error uploading image.";
                    continue;
                }
            }
            
            if ($question['id']) {
                // Update existing question
                $stmt = $conn->prepare("UPDATE questions SET 
                    question_type = ?, 
                    question_text = ?, 
                    option_a = ?, 
                    option_b = ?, 
                    option_c = ?, 
                    option_d = ?, 
                    answer = ?, 
                    points = ?,
                    image_url = ?
                    WHERE id = ? AND version_id = ?");
                $stmt->bind_param("sssssssissi", 
                    $question['type'],
                    $question['text'],
                    $question['option_a'],
                    $question['option_b'],
                    $question['option_c'],
                    $question['option_d'],
                    $question['answer'],
                    $question['points'],
                    $image_url,
                    $question['id'],
                    $version_id
                );
            } else {
                // Insert new question
                $stmt = $conn->prepare("INSERT INTO questions 
                    (version_id, question_type, question_text, option_a, option_b, option_c, option_d, answer, points, image_url) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssis", 
                    $version_id,
                    $question['type'],
                    $question['text'],
                    $question['option_a'],
                    $question['option_b'],
                    $question['option_c'],
                    $question['option_d'],
                    $question['answer'],
                    $question['points'],
                    $image_url
                );
            }
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        
        if ($success_count > 0) {
            $success_message = "Successfully saved exam information and $success_count questions" . 
                             ($error_count > 0 ? " ($error_count failed)" : "") . "!";
        } else {
            $error_message = "Failed to save any questions.";
        }
    }
}

// Store messages in session for toast display
if (isset($error_message)) {
    $_SESSION['error_message'] = $error_message;
}
if (isset($success_message)) {
    $_SESSION['success_message'] = $success_message;
}

// Clean up session messages after displaying them
if (isset($_SESSION['error_message']) || isset($_SESSION['success_message'])) {
    // Messages will be displayed and then cleared by JavaScript
}

// Handle question deletion
if (isset($_POST['delete_question'])) {
  $question_id = intval($_POST['question_id']);
  
  if ($question_id > 0) {
      // Prepare and execute delete statement
      $delete_stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND version_id = ?");
      $delete_stmt->bind_param("ii", $question_id, $version_id);
      
      if ($delete_stmt->execute()) {
          $_SESSION['success_message'] = "Question deleted successfully!";
      } else {
          $_SESSION['error_message'] = "Error deleting question: " . $conn->error;
      }
      
      $delete_stmt->close();
      
      // Redirect to show the message
      header("Location: exam-management.php?version_id=" . $version_id);
      exit;
  }
}

// Fetch questions for this version
$questions = $conn->query("SELECT * FROM questions WHERE version_id = $version_id ORDER BY id ASC");

// Fetch general exam information
$exam_info = $conn->query("SELECT * FROM exam_versions WHERE id = $version_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exam Builder - <?= htmlspecialchars($version['version_name']) ?></title>
  <link rel="icon" type="image/png" href="images/chmsu.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { 
      background-color: #f8f9fa; 
      padding-bottom: 2rem;
    }
    .overlay {
      background-color: rgba(255, 255, 255, 0.8);
      min-height: 100vh;
      padding-top: 100px;
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
    .question-card {
      margin-bottom: 10px;
      border: 1px solid #c8e6c9;
      padding: 12px;
      border-radius: 8px;
      background-color: #f9fff9;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      width:60%;
      margin-left: auto;
      margin-right: auto;
      border-left: 5px solid #198754;
    }
    .general-info-card {
      margin-bottom: 10px;
      border: 2px rgb(0, 105, 42);
      padding: 12px;
      border-radius: 8px;
      background-color:rgb(0, 105, 42);
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      width: 60%; 
      margin-left: auto;
      margin-right: auto;
      color:white;
    }
    .option-input {
      margin-bottom: 8px;
    }
    .question-card .form-control[contenteditable] {
      min-height: 80px;
      padding: 0.5rem;
    }
    .instruction {
      font-size: 14px;
      color: #666;
      margin-bottom: 5px;
    }
    .true-false-options {
      display: flex;
      gap: 15px;
      margin-bottom: 10px;
    }
    .true-false-options label {
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    .true-false-options input {
      margin-right: 5px;
    }
    .question-image {
      max-width: 100%;
      margin: 10px 0;
    }
    .image-preview {
      max-width: 200px;
      margin: 10px 0;
    }
    
    /* Back Button Styling */
    .btn-back {
      background-color: rgb(0, 105, 42);
      color: white;
      border: 1px solid rgb(0, 105, 42);
      transition: all 0.2s ease;
    }
    
    .btn-back:hover {
      background-color: rgb(0, 85, 34);
      border-color: rgb(0, 85, 34);
      color: white;
    }
    
    .btn-back:active {
      background-color: rgb(0, 65, 26);
      border-color: rgb(0, 65, 26);
      color: white;
    }
    
    /* Outline Success Button Theme Styling */
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
  </style>
</head>
<body>
<div class="overlay">
  <div class="header-bar d-flex align-items-center">
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
      <p class="mb-0">Academic Program Application and Screening Management System</p>
    </div>
  </div>
<div class="container" style="padding-top: 20px;">
  <!-- Back Button -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <a href="chair_main.php?page=exam_versions" class="btn btn-outline-success me-3">
            <i class="fas fa-arrow-left me-1"></i> Back to Exam Versions
          </a>
          <h4 class="mb-0"><i class="fas fa-question-circle me-2"></i>Exam Questions</h4>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <?php if (isset($_SESSION['error_message']) && !empty(trim($_SESSION['error_message']))): ?>
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
          <i class="fas fa-exclamation-circle text-danger me-2"></i>
          <strong class="me-auto text-danger">Error</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #f8d7da;">
          <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message']) && !empty(trim($_SESSION['success_message']))): ?>
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
          <i class="fas fa-check-circle text-success me-2"></i>
          <strong class="me-auto text-success">Success</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #d4edda;">
          <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <form method="POST" action="" id="examForm">
    <!-- General Exam Information -->
    <div class="general-info-card">
      <h4>General Exam Information</h4>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Exam Title</label>
          <input type="text" class="form-control" name="exam_title" value="<?= htmlspecialchars($exam_info['version_name'] ?? '') ?>">
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">Time Limit (minutes)</label>
          <input type="number" class="form-control" name="time_limit" value="<?= htmlspecialchars($exam_info['time_limit'] ?? '60') ?>">
        </div>
      </div>
      
      <div class="mb-3">
        <label class="form-label">Instructions</label>
        <div contenteditable="true" class="form-control" id="exam_instructions" data-field="instructions"><?= $exam_info['instructions'] ?? 'Answer all questions to the best of your ability.' ?></div>
        <input type="hidden" name="instructions" id="instructions_hidden" value="<?= htmlspecialchars($exam_info['instructions'] ?? 'Answer all questions to the best of your ability.') ?>">
      </div>
    </div>
    
    <div id="questions-container">
      <!-- Existing questions will be loaded here if available -->
      <?php if ($questions && $questions->num_rows > 0): ?>
        <?php $q_index = 1; while ($row = $questions->fetch_assoc()): ?>
          <div class="question-card" id="question-<?= $q_index ?>" data-db-id="<?= $row['id'] ?>">
            <div class="row">
              <div class="col-md-7 mb-3">
              <label class="form-label"><b>Question <?= $q_index ?></b></label>
            </div>
              <div class="col-md-5 mb-3">
              <label class="form-label"><b>Question Type:</b></label>
              <select class="form-select question-type" onchange="toggleOptions(this)" data-field="question_type">
                <option value="multiple" <?= $row['question_type'] == 'multiple' ? 'selected' : '' ?>>Multiple Choice</option>
                <option value="truefalse" <?= $row['question_type'] == 'truefalse' ? 'selected' : '' ?>>True/False</option>
                <option value="short" <?= $row['question_type'] == 'short' ? 'selected' : '' ?>>Short Answer</option>
              </select>
              <input type="hidden" name="questions[<?= $q_index ?>][question_type]" value="<?= htmlspecialchars($row['question_type']) ?>">
      
        </div>
        
              <div contenteditable="true" class="form-control question-text mb-2" style="width:95%;margin-left:auto;margin-right:auto;" data-field="question_text"><?= htmlspecialchars($row['question_text']) ?></div>
              <input type="hidden" name="questions[<?= $q_index ?>][question_text]" value="<?= htmlspecialchars($row['question_text']) ?>">
              <input type="hidden" name="questions[<?= $q_index ?>][id]" value="<?= $row['id'] ?>">
            </div>

            <div class="options-area">
              <?php if ($row['question_type'] == 'multiple'): ?>
                <div class="row" style="margin-left:auto;margin-right:auto;">
                <div class="col-md-6 option-input">
                  <input type="radio" <?= $row['answer'] == 'A' ? 'checked' : '' ?> onclick="updateAnswer(this, 'A')"> 
                  <input type="text" class="form-control d-inline-block" style="width: 92%" placeholder="" value="<?= htmlspecialchars($row['option_a']) ?>" data-field="option_a">
                  <input type="hidden" name="questions[<?= $q_index ?>][option_a]" value="<?= htmlspecialchars($row['option_a']) ?>">
                </div>
                <div class="col-md-6 option-input">
                  <input type="radio" <?= $row['answer'] == 'C' ? 'checked' : '' ?> onclick="updateAnswer(this, 'C')"> 
                  <input type="text" class="form-control d-inline-block" style="width: 92%;" placeholder="" value="<?= htmlspecialchars($row['option_c']) ?>" data-field="option_c">
                  <input type="hidden" name="questions[<?= $q_index ?>][option_c]" value="<?= htmlspecialchars($row['option_c']) ?>">
                </div>
              </div>
              <div class="row" style="margin-left:auto;margin-right:auto;">
                <div class="col-md-6 option-input">
                  <input type="radio" <?= $row['answer'] == 'B' ? 'checked' : '' ?> onclick="updateAnswer(this, 'B')"> 
                  <input type="text" class="form-control d-inline-block" style="width: 92%;" placeholder="" value="<?= htmlspecialchars($row['option_b']) ?>" data-field="option_b">
                  <input type="hidden" name="questions[<?= $q_index ?>][option_b]" value="<?= htmlspecialchars($row['option_b']) ?>">
                </div>
                <div class="col-md-6 option-input">
                  <input type="radio" <?= $row['answer'] == 'D' ? 'checked' : '' ?> onclick="updateAnswer(this, 'D')"> 
                  <input type="text" class="form-control d-inline-block" style="width: 92%;" placeholder="" value="<?= htmlspecialchars($row['option_d']) ?>" data-field="option_d">
                  <input type="hidden" name="questions[<?= $q_index ?>][option_d]" value="<?= htmlspecialchars($row['option_d']) ?>">
                </div>
              </div>
              <?php elseif ($row['question_type'] == 'truefalse'): ?>
                <div class="true-false-options">
                  <label>
                    <input type="radio" name="tf-option-<?= $q_index ?>" <?= $row['answer'] == 'True' ? 'checked' : '' ?> onclick="updateAnswer(this, 'True')"> True
                  </label>
                  <label>
                    <input type="radio" name="tf-option-<?= $q_index ?>" <?= $row['answer'] == 'False' ? 'checked' : '' ?> onclick="updateAnswer(this, 'False')"> False
                  </label>
                </div>
                <input type="hidden" name="questions[<?= $q_index ?>][option_a]" value="True">
                <input type="hidden" name="questions[<?= $q_index ?>][option_b]" value="False">
                <input type="hidden" name="questions[<?= $q_index ?>][option_c]" value="">
                <input type="hidden" name="questions[<?= $q_index ?>][option_d]" value="">
              <?php else: ?>
                <input type="text" class="form-control" placeholder="Short answer text" value="<?= htmlspecialchars($row['answer']) ?>" data-field="answer">
              <?php endif; ?>
            </div>
              
<div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Answer Key:</label>
              <input type="text" class="form-control answer-key" placeholder="Correct Answer" value="<?= htmlspecialchars($row['answer']) ?>" data-field="answer">
              <input type="hidden" name="questions[<?= $q_index ?>][answer]" value="<?= htmlspecialchars($row['answer']) ?>">
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Points:</label>
              <input type="number" class="form-control points-input" value="<?= intval($row['points']) ?>" min="1" data-field="points">
              <input type="hidden" name="questions[<?= $q_index ?>][points]" value="<?= intval($row['points']) ?>">
            </div>
              </div>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteQuestionModal" data-question-id="<?= $row['id'] ?>" data-question-number="<?= $q_index ?>">Delete Question</button>
          </div>
        <?php $q_index++; endwhile; ?>
      <?php endif; ?>
    </div>"
    
    <button type="button" class="btn btn-back" onclick="addQuestion()" style="margin-left:59%;">Add Question</button>
    <button type="button" class="btn btn-back" data-bs-toggle="modal" data-bs-target="#saveQuestionsModal">Save Exam</button>
  </form>
</div>

<!-- Delete Question Confirmation Modal -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1" aria-labelledby="deleteQuestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="deleteQuestionModalLabel">Confirm Delete Question</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this question?</p>
        <p><strong>Question Number:</strong> <span id="deleteQuestionNumber"></span></p>
        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteQuestion">Delete Question</button>
      </div>
    </div>
  </div>
</div>

<!-- Save Exam Confirmation Modal -->
<div class="modal fade" id="saveQuestionsModal" tabindex="-1" aria-labelledby="saveQuestionsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="saveQuestionsModalLabel">Confirm Save Exam</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to save the exam?</p>
        <p><strong>Total Questions:</strong> <span id="totalQuestionsCount"></span></p>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Note:</strong> This will save the exam title, instructions, and all questions with their answers. Make sure everything is complete before saving.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" id="confirmSaveQuestions">
          <i class="fas fa-save me-2"></i>Save Exam
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let questionCount = <?= ($questions && $questions->num_rows > 0) ? $questions->num_rows : 0 ?>;

function addQuestion() {
  questionCount++;
  const container = document.getElementById('questions-container');
  
  const card = document.createElement('div');
  card.className = 'question-card';
  card.id = 'question-' + questionCount;
  card.innerHTML = `
<div class="row">
  <div class="col-md-7 mb-1">
      <label class="form-label"><b>Question ${questionCount}</b></label>
  </div>
          <div class="col-md-5 mb-1">
      <label class="form-label"><b>Question Type:</b></label>
      <select class="form-select question-type" onchange="toggleOptions(this)" data-field="question_type">
        <option value="multiple">Multiple Choice</option>
        <option value="truefalse">True/False</option>
        <option value="short">Short Answer</option>
      </select>
      <input type="hidden" name="questions[${questionCount}][question_type]" value="multiple">
    </div>
</div>
      <div contenteditable="true" class="form-control question-text mb-2" data-field="question_text"></div>
      <input type="hidden" name="questions[${questionCount}][question_text]" placeholder="Enter your question here...">
    </div>


    <div class="options-area">
      ${generateOptions(questionCount)}
    </div>
<div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label">Answer Key:</label>
      <input type="text" class="form-control answer-key" placeholder="Correct Answer" value="" data-field="answer">
      <input type="hidden" name="questions[${questionCount}][answer]" value="">
    </div>

    <div class="col-md-6 mb-2">
      <label class="form-label">Points:</label>
      <input type="number" class="form-control points-input" value="1" min="1" data-field="points">
      <input type="hidden" name="questions[${questionCount}][points]" value="1">
    </div>
</div>
    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteQuestionModal" data-question-id="null" data-question-number="${questionCount}">Delete Question</button>
  `;

  container.appendChild(card);
  
  // Add event listeners to update hidden fields
  attachInputListeners(card);
}

function generateOptions(index) {
  return `
    <div class="row">
  <div class="col-md-6 mb-1">
    <div class="d-flex align-items-center gap-2">
      <input type="radio" name="question${index}" onclick="updateAnswer(this, 'A')">
      <input type="text" class="form-control" placeholder="" data-field="option_a">
      <input type="hidden" name="questions[${index}][option_a]" value="">
    </div>
  </div>
  <div class="col-md-6 mb-1">
    <div class="d-flex align-items-center gap-2">
      <input type="radio" name="question${index}" onclick="updateAnswer(this, 'C')">
      <input type="text" class="form-control" placeholder="" data-field="option_c">
      <input type="hidden" name="questions[${index}][option_c]" value="">
    </div>
  </div>
  <div class="col-md-6 mb-1">
    <div class="d-flex align-items-center gap-2">
      <input type="radio" name="question${index}" onclick="updateAnswer(this, 'B')">
      <input type="text" class="form-control" placeholder="" data-field="option_b">
      <input type="hidden" name="questions[${index}][option_b]" value="">
    </div>
  </div>
  <div class="col-md-6 mb-1">
    <div class="d-flex align-items-center gap-2">
      <input type="radio" name="question${index}" onclick="updateAnswer(this, 'D')">
      <input type="text" class="form-control" placeholder="" data-field="option_d">
      <input type="hidden" name="questions[${index}][option_d]" value="">
    </div>
  </div>
</div>

  `;
}

function generateTrueFalseOptions(index) {
  return `
    <div class="true-false-options">
      <label>
        <input type="radio" name="tf-option-${index}" onclick="updateAnswer(this, 'True')"> True
      </label>
      <label>
        <input type="radio" name="tf-option-${index}" onclick="updateAnswer(this, 'False')"> False
      </label>
    </div>
    <input type="hidden" name="questions[${index}][option_a]" value="True">
    <input type="hidden" name="questions[${index}][option_b]" value="False">
    <input type="hidden" name="questions[${index}][option_c]" value="">
    <input type="hidden" name="questions[${index}][option_d]" value="">
  `;
}

function toggleOptions(selectElement) {
  const questionCard = selectElement.closest('.question-card');
  const index = questionCard.id.split('-')[1];
  const optionsArea = questionCard.querySelector('.options-area');
  
  // Update the hidden field
  questionCard.querySelector('input[name="questions[' + index + '][question_type]"]').value = selectElement.value;
  
  if (selectElement.value === 'multiple') {
    optionsArea.innerHTML = generateOptions(index);
    // Add event listeners for the new options
    const optionInputs = optionsArea.querySelectorAll('input[type="text"]');
    optionInputs.forEach(input => {
      input.addEventListener('input', function() {
        updateHiddenField(this);
      });
    });
  } else if (selectElement.value === 'truefalse') {
    optionsArea.innerHTML = generateTrueFalseOptions(index);
    // Set default answer key to blank
    const answerKeyInput = questionCard.querySelector('.answer-key');
    answerKeyInput.value = '';
    questionCard.querySelector('input[name="questions[' + index + '][answer]"]').value = '';
  } else {
    optionsArea.innerHTML = `
      <input type="text" class="form-control" placeholder="Short answer text" data-field="answer">
      <input type="hidden" name="questions[${index}][option_a]" value="">
      <input type="hidden" name="questions[${index}][option_b]" value="">
      <input type="hidden" name="questions[${index}][option_c]" value="">
      <input type="hidden" name="questions[${index}][option_d]" value="">
    `;
    
    // Add event listener for the short answer input
    const answerInput = optionsArea.querySelector('input[type="text"]');
    answerInput.addEventListener('input', function() {
      // Update both the answer field and the answer key field
      const answerKeyInput = questionCard.querySelector('.answer-key');
      answerKeyInput.value = this.value;
      questionCard.querySelector('input[name="questions[' + index + '][answer]"]').value = this.value;
    });
  }
}

function updateAnswer(radioButton, value) {
  const questionCard = radioButton.closest('.question-card');
  const index = questionCard.id.split('-')[1];
  const answerKeyInput = questionCard.querySelector('.answer-key');
  answerKeyInput.value = value;
  questionCard.querySelector('input[name="questions[' + index + '][answer]"]').value = value;
}


// Modal event handlers
let currentDeleteButton = null;
let currentQuestionId = null;

// Function to show success toast dynamically
function showSuccessToast(message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'successToast_' + Date.now();
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast show';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    
    toastElement.innerHTML = `
        <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto text-success">Success</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #d4edda;">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
        const toast = new bootstrap.Toast(toastElement);
        toast.hide();
    }, 2000);
    
    // Remove the toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide toasts after 2 seconds
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
    
    // Modal show event for delete
    document.getElementById('deleteQuestionModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        currentDeleteButton = button;
        currentQuestionId = button.getAttribute('data-question-id');
        var questionNumber = button.getAttribute('data-question-number');
        
        document.getElementById('deleteQuestionNumber').textContent = questionNumber;
    });

    // Modal show event for save
    document.getElementById('saveQuestionsModal').addEventListener('show.bs.modal', function (event) {
        var totalQuestions = document.querySelectorAll('.question-card').length;
        document.getElementById('totalQuestionsCount').textContent = totalQuestions;
    });

    // Confirm delete button click
    document.getElementById('confirmDeleteQuestion').addEventListener('click', function() {
        if (currentQuestionId && currentQuestionId !== 'null') {
            // Create a form to submit the delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'question_id';
            idInput.value = currentQuestionId;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'delete_question';
            actionInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            
            form.submit();
        } else {
            // Simply remove from DOM if not yet saved to DB
            currentDeleteButton.closest('.question-card').remove();
            
            // Show success toast for client-side deletion
            showSuccessToast('Question deleted successfully!');
            
            // Close the modal
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteQuestionModal'));
            modal.hide();
        }
    });

    // Confirm save button click
    document.getElementById('confirmSaveQuestions').addEventListener('click', function() {
        // Prepare form data
        prepareFormData();
        
        // Add save_questions hidden input
        const form = document.getElementById('examForm');
        const saveInput = document.createElement('input');
        saveInput.type = 'hidden';
        saveInput.name = 'save_questions';
        saveInput.value = '1';
        form.appendChild(saveInput);
        
        // Submit the form
        form.submit();
    });
});

function deleteQuestionFromDB(button, questionId) {
    // This function is kept for backward compatibility but now uses modal
    if (questionId) {
        button.setAttribute('data-question-id', questionId);
    } else {
        button.setAttribute('data-question-id', 'null');
    }
    // Trigger modal
    var modal = new bootstrap.Modal(document.getElementById('deleteQuestionModal'));
    modal.show();
}

function updateHiddenField(inputElement) {
  const questionCard = inputElement.closest('.question-card');
  
  // Check if questionCard exists and has an id
  if (!questionCard || !questionCard.id) {
    console.warn('updateHiddenField: No question card found or card has no id');
    return;
  }
  
  const index = questionCard.id.split('-')[1];
  const fieldName = inputElement.dataset.field;
  
  if (fieldName && index) {
    const value = inputElement.tagName === 'DIV' ? inputElement.innerHTML : inputElement.value;
    const hiddenField = questionCard.querySelector('input[name="questions[' + index + '][' + fieldName + ']"]');
    if (hiddenField) {
      hiddenField.value = value;
    }
  }
}

function attachInputListeners(container) {
  if (!container) {
    console.warn('attachInputListeners: Container is null or undefined');
    return;
  }
  
  // For text inputs, selects, and number inputs
  const inputs = container.querySelectorAll('input[type="text"], input[type="number"], select');
  inputs.forEach(input => {
    input.addEventListener('input', function() {
      try {
        updateHiddenField(this);
      } catch (error) {
        console.warn('Error in input listener:', error);
      }
    });
    input.addEventListener('change', function() {
      try {
        updateHiddenField(this);
      } catch (error) {
        console.warn('Error in change listener:', error);
      }
    });
  });
  
  // For contenteditable divs
  const editors = container.querySelectorAll('[contenteditable="true"]');
  editors.forEach(editor => {
    editor.addEventListener('input', function() {
      try {
        updateHiddenField(this);
      } catch (error) {
        console.warn('Error in editor input listener:', error);
      }
    });
    editor.addEventListener('blur', function() {
      try {
        updateHiddenField(this);
      } catch (error) {
        console.warn('Error in editor blur listener:', error);
      }
    });
  });
}

function prepareFormData() {
    // Update all contenteditable fields before submission
    document.querySelectorAll('[contenteditable="true"]').forEach(editor => {
        try {
            updateHiddenField(editor);
        } catch (error) {
            console.warn('Error updating contenteditable field:', error);
        }
    });
    
    // Update all visible inputs
    document.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
        try {
            updateHiddenField(input);
        } catch (error) {
            console.warn('Error updating input field:', error);
        }
    });
    
    // Update exam title and instructions
    const examTitle = document.querySelector('input[name="exam_title"]').value;
    const examInstructions = document.getElementById('exam_instructions').innerHTML;
    
    // Update the hidden instructions field
    document.getElementById('instructions_hidden').value = examInstructions;
    
    // Update the form with exam information
    const form = document.getElementById('examForm');
    const formData = new FormData(form);
    
    // Add exam information to form data
    formData.set('exam_title', examTitle);
    formData.set('instructions', examInstructions);
    
    // Add image files to formData
    document.querySelectorAll('.question-image').forEach((img, index) => {
        if (img.src.startsWith('data:')) {
            // Convert base64 to blob
            fetch(img.src)
                .then(res => res.blob())
                .then(blob => {
                    formData.append(`questions[${index}][image]`, blob, `image_${index}.jpg`);
                });
        }
    });
    
    return formData;
}

// Attach input listeners to existing elements
document.addEventListener('DOMContentLoaded', function() {
  const questionCards = document.querySelectorAll('.question-card');
  questionCards.forEach(card => {
    // Check if the card has the expected structure
    if (card && card.id && card.id.includes('question-')) {
      attachInputListeners(card);
    } else {
      console.warn('Question card missing proper structure:', card);
    }
  });
  
  // Add listener for instructions contenteditable div
  const instructionsDiv = document.getElementById('exam_instructions');
  if (instructionsDiv) {
    instructionsDiv.addEventListener('input', function() {
      document.getElementById('instructions_hidden').value = this.innerHTML;
    });
    instructionsDiv.addEventListener('blur', function() {
      document.getElementById('instructions_hidden').value = this.innerHTML;
    });
  }
});
</script>

<?php
// Clean up session messages after displaying them
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

</body>
</html>