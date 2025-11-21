<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is logged in as chairperson
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'chairperson') {
    echo "<div class='alert alert-danger'>Access denied. Please login as chairperson.</div>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "admission");

// Get chairperson's assigned program and campus
$chair_program = $_SESSION['program'] ?? '';
$chair_campus = $_SESSION['campus'] ?? '';

if (!$chair_program || !$chair_campus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

// SQL query with proper FROM and JOINs
$sql = "
    SELECT 
        pi.id as personal_info_id,
        pi.last_name, pi.first_name, pi.contact_number,
        s.name as strand, ab.g11_1st_avg, ab.g11_2nd_avg, ab.g12_1st_avg,
        sr.gwa_score, sr.stanine_result, sr.stanine_score, 
        sr.exam_total_score, sr.interview_total_score,
        sr.plus_factor, sr.rank, d.ncii_status,
        ea.points_earned, ea.points_possible
    FROM program_application pa
    LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
    LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
    LEFT JOIN strands s ON ab.strand_id = s.id
    LEFT JOIN documents d ON d.personal_info_id = pi.id
    LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
    LEFT JOIN registration r ON r.personal_info_id = pi.id
    LEFT JOIN exam_answers ea ON ea.applicant_id = r.id
    WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
    ORDER BY 
        CASE WHEN sr.rank IS NULL THEN 1 ELSE 0 END,
        sr.rank ASC,
        sr.final_rating DESC,
        pi.last_name ASC,
        pi.first_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $chair_program, $chair_campus);
$stmt->execute();
$result = $stmt->get_result();

// Calculate and save plus_factor for all applicants in the results
// Fetch all rows first to calculate plus factors
$applicants_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $applicants_data[] = $row;
}

// Calculate and update plus_factor for all applicants
foreach ($applicants_data as $row) {
    $personal_info_id = $row['personal_info_id'];
    $strand = $row['strand'] ?? '';
    $ncii_status = $row['ncii_status'] ?? '';
    
    // Calculate plus factor
    $calculated_plus_factor = calculatePlusFactor($strand, $ncii_status);
    
    // Update or insert plus_factor in screening_results
    $update_plus_factor_sql = "INSERT INTO screening_results (personal_info_id, plus_factor) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE plus_factor = VALUES(plus_factor)";
    $update_stmt = $conn->prepare($update_plus_factor_sql);
    $update_stmt->bind_param("id", $personal_info_id, $calculated_plus_factor);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Recalculate and update final_rating since it depends on plus_factor
    // final_rating = initial_total + exam_percentage + interview_percentage + plus_factor
    $update_final_sql = "UPDATE screening_results 
                        SET final_rating = ROUND(
                            IFNULL(initial_total, 0) + 
                            IFNULL(exam_percentage, 0) + 
                            IFNULL(interview_percentage, 0) + 
                            IFNULL(plus_factor, 0), 
                            2
                        )
                        WHERE personal_info_id = ?";
    $update_final_stmt = $conn->prepare($update_final_sql);
    $update_final_stmt->bind_param("i", $personal_info_id);
    $update_final_stmt->execute();
    $update_final_stmt->close();
}

// Recalculate ranks for all applicants in this program and campus
// This ensures rankings are always up-to-date based on final_rating
$recalculate_ranks_sql = "
    SELECT sr.personal_info_id, IFNULL(sr.final_rating, 0) AS final_rating
    FROM screening_results sr
    JOIN personal_info pi ON pi.id = sr.personal_info_id
    JOIN program_application pa ON pa.personal_info_id = pi.id
    WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
    ORDER BY final_rating DESC, sr.personal_info_id ASC
";
$recalc_stmt = $conn->prepare($recalculate_ranks_sql);
$recalc_stmt->bind_param("ss", $chair_program, $chair_campus);
$recalc_stmt->execute();
$recalc_result = $recalc_stmt->get_result();

$position = 0;
$currentRank = 0;
$lastScore = null;
$update_rank_stmt = $conn->prepare("UPDATE screening_results SET rank = ? WHERE personal_info_id = ?");

while ($rank_row = $recalc_result->fetch_assoc()) {
    $position += 1;
    $score = (float)$rank_row['final_rating'];
    if ($lastScore === null || $score !== $lastScore) {
        $currentRank = $position;
        $lastScore = $score;
    }
    $pid = (int)$rank_row['personal_info_id'];
    $update_rank_stmt->bind_param("ii", $currentRank, $pid);
    $update_rank_stmt->execute();
}
$update_rank_stmt->close();
$recalc_stmt->close();

// Re-fetch results with updated plus_factor values
$stmt->execute();
$result = $stmt->get_result();

// Store screening results in array
$screening_results = [];
while ($row = mysqli_fetch_assoc($result)) {
    $screening_results[] = $row;
}

// CHED Reports Query - Only applicants with at least one determinant
$ched_sql = "
    SELECT 
        pi.id as personal_info_id,
        pi.last_name, 
        pi.first_name, 
        pi.contact_number,
        s.name as strand,
        sd.first_gen_college,
        sd.indigenous_group,
        sd.has_disability
    FROM program_application pa
    LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
    LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
    LEFT JOIN strands s ON ab.strand_id = s.id
    LEFT JOIN socio_demographic sd ON sd.personal_info_id = pi.id
    WHERE LOWER(pa.program) = LOWER(?) 
        AND LOWER(pa.campus) = LOWER(?)
        AND (
            (sd.first_gen_college = 'Yes' OR sd.first_gen_college = '1')
            OR (sd.indigenous_group = 'Yes' OR sd.indigenous_group = '1')
            OR (sd.has_disability = 'Yes' OR sd.has_disability = '1')
        )
    ORDER BY pi.last_name, pi.first_name
";

$ched_stmt = $conn->prepare($ched_sql);
$ched_stmt->bind_param("ss", $chair_program, $chair_campus);
$ched_stmt->execute();
$ched_result = $ched_stmt->get_result();

// Store CHED results in array
$ched_results = [];
while ($row = mysqli_fetch_assoc($ched_result)) {
    $ched_results[] = $row;
}

