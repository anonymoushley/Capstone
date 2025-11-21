<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in as chairperson
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'chairperson') {
    echo "<div class='alert alert-danger'>Access denied. Please login as chairperson.</div>";
    exit;
}

// Get program and campus from chairperson session
$chair_program = $_SESSION['program'] ?? '';
$chair_campus = $_SESSION['campus'] ?? '';

if (!$chair_program || !$chair_campus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Total applicants for this chairperson's program and campus
// Match the same logic as chair_applicants.php - fetch from personal_info table
$totalSql = "SELECT COUNT(DISTINCT pi.id) AS total 
             FROM personal_info pi
             INNER JOIN program_application pa ON pa.personal_info_id = pi.id
             LEFT JOIN registration r ON r.personal_info_id = pi.id
             WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
             AND (r.id IS NULL OR (r.email_address IS NOT NULL AND r.email_address != ''))";
$totalStmt = $conn->prepare($totalSql);
if (!$totalStmt) {
    die("Prepare failed: " . $conn->error);
}
$totalStmt->bind_param("ss", $chair_program, $chair_campus);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalApplicants = $totalResult->fetch_assoc()['total'] ?? 0;

// Get completed exams count (using exam_answers table)
$examSql = "SELECT COUNT(DISTINCT ea.applicant_id) AS completed
            FROM exam_answers ea
            LEFT JOIN registration r ON ea.applicant_id = r.id
            LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
            LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
            WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)";
$examStmt = $conn->prepare($examSql);
if (!$examStmt) {
    die("Prepare failed: " . $conn->error);
}
$examStmt->bind_param("ss", $chair_program, $chair_campus);
$examStmt->execute();
$examResult = $examStmt->get_result();
$completedExams = $examResult->fetch_assoc()['completed'] ?? 0;

// Get completed interviews count (using screening_results table with interview scores)
$interviewSql = "SELECT COUNT(DISTINCT sr.personal_info_id) AS completed
                 FROM screening_results sr
                 LEFT JOIN personal_info pi ON sr.personal_info_id = pi.id
                 LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
                 WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
                 AND sr.interview_total_score IS NOT NULL";
$interviewStmt = $conn->prepare($interviewSql);
if (!$interviewStmt) {
    die("Prepare failed: " . $conn->error);
}
$interviewStmt->bind_param("ss", $chair_program, $chair_campus);
$interviewStmt->execute();
$interviewResult = $interviewStmt->get_result();
$completedInterviews = $interviewResult->fetch_assoc()['completed'] ?? 0;

// SQL: Filtered applicants per day per campus per program
$sql = "SELECT DATE(r.created_at) AS reg_date, pa.campus, pa.program, COUNT(*) AS total
        FROM registration r
        LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
        LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
        WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
        GROUP BY DATE(r.created_at), pa.campus, pa.program
        ORDER BY pa.campus, pa.program, reg_date";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ss", $chair_program, $chair_campus);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);

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

sort($dateLabels); // Ensure chronological order
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
    
    .chart-card {
        background: white;
        padding: 25px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
    }
    
    .chart-card h5 {
        color: rgb(0, 105, 42);
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    #chart_canvas {
        max-width: 100%;
        height: 300px;
        width: 100%;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
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

<div class="container-fluid px-4 py-3">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3><i class="fas fa-user-circle me-2"></i>Welcome, <?= htmlspecialchars($_SESSION['chair_name']) ?></h3>
        <p><i class="fas fa-university me-1"></i> <?= htmlspecialchars($chair_program) ?> - <?= htmlspecialchars($chair_campus) ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h2 class="number"><?= $totalApplicants ?></h2>
                <p class="label">Total Applicants</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h2 class="number"><?= $completedExams ?></h2>
                <p class="label">Completed Exams</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-microphone"></i>
                </div>
                <h2 class="number"><?= $completedInterviews ?></h2>
                <p class="label">Completed Interviews</p>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <h5><i class="fas fa-chart-line me-2"></i>Daily Applicants - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>

                <?php if (!empty($data)): ?>
                    <div class="chart-container">
                        <canvas id="chart_canvas"></canvas>
                    </div>
                    <script>
                        const ctx = document.getElementById('chart_canvas').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?= json_encode($dateLabels) ?>,
                                datasets: [
                                    <?php foreach ($data as $campus => $programs): ?>
                                        <?php foreach ($programs as $program => $dates): 
                                            $dailyCounts = [];
                                            foreach ($dateLabels as $date) {
                                                $dailyCounts[] = $dates[$date] ?? 0;
                                            }
                                        ?>
                                        {
                                            label: '<?= $program ?>',
                                            data: <?= json_encode($dailyCounts) ?>,
                                            fill: false,
                                            borderColor: 'rgb(0, 105, 42)',
                                            backgroundColor: 'rgba(0, 105, 42, 0.1)',
                                            tension: 0.4,
                                            pointBackgroundColor: 'rgb(0, 105, 42)',
                                            pointBorderColor: 'rgb(0, 105, 42)',
                                            pointRadius: 5,
                                            pointHoverRadius: 7
                                        },
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                aspectRatio: 2,
                                layout: {
                                    padding: {
                                        top: 10,
                                        bottom: 10,
                                        left: 10,
                                        right: 10
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 15,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            color: 'rgba(0,0,0,0.1)',
                                            display: true
                                        },
                                        ticks: {
                                            font: {
                                                size: 10
                                            },
                                            maxRotation: 45
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: { 
                                            precision: 0,
                                            font: {
                                                size: 10
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(0,0,0,0.1)',
                                            display: true
                                        }
                                    }
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                animation: {
                                    duration: 0
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Data Available</h5>
                        <p class="text-muted">No applicant data found for your program and campus.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
