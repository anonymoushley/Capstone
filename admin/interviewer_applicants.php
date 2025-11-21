<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle interview form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_interview'])) {
    $applicant_id = $_POST['applicant_id'] ?? null;
    $communication_skills = floatval($_POST['communication_skills']);
    $problem_solving = floatval($_POST['problem_solving']);
    $motivation = floatval($_POST['motivation']);
    $knowledge = floatval($_POST['knowledge']);
    $overall_impression = floatval($_POST['overall_impression']);
    
    // Calculate total score (out of 100)
    $total_score = ($communication_skills + $problem_solving + $motivation + $knowledge + $overall_impression) / 5;
    
    // Calculate interview percentage: (interview_total_score / 100) × 35
    $interview_percentage = ($total_score / 100) * 35;
    
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
        $update_sql = "INSERT INTO screening_results (personal_info_id, interview_total_score, interview_percentage, communication_skills, problem_solving, motivation, knowledge, overall_impression, interview_date)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                       ON DUPLICATE KEY UPDATE 
                       interview_total_score = VALUES(interview_total_score),
                       interview_percentage = VALUES(interview_percentage),
                       communication_skills = VALUES(communication_skills),
                       problem_solving = VALUES(problem_solving),
                       motivation = VALUES(motivation),
                       knowledge = VALUES(knowledge),
                       overall_impression = VALUES(overall_impression),
                       interview_date = NOW()";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("idddddd", $personal_info_id, $total_score, $interview_percentage, $communication_skills, $problem_solving, $motivation, $knowledge, $overall_impression);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Interview scores saved successfully!";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewer_applicants");
            exit;
        } else {
            $_SESSION['error_message'] = "Error saving interview scores.";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewer_applicants");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Applicant not found.";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewer_applicants");
        exit;
    }
}

// Get search term
$search = $_GET['search'] ?? '';

// Check if interview_schedule_applicants table exists
$table_check = $conn->query("SHOW TABLES LIKE 'interview_schedule_applicants'");
$table_exists = $table_check && $table_check->num_rows > 0;

// Get applicants for interview (completed exam - same as scheduling page)
// Fetch from exam_answers table only - if record exists, exam was completed
$applicants = [];
$sql = "SELECT 
            r.id AS registration_id,
            r.applicant_status,
            pi.last_name,
            pi.first_name,
            pi.middle_name,
            ea.points_earned,
            ea.points_possible,
            sr.interview_total_score,
            isa.schedule_id,
            isch.event_date,
            isch.event_time,
            isch.venue
        FROM exam_answers ea
        INNER JOIN registration r ON ea.applicant_id = r.id
        INNER JOIN personal_info pi ON r.personal_info_id = pi.id
        LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
        INNER JOIN interview_schedule_applicants isa ON r.id = isa.applicant_id
        INNER JOIN interview_schedules isch ON isa.schedule_id = isch.id
        INNER JOIN (
            SELECT applicant_id, MAX(submitted_at) as latest_submitted
            FROM exam_answers
            GROUP BY applicant_id
        ) latest_ea ON ea.applicant_id = latest_ea.applicant_id AND ea.submitted_at = latest_ea.latest_submitted
        WHERE (sr.interview_total_score IS NULL OR sr.interview_total_score = 0 OR sr.interview_total_score = '')";

// Add search conditions if search term is provided
if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $sql .= " AND (
        r.id LIKE ? OR
        pi.last_name LIKE ? OR 
        pi.first_name LIKE ? OR 
        pi.middle_name LIKE ?
    )";
}

$sql .= " ORDER BY pi.last_name ASC, pi.first_name ASC, pi.middle_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($search)) {
        $stmt->bind_param("isss", $search, $search_term, $search_term, $search_term);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $applicants = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
?>

<style>
    .table-container {
        max-height: 70vh;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    /* Custom Scrollbar Styling */
    .table-container::-webkit-scrollbar {
        width: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: rgb(0, 105, 42);
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: rgb(0, 85, 34);
    }

    /* Hide scrollbar for Firefox */
    .table-container {
        scrollbar-width: thin;
        scrollbar-color: rgb(0, 105, 42) #f1f1f1;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        background-color: rgb(0, 105, 42) !important;
        color: white !important;
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
    }

    .table-container thead tr {
        background-color: rgb(0, 105, 42) !important;
    }

    .table-container thead {
        background-color: rgb(0, 105, 42) !important;
    }

    .status-badge {
        font-size: 0.8em;
        padding: 4px 8px;
    }

    .btn-interview {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
    }

    .btn-interview:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }

    .btn-interview:active {
        background-color: rgb(0, 65, 26);
        border-color: rgb(0, 65, 26);
        color: white;
    }

    .badge-completed {
        background-color: rgb(0, 105, 42) !important;
        color: white;
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-users me-2"></i>Applicants for Interview</h4>
                <div class="badge fs-6" style="background-color: rgb(0, 105, 42);">
                    <?php 
                    $total_applicants = count($applicants);
                    echo $total_applicants . " Applicant" . ($total_applicants != 1 ? 's' : '');
                    ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Search Bar -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Search by applicant number or name..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Interview Queue</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-bordered table-striped text-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Applicant No.</th>
                            <th class="text-center">Name</th>
                            <th class="text-center">Schedule</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applicants)): ?>
                            <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td class="text-center">
                                        <strong><?= $applicant['registration_id'] ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= htmlspecialchars(ucwords(strtolower($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name']))) ?>
                                    </td>
                                    <td>
                                        <?php if ($applicant['event_date'] && $applicant['event_time']): ?>
                                            <small>
                                                <strong><?= date('M d, Y', strtotime($applicant['event_date'])) ?></strong><br>
                                                <?= date('g:i A', strtotime($applicant['event_time'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Not scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="interviewer_interview_form.php?applicant_id=<?= $applicant['registration_id'] ?>" class="btn btn-interview btn-sm">
                                            <i class="fas fa-microphone me-1"></i>
                                            Interview
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Applicants for Interview</h5>
                                    <p class="text-muted">There are currently no applicants scheduled for interview.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
// Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.querySelector('tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        if (searchTerm === '') {
            // Show all rows if search is empty
            originalRows.forEach(row => {
                tableBody.appendChild(row.cloneNode(true));
            });
        } else {
            // Filter rows based on search term
            const filteredRows = originalRows.filter(row => {
                const cells = row.querySelectorAll('td');
                let rowText = '';
                
                cells.forEach(cell => {
                    rowText += cell.textContent.toLowerCase() + ' ';
                });
                
                return rowText.includes(searchTerm);
            });
            
            if (filteredRows.length > 0) {
                filteredRows.forEach(row => {
                    tableBody.appendChild(row.cloneNode(true));
                });
            } else {
                // Show "no results" message
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Results Found</h5>
                            <p class="text-muted">No applicants match your search criteria.</p>
                        </td>
                    </tr>
                `;
            }
        }
    });
});
</script>
