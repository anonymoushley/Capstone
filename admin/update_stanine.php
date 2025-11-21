<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['chair_id']) && !isset($_SESSION['interviewer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get POST data
$applicant_id = $_POST['applicant_id'] ?? null;
$stanine = $_POST['stanine'] ?? null;

if (!$applicant_id || !$stanine) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Use mysqli connection like reports.php
$conn = new mysqli("localhost", "root", "", "admission");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Use INSERT ... ON DUPLICATE KEY UPDATE now that we have unique constraint
    $sql = "INSERT INTO screening_results (personal_info_id, stanine_result) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE stanine_result = VALUES(stanine_result)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $applicant_id, $stanine);
    $stmt->execute();
    
    // Derive stanine_score from stanine_result and store it (percent scale)
    $stanineScoreSql = "UPDATE screening_results
                        SET stanine_score = ROUND(CASE 
                            WHEN stanine_result BETWEEN 0 AND 100 THEN stanine_result
                            WHEN stanine_result BETWEEN 1 AND 9 THEN (stanine_result / 9) * 100
                            ELSE NULL END, 2)
                        WHERE personal_info_id = ?";
    $stanineStmt = $conn->prepare($stanineScoreSql);
    $stanineStmt->bind_param("i", $applicant_id);
    $stanineStmt->execute();

    // Also compute and persist GWA score based on academic_background
    $gwaSql = "UPDATE screening_results sr
               JOIN academic_background ab ON ab.personal_info_id = sr.personal_info_id
               SET sr.gwa_score = ROUND(((IFNULL(ab.g11_1st_avg,0) + IFNULL(ab.g11_2nd_avg,0) + IFNULL(ab.g12_1st_avg,0)) / 3), 2)
               WHERE sr.personal_info_id = ?";
    $gwaStmt = $conn->prepare($gwaSql);
    $gwaStmt->bind_param("i", $applicant_id);
    $gwaStmt->execute();

    // Compute and store initial_total = 10% of GWA + 15% of Stanine
    $initialTotalSql = "UPDATE screening_results
                        SET initial_total = ROUND(((IFNULL(gwa_score,0) / 100) * 10) + (IFNULL(stanine_score,0) * 0.15), 2)
                        WHERE personal_info_id = ?";
    $initialStmt = $conn->prepare($initialTotalSql);
    $initialStmt->bind_param("i", $applicant_id);
    $initialStmt->execute();

    // Compute and store exam/interview percentages and final_rating
    $examPctSql = "UPDATE screening_results
                   SET exam_percentage = ROUND(((IFNULL(exam_total_score,0) / 100) * 40), 2)
                   WHERE personal_info_id = ?";
    $examStmt = $conn->prepare($examPctSql);
    $examStmt->bind_param("i", $applicant_id);
    $examStmt->execute();

    $interviewPctSql = "UPDATE screening_results
                        SET interview_percentage = ROUND(((IFNULL(interview_total_score,0) / 100) * 35), 2)
                        WHERE personal_info_id = ?";
    $intStmt = $conn->prepare($interviewPctSql);
    $intStmt->bind_param("i", $applicant_id);
    $intStmt->execute();

    $finalSql = "UPDATE screening_results
                 SET final_rating = ROUND(IFNULL(initial_total,0) + IFNULL(exam_percentage,0) + IFNULL(interview_percentage,0) + IFNULL(plus_factor,0), 2)
                 WHERE personal_info_id = ?";
    $finalStmt = $conn->prepare($finalSql);
    $finalStmt->bind_param("i", $applicant_id);
    $finalStmt->execute();

    // Recompute and persist ranks for the applicant's cohort (program + campus)
    $cohortSql = "SELECT pa.program, pa.campus
                  FROM personal_info pi
                  JOIN program_application pa ON pa.personal_info_id = pi.id
                  WHERE pi.id = ?
                  LIMIT 1";
    $cohortStmt = $conn->prepare($cohortSql);
    $cohortStmt->bind_param("i", $applicant_id);
    $cohortStmt->execute();
    $cohortRes = $cohortStmt->get_result();
    $cohort = $cohortRes->fetch_assoc();

    if ($cohort) {
        $program = $cohort['program'];
        $campus = $cohort['campus'];

        $fetchSql = "SELECT sr.personal_info_id, IFNULL(sr.final_rating, 0) AS final_rating
                     FROM screening_results sr
                     JOIN personal_info pi ON pi.id = sr.personal_info_id
                     JOIN program_application pa ON pa.personal_info_id = pi.id
                     WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
                     ORDER BY final_rating DESC, sr.personal_info_id ASC";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bind_param("ss", $program, $campus);
        $fetchStmt->execute();
        $rows = $fetchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $position = 0;
        $currentRank = 0;
        $lastScore = null;
        $updateRankStmt = $conn->prepare("UPDATE screening_results SET rank = ? WHERE personal_info_id = ?");
        foreach ($rows as $row) {
            $position += 1;
            $score = (float)$row['final_rating'];
            if ($lastScore === null || $score !== $lastScore) {
                $currentRank = $position;
                $lastScore = $score;
            }
            $pid = (int)$row['personal_info_id'];
            $updateRankStmt->bind_param("ii", $currentRank, $pid);
            $updateRankStmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Stanine updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>

