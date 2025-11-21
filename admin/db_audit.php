<?php
$conn = new mysqli("localhost", "root", "", "admission");
if ($conn->connect_error) { die('DB error'); }

header('Content-Type: text/html; charset=utf-8');

function q($conn, $sql) {
	$res = $conn->query($sql);
	if (!$res) { return [[], $conn->error]; }
	$rows = [];
	while ($row = $res->fetch_assoc()) { $rows[] = $row; }
	return [$rows, null];
}

list($registrationCount) = q($conn, "SELECT COUNT(*) AS cnt FROM registration");
list($distinctRegPi) = q($conn, "SELECT COUNT(DISTINCT personal_info_id) AS cnt FROM registration WHERE personal_info_id IS NOT NULL");
list($piCount) = q($conn, "SELECT COUNT(*) AS cnt FROM personal_info");
list($paCount) = q($conn, "SELECT COUNT(*) AS cnt FROM program_application");
list($abCount) = q($conn, "SELECT COUNT(*) AS cnt FROM academic_background");
list($srCount) = q($conn, "SELECT COUNT(*) AS cnt FROM screening_results");

list($regNoPi) = q($conn, "SELECT id FROM registration WHERE personal_info_id IS NULL");
list($regPiNoPI) = q($conn, "SELECT DISTINCT r.personal_info_id FROM registration r LEFT JOIN personal_info pi ON pi.id = r.personal_info_id WHERE r.personal_info_id IS NOT NULL AND pi.id IS NULL");
list($regPiNoPA) = q($conn, "SELECT DISTINCT r.personal_info_id FROM registration r LEFT JOIN program_application pa ON pa.personal_info_id = r.personal_info_id WHERE r.personal_info_id IS NOT NULL AND pa.personal_info_id IS NULL");
list($regPiNoAB) = q($conn, "SELECT DISTINCT r.personal_info_id FROM registration r LEFT JOIN academic_background ab ON ab.personal_info_id = r.personal_info_id WHERE r.personal_info_id IS NOT NULL AND ab.personal_info_id IS NULL");
list($srOrphans) = q($conn, "SELECT sr.id, sr.personal_info_id FROM screening_results sr LEFT JOIN personal_info pi ON pi.id = sr.personal_info_id WHERE pi.id IS NULL");

echo '<h2>DB Audit Summary</h2>';
echo '<ul>';
echo '<li>registration rows: '.($registrationCount[0]['cnt']??0).'</li>';
echo '<li>registration DISTINCT personal_info_id: '.($distinctRegPi[0]['cnt']??0).'</li>';
echo '<li>personal_info rows: '.($piCount[0]['cnt']??0).'</li>';
echo '<li>program_application rows: '.($paCount[0]['cnt']??0).'</li>';
echo '<li>academic_background rows: '.($abCount[0]['cnt']??0).'</li>';
echo '<li>screening_results rows: '.($srCount[0]['cnt']??0).'</li>';
echo '</ul>';

function table($title, $rows) {
	echo '<h3>'.$title.' ('.count($rows).')</h3>';
	if (!count($rows)) { echo '<div>None</div>'; return; }
	echo '<table border="1" cellspacing="0" cellpadding="4">';
	echo '<tr>';
	foreach (array_keys($rows[0]) as $k) { echo '<th>'.htmlspecialchars($k).'</th>'; }
	echo '</tr>';
	foreach ($rows as $r) {
		echo '<tr>';
		foreach ($r as $v) { echo '<td>'.htmlspecialchars((string)$v).'</td>'; }
		echo '</tr>';
	}
	echo '</table>';
}

table('registration rows with NULL personal_info_id', $regNoPi);
table('registration personal_info_id not found in personal_info (orphans)', $regPiNoPI);
table('registration personal_info_id missing program_application', $regPiNoPA);
table('registration personal_info_id missing academic_background', $regPiNoAB);
table('screening_results rows without matching personal_info (orphans)', $srOrphans);

$conn->close();
?>


