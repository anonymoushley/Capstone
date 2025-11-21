<?php
session_start();

// Allow chairpersons, interviewers, and admins to trigger recompute
if (!isset($_SESSION['chair_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['interviewer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$applicantId = 0;
if (isset($_POST['applicant_id'])) {
    $applicantId = intval($_POST['applicant_id']);
} elseif (isset($_GET['applicant_id'])) {
    $applicantId = intval($_GET['applicant_id']);
}
if ($applicantId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing applicant_id']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Determine the applicant's cohort (program + campus)
$cohortSql = "SELECT pa.program, pa.campus
              FROM personal_info pi
              JOIN program_application pa ON pa.personal_info_id = pi.id
              WHERE pi.id = ?
              LIMIT 1";
$cohortStmt = $conn->prepare($cohortSql);
$cohortStmt->bind_param('i', $applicantId);
$cohortStmt->execute();
$cohortRes = $cohortStmt->get_result();
$cohort = $cohortRes->fetch_assoc();

if (!$cohort) {
    echo json_encode(['success' => false, 'message' => 'Cohort not found for applicant']);
    $conn->close();
    exit;
}

$program = $cohort['program'];
$campus = $cohort['campus'];

// Compatibility approach: compute ranks in PHP (handles ties) and persist
$fetchSql = "SELECT sr.personal_info_id, IFNULL(sr.final_rating, 0) AS final_rating
             FROM screening_results sr
             JOIN personal_info pi ON pi.id = sr.personal_info_id
             JOIN program_application pa ON pa.personal_info_id = pi.id
             WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
             ORDER BY final_rating DESC, sr.personal_info_id ASC";

$fetchStmt = $conn->prepare($fetchSql);
$fetchStmt->bind_param('ss', $program, $campus);

try {
    $fetchStmt->execute();
    $res = $fetchStmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    $position = 0;
    $currentRank = 0;
    $lastScore = null;

    $updateStmt = $conn->prepare("UPDATE screening_results SET rank = ? WHERE personal_info_id = ?");

    foreach ($rows as $row) {
        $position += 1;
        $score = (float)$row['final_rating'];
        if ($lastScore === null || $score !== $lastScore) {
            $currentRank = $position;
            $lastScore = $score;
        }
        $pid = (int)$row['personal_info_id'];
        $updateStmt->bind_param('ii', $currentRank, $pid);
        $updateStmt->execute();
    }

    echo json_encode(['success' => true, 'updated' => count($rows)]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Recompute failed: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>


