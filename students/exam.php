<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: exam_login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>You are not logged in.</div>";
    exit;
}

$applicant_id = $_SESSION['user_id'];

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information first (needed for UI)
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM registration WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get the latest published exam version
$result = $conn->query("SELECT * FROM exam_versions WHERE is_published = 1 ORDER BY published_at DESC LIMIT 1");

if ($result->num_rows === 0) {
    // Render styled "No Active Exam" UI
    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>No Active Exam</title>
    <link rel=\"icon\" href=\"images/chmsu.png\" type=\"image/png\" />
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }
        :root { --theme-green: #00692a; --theme-green-dark: #005223; }
        .overlay {
            position: relative;
            min-height: 100vh;
            width: 100%;
            padding-top: 80px;
        }
        .overlay::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            z-index: 0;
        }
        .overlay > * { position: relative; z-index: 1; }
        .header-bar {
            background-color: rgb(0, 105, 42);
            color: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-bar img {
            width: 65px;
            margin-right: 10px;
        }
        .no-exam-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
            padding: 2rem;
        }
        .no-exam-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }
        .no-exam-header {
            background-color: #00692a;
            color: #fff;
            padding: 2rem 2rem 1.5rem;
            border: none;
        }
        .no-exam-body {
            padding: 2rem;
        }
        .warning-icon {
            font-size: 5rem;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .no-exam-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 1rem;
        }
        .no-exam-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
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
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #00692a;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1.5rem;
            text-align: left;
        }
        .info-box i {
            color: #00692a;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class=\"overlay\">
        <div class=\"header-bar\">
            <div class=\"header-left\">
                <img src=\"images/chmsu.png\" alt=\"CHMSU Logo\">
                <div class=\"ms-1\">
                    <h4 class=\"mb-0\">Carlos Hilado Memorial State University</h4>
                    <p class=\"mb-0\">Academic Program Application and Screening Management System</p>
                </div>
            </div>
            <div class=\"me-3\">";
    
    if (isset($user)) {
        $displayName = ucwords(strtolower(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))));
        echo "<a class=\"text-white text-decoration-none fw-semibold\" href=\"exam_login.php\">
            <i class=\"fas fa-user me-1\"></i>
            " . htmlspecialchars($displayName) . "
        </a>";
    }
    
    echo "            </div>
        </div>
        
        <div class=\"no-exam-container\">
            <div class=\"no-exam-card\">
                <div class=\"no-exam-header\">
                    <div class=\"warning-icon\">
                        <i class=\"fas fa-exclamation-triangle\"></i>
                    </div>
                    <h4 class=\"no-exam-title mb-0\">No Active Exam Available</h4>
                </div>
                <div class=\"no-exam-body\">
                    <p class=\"no-exam-message\">
                        There is currently no active examination available at this moment. 
                        Please check back later or contact your administrator for more information.
                    </p>
                    <a href=\"exam_login.php\" class=\"btn btn-success\">
                        <i class=\"fas fa-arrow-left me-2\"></i>Return to Exam Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
    exit;
}

$exam = $result->fetch_assoc();
$version_id = $exam['id'];

// User information already fetched above, no need to fetch again

// Check if applicant is scheduled for an exam
$schedule_check = $conn->prepare("SELECT sa.schedule_id, s.event_date, s.event_time, s.venue 
                                  FROM schedule_applicants sa 
                                  INNER JOIN schedules s ON sa.schedule_id = s.id 
                                  WHERE sa.applicant_id = ? 
                                  AND s.event_date >= CURDATE()
                                  ORDER BY s.event_date ASC, s.event_time ASC 
                                  LIMIT 1");
$schedule_check->bind_param("i", $applicant_id);
$schedule_check->execute();
$schedule_result = $schedule_check->get_result();
$schedule = $schedule_result->fetch_assoc();
$schedule_check->close();

// If applicant is not scheduled, show access denied message
if (!$schedule) {
    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Access Denied</title>
    <link rel=\"icon\" href=\"images/chmsu.png\" type=\"image/png\" />
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }
        :root { --theme-green: #00692a; --theme-green-dark: #005223; }
        .overlay {
            position: relative;
            min-height: 100vh;
            width: 100%;
            padding-top: 80px;
        }
        .overlay::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            z-index: 0;
        }
        .overlay > * { position: relative; z-index: 1; }
        .header-bar {
            background-color: rgb(0, 105, 42);
            color: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-bar img {
            width: 65px;
            margin-right: 10px;
        }
        .no-exam-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
            padding: 2rem;
        }
        .no-exam-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }
        .no-exam-header {
            background-color: #dc3545;
            color: #fff;
            padding: 2rem 2rem 1.5rem;
            border: none;
        }
        .no-exam-body {
            padding: 2rem;
        }
        .warning-icon {
            font-size: 5rem;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .no-exam-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 1rem;
        }
        .no-exam-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
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
    </style>
