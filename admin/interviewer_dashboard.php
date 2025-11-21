<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get interviewer info
$interviewer_id = $_SESSION['interviewer_id'] ?? null;
$interviewer_name = $_SESSION['interviewer_name'] ?? '';

// Check if interview_schedule_applicants table exists
$table_check = $conn->query("SHOW TABLES LIKE 'interview_schedule_applicants'");
$table_exists = $table_check && $table_check->num_rows > 0;

// Initialize counts
$totalApplicants = 0;
$completedInterviews = 0;
$pendingInterviews = 0;

// Get total applicants eligible for interview (completed exam - same as scheduling page)
// Fetch from exam_answers table only - if record exists, exam was completed
$totalSql = "SELECT COUNT(DISTINCT r.id) AS total 
             FROM exam_answers ea
             INNER JOIN registration r ON ea.applicant_id = r.id
             INNER JOIN personal_info pi ON r.personal_info_id = pi.id
             INNER JOIN (
                 SELECT applicant_id, MAX(submitted_at) as latest_submitted
                 FROM exam_answers
                 GROUP BY applicant_id
             ) latest_ea ON ea.applicant_id = latest_ea.applicant_id AND ea.submitted_at = latest_ea.latest_submitted";
$totalResult = mysqli_query($conn, $totalSql);
if ($totalResult) {
    $totalApplicants = mysqli_fetch_assoc($totalResult)['total'] ?? 0;
}

// Get completed interviews count (applicants who completed exam and have been interviewed)
$completedSql = "SELECT COUNT(DISTINCT r.id) AS completed
                 FROM exam_answers ea
                 INNER JOIN registration r ON ea.applicant_id = r.id
                 INNER JOIN personal_info pi ON r.personal_info_id = pi.id
                 INNER JOIN screening_results sr ON sr.personal_info_id = pi.id
                 INNER JOIN (
                     SELECT applicant_id, MAX(submitted_at) as latest_submitted
                     FROM exam_answers
                     GROUP BY applicant_id
                 ) latest_ea ON ea.applicant_id = latest_ea.applicant_id AND ea.submitted_at = latest_ea.latest_submitted
                 WHERE sr.interview_total_score IS NOT NULL
                 AND sr.interview_total_score > 0";
$completedResult = mysqli_query($conn, $completedSql);
if ($completedResult) {
    $completedInterviews = mysqli_fetch_assoc($completedResult)['completed'] ?? 0;
}

// Get pending interviews count (eligible but not yet completed)
$pendingInterviews = max(0, $totalApplicants - $completedInterviews);
?>

<style>
    .stats-card {
        background: white;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .stats-card .icon {
        font-size: 2.5rem;
        color: rgb(0, 105, 42);
        margin-bottom: 10px;
    }
    
    .stats-card .number {
        font-size: 2.5rem;
        font-weight: bold;
        color: rgb(0, 105, 42);
        margin: 0;
    }
    
    .stats-card .label {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 5px 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, rgb(0, 105, 42), rgb(0, 85, 34));
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .welcome-section h3 {
        margin: 0;
        font-weight: 600;
    }
    
    .welcome-section p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3><i class="fas fa-user-circle me-2"></i>Welcome, <?= htmlspecialchars($interviewer_name) ?></h3>
        <p><i class="fas fa-microphone me-1"></i> Interviewer Portal</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h2 class="number"><?= $totalApplicants ?></h2>
                <p class="label">Total Applicants for Interview</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="number"><?= $pendingInterviews ?></h2>
                <p class="label">Pending Interviews</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="number"><?= $completedInterviews ?></h2>
                <p class="label">Completed Interviews</p>
            </div>
        </div>
    </div>

</div>
