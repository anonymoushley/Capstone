<?php
// Ensure screening_results has rows and persist GWA, stanine_score, and initial_total for all applicants
$conn = new mysqli("localhost", "root", "", "admission");

header('Content-Type: application/json');

if ($conn->connect_error) {
	echo json_encode(['success' => false, 'message' => 'DB connect failed']);
	exit;
}

try {
	// 1) Insert missing screening_results rows for applicants present in registration
	$insertMissing = "INSERT INTO screening_results (personal_info_id)
		SELECT DISTINCT r.personal_info_id
		FROM registration r
		INNER JOIN personal_info pi ON pi.id = r.personal_info_id
		LEFT JOIN screening_results sr ON sr.personal_info_id = r.personal_info_id
		WHERE r.personal_info_id IS NOT NULL
		  AND sr.personal_info_id IS NULL";
	$conn->query($insertMissing);

	// 2) Persist GWA from academic_background averages
	$updateGwa = "UPDATE screening_results sr
		JOIN academic_background ab ON ab.personal_info_id = sr.personal_info_id
		SET sr.gwa_score = ROUND(((IFNULL(ab.g11_1st_avg,0) + IFNULL(ab.g11_2nd_avg,0) + IFNULL(ab.g12_1st_avg,0)) / 3), 2)";
	$conn->query($updateGwa);

	// 3) Persist stanine_score if stanine_result is populated
	$updateStanineScore = "UPDATE screening_results
		SET stanine_score = ROUND(CASE 
			WHEN stanine_result BETWEEN 0 AND 100 THEN stanine_result
			WHEN stanine_result BETWEEN 1 AND 9 THEN (stanine_result / 9) * 100
			ELSE IFNULL(stanine_score, 0) END, 2)
		WHERE stanine_result IS NOT NULL";
	$conn->query($updateStanineScore);

	// 4) Persist initial_total = 10% GWA + 15% Stanine
	$updateInitial = "UPDATE screening_results
		SET initial_total = ROUND(((IFNULL(gwa_score,0) / 100) * 10) + (IFNULL(stanine_score,0) * 0.15), 2)";
	$conn->query($updateInitial);

	// 5) Persist exam/interview percentages
	$updateExamPct = "UPDATE screening_results
		SET exam_percentage = ROUND(((IFNULL(exam_total_score,0) / 100) * 40), 2)";
	$conn->query($updateExamPct);

	$updateInterviewPct = "UPDATE screening_results
		SET interview_percentage = ROUND(((IFNULL(interview_total_score,0) / 100) * 35), 2)";
	$conn->query($updateInterviewPct);

	// 6) Persist final_rating = initial_total + exam_percentage + interview_percentage + plus_factor
	$updateFinal = "UPDATE screening_results
		SET final_rating = ROUND(IFNULL(initial_total,0) + IFNULL(exam_percentage,0) + IFNULL(interview_percentage,0) + IFNULL(plus_factor,0), 2)";
	$conn->query($updateFinal);

	echo json_encode(['success' => true]);
} catch (Throwable $e) {
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
	$conn->close();
}
?>


