<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$applicant_id = $_GET['applicant_id'] ?? null;
$applicant_data = null;

// Get applicant data if ID is provided
if ($applicant_id) {
    $sql = "SELECT 
                r.id AS registration_id,
                r.personal_info_id,
                r.applicant_status,
                pi.last_name,
                pi.first_name,
                pi.middle_name,
                pi.contact_number,
                s.name as strand,
                ab.year_graduated,
                pa.program,
                pa.campus,
                sr.interview_total_score
            FROM registration r
            LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
            LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
            LEFT JOIN strands s ON ab.strand_id = s.id
            LEFT JOIN program_application pa ON pa.personal_info_id = pi.id
            LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
            WHERE r.id = ? AND r.applicant_status = 'For Interview'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicant_data = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_interview'])) {
    $communication_skills = floatval($_POST['communication_skills']);
    $problem_solving = floatval($_POST['problem_solving']);
    $motivation = floatval($_POST['motivation']);
    $knowledge = floatval($_POST['knowledge']);
    $overall_impression = floatval($_POST['overall_impression']);
    
    // Calculate total score (out of 100)
    $total_score = ($communication_skills + $problem_solving + $motivation + $knowledge + $overall_impression) / 5;
    
    // Calculate interview percentage: (interview_total_score / 100) × 35
    $interview_percentage = ($total_score / 100) * 35;
    
    // Update or insert interview results - only store the final score
    $update_sql = "INSERT INTO screening_results (personal_info_id, interview_total_score, interview_percentage)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE 
                   interview_total_score = VALUES(interview_total_score),
                   interview_percentage = VALUES(interview_percentage)";
    
    $personal_info_id = $applicant_data['personal_info_id'];
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("idd", $personal_info_id, $total_score, $interview_percentage);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Interview scores saved successfully!";
        // Refresh applicant data
        $result = $stmt->get_result();
        $applicant_data = $result->fetch_assoc();
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?applicant_id=" . $applicant_id);
        exit;
    } else {
        $_SESSION['error_message'] = "Error saving interview scores.";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?applicant_id=" . $applicant_id);
        exit;
    }
}
?>

<style>
    .interview-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
    }
    
    .applicant-info {
        background: linear-gradient(135deg, rgb(0, 105, 42), rgb(0, 85, 34));
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
    }
    
    .score-input {
        width: 80px;
        text-align: center;
    }
    
    .btn-save {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
        font-weight: 600;
    }
    
    .btn-save:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 105, 42, 0.3);
    }
    
    .btn-outline-success:hover {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
        color: white;
    }
    
    .score-display {
        font-size: 1.2em;
        font-weight: bold;
        color: rgb(0, 105, 42);
    }
    
    .page-header {
        margin-bottom: 2rem;
        padding-top: 1rem;
    }
    
    /* Custom radio button styling to match header green */
    input[type="radio"] {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid rgb(0, 105, 42);
        border-radius: 50%;
        background-color: white;
        cursor: pointer;
        position: relative;
        margin-right: 8px;
    }
    
    input[type="radio"]:checked {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    input[type="radio"]:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        background-color: white;
        border-radius: 50%;
    }
    
    input[type="radio"]:hover {
        border-color: rgb(0, 85, 34);
        box-shadow: 0 0 5px rgba(0, 105, 42, 0.3);
    }
    
    input[type="radio"]:checked:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
</style>

<div class="container-fluid px-4" style="padding-top: 200px;">
    <!-- Header with Back Button -->
    <div class="row mb-4 page-header">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="?page=interviewer_applicants" class="btn btn-outline-success me-3" style="border-color: rgb(0, 105, 42); color: rgb(0, 105, 42);">
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
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            <?= htmlspecialchars($applicant_data['last_name'] . ', ' . $applicant_data['first_name'] . ' ' . $applicant_data['middle_name']) ?>
                        </h5>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-graduation-cap me-1"></i>
                            <?= htmlspecialchars($applicant_data['program']) ?> - <?= htmlspecialchars($applicant_data['campus']) ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-book me-1"></i>
                            <?= htmlspecialchars($applicant_data['strand']) ?>
                        </p>
                    </div>

                    <!-- Interview Form -->
                    <div class="p-4">
                        <!-- Display success/error messages as toasts -->
                        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
                            <?php if (isset($_SESSION['success_message'])): ?>
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
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['error_message'])): ?>
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
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Communication Skills (1-100)</label>
                                        <input type="number" name="communication_skills" class="form-control score-input" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Problem Solving (1-100)</label>
                                        <input type="number" name="problem_solving" class="form-control score-input" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Motivation (1-100)</label>
                                        <input type="number" name="motivation" class="form-control score-input" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Knowledge (1-100)</label>
                                        <input type="number" name="knowledge" class="form-control score-input" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Overall Impression (1-100)</label>
                                        <input type="number" name="overall_impression" class="form-control score-input" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Total Score</label>
                                        <div class="score-display" id="totalScore">
                                            <?= $applicant_data['interview_total_score'] ? number_format($applicant_data['interview_total_score'], 2) : '0.00' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="submit_interview" class="btn btn-save btn-lg" onclick="return confirmInterviewSubmission()">
                                    <i class="fas fa-save me-2"></i>Save Interview Scores
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
                        <a href="?page=interviewer_applicants" class="btn btn-outline-success" style="border-color: rgb(0, 105, 42); color: rgb(0, 105, 42);">
                            <i class="fas fa-users me-2"></i>View Applicants List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Confirmation function for interview submission
function confirmInterviewSubmission() {
    const communicationSkills = document.querySelector('input[name="communication_skills"]').value;
    const problemSolving = document.querySelector('input[name="problem_solving"]').value;
    const motivation = document.querySelector('input[name="motivation"]').value;
    const knowledge = document.querySelector('input[name="knowledge"]').value;
    const overallImpression = document.querySelector('input[name="overall_impression"]').value;
    
    // Check if all fields are filled
    if (!communicationSkills || !problemSolving || !motivation || !knowledge || !overallImpression) {
        alert('Please fill in all interview scores before submitting.');
        return false;
    }
    
    // Calculate total score for confirmation
    const totalScore = ((parseFloat(communicationSkills) + parseFloat(problemSolving) + parseFloat(motivation) + parseFloat(knowledge) + parseFloat(overallImpression)) / 5).toFixed(2);
    
    const confirmMessage = `Are you sure you want to save these interview scores?\n\n` +
                         `Communication Skills: ${communicationSkills}\n` +
                         `Problem Solving: ${problemSolving}\n` +
                         `Motivation: ${motivation}\n` +
                         `Knowledge: ${knowledge}\n` +
                         `Overall Impression: ${overallImpression}\n\n` +
                         `Total Score: ${totalScore}\n\n` +
                         `This action cannot be undone.`;
    
    return confirm(confirmMessage);
}

// Auto-calculate total score
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    const totalScoreDisplay = document.getElementById('totalScore');
    
    function calculateTotal() {
        let total = 0;
        let count = 0;
        
        scoreInputs.forEach(input => {
            if (input.value && !isNaN(input.value)) {
                total += parseFloat(input.value);
                count++;
            }
        });
        
        if (count > 0) {
            const average = total / count;
            totalScoreDisplay.textContent = average.toFixed(2);
        } else {
            totalScoreDisplay.textContent = '0.00';
        }
    }
    
    scoreInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Initial calculation
    calculateTotal();
    
    // Show toast notifications if they exist
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
});
</script>
