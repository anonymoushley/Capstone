<?php
/**
 * Exam Submission Handler
 * 
 * Processes and saves exam answers submitted by students.
 * Calculates scores, validates answers, and stores results in the database.
 * 
 * @package Students
 */

session_start(); // Important: make sure session is started

// Get applicant ID from session or fallback POST
$applicant_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? null;
if (!$applicant_id) {
    require_once __DIR__ . '/../config/error_handler.php';
    handleError("Unauthorized access.", "Unauthorized exam submission attempt", 401, false);
}

require_once __DIR__ . '/../config/error_handler.php';
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    handleError("System error. Please try again later.", $e->getMessage(), 500, false);
}

$version_id = isset($_POST['exam_version_id']) ? intval($_POST['exam_version_id']) : null;
$answers = $_POST['answers'] ?? [];

if (!$version_id || $version_id <= 0 || empty($answers) || !is_array($answers)) {
    handleError("Invalid exam data submitted.", "Missing exam version or answers", 400, false);
}

// Check for duplicate aggregated submission
$check = $conn->prepare("SELECT COUNT(*) FROM exam_answers WHERE applicant_id = ? AND version_id = ?");
$check->bind_param("ii", $applicant_id, $version_id);
$check->execute();
$check->bind_result($already_submitted);
$check->fetch();
$check->close();