// Exam Reports Query
$exam_sql = "
    SELECT 
        pi.id as personal_info_id,
        pi.last_name, 
        pi.first_name, 
        pi.contact_number,
        s.name as strand,
        ea.points_earned,
        ea.points_possible,
        ea.submitted_at,
        sr.exam_total_score,
        sr.exam_percentage
    FROM program_application pa
    LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
    LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
    LEFT JOIN strands s ON ab.strand_id = s.id
    LEFT JOIN registration r ON r.personal_info_id = pi.id
    LEFT JOIN exam_answers ea ON ea.applicant_id = r.id
    LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
    WHERE LOWER(pa.program) = LOWER(?) 
        AND LOWER(pa.campus) = LOWER(?)
        AND ea.points_earned IS NOT NULL
    ORDER BY sr.exam_percentage DESC, pi.last_name, pi.first_name
";

$exam_stmt = $conn->prepare($exam_sql);
$exam_stmt->bind_param("ss", $chair_program, $chair_campus);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();

// Store Exam results in array and update missing exam_percentage values
$exam_results = [];
$update_exam_pct_stmt = $conn->prepare("UPDATE screening_results SET exam_percentage = ? WHERE personal_info_id = ?");
while ($row = mysqli_fetch_assoc($exam_result)) {
    // If exam_percentage is missing or 0 but exam_total_score exists, calculate and update it
    if ((!isset($row['exam_percentage']) || !is_numeric($row['exam_percentage']) || $row['exam_percentage'] == 0) 
        && isset($row['exam_total_score']) && is_numeric($row['exam_total_score']) && $row['exam_total_score'] > 0) {
        $calculated_pct = round(($row['exam_total_score'] / 100) * 40, 2);
        $update_exam_pct_stmt->bind_param("di", $calculated_pct, $row['personal_info_id']);
        $update_exam_pct_stmt->execute();
        $row['exam_percentage'] = $calculated_pct; // Update the row data for display
    }
    $exam_results[] = $row;
}
$update_exam_pct_stmt->close();

// Interview Reports Query
$interview_sql = "
    SELECT 
        pi.id as personal_info_id,
        pi.last_name, 
        pi.first_name, 
        pi.contact_number,
        s.name as strand,
        sr.interview_total_score,
        sr.interview_percentage
    FROM program_application pa
    LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
    LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
    LEFT JOIN strands s ON ab.strand_id = s.id
    LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
    WHERE LOWER(pa.program) = LOWER(?) 
        AND LOWER(pa.campus) = LOWER(?)
        AND sr.interview_total_score IS NOT NULL
    ORDER BY sr.interview_percentage DESC, pi.last_name, pi.first_name
";

$interview_stmt = $conn->prepare($interview_sql);
$interview_stmt->bind_param("ss", $chair_program, $chair_campus);
$interview_stmt->execute();
$interview_result = $interview_stmt->get_result();

// Store Interview results in array
$interview_results = [];
while ($row = mysqli_fetch_assoc($interview_result)) {
    $interview_results[] = $row;
}
?>


