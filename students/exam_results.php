<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;

if ($submission_id === 0) {
    header('Location: applicant_dashboard.php');
    exit();
}

try {
    // Fetch submission details
    $stmt = $pdo->prepare("
        SELECT s.*, e.version_name, e.time_limit, e.instructions
        FROM exam_submissions s
        JOIN exam_versions e ON s.exam_version_id = e.id
        WHERE s.id = ? AND s.student_id = ?
    ");
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        throw new Exception("Submission not found.");
    }

    // Fetch student's answers with questions
    $stmt = $pdo->prepare("
        SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
               q.correct_answer, q.points, q.image_path
        FROM student_answers sa
        JOIN questions q ON sa.question_id = q.id
        WHERE sa.submission_id = ?
        ORDER BY q.question_number
    ");
    $stmt->execute([$submission_id]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total possible points
    $total_possible_points = array_sum(array_column($answers, 'points'));
    $percentage = ($submission['total_points'] / $total_possible_points) * 100;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: applicant_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?= htmlspecialchars($submission['version_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .card {
                margin: 10px 0;
            }
            .card-body {
                padding: 1rem;
            }
            .alert {
                padding: 0.75rem;
            }
            .alert .row {
                margin: 0;
            }
            .alert .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .card-title {
                font-size: 1.1rem;
            }
            .badge {
                font-size: 0.75rem;
            }
            .btn {
                width: 100%;
            }
            .img-fluid {
                max-width: 100%;
            }
        }
        @media (max-width: 576px) {
            .container {
                padding: 5px;
            }
            .card-body {
                padding: 0.75rem;
            }
            .card-title {
                font-size: 1rem;
            }
            h4 {
                font-size: 1.25rem;
            }
            .form-check-label {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Exam Results</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($submission['version_name']) ?></h5>
                        
                        <div class="alert alert-info mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Score:</strong> <?= $submission['total_points'] ?> / <?= $total_possible_points ?> points</p>
                                    <p><strong>Percentage:</strong> <?= number_format($percentage, 1) ?>%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Submitted:</strong> <?= date('F j, Y g:i A', strtotime($submission['submitted_at'])) ?></p>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-3">Question Review:</h6>
                        <?php foreach ($answers as $index => $answer): ?>
                            <div class="card mb-3 <?= $answer['points_earned'] > 0 ? 'border-success' : 'border-danger' ?>">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        Question <?= $index + 1 ?>
                                        <span class="badge <?= $answer['points_earned'] > 0 ? 'bg-success' : 'bg-danger' ?> float-end">
                                            <?= $answer['points_earned'] ?> / <?= $answer['points'] ?> points
                                        </span>
                                    </h6>
                                    
                                    <p class="card-text"><?= nl2br(htmlspecialchars($answer['question_text'])) ?></p>
                                    
                                    <?php if ($answer['image_path']): ?>
                                        <img src="<?= htmlspecialchars($answer['image_path']) ?>" 
                                             class="img-fluid mb-3" 
                                             alt="Question Image">
                                    <?php endif; ?>

                                    <div class="options">
                                        <?php
                                        $options = [
                                            'A' => $answer['option_a'],
                                            'B' => $answer['option_b'],
                                            'C' => $answer['option_c'],
                                            'D' => $answer['option_d']
                                        ];
                                        foreach ($options as $key => $value):
                                            $is_correct = $key === $answer['correct_answer'];
                                            $is_selected = $key === $answer['answer'];
                                            $class = '';
                                            if ($is_correct) $class = 'text-success';
                                            if ($is_selected && !$is_correct) $class = 'text-danger';
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" disabled
                                                       <?= $is_selected ? 'checked' : '' ?>>
                                                <label class="form-check-label <?= $class ?>">
                                                    <?= $key ?>) <?= htmlspecialchars($value) ?>
                                                    <?php if ($is_correct): ?>
                                                        <i class="fas fa-check text-success"></i>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-grid gap-2">
                            <a href="applicant_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 