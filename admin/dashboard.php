<?php
require_once '../config/database.php';

// SQL: Applicants per day per campus per program
$sql = "SELECT DATE(r.created_at) AS reg_date, pa.campus, pa.program, COUNT(*) AS total
        FROM registration r
        LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
        LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
        GROUP BY DATE(r.created_at), pa.campus, pa.program
        ORDER BY pa.campus, pa.program, reg_date";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data: [campus][program][date] => count
$data = [];
$dateLabels = [];

foreach ($rows as $row) {
    $campus = $row['campus'];
    $program = $row['program'];
    $date = $row['reg_date'];
    $count = (int)$row['total'];

    $data[$campus][$program][$date] = $count;

    if (!in_array($date, $dateLabels)) {
        $dateLabels[] = $date;
    }
}

sort($dateLabels); // Ensure date order
?>

</head>
<body>
<div class="container mt-4">
  <h4 class="mb-4">Daily Applicants per Program per Campus</h4>
  <?php foreach ($data as $campus => $programs): ?>
    <div class="mb-5">
      <h5 class="text-primary"><?= htmlspecialchars($campus) ?> Campus</h5>
      <canvas id="chart_<?= md5($campus) ?>" height="100"></canvas>

      <script>
        const ctx_<?= md5($campus) ?> = document.getElementById("chart_<?= md5($campus) ?>").getContext('2d');
        new Chart(ctx_<?= md5($campus) ?>, {
          type: 'line',
          data: {
            labels: <?= json_encode($dateLabels) ?>,
            datasets: [
              <?php foreach ($programs as $program => $dates): 
                $dailyCounts = [];
                foreach ($dateLabels as $date) {
                    $dailyCounts[] = isset($dates[$date]) ? $dates[$date] : 0;
                }
              ?>
              {
                label: '<?= $program ?>',
                data: <?= json_encode($dailyCounts) ?>,
                fill: false,
                borderColor: '<?= sprintf("#%06X", mt_rand(0, 0xFFFFFF)) ?>',
                tension: 0.1
              },
              <?php endforeach; ?>
            ]
          },
          options: {
            responsive: true,
            plugins: {
              title: {
                display: true,
                text: 'Daily Applicant Count (<?= htmlspecialchars($campus) ?>)'
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { precision: 0 }
              }
            }
          }
        });
      </script>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>