<style>
    /* Prevent page scrolling for reports page */
    .main-content .container-fluid {
        height: calc(100vh - 90px);
        max-height: calc(100vh - 90px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 15px 15px 0 15px !important;
        padding-top: 30px !important;
        padding-bottom: 0 !important;
    }
    
    /* Header row with back button and tabs */
    .main-content .container-fluid > .row:first-of-type {
        flex-shrink: 0;
        margin-top: 15px;
        margin-bottom: 20px;
        position: relative !important;
        z-index: 1;
    }
    
    /* Ensure navigation elements don't stick */
    .no-print {
        position: relative !important;
    }
    
    .nav-tabs {
        position: relative !important;
    }
    
    /* Prevent any sticky positioning on navigation row */
    .container-fluid > .row.no-print {
        position: relative !important;
        top: auto !important;
        bottom: auto !important;
        left: auto !important;
        right: auto !important;
    }
    
    /* Ensure back button and tabs container don't stick */
    .container-fluid > .row.no-print .d-flex,
    .container-fluid > .row.no-print .btn,
    .container-fluid > .row.no-print .nav-tabs {
        position: relative !important;
        top: auto !important;
    }
    
    .tab-content {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    
    .tab-pane {
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .tab-pane.active {
        display: flex !important;
    }
    
    .tab-pane:not(.active) {
        display: none !important;
    }
    
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;
        margin-bottom: 0;
        border: none;
        box-shadow: none;
    }
    
    .card-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
        padding: 0 !important;
    }
    
    th, td { 
        vertical-align: middle !important; 
        font-size: 11px; 
    }
    
    .card-header { 
        background-color: rgb(0, 105, 42) !important; 
        color: white !important;
        flex-shrink: 0;
        padding: 1rem !important;
    }
    
    .card-header .d-flex {
        margin-bottom: 0.5rem;
    }
    
    .card-header .w-50 {
        width: 50% !important;
    }
    
    .table-container {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        width: 100%;
        min-height: 0;
        margin: 0;
    }
    
    /* Make tab content stretch to edges */
    .tab-content {
        margin: 0 -15px;
        padding: 0 15px 0 15px;
        padding-bottom: 0 !important;
        height: 100%;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }
    
    .card.shadow-sm {
        margin-left: -15px;
        margin-right: -15px;
        margin-bottom: 0 !important;
        margin-top: 0;
        border-radius: 0;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .card.shadow-sm .card-header {
        border-radius: 0;
    }
    
    .card.shadow-sm .table-container {
        border-left: none;
        border-right: none;
        border-bottom: none;
        border-radius: 0;
        flex: 1;
        min-height: 0;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .card.shadow-sm .card-body {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    /* Custom Scrollbar Styling */
    .table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
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

    /* Scrollbar for Firefox */
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
        white-space: normal;
    }
    
    .table-container table {
        width: 100% !important;
        table-layout: fixed;
        margin-bottom: 0;
    }
    
    .table-container table td {
        padding: 8px 6px !important;
        font-size: 11px;
        word-wrap: break-word;
    }
    
    .table-container table th {
        padding: 10px 6px !important;
        font-size: 11px;
        white-space: normal;
        line-height: 1.3;
        text-align: center;
    }
    
    .table-container table th small {
        display: block;
        font-size: 9px;
        font-weight: 400;
        margin-top: 2px;
        opacity: 0.9;
    }

    .table-container thead tr {
        background-color: rgb(0, 105, 42) !important;
    }

    .table-container thead {
        background-color: rgb(0, 105, 42) !important;
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

    /* Print Styling */
    @media print {
        @page {
            margin: 0.5in;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .no-print {
            display: none !important;
        }
        
        /* Hide card headers (green headers with print/search buttons) */
        .card-header {
            display: none !important;
        }
        
        /* Hide all print headers (logo headers) when printing */
        .print-header {
            display: none !important;
        }
        
        /* Remove scrollbar and height restrictions when printing */
        .table-container {
            max-height: none !important;
            overflow: visible !important;
            height: auto !important;
        }
        
        /* Hide "no data found" message when printing */
        .no-data-found {
            display: none !important;
        }
        
        body {
            background: white !important;
            color: black !important;
            font-size: 12px;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
        
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
        
        .table {
            font-size: 10px !important;
        }
        
        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
        }
    }
    
    .print-header {
        display: none;
    }

    /* Stanine input styling */
    .stanine-input {
        width: 100%;
        max-width: 70px;
        text-align: center;
        font-size: 10px;
        padding: 4px 6px;
        margin: 0 auto;
        display: block;
    }
    
    /* Remove number input arrows */
    .stanine-input::-webkit-outer-spin-button,
    .stanine-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    /* Remove number input arrows for Firefox */
    .stanine-input[type=number] {
        -moz-appearance: textfield;
    }
    
    .stanine-input::placeholder {
        font-size: 8px;
        color: #999;
    }

    .stanine-input:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
        outline: none;
    }
    
    /* Stanine input cell styling */
    .table-container table td .stanine-input {
        margin: 0 auto;
    }
    
    /* Contact number cell styling */
    .table-container table td {
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    /* Ensure contact numbers wrap properly and have smaller font */
    #screeningTable td:nth-child(3) {
        word-break: break-all;
        font-size: 9px !important;
    }
    
    /* Smooth transition for row reordering */
    #screeningTable tbody tr {
        transition: all 0.3s ease;
    }
    
    /* Column width adjustments to keep name on one line */
    #screeningTable th:nth-child(1),
    #screeningTable td:nth-child(1) {
        width: 12% !important;
        min-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    #screeningTable th:nth-child(2),
    #screeningTable td:nth-child(2) {
        width: 6% !important;
        min-width: 80px;
    }
    
    #screeningTable th:nth-child(3),
    #screeningTable td:nth-child(3) {
        width: 5% !important;
        min-width: 90px;
    }
    
    #screeningTable th:nth-child(4),
    #screeningTable td:nth-child(4),
    #screeningTable th:nth-child(5),
    #screeningTable td:nth-child(5),
    #screeningTable th:nth-child(6),
    #screeningTable td:nth-child(6) {
        width: 4.5% !important;
        min-width: 65px;
    }
    
    #screeningTable th:nth-child(7),
    #screeningTable td:nth-child(7),
    #screeningTable th:nth-child(8),
    #screeningTable td:nth-child(8),
    #screeningTable th:nth-child(9),
    #screeningTable td:nth-child(9),
    #screeningTable th:nth-child(10),
    #screeningTable td:nth-child(10) {
        width: 5% !important;
        min-width: 70px;
    }
    
    #screeningTable th:nth-child(11),
    #screeningTable td:nth-child(11),
    #screeningTable th:nth-child(12),
    #screeningTable td:nth-child(12),
    #screeningTable th:nth-child(13),
    #screeningTable td:nth-child(13),
    #screeningTable th:nth-child(14),
    #screeningTable td:nth-child(14),
    #screeningTable th:nth-child(15),
    #screeningTable td:nth-child(15) {
        width: 5.5% !important;
        min-width: 75px;
    }
    
    #screeningTable th:nth-child(16),
    #screeningTable td:nth-child(16) {
        width: 6% !important;
        min-width: 85px;
    }
    
    #screeningTable th:nth-child(17),
    #screeningTable td:nth-child(17) {
        width: 4% !important;
        min-width: 50px;
    }
</style>