</head>
<body>
    <div class=\"overlay\">
        <div class=\"header-bar\">
            <div class=\"header-left\">
                <img src=\"images/chmsu.png\" alt=\"CHMSU Logo\">
                <div class=\"ms-1\">
                    <h4 class=\"mb-0\">Carlos Hilado Memorial State University</h4>
                    <p class=\"mb-0\">Academic Program Application and Screening Management System</p>
                </div>
            </div>
            <div class=\"me-3\">";
    
    if (isset($user)) {
        $displayName = ucwords(strtolower(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))));
        echo "<a class=\"text-white text-decoration-none fw-semibold\" href=\"exam_login.php\">
            <i class=\"fas fa-user me-1\"></i>
            " . htmlspecialchars($displayName) . "
        </a>";
    }
    
    echo "            </div>
        </div>
        
        <div class=\"no-exam-container\">
            <div class=\"no-exam-card\">
                <div class=\"no-exam-header\">
                    <div class=\"warning-icon\">
                        <i class=\"fas fa-lock\"></i>
                    </div>
                    <h4 class=\"no-exam-title mb-0\">Access Denied</h4>
                </div>
                <div class=\"no-exam-body\">
                    <p class=\"no-exam-message\">
                        You are not scheduled for an examination at this time. 
                        Please wait for your exam schedule to be assigned by the admissions office.
                    </p>
                    <a href=\"exam_login.php\" class=\"btn btn-success\">
                        <i class=\"fas fa-arrow-left me-2\"></i>Return to Exam Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
    exit;
}

// Check if this applicant has already answered this exam version
$checkSubmission = $conn->prepare("SELECT * FROM exam_answers WHERE applicant_id = ? AND version_id = ?");
$checkSubmission->bind_param("ii", $applicant_id, $version_id);
$checkSubmission->execute();
$submissionResult = $checkSubmission->get_result();

if ($submissionResult->num_rows > 0) {
    // If answers exist, show the same success UI as submit_exam.php
    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Exam Already Completed</title>
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
    </style>
    <meta http-equiv=\"refresh\" content=\"5;url=exam_login.php\">
</head>
<body>
    <div class=\"overlay\"></div>
    <div class=\"card\">
        <div class=\"card-header\">
            <h4 class=\"mb-0\">Exam Completed</h4>
        </div>
        <div class=\"card-body\">
            <div class=\"success-icon\">
                <i class=\"fas fa-check-circle\"></i>
            </div>
            <p class=\"lead\">You have already completed this examination.</p>
            <p class=\"text-muted mb-4\">You'll be redirected to the exam login shortly.</p>
            <a class=\"btn btn-success\" href=\"exam_login.php\">
                <i class=\"fas fa-arrow-right me-2\"></i>Continue
            </a>
        </div>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
    exit;
}

// Fetch questions for this version
$stmt = $conn->prepare("SELECT * FROM questions WHERE version_id = ? ORDER BY id");
$stmt->bind_param("i", $version_id);
$stmt->execute();
$questions_result = $stmt->get_result();

// Convert to array for randomization
$questions_array = [];
while ($q = $questions_result->fetch_assoc()) {
    $questions_array[] = $q;
}

// Generate or retrieve randomized question order for this student
$session_key = 'exam_question_order_' . $version_id . '_' . $applicant_id;
if (!isset($_SESSION[$session_key])) {
    // Create a shuffled array of question IDs
    $question_ids = array_column($questions_array, 'id');
    shuffle($question_ids);
    $_SESSION[$session_key] = $question_ids;
}

// Reorder questions according to the stored random order
$ordered_questions = [];
$question_map = [];
foreach ($questions_array as $q) {
    $question_map[$q['id']] = $q;
}

foreach ($_SESSION[$session_key] as $q_id) {
    if (isset($question_map[$q_id])) {
        $ordered_questions[] = $question_map[$q_id];
    }
}