if ($already_submitted > 0) {
    // Render modern duplicate submission UI
    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Exam Already Submitted</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\" rel=\"stylesheet\">
    <style>
        body { 
            background: url('images/chmsubg.jpg') no-repeat center center fixed; 
            background-size: cover;
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            z-index: -1;
        }
        .card { 
            border: none; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .card-header { 
            background-color: #ffc107; 
            color: #212529; 
            border: none;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .card-body { 
            padding: 2rem; 
            text-align: center;
        }
        .warning-icon {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }
        .btn-success { 
            background-color: #00692a; 
            border: none; 
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover { 
            background-color: #005223; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 105, 42, 0.3);
        }
        .lead {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 0.95rem;
        }
        @media (max-width: 576px) {
            .card {
                margin: 10px;
                border-radius: 15px;
            }
            .card-header {
                padding: 1.5rem 1rem 1rem !important;
            }
            .card-body {
                padding: 1.5rem 1rem !important;
            }
            .warning-icon {
                font-size: 3rem !important;
            }
            .lead {
                font-size: 1rem !important;
            }
            h4 {
                font-size: 1.25rem !important;
            }
            body {
                padding: 10px;
            }
        }
    </style>
    <meta http-equiv=\"refresh\" content=\"4;url=exam_login.php\">
</head>
<body>
    <div class=\"overlay\"></div>
    <div class=\"card\">
        <div class=\"card-header\">
            <h4 class=\"mb-0\">Exam Already Submitted</h4>
        </div>
        <div class=\"card-body\">
            <div class=\"warning-icon\">
                <i class=\"fas fa-circle-exclamation\"></i>
            </div>
            <p class=\"lead\">You have already submitted this exam version.</p>
            <p class=\"text-muted mb-4\">You'll be redirected to the exam login shortly.</p>
            <a href=\"exam_login.php\" class=\"btn btn-success\">
                <i class=\"fas fa-arrow-right me-2\"></i>Continue
            </a>
        </div>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
    exit;
}

// Compute total questions and total possible points for this version
$metaStmt = $conn->prepare("SELECT COUNT(*) AS total_questions, COALESCE(SUM(points),0) AS total_points FROM questions WHERE version_id = ?");
$metaStmt->bind_param("i", $version_id);
$metaStmt->execute();
$metaStmt->bind_result($total_questions, $total_points_possible);
$metaStmt->fetch();
$metaStmt->close();

// Fetch correct answers, points, and option values for all questions in this version
$qStmt = $conn->prepare("SELECT id, question_type, answer, points, option_a, option_b, option_c, option_d FROM questions WHERE version_id = ?");
$qStmt->bind_param("i", $version_id);
$qStmt->execute();
$qResult = $qStmt->get_result();

$questionIdToMeta = [];
while ($row = $qResult->fetch_assoc()) {
    $rawPoints = isset($row['points']) ? (float)$row['points'] : 0.0;
    $safePoints = $rawPoints > 0 ? $rawPoints : 1.0; // default to 1 point if missing/zero
    $questionIdToMeta[(int)$row['id']] = [
        'type' => (string)$row['question_type'],
        'correct' => (string)$row['answer'],
        'points' => $safePoints,
        'option_a' => isset($row['option_a']) ? trim((string)$row['option_a']) : '',
        'option_b' => isset($row['option_b']) ? trim((string)$row['option_b']) : '',
        'option_c' => isset($row['option_c']) ? trim((string)$row['option_c']) : '',
        'option_d' => isset($row['option_d']) ? trim((string)$row['option_d']) : ''
    ];
    
}
$qStmt->close();

// Calculate points earned based on submitted answers
$points_earned = 0.0;
foreach ($answers as $qid => $ans) {
    $qidInt = (int)$qid;
    if (!isset($questionIdToMeta[$qidInt])) {
        continue;
    }
    $meta = $questionIdToMeta[$qidInt];

    $submitted = trim((string)$ans);
    $correct = (string)$meta['correct'];

    // Normalize for comparison
    $isCorrect = false;
    if ($meta['type'] === 'multiple') {
        // For multiple choice, the form submits the option text (e.g., "Philippines")
        // but the database stores the answer key as a letter (e.g., "A")
        // We need to map the submitted option text to its corresponding letter
        
        $submittedLetter = null;
        if (trim($submitted) === $meta['option_a']) {
            $submittedLetter = 'A';
        } elseif (trim($submitted) === $meta['option_b']) {
            $submittedLetter = 'B';
        } elseif (trim($submitted) === $meta['option_c']) {
            $submittedLetter = 'C';
        } elseif (trim($submitted) === $meta['option_d']) {
            $submittedLetter = 'D';
        }
        
        // Compare the mapped letter with the stored answer key
        $isCorrect = ($submittedLetter !== null && trim($submittedLetter) === trim($correct));
        
    } elseif ($meta['type'] === 'truefalse') {
        // Handle both 'TRUE'/'FALSE' from form and 'True'/'False' from database
        $normSubmitted = ucfirst(strtolower(trim($submitted)));
        $normCorrect = ucfirst(strtolower(trim($correct)));
        $isCorrect = $normSubmitted === $normCorrect;
    } else { // short or others
        // Collapse multiple spaces and compare case-insensitively
        $normSubmitted = preg_replace('/\s+/', ' ', strtolower(trim($submitted)));
        $normCorrect = preg_replace('/\s+/', ' ', strtolower(trim($correct)));
        $isCorrect = $normSubmitted === $normCorrect;
    }

    if ($isCorrect) {
        $points_earned += (float)$meta['points'];
    }
}

// Insert aggregated row into exam_answers
try {
    $insertAgg = $conn->prepare("INSERT INTO exam_answers (applicant_id, version_id, total_questions, points_earned, points_possible) VALUES (?, ?, ?, ?, ?)");
    if (!$insertAgg) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
} catch (Exception $e) {
    require_once __DIR__ . '/../config/error_handler.php';
    handleError("System error. Please try again later.", $e->getMessage(), 500, false);
}
$points_possible = (float)$total_points_possible;
$insertAgg->bind_param("iiidd", $applicant_id, $version_id, $total_questions, $points_earned, $points_possible);
$insertAgg->execute();
$insertAgg->close();

// Persist exam_total_score (percentage) into screening_results
// Find personal_info_id for this applicant
require_once __DIR__ . '/../config/functions.php';
$personal_info_id = getPersonalInfoIdFromRegistration($conn, $applicant_id);

if (!empty($personal_info_id) && $points_possible > 0) {
    $percentScore = round(($points_earned / $points_possible) * 100, 2);
    // Calculate exam_percentage: (exam_total_score / 100) * 40
    $exam_percentage = round(($percentScore / 100) * 40, 2);
    $srStmt = $conn->prepare("INSERT INTO screening_results (personal_info_id, exam_total_score, exam_percentage) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE exam_total_score = VALUES(exam_total_score), exam_percentage = VALUES(exam_percentage)");
    $srStmt->bind_param("idd", $personal_info_id, $percentScore, $exam_percentage);
    $srStmt->execute();
    $srStmt->close();
}

$conn->close();

// Render modern success UI with points summary
echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Exam Submitted Successfully</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\" rel=\"stylesheet\">
    <style>
        body { 
            background: url('images/chmsubg.jpg') no-repeat center center fixed; 
            background-size: cover;
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            z-index: -1;
        }
        .card { 
            border: none; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .card-header { 
            background-color: #00692a; 
            color: #fff; 
            border: none;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .card-body { 
            padding: 2rem; 
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #00692a;
            margin-bottom: 1rem;
        }
        .btn-success { 
            background-color: #00692a; 
            border: none; 
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover { 
            background-color: #005223; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 105, 42, 0.3);
        }
        .lead {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 0.95rem;
        }
        @media (max-width: 576px) {
            .card {
                margin: 10px;
                border-radius: 15px;
            }
            .card-header {
                padding: 1.5rem 1rem 1rem !important;
            }
            .card-body {
                padding: 1.5rem 1rem !important;
            }
            .success-icon {
                font-size: 3rem !important;
            }
            .lead {
                font-size: 1rem !important;
            }
            h4 {
                font-size: 1.25rem !important;
            }
            body {
                padding: 10px;
            }
        }
    </style>
    <meta http-equiv=\"refresh\" content=\"5;url=exam_login.php\">
</head>
<body>
    <div class=\"overlay\"></div>
    <div class=\"card\">
        <div class=\"card-header\">
            <h4 class=\"mb-0\">Exam Submitted</h4>
        </div>
        <div class=\"card-body\">
            <div class=\"success-icon\">
                <i class=\"fas fa-check-circle\"></i>
            </div>
            <p class=\"lead\">Your exam has been submitted successfully.</p>
            <p class=\"text-muted mb-4\">You'll be redirected to the exam login shortly.</p>
            <a class=\"btn btn-success\" href=\"exam_login.php\">
                <i class=\"fas fa-arrow-right me-2\"></i>Continue
            </a>
        </div>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";

?>