<div class="container-fluid">
    <!-- Print Headers (hidden on screen, visible when printing) -->
    <div class="print-header" id="screening-print-header">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>Applicant Screening Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h4>
    </div>
    <div class="print-header" id="ched-print-header" style="display: none;">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>CHED Reports - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h4>
    </div>
    <div class="print-header" id="exam-print-header" style="display: none;">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>Exam Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h4>
    </div>
    <div class="print-header" id="interview-print-header" style="display: none;">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>Interview Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h4>
    </div>
    <!-- Header with Back Button and Tabs -->
    <div class="row no-print">
        <div class="col-12">
            <div class="d-flex align-items-center flex-wrap">
                <a href="chair_main.php?page=chair_dashboard" class="btn btn-outline-success me-3 mb-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-2 no-print" id="reportsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="screening-tab" data-bs-toggle="tab" data-bs-target="#screening" type="button" role="tab" aria-controls="screening" aria-selected="true">
                            <i class="fas fa-chart-bar me-2"></i>Screening Report
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ched-tab" data-bs-toggle="tab" data-bs-target="#ched" type="button" role="tab" aria-controls="ched" aria-selected="false">
                            <i class="fas fa-users me-2"></i>CHED Reports
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="exam-tab" data-bs-toggle="tab" data-bs-target="#exam" type="button" role="tab" aria-controls="exam" aria-selected="false">
                            <i class="fas fa-file-alt me-2"></i>Exam Report
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="interview-tab" data-bs-toggle="tab" data-bs-target="#interview" type="button" role="tab" aria-controls="interview" aria-selected="false">
                            <i class="fas fa-comments me-2"></i>Interview Report
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="reportsTabContent">
        <!-- Screening Report Tab -->
        <div class="tab-pane fade show active" id="screening" role="tabpanel" aria-labelledby="screening-tab">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Screening Results - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>
                        <button class="btn btn-sm pdf-btn" data-report-type="screening" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid rgb(0, 105, 42);">
                            <i class="fas fa-file-pdf me-1"></i> Save as PDF
                        </button>
                    </div>
                    <div class="w-50">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="screeningSearch" class="form-control" placeholder="Search by name, strand  or contact number">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table id="screeningTable" class="table table-bordered table-striped text-center mb-0 align-middle">
                    <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                    <tr>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Strand</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Contact</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">G11 1st</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">G11 2nd</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">G12 1st</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">GWA<br><small>(10%)</small></th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Stanine</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Stanine Score<br><small>(15%)</small></th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Initial Total</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Exam Score</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Exam<br><small>(40%)</small></th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Interview Score</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Interview<br><small>(35%)</small></th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Plus Factor</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Final Rating</th>
                        <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Rank</th>
                    </tr>
                </thead>
                <tbody id="screeningTableBody">
                    <?php if (empty($screening_results)): ?>
                        <tr>
                            <td colspan="17" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                No screening results found.
                            </td>
                        </tr>
                    <?php else: ?>
                    <?php foreach($screening_results as $row): 
                        // Use 0 if NULL
                        $gwa_score = is_numeric($row['gwa_score']) ? $row['gwa_score'] : 0;
                        $stanine_score = is_numeric($row['stanine_score']) ? $row['stanine_score'] : 0;
                        $exam_score = is_numeric($row['exam_total_score']) ? $row['exam_total_score'] : 0;
                        $interview_score = is_numeric($row['interview_total_score']) ? $row['interview_total_score'] : 0;
                        // Use stored plus_factor from database, or calculate if not stored
                        $plus_factor = is_numeric($row['plus_factor']) ? $row['plus_factor'] : calculatePlusFactor($row['strand'], $row['ncii_status']);

                        // Calculate weights
                        $gwa_pct = ($gwa_score / 100) * 10;
                        $stanine_pct = $stanine_score * 0.15;
                        $initial_total = $gwa_pct + $stanine_pct;
                        $exam_pct = ($exam_score / 100) * 40;
                        $interview_pct = ($interview_score / 100) * 35;
                        $final_rating = $initial_total + $exam_pct + $interview_pct + $plus_factor;
                        ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                        <td><?= $row['strand'] ?: '-' ?></td>
                        <td><?= $row['contact_number'] ?: '-' ?></td>
                        <td><?= is_numeric($row['g11_1st_avg']) ? number_format($row['g11_1st_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g11_2nd_avg']) ? number_format($row['g11_2nd_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g12_1st_avg']) ? number_format($row['g12_1st_avg'], 2) : '-' ?></td>
                        <td><?= number_format((($row['g11_1st_avg']+$row['g11_2nd_avg']+$row['g12_1st_avg'])/3)*.1, 2) ?></td>
                        <td>
                            <input type="number" class="form-control form-control-sm stanine-input" 
                                   value="<?= htmlspecialchars($row['stanine_result'] ?: '') ?>" 
                                   data-applicant-id="<?= $row['personal_info_id'] ?? '' ?>"
                                   placeholder="Enter stanine"
                                   min="1"
                                   max="9"
                                   step="1">
                        </td>
                        <td><?= number_format($stanine_pct, 2) ?></td>
                        <td><?= number_format($initial_total, 2) ?></td>
                        <td>
                            <?php 
                            $points_earned = isset($row['points_earned']) && is_numeric($row['points_earned']) ? $row['points_earned'] : null;
                            $points_possible = isset($row['points_possible']) && is_numeric($row['points_possible']) ? $row['points_possible'] : null;
                            if ($points_earned !== null && $points_possible !== null && $points_possible > 0) {
                                echo number_format($points_earned, 0) . '/' . number_format($points_possible, 0);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= number_format($exam_pct, 2) ?></td>
                        <td><?= number_format($interview_score, 2) ?></td>
                        <td><?= number_format($interview_pct, 2) ?></td>
                        <td><?= number_format($plus_factor, 2) ?></td>
                        <td><strong><?= number_format($final_rating, 2) ?></strong></td>
                        <td><?= $row['rank'] ?: '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHED Reports Tab -->
        <div class="tab-pane fade" id="ched" role="tabpanel" aria-labelledby="ched-tab">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>CHED Reports - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>
                        <button class="btn btn-sm pdf-btn" data-report-type="ched" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid rgb(0, 105, 42);">
                            <i class="fas fa-file-pdf me-1"></i> Save as PDF
                        </button>
                    </div>
                    <div class="w-50">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="chedSearch" class="form-control" placeholder="Search by name or contact number">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table id="chedTable" class="table table-bordered table-striped text-center mb-0 align-middle">
                            <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                                <tr>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Strand</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Contact Number</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">1st Generation in College</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Member of Indigenous Group</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Person with Disability</th>
                                </tr>
                            </thead>
                            <tbody id="chedTableBody">
                                <?php 
                                if (empty($ched_results)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                            No applicants found with the specified determinants.
                                        </td>
                                    </tr>
                                <?php else: 
                                    foreach($ched_results as $row): 
                                        // Check determinant values (handle both 'Yes'/'No' and '1'/'0' formats)
                                        $is_first_gen = (strtolower($row['first_gen_college'] ?? '') === 'yes' || $row['first_gen_college'] === '1');
                                        $is_indigenous = (strtolower($row['indigenous_group'] ?? '') === 'yes' || $row['indigenous_group'] === '1');
                                        $has_disability = (strtolower($row['has_disability'] ?? '') === 'yes' || $row['has_disability'] === '1');
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                                        <td><?= htmlspecialchars($row['strand'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['contact_number'] ?: '-') ?></td>
                                        <td>
                                            <?php if ($is_first_gen): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_indigenous): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_disability): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; 
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Report Tab -->
        <div class="tab-pane fade" id="exam" role="tabpanel" aria-labelledby="exam-tab">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Exam Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>
                        <button class="btn btn-sm pdf-btn" data-report-type="exam" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid rgb(0, 105, 42);">
                            <i class="fas fa-file-pdf me-1"></i> Save as PDF
                        </button>
                    </div>
                    <div class="w-50">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="examSearch" class="form-control" placeholder="Search by name, strand or contact number">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table id="examTable" class="table table-bordered table-striped text-center mb-0 align-middle">
                            <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                                <tr>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Strand</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Contact Number</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Exam Score</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Exam<br><small>(40%)</small></th>
                                </tr>
                            </thead>
                            <tbody id="examTableBody">
                                <?php 
                                if (empty($exam_results)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                            No exam results found.
                                        </td>
                                    </tr>
                                <?php else: 
                                    foreach($exam_results as $row): 
                                        $points_earned = isset($row['points_earned']) && is_numeric($row['points_earned']) ? $row['points_earned'] : null;
                                        $points_possible = isset($row['points_possible']) && is_numeric($row['points_possible']) ? $row['points_possible'] : null;
                                        
                                        // Calculate exam_percentage - use stored value if available, otherwise calculate from exam_total_score or points
                                        $exam_percentage = 0;
                                        if (isset($row['exam_percentage']) && is_numeric($row['exam_percentage']) && $row['exam_percentage'] > 0) {
                                            $exam_percentage = $row['exam_percentage'];
                                        } elseif (isset($row['exam_total_score']) && is_numeric($row['exam_total_score']) && $row['exam_total_score'] > 0) {
                                            // Calculate from exam_total_score: (exam_total_score / 100) * 40
                                            $exam_percentage = round(($row['exam_total_score'] / 100) * 40, 2);
                                        } elseif ($points_earned !== null && $points_possible !== null && $points_possible > 0) {
                                            // Fallback: calculate from points if exam_total_score is not available
                                            $percentScore = round(($points_earned / $points_possible) * 100, 2);
                                            $exam_percentage = round(($percentScore / 100) * 40, 2);
                                        }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                                        <td><?= htmlspecialchars($row['strand'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['contact_number'] ?: '-') ?></td>
                                        <td>
                                            <?php 
                                            if ($points_earned !== null && $points_possible !== null && $points_possible > 0) {
                                                echo number_format($points_earned, 0) . '/' . number_format($points_possible, 0);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?= number_format($exam_percentage, 2) ?></td>
                                    </tr>
                                <?php endforeach; 
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interview Report Tab -->
        <div class="tab-pane fade" id="interview" role="tabpanel" aria-labelledby="interview-tab">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Interview Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>
                        <button class="btn btn-sm pdf-btn" data-report-type="interview" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid rgb(0, 105, 42);">
                            <i class="fas fa-file-pdf me-1"></i> Save as PDF
                        </button>
                    </div>
                    <div class="w-50">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="interviewSearch" class="form-control" placeholder="Search by name, strand or contact number">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table id="interviewTable" class="table table-bordered table-striped text-center mb-0 align-middle">
                            <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                                <tr>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Strand</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Contact Number</th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Interview Score<br><small>(%)</small></th>
                                    <th style="background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Interview Percentage<br><small>(35%)</small></th>
                                </tr>
                            </thead>
                            <tbody id="interviewTableBody">
                                <?php 
                                if (empty($interview_results)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                            No interview results found.
                                        </td>
                                    </tr>
                                <?php else: 
                                    foreach($interview_results as $row): 
                                        $interview_score = isset($row['interview_total_score']) && is_numeric($row['interview_total_score']) ? $row['interview_total_score'] : 0;
                                        $interview_percentage = isset($row['interview_percentage']) && is_numeric($row['interview_percentage']) ? $row['interview_percentage'] : 0;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                                        <td><?= htmlspecialchars($row['strand'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['contact_number'] ?: '-') ?></td>
                                        <td><?= number_format($interview_score, 2) ?></td>
                                        <td><?= number_format($interview_percentage, 2) ?></td>
                                    </tr>
                                <?php endforeach; 
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    .nav-tabs .nav-link {
        color: rgb(0, 105, 42);
        border: 1px solid transparent;
        border-radius: 0.5rem;
        font-weight: 500;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        color: rgb(0, 105, 42);
    }
    
    .nav-tabs .nav-link.active {
        color: white;
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42) rgb(0, 105, 42) rgb(0, 105, 42);
        border-radius: 0.5rem;
        font-weight: 600;
    }
    
    .badge {
        font-size: 11px;
        padding: 5px 10px;
    }
</style>

<script>
    // Save and restore active tab
    $(document).ready(function() {
        // Restore active tab from localStorage
        const savedTab = localStorage.getItem('chairReportsActiveTab');
        if (savedTab) {
            // Use a small delay to ensure Bootstrap is fully initialized
            setTimeout(function() {
                const tabElement = document.querySelector(`#${savedTab}-tab`);
                if (tabElement) {
                    const tab = new bootstrap.Tab(tabElement);
                    tab.show();
                }
            }, 100);
        }
        
        // Save active tab to localStorage when tab is clicked
        $('#reportsTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const activeTabId = e.target.id.replace('-tab', '');
            localStorage.setItem('chairReportsActiveTab', activeTabId);
        });
        
        // Simple search functionality for Screening Table
        $('#screeningSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            let visibleCount = 0;
            
            $('#screeningTableBody tr').not('.no-data-found').filter(function() {
                const isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(isVisible);
                if (isVisible) visibleCount++;
                return isVisible;
            });
            
            // Show/hide "no data found" message
            let noDataRow = $('#screeningTableBody').find('tr.no-data-found');
            if (visibleCount === 0 && value !== '') {
                if (noDataRow.length === 0) {
                    noDataRow = $('<tr class="no-data-found"><td colspan="17" class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2"></i><br>No data found matching your search.</td></tr>');
                    $('#screeningTableBody').append(noDataRow);
                } else {
                    noDataRow.show();
                }
            } else {
                noDataRow.hide();
            }
        });
        
        // Simple search functionality for CHED Table
        $('#chedSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            let visibleCount = 0;
            
            $('#chedTableBody tr').not('.no-data-found').filter(function() {
                const isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(isVisible);
                if (isVisible) visibleCount++;
                return isVisible;
            });
            
            // Show/hide "no data found" message
            let noDataRow = $('#chedTableBody').find('tr.no-data-found');
            if (visibleCount === 0 && value !== '') {
                if (noDataRow.length === 0) {
                    noDataRow = $('<tr class="no-data-found"><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2"></i><br>No data found matching your search.</td></tr>');
                    $('#chedTableBody').append(noDataRow);
                } else {
                    noDataRow.show();
                }
            } else {
                noDataRow.hide();
            }
        });
        
        // Simple search functionality for Exam Table
        $('#examSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            let visibleCount = 0;
            
            $('#examTableBody tr').not('.no-data-found').filter(function() {
                const isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(isVisible);
                if (isVisible) visibleCount++;
                return isVisible;
            });
            
            // Show/hide "no data found" message
            let noDataRow = $('#examTableBody').find('tr.no-data-found');
            if (visibleCount === 0 && value !== '') {
                if (noDataRow.length === 0) {
                    noDataRow = $('<tr class="no-data-found"><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2"></i><br>No data found matching your search.</td></tr>');
                    $('#examTableBody').append(noDataRow);
                } else {
                    noDataRow.show();
                }
            } else {
                noDataRow.hide();
            }
        });
        
        // Simple search functionality for Interview Table
        $('#interviewSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            let visibleCount = 0;
            
            $('#interviewTableBody tr').not('.no-data-found').filter(function() {
                const isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(isVisible);
                if (isVisible) visibleCount++;
                return isVisible;
            });
            
            // Show/hide "no data found" message
            let noDataRow = $('#interviewTableBody').find('tr.no-data-found');
            if (visibleCount === 0 && value !== '') {
                if (noDataRow.length === 0) {
                    noDataRow = $('<tr class="no-data-found"><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2"></i><br>No data found matching your search.</td></tr>');
                    $('#interviewTableBody').append(noDataRow);
                } else {
                    noDataRow.show();
                }
            } else {
                noDataRow.hide();
            }
        });
        
        // Handle PDF generation
        $('.pdf-btn').on('click', function(e) {
            e.preventDefault();
            const reportType = $(this).data('report-type');
            const { jsPDF } = window.jspdf;
            
            // Show loading indicator
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generating PDF...');
            
            // Determine which table and header to use
            let tableElement, headerElement, fileName;
            if (reportType === 'screening') {
                tableElement = document.querySelector('#screening .card');
                headerElement = document.getElementById('screening-print-header');
                fileName = 'Screening_Report_' + '<?= htmlspecialchars($chair_program) ?>_' + '<?= htmlspecialchars($chair_campus) ?>_' + new Date().toISOString().split('T')[0] + '.pdf';
            } else if (reportType === 'ched') {
                tableElement = document.querySelector('#ched .card');
                headerElement = document.getElementById('ched-print-header');
                fileName = 'CHED_Report_' + '<?= htmlspecialchars($chair_program) ?>_' + '<?= htmlspecialchars($chair_campus) ?>_' + new Date().toISOString().split('T')[0] + '.pdf';
            } else if (reportType === 'exam') {
                tableElement = document.querySelector('#exam .card');
                headerElement = document.getElementById('exam-print-header');
                fileName = 'Exam_Report_' + '<?= htmlspecialchars($chair_program) ?>_' + '<?= htmlspecialchars($chair_campus) ?>_' + new Date().toISOString().split('T')[0] + '.pdf';
            } else if (reportType === 'interview') {
                tableElement = document.querySelector('#interview .card');
                headerElement = document.getElementById('interview-print-header');
                fileName = 'Interview_Report_' + '<?= htmlspecialchars($chair_program) ?>_' + '<?= htmlspecialchars($chair_campus) ?>_' + new Date().toISOString().split('T')[0] + '.pdf';
            }
            
            // Create a temporary container for PDF generation
            const tempContainer = document.createElement('div');
            tempContainer.style.position = 'absolute';
            tempContainer.style.left = '-9999px';
            tempContainer.style.width = '1400px'; // Fixed width for consistent PDF
            tempContainer.style.backgroundColor = '#ffffff';
            tempContainer.style.padding = '20px';
            tempContainer.style.display = 'flex';
            tempContainer.style.flexDirection = 'column';
            tempContainer.style.alignItems = 'center';
            document.body.appendChild(tempContainer);
            
            // Create a new header that matches the website header structure
            const pdfHeader = document.createElement('div');
            pdfHeader.style.display = 'flex';
            pdfHeader.style.alignItems = 'center';
            pdfHeader.style.marginBottom = '20px';
            pdfHeader.style.paddingBottom = '15px';
            pdfHeader.style.borderBottom = '2px solid #000';
            pdfHeader.style.width = '100%';
            pdfHeader.style.maxWidth = '100%';
            pdfHeader.style.pageBreakInside = 'avoid';
            
            // Create logo container
            const logoContainer = document.createElement('div');
            const logoImg = headerElement.querySelector('img');
            if (logoImg) {
                const newLogo = logoImg.cloneNode(true);
                newLogo.style.width = '65px';
                newLogo.style.height = 'auto';
                newLogo.style.marginRight = '10px';
                newLogo.style.display = 'block';
                logoContainer.appendChild(newLogo);
            }
            
            // Create text container (matching website header structure)
            const textContainer = document.createElement('div');
            textContainer.style.flex = '1';
            textContainer.style.marginLeft = '10px';
            
            // University name (h4 from website header)
            const h4 = document.createElement('h4');
            h4.textContent = 'Carlos Hilado Memorial State University';
            h4.style.margin = '0 0 5px 0';
            h4.style.color = '#000';
            h4.style.fontSize = '18px';
            h4.style.fontWeight = 'bold';
            textContainer.appendChild(h4);
            
            // System name (p from website header)
            const p = document.createElement('p');
            p.textContent = 'Academic Program Application and Screening Management System';
            p.style.margin = '0 0 5px 0';
            p.style.color = '#000';
            p.style.fontSize = '14px';
            textContainer.appendChild(p);
            
            // Report title (h4 from print header)
            const reportTitle = headerElement.querySelector('h4');
            if (reportTitle) {
                const titleH4 = document.createElement('h4');
                titleH4.textContent = reportTitle.textContent;
                titleH4.style.margin = '5px 0 0 0';
                titleH4.style.color = '#000';
                titleH4.style.fontSize = '16px';
                titleH4.style.fontWeight = 'bold';
                textContainer.appendChild(titleH4);
            }
            
            // Assemble header
            pdfHeader.appendChild(logoContainer);
            pdfHeader.appendChild(textContainer);
            tempContainer.appendChild(pdfHeader);
            
            // Clone the card content
            const clonedCard = tableElement.cloneNode(true);
            // Remove search input and buttons from cloned card
            $(clonedCard).find('.card-header .input-group, .card-header .d-flex .gap-2').remove();
            $(clonedCard).find('.no-print').remove();
            // Specifically remove PDF button
            $(clonedCard).find('.pdf-btn').remove();
            $(clonedCard).find('button[data-report-type]').remove();
            $(clonedCard).find('.stanine-input').each(function() {
                // Replace input with its value for PDF
                const value = $(this).val();
                $(this).replaceWith($('<span>').text(value || '-'));
            });
            // Remove max-height and overflow restrictions for PDF
            $(clonedCard).find('.table-container').css({
                'max-height': 'none',
                'overflow': 'visible'
            });
            clonedCard.style.width = '100%';
            clonedCard.style.maxWidth = '100%';
            clonedCard.style.boxShadow = 'none';
            clonedCard.style.border = '1px solid #ddd';
            clonedCard.style.backgroundColor = '#ffffff';
            clonedCard.style.margin = '0 auto';
            tempContainer.appendChild(clonedCard);
            
            // Wait for images to load before generating PDF
            const images = tempContainer.querySelectorAll('img');
            let imagesLoaded = 0;
            const totalImages = images.length;
            let pdfGenerated = false;
            
            function generatePDF() {
                if (pdfGenerated) return; // Prevent multiple calls
                pdfGenerated = true;
                
                // Generate PDF using html2canvas
                html2canvas(tempContainer, {
                    scale: 1.5,
                    useCORS: true,
                    logging: false,
                    backgroundColor: '#ffffff',
                    windowWidth: 1400,
                    allowTaint: true
                }).then(function(canvas) {
                    // Use landscape orientation for wide tables
                    const pdf = new jsPDF('landscape', 'pt', 'a4');
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;
                    
                    // Calculate scaling to fit page width
                    const ratio = pdfWidth / imgWidth;
                    const scaledHeight = imgHeight * ratio;
                    const scaledWidth = pdfWidth;
                    
                    // If content fits on one page
                    if (scaledHeight <= pdfHeight) {
                        const imgData = canvas.toDataURL('image/png', 0.95);
                        // Center the image horizontally if it's narrower than page width
                        const xOffset = (pdfWidth - scaledWidth) / 2;
                        pdf.addImage(imgData, 'PNG', xOffset, 0, scaledWidth, scaledHeight);
                    } else {
                        // Content spans multiple pages - split properly
                        // Calculate pixels per PDF point: since ratio = pdfWidth/imgWidth,
                        // to convert PDF points back to pixels: pixels = points * (imgWidth/pdfWidth)
                        const pixelsPerPoint = imgWidth / pdfWidth;
                        const pageHeightInPixels = pdfHeight * pixelsPerPoint;
                        
                        let yPosition = 0;
                        let pageNumber = 0;
                        
                        while (yPosition < imgHeight) {
                            if (pageNumber > 0) {
                                pdf.addPage();
                            }
                            
                            // Calculate the height for this page in pixels
                            const remainingHeight = imgHeight - yPosition;
                            const currentPageHeightPixels = Math.min(pageHeightInPixels, remainingHeight);
                            const currentPageHeightPoints = currentPageHeightPixels / pixelsPerPoint;
                            
                            // Create a temporary canvas for this page slice
                            const pageCanvas = document.createElement('canvas');
                            pageCanvas.width = imgWidth;
                            pageCanvas.height = currentPageHeightPixels;
                            const pageCtx = pageCanvas.getContext('2d');
                            
                            // Fill with white background
                            pageCtx.fillStyle = '#ffffff';
                            pageCtx.fillRect(0, 0, imgWidth, currentPageHeightPixels);
                            
                            // Draw the portion of the image for this page
                            pageCtx.drawImage(
                                canvas,
                                0, yPosition,                    // Source x, y (from original canvas)
                                imgWidth, currentPageHeightPixels, // Source width, height
                                0, 0,                             // Destination x, y (on page canvas)
                                imgWidth, currentPageHeightPixels  // Destination width, height
                            );
                            
                            // Convert to image data
                            const pageImgData = pageCanvas.toDataURL('image/png', 0.95);
                            
                            // Add to PDF - center horizontally
                            const xOffset = (pdfWidth - scaledWidth) / 2;
                            pdf.addImage(pageImgData, 'PNG', xOffset, 0, scaledWidth, currentPageHeightPoints);
                            
                            // Move to next page
                            yPosition += currentPageHeightPixels;
                            pageNumber++;
                        }
                    }
                    
                    pdf.save(fileName);
                    
                    // Clean up
                    document.body.removeChild(tempContainer);
                    
                    // Restore button
                    btn.prop('disabled', false).html(originalHtml);
                }).catch(function(error) {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF. Please try again.');
                    if (document.body.contains(tempContainer)) {
                        document.body.removeChild(tempContainer);
                    }
                    btn.prop('disabled', false).html(originalHtml);
                });
            }
            
            if (totalImages === 0) {
                // No images, generate PDF immediately
                generatePDF();
            } else {
                // Wait for all images to load
                images.forEach(function(img) {
                    if (img.complete) {
                        imagesLoaded++;
                        if (imagesLoaded === totalImages) {
                            generatePDF();
                        }
                    } else {
                        img.onload = function() {
                            imagesLoaded++;
                            if (imagesLoaded === totalImages) {
                                generatePDF();
                            }
                        };
                        img.onerror = function() {
                            imagesLoaded++;
                            if (imagesLoaded === totalImages) {
                                generatePDF();
                            }
                        };
                    }
                });
            }
        });
    });

    // Handle stanine input updates
    document.addEventListener('DOMContentLoaded', function() {
        const stanineInputs = document.querySelectorAll('.stanine-input');
        
        function toNumber(value) {
            const n = parseFloat((value || '').toString().replace(/[^0-9.\-]/g, ''));
            return isNaN(n) ? 0 : n;
        }

        function computeStanineScore(rawValue) {
            const v = toNumber(rawValue);
            if (v >= 0 && v <= 100) return v; // already a percentage
            if (v >= 1 && v <= 9) return v; // return raw stanine value 1-9
            return 0;
        }

        function recalcRow(input) {
            const tr = input.closest('tr');
            if (!tr) return;

            const tds = tr.querySelectorAll('td');
            // Column indices based on header
            const gwaPct = toNumber(tds[6]?.textContent);
            const examPct = toNumber(tds[11]?.textContent);
            const interviewPct = toNumber(tds[13]?.textContent);
            const plusFactor = toNumber(tds[14]?.textContent);

            const stanineScore = computeStanineScore(input.value);
            const staninePct = stanineScore * 0.15;
            const initialTotal = gwaPct + staninePct;
            const finalRating = initialTotal + examPct + interviewPct + plusFactor;

            // Update cells
            if (tds[8]) tds[8].textContent = staninePct.toFixed(2);
            if (tds[9]) tds[9].textContent = initialTotal.toFixed(2);
            if (tds[15]) tds[15].querySelector('strong')
                ? tds[15].querySelector('strong').textContent = finalRating.toFixed(2)
                : tds[15].textContent = finalRating.toFixed(2);
        }

        function recomputeAllRanks() {
            const tbody = document.querySelector('#screeningTable tbody');
            if (!tbody) return;
            
            // Get all rows (excluding "no data found" row)
            const rows = Array.from(tbody.querySelectorAll('tr:not(.no-data-found)'));
            
            // Map rows with their final ratings
            const scored = rows.map((tr) => {
                const finalCell = tr.querySelectorAll('td')[15];
                const finalText = finalCell?.querySelector('strong')?.textContent || finalCell?.textContent || '0';
                const finalVal = toNumber(finalText);
                return { tr, finalVal };
            });

            // Sort by final rating (descending)
            scored.sort((a, b) => {
                if (b.finalVal !== a.finalVal) {
                    return b.finalVal - a.finalVal;
                }
                // If scores are equal, maintain original order (by name)
                const nameA = a.tr.querySelectorAll('td')[0]?.textContent || '';
                const nameB = b.tr.querySelectorAll('td')[0]?.textContent || '';
                return nameA.localeCompare(nameB);
            });

            // Reorder rows in the DOM
            scored.forEach((item) => {
                tbody.appendChild(item.tr);
            });

            // Calculate and assign ranks
            let currentRank = 0;
            let lastScore = null;
            let position = 0;
            for (const item of scored) {
                position += 1;
                if (lastScore === null || item.finalVal !== lastScore) {
                    currentRank = position;
                    lastScore = item.finalVal;
                }
                const rankCell = item.tr.querySelectorAll('td')[16];
                if (rankCell) rankCell.textContent = currentRank.toString();
            }
        }

        function updateStanine(input) {
            const stanineValue = input.value.trim();
            const applicantId = input.getAttribute('data-applicant-id');
            
            if (applicantId && stanineValue) {
                // Update stanine in database
                fetch('update_stanine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `applicant_id=${applicantId}&stanine=${encodeURIComponent(stanineValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success indicator
                        input.style.borderColor = '#28a745';
                        setTimeout(() => {
                            input.style.borderColor = '';
                        }, 2000);
                        
                        // Recalculate ranks in UI after database update
                        // The update_stanine.php already recalculates ranks in DB,
                        // but we need to refresh the displayed ranks immediately
                        // Recalculate all ranks based on current final ratings
                        recomputeAllRanks();
                    } else {
                        alert('Error updating stanine: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating stanine');
                });
            }
        }
        
        function refreshRanksFromDatabase() {
            // Fetch updated ranks from server without reloading the page
            // Get all applicant IDs and their current final ratings
            const rows = Array.from(document.querySelectorAll('#screeningTable tbody tr'));
            const applicantData = rows.map((tr) => {
                const applicantId = tr.querySelector('.stanine-input')?.getAttribute('data-applicant-id');
                const finalCell = tr.querySelectorAll('td')[15];
                const finalText = finalCell?.querySelector('strong')?.textContent || finalCell?.textContent || '0';
                const finalVal = toNumber(finalText);
                return { applicantId, finalVal, tr };
            }).filter(item => item.applicantId); // Only include rows with applicant IDs
            
            // Sort by final rating descending
            applicantData.sort((a, b) => b.finalVal - a.finalVal);
            
            // Update ranks in UI
            let currentRank = 0;
            let lastScore = null;
            let position = 0;
            for (const item of applicantData) {
                position += 1;
                if (lastScore === null || item.finalVal !== lastScore) {
                    currentRank = position;
                    lastScore = item.finalVal;
                }
                const rankCell = item.tr.querySelectorAll('td')[16];
                if (rankCell) rankCell.textContent = currentRank.toString();
            }
        }
        
        stanineInputs.forEach(input => {
            // Validate input to only allow 1-9
            input.addEventListener('keypress', function(e) {
                // Handle Enter key
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    updateStanine(this);
                    this.blur();
                    recomputeAllRanks();
                    return;
                }
                
                // Allow: backspace, delete, tab, escape
                if ([8, 9, 27, 46].indexOf(e.keyCode) !== -1 ||
                    // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }
                // Ensure that it is a number 1-9 only
                if ((e.shiftKey || (e.keyCode < 49 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
            
            // Validate on input to ensure value is between 1-9
            input.addEventListener('input', function() {
                let value = this.value;
                // Remove any non-numeric characters
                value = value.replace(/[^0-9]/g, '');
                // Ensure value is between 1-9
                if (value !== '' && (parseInt(value) < 1 || parseInt(value) > 9)) {
                    if (parseInt(value) > 9) {
                        this.value = '9';
                    } else if (parseInt(value) < 1 && value !== '') {
                        this.value = '1';
                    }
                } else {
                    this.value = value;
                }
                recalcRow(this);
                recomputeAllRanks();
            });
            
            // Validate on blur to ensure final value is 1-9
            input.addEventListener('blur', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) {
                    this.value = '';
                } else if (value > 9) {
                    this.value = '9';
                } else {
                    this.value = value.toString();
                }
                updateStanine(this);
            });
            
            // Initialize current values on load
            recalcRow(input);
            recomputeAllRanks();
        });
    });
</script>