// Count total questions for progress indicator
$totalQuestions = count($ordered_questions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITE Qualifying Exam</title>
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
        /* Theme green */
        :root { --theme-green: #00692a; --theme-green-dark: #005223; }
        .overlay {
            position: relative;
            min-height: 100vh;
            min-width: 100vw;
            width: 100%;
            padding-top: 80px;
        }
        .overlay::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            z-index: 0;
        }
        /* Ensure overlay children (header, sidebar, content) sit above the white background */
        .overlay > * { position: relative; z-index: 1; }
        /* Ensure Bootstrap modals appear above all content */
        .modal-backdrop { z-index: 3040 !important; }
        .modal { z-index: 3050 !important; }
        /* Prevent layout shift when modal opens (scrollbar compensation) */
        body.modal-open { padding-right: 0 !important; overflow: hidden; }
        .header-bar {
            background-color: rgb(0, 105, 42);
            color: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-bar img {
            width: 65px;
            margin-right: 10px;
        }
        .sidebar {
            background-color: rgba(232, 245, 233, 0.88);
            position: fixed;
            top: 90px;
            bottom: 0;
            left: 0;
            width: 250px;
            padding: 1rem;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #c8e6c9;
            font-weight: bold;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            padding-top: 60px;
        }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 10px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 50%;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                top: 0;
                margin-bottom: 1rem;
                padding: 0.75rem;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 20px;
            }
            .header-bar {
                padding: 0.7rem 0.5rem;
                flex-wrap: wrap;
            }
            .header-bar img {
                width: 50px;
                margin-right: 8px;
            }
            .overlay {
                padding-top: 100px;
            }
            .sidebar a {
                padding: 8px;
                font-size: 0.9rem;
            }
            .question-image {
                max-width: 100%;
            }
            .exam-header {
                padding: 10px;
            }
            .timer {
                font-size: 1rem;
            }
            .card {
                margin-bottom: 1rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .btn-group .btn {
                width: auto;
            }
            .no-exam-card {
                margin: 15px;
            }
        }
        @media (max-width: 576px) {
            .header-bar {
                padding: 0.5rem;
            }
            .header-bar img {
                width: 40px;
            }
            .overlay {
                padding-top: 80px;
            }
            .main-content {
                padding: 0.75rem;
            }
            .sidebar a {
                padding: 6px;
                font-size: 0.85rem;
            }
            .timer {
                font-size: 0.9rem;
            }
            .exam-header h5 {
                font-size: 1rem;
            }
            .no-exam-card {
                margin: 10px;
            }
            .no-exam-header {
                padding: 1.5rem 1rem 1rem;
            }
            .no-exam-body {
                padding: 1.5rem 1rem;
            }
            .warning-icon {
                font-size: 3rem;
            }
            .no-exam-title {
                font-size: 1.25rem;
            }
        }
        .dropdown-toggle::after {
            margin-left: 8px;
        }
    .question-image {
        max-width: 50%;
        height: auto;
        margin: 10px 0;
        border-radius: 5px;
    }
    .exam-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .progress {
        height: 10px;
    }
        /* Make radios/checkboxes green */
        .form-check-input {
            accent-color: var(--theme-green);
            border-color: var(--theme-green);
        }
        .form-check-input:focus {
            border-color: var(--theme-green);
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
        }
        .form-check-input:checked {
            background-color: var(--theme-green);
            border-color: var(--theme-green);
        }
        /* Buttons to match theme */
        .btn-success {
            background-color: var(--theme-green);
            border-color: var(--theme-green);
        }
        .btn-success:hover { background-color: var(--theme-green-dark); border-color: var(--theme-green-dark); }
        .btn-outline-success { color: var(--theme-green); border-color: var(--theme-green); }
        .btn-outline-success:hover { background-color: var(--theme-green); border-color: var(--theme-green); }
    .timer {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .card-border-left {
        border-left: 5px solid #198754;
    }
 .blurred {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
  }
  .unblurred {
    filter: none;
    pointer-events: auto;
    user-select: auto;
  }
</style>

</head>
<body>
<div class="overlay">
    <div class="header-bar">
        <div class="header-left">
            <img src="images/chmsu.png" alt="CHMSU Logo">
            <div class="ms-1">
                <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
                <p class="mb-0">Academic Program Application and Screening Management System</p>
            </div>
</div>
<div class="me-3">
        <?php $displayName = ucwords(strtolower(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))); ?>
        <a class="text-white text-decoration-none fw-semibold" href="?page=my_account">
            <i class="fas fa-user me-1"></i>
            <?= htmlspecialchars($displayName) ?>
        </a>
        </div>
</div>
<br>
    <div id="exam-content" class="blurred">
    <div class="container">
        <div class="exam-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><?= htmlspecialchars($exam['version_name']) ?></h2>
                    <?php if (!empty($exam['description'])): ?>
                        <p class="text-muted"><?= htmlspecialchars($exam['description']) ?></p>
                    <?php endif; ?>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="exam-progress">0%</div>
                    </div>
                    <small class="text-muted">Question <span id="current-question">0</span> of <?= $totalQuestions ?></small>
                </div>
                <?php if (!empty($exam['time_limit'])): ?>
                <div class="col-md-4 text-end">
                    <div class="timer" id="exam-timer">Time remaining: <?= $exam['time_limit'] ?> minutes</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="submit_exam.php" id="examForm">
            <input type="hidden" name="exam_version_id" value="<?= $version_id ?>">
            <input type="hidden" name="exam_start_time" value="<?= time() ?>">

            <?php $number = 1; ?>
            <?php foreach ($ordered_questions as $q): ?>
                <div class="card mb-2 card-border-left">
                    <div class="card-header d-flex justify-content-between">
                        <span class="badge bg-secondary"><?= $q['points'] ?> point<?= $q['points'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-body">
                        <p class="question-text fw-semibold"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                        <?php if (!empty($q['image_url'])): ?>
                            <img src="<?= htmlspecialchars($q['image_url']) ?>" alt="Question <?= $number ?> Image" class="question-image">
                        <?php endif; ?>
                        <div class="answer-section mt-2">
                            <?php if ($q['question_type'] === 'multiple'): ?>
                                <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                                    <?php $opt_val = $q["option_$opt"]; ?>
                                    <?php if (!empty($opt_val)): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input answer-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_<?= $opt ?>" value="<?= htmlspecialchars($opt_val) ?>" required>
                                            <label class="form-check-label" for="q<?= $q['id'] ?>_<?= $opt ?>">
                                                <?= strtoupper($opt) ?>. <?= htmlspecialchars($opt_val) ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php elseif ($q['question_type'] === 'short'): ?>
                                <input type="text" class="form-control answer-input" name="answers[<?= $q['id'] ?>]" style="width:75%" placeholder="Type your answer here" required>
                            <?php elseif ($q['question_type'] === 'truefalse'): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input answer-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_true" value="TRUE" required>
                                    <label class="form-check-label" for="q<?= $q['id'] ?>_true">True</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input answer-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_false" value="FALSE" required>
                                    <label class="form-check-label" for="q<?= $q['id'] ?>_false">False</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $number++; ?>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between mt-4 mb-5">
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#confirmExitModal"><i class="fas fa-sign-out-alt me-1"></i> Exit Exam</button>
                <button type="button" class="btn btn-success" id="openSubmitModal" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal" disabled><i class="fas fa-paper-plane me-1"></i> Submit Answers</button>
            </div>
        </form>
    </div>
                            </div>
<!-- Start Confirmation Modal (no X) -->
<div class="modal fade" id="examStartModal" tabindex="-1" aria-labelledby="examStartLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="examStartLabel">Start Departmental Exam</h5>
      </div>
      <div class="modal-body">
        <p>Once you start the exam, the timer will begin, and you cannot pause or navigate away. Please make sure you're ready.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" onclick="window.location.href='exam_login.php'">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmStart" >Start Exam</button>
      </div>
    </div>
  </div>
</div>

<!-- Exit Confirmation Modal (no X) -->
<div class="modal fade" id="confirmExitModal" tabindex="-1" aria-labelledby="confirmExitLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="confirmExitLabel">Exit Exam</h5>
      </div>
      <div class="modal-body">
        Are you sure you want to exit the exam? All progress will be lost.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stay</button>
        <button type="button" class="btn btn-danger" id="confirmExitBtn">Exit</button>
      </div>
    </div>
  </div>
 </div>

<!-- Submit Confirmation Modal (no X) -->
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="confirmSubmitLabel">Submit Answers</h5>
      </div>
      <div class="modal-body">
        Do you want to submit your answers now? You won't be able to change them after submitting.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Review</button>
        <button type="button" class="btn btn-success" id="confirmSubmitBtn"><i class="fas fa-paper-plane me-1"></i> Submit</button>
      </div>
    </div>
  </div>
 </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalQuestions = <?= $totalQuestions ?>;
        let answeredQuestions = 0;
        const inputs = document.querySelectorAll('.answer-input');
        const timeLimitSeconds = <?= (int)$exam['time_limit'] * 60 ?>;
        const storageKey = 'exam_end_time_<?= (int)$version_id ?>_<?= (int)$user_id ?>';

        inputs.forEach(input => {
            input.addEventListener('change', updateProgress);
        });

        function updateProgress() {
            const questionGroups = {};

            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (!questionGroups[name]) questionGroups[name] = false;

                if ((input.type === 'radio' && input.checked) || (input.type === 'text' && input.value.trim() !== '')) {
                    questionGroups[name] = true;
                }
            });

            answeredQuestions = Object.values(questionGroups).filter(v => v).length;
            const progressPercentage = Math.round((answeredQuestions / totalQuestions) * 100);

            document.getElementById('exam-progress').style.width = progressPercentage + '%';
            document.getElementById('exam-progress').textContent = progressPercentage + '%';
            document.getElementById('exam-progress').setAttribute('aria-valuenow', progressPercentage);
            document.getElementById('current-question').textContent = answeredQuestions;
            // Enable submit only if at least one question answered
            const openSubmitBtn = document.getElementById('openSubmitModal');
            if (openSubmitBtn) {
                openSubmitBtn.disabled = answeredQuestions < 1;
            }
        }

        // Exit and Submit modal handlers
        document.getElementById('confirmExitBtn').addEventListener('click', () => {
            try { localStorage.removeItem(storageKey); } catch (e) {}
            window.location.href = 'exam_login.php';
        });
        document.getElementById('confirmSubmitBtn').addEventListener('click', () => {
            try { localStorage.removeItem(storageKey); } catch (e) {}
            document.getElementById('examForm').submit();
        });
        // Ensure modals are attached to <body> to avoid stacking-context issues
        (function ensureModalsAtBodyRoot(){
            var ids = ['examStartModal','confirmExitModal','confirmSubmitModal'];
            ids.forEach(function(id){
                var el = document.getElementById(id);
                if (el && el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
            });
        })();
        window.addEventListener('DOMContentLoaded', () => {
        const modal = new bootstrap.Modal(document.getElementById('examStartModal'));
        // Resume if an end time exists
        let storedEnd = 0;
        try { storedEnd = parseInt(localStorage.getItem(storageKey) || '0', 10); } catch (e) { storedEnd = 0; }
        const nowMs = Date.now();
        if (storedEnd && storedEnd > nowMs) {
            document.getElementById('exam-content').classList.remove('blurred');
            document.getElementById('exam-content').classList.add('unblurred');
            const remaining = Math.max(1, Math.floor((storedEnd - nowMs) / 1000));
            startTimer(remaining);
        } else {
            modal.show(); // Show the modal immediately on load
        }

        document.getElementById('confirmStart').addEventListener('click', () => {
            document.getElementById('exam-content').classList.remove('blurred');
            document.getElementById('exam-content').classList.add('unblurred');

            // Persist end time to survive refresh
            const endTime = Date.now() + (timeLimitSeconds * 1000);
            try { localStorage.setItem(storageKey, String(endTime)); } catch (e) {}

            modal.hide();

            // Optional: Start fullscreen
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else if (document.documentElement.webkitRequestFullscreen) {
                document.documentElement.webkitRequestFullscreen();
            } else if (document.documentElement.msRequestFullscreen) {
                document.documentElement.msRequestFullscreen();
            }

            // Start timer
            startTimer(timeLimitSeconds);
        });
        });
        // Timer function
function startTimer(duration) {
    let timer = duration, minutes, seconds;
    const display = document.getElementById('exam-timer');
    const interval = setInterval(() => {
        minutes = Math.floor(timer / 60);
        seconds = timer % 60;

        display.textContent = `Time remaining: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        
        if (--timer < 0) {
            clearInterval(interval);
            alert("Time is up! Your answers will now be submitted.");
            try { localStorage.removeItem(storageKey); } catch (e) {}
            document.getElementById("examForm").submit();
        }
    }, 1000);
}
    </script>
</body>
</html>
