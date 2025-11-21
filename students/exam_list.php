<?php
session_start();
require_once '../config/database.php';

// Fetch all published exams
$stmt = $pdo->prepare("
    SELECT * FROM exam_versions 
    WHERE status = 'Published' 
    AND is_published = 1 
    AND is_archived = 0 
    ORDER BY published_at DESC
");
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .exam-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
        .exam-header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .exam-body {
            padding: 20px;
        }
        .exam-footer {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 0;
            }
            .exam-card {
                margin-bottom: 15px;
            }
            .exam-header h3 {
                font-size: 1.25rem;
            }
            .exam-body {
                padding: 15px;
            }
            .exam-footer {
                padding: 10px 15px;
            }
            .btn {
                width: 100%;
            }
            .col-md-6 {
                margin-bottom: 15px;
            }
        }
        @media (max-width: 576px) {
            body {
                padding: 5px;
            }
            h1 {
                font-size: 1.5rem;
            }
            .exam-header h3 {
                font-size: 1.1rem;
            }
            .exam-body {
                padding: 10px;
            }
            .exam-body p {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Available Exams</h1>
        
        <?php if (empty($exams)): ?>
            <div class="alert alert-info">
                No exams are currently available. Please check back later.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($exams as $exam): ?>
                    <div class="col-md-6">
                        <div class="exam-card">
                            <div class="exam-header">
                                <h3><?= htmlspecialchars($exam['version_name']) ?></h3>
                            </div>
                            <div class="exam-body">
                                <p><strong>Time Limit:</strong> <?= $exam['time_limit'] ?> minutes</p>
                                <?php if ($exam['instructions']): ?>
                                    <p><strong>Instructions:</strong></p>
                                    <div class="instructions">
                                        <?= nl2br(htmlspecialchars($exam['instructions'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="exam-footer">
                                <a href="exam.php?version_id=<?= $exam['id'] ?>" class="btn btn-primary">Start Exam</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 