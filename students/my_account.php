<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">You must be logged in to view this page.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch registration to get personal_info_id
$stmt = $pdo->prepare('SELECT * FROM registration WHERE id = ?');
$stmt->execute([$user_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$registration) {
    echo '<div class="alert alert-danger">Applicant not found.</div>';
    exit();
}
$personal_info_id = $registration['personal_info_id'] ?? null;
if (!$personal_info_id) {
    echo '<div class="alert alert-warning">Please complete your profiling to view your account details.</div>';
    exit();
}

// Fetch all profiling sections
$personal = $pdo->prepare('SELECT * FROM personal_info WHERE id = ?');
$personal->execute([$personal_info_id]);
$personal = $personal->fetch(PDO::FETCH_ASSOC);

$socio = $pdo->prepare('SELECT * FROM socio_demographic WHERE personal_info_id = ?');
$socio->execute([$personal_info_id]);
$socio = $socio->fetch(PDO::FETCH_ASSOC);

$academic = $pdo->prepare('SELECT ab.*, s.name as strand_name FROM academic_background ab LEFT JOIN strands s ON ab.strand_id = s.id WHERE ab.personal_info_id = ?');
$academic->execute([$personal_info_id]);
$academic = $academic->fetch(PDO::FETCH_ASSOC);

$program = $pdo->prepare('SELECT * FROM program_application WHERE personal_info_id = ?');
$program->execute([$personal_info_id]);
$program = $program->fetch(PDO::FETCH_ASSOC);

$documents = $pdo->prepare('SELECT * FROM documents WHERE personal_info_id = ?');
$documents->execute([$personal_info_id]);
$documents = $documents->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - CHMSU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: white;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
        }
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }
        /* Hide sidebar if page is included in dashboard */
        .sidebar {
            display: none !important;
        }
        .col-md-3.sidebar {
            display: none !important;
        }
        /* Ensure full width when sidebar is hidden */
        .main-content {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .col-md-9.main-content {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        /* Override row and container-fluid when my_account page is included */
        .container-fluid:has(.col-md-9.main-content .my-account-page),
        .row:has(.col-md-9.main-content .my-account-page),
        .container-fluid:has(.main-content .my-account-page) {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        /* Override the row when my_account is included */
        .row:has(.main-content .my-account-page) {
            margin: 0 !important;
        }
        /* Center the container and card */
        .container.mt-4.mb-4 {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 15px;
            width: 100%;
            overflow-x: visible;
        }
        .container.mt-4.mb-4 > * {
            width: 100%;
            max-width: 1200px;
        }
        /* Override parent container if included in dashboard */
        .my-account-page {
            margin-left: auto !important;
            margin-right: auto !important;
            width: 100% !important;
            max-width: 1200px !important;
            display: block !important;
            padding: 0 15px !important;
        }
        .col-md-9.main-content .my-account-page,
        .main-content .my-account-page {
            margin-left: auto !important;
            margin-right: auto !important;
            width: 100% !important;
            max-width: 1200px !important;
            display: block !important;
            padding: 0 15px !important;
        }
        .col-md-9.main-content .card,
        .main-content .card {
            margin-left: auto !important;
            margin-right: auto !important;
            display: block !important;
        }
        /* Ensure the row doesn't constrain width */
        .row:has(.col-md-9.main-content .my-account-page) .col-md-9,
        .row:has(.main-content .my-account-page) .col-md-9,
        .row:has(.main-content .my-account-page) {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        .resume-section { 
            margin-bottom: 2rem;
            position: relative;
        }
        .resume-section h5 { 
            border-bottom: 1px solid #ccc; 
            padding: 0.75rem 1rem; 
            margin-bottom: 1rem; 
            background-color: rgb(0, 105, 42); 
            color: white;
            border-radius: 4px; 
            font-weight: 600;
        }
        .resume-label { 
            font-weight: bold; 
            color: #333; 
        }
        .id-picture {
            border-radius: 5px; 
            width: 2in;
            height: 2in;
            object-fit: cover;
            border: 2px solid rgb(0, 105, 42);
            display: block;
            margin-left: auto;
            margin-right: 0;
            margin-top: 0 !important;
        }
        .personal-info-row {
            position: relative;
        }
        .personal-info-row .picture-col {
            position: absolute;
            top: 0;
            right: 15px;
            width: auto;
            padding: 0;
        }
        .personal-info-row .info-col {
            padding-right: 220px !important;
        }
        @media (max-width: 768px) {
            .personal-info-row {
                display: flex;
                flex-direction: column;
            }
            .personal-info-row .picture-col {
                position: relative;
                width: 100%;
                text-align: center;
                margin-top: 0;
                margin-bottom: 1rem;
                order: -1;
            }
            .personal-info-row .info-col {
                padding-right: 15px !important;
                order: 1;
            }
            .id-picture {
                width: 150px !important;
                height: 150px !important;
                margin: 0 auto !important;
            }
            .resume-section h5 {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
            .card {
                margin: 10px;
            }
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            table {
                font-size: 0.9rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .btn-group .btn {
                width: auto;
            }
            .modal-dialog {
                max-width: 95% !important;
                margin: 10px auto;
            }
        }
        @media (max-width: 576px) {
            .id-picture {
                width: 120px !important;
                height: 120px !important;
            }
            .resume-section h5 {
                font-size: 0.9rem;
                padding: 0.4rem 0.5rem;
            }
            .resume-label {
                font-size: 0.9rem;
            }
            .card-body {
                padding: 1rem;
            }
            table {
                font-size: 0.85rem;
            }
            .card {
                margin: 5px;
            }
            .modal-dialog {
                max-width: 100% !important;
                margin: 5px;
            }
        }
        .card {
            max-width: 1200px !important; 
            margin: 0 auto !important; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            width: 100%;
            overflow: visible;
        }
        .card-header {
            background-color: rgb(0, 105, 42) !important;
            color: white;
            border-bottom: 2px solid rgb(0, 85, 34);
        }
        .card-body {
            overflow: visible;
        }
        .modal-dialog {
            max-width: 50%; 
            margin: auto; 
        }
        .btn-info {
            background-color: rgb(0, 105, 42);
            border-color: rgb(0, 105, 42);
            color: white;
        }
        .btn-info:hover {
            background-color: rgb(0, 85, 34);
            border-color: rgb(0, 85, 34);
            color: white;
        }
        .btn-info:focus,
        .btn-info:active,
        .btn-info:focus-visible,
        .btn-info.active,
        .btn-info:active:focus {
            background-color: rgb(0, 85, 34) !important;
            border-color: rgb(0, 85, 34) !important;
            color: white !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
        }
        .table {
            background-color: white;
        }
        .table thead {
            background-color: rgb(0, 105, 42);
            color: white;
        }
        .table thead th {
            border-color: rgb(0, 85, 34);
        }
        /* Back Button Theme Styling - Matching Admin Side */
        .btn-outline-success {
            border-color: rgb(0, 105, 42);
            color: rgb(0, 105, 42);
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
            margin-bottom: 1rem;
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
        .btn-outline-success:focus {
            background-color: rgb(0, 105, 42);
            border-color: rgb(0, 105, 42);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
        }
        .back-btn-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 15px;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        .my-account-page {
            padding-top: 15px !important;
        }
        @media (max-width: 768px) {
            .back-btn-container {
                padding-top: 15px;
                margin-bottom: 1rem;
                position: relative;
                z-index: 1;
            }
            .my-account-page {
                padding-top: 15px !important;
            }
            .btn-outline-success {
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }
            .btn-outline-success:hover,
            .btn-outline-success:focus {
                background-color: rgb(0, 105, 42);
                border-color: rgb(0, 105, 42);
                color: white;
            }
            .btn-outline-success:active {
                background-color: rgb(0, 85, 34);
                border-color: rgb(0, 85, 34);
                color: white;
            }
        }
        @media (max-width: 576px) {
            .back-btn-container {
                padding-top: 15px;
                margin-bottom: 0.75rem;
                position: relative;
                z-index: 1;
            }
            .my-account-page {
                padding-top: 15px !important;
            }
            .btn-outline-success {
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }
            .btn-outline-success:hover,
            .btn-outline-success:focus {
                background-color: rgb(0, 105, 42);
                border-color: rgb(0, 105, 42);
                color: white;
            }
            .btn-outline-success:active {
                background-color: rgb(0, 85, 34);
                border-color: rgb(0, 85, 34);
                color: white;
            }
        }
    </style>
</head>
<body>
<div class="container mb-4 my-account-page">
    <div class="back-btn-container">
        <a href="applicant_dashboard.php" class="btn btn-outline-success">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
    
    <div class="card shadow mb-3">
        <div class="card-header">
         <h3 class="mb-0"><i class="fas fa-user me-2"></i>Applicant: <?= htmlspecialchars($registration['id']) ?></h3>
        </div>
        <div class="card-body">
            <!-- Personal Info -->
            <div class="resume-section">
                <h5><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
                <?php if ($personal): ?>
                    <div class="row align-items-start personal-info-row">
                        <div class="col-md-3 text-end picture-col">
                            <?php if (!empty($personal['id_picture'])): ?>
                                <img src="../uploads/id_pictures/<?= htmlspecialchars($personal['id_picture']) ?>" class="id-picture" alt="ID Picture">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9 info-col">
                            <p><span class="resume-label">Name:</span> <?= htmlspecialchars(ucfirst(strtolower($personal['last_name'])) . ', ' . ucfirst(strtolower($personal['first_name'])) . ' ' . ucfirst(strtolower($personal['middle_name']))) ?></p>
                            <p><span class="resume-label">Date of Birth:</span> <?= htmlspecialchars($personal['date_of_birth']) ?></p>
                            <p><span class="resume-label">Age:</span> <?= htmlspecialchars($personal['age']) ?></p>
                            <p><span class="resume-label">Sex:</span> <?= htmlspecialchars($personal['sex']) ?></p>
                            <p><span class="resume-label">Contact Number:</span> <?= htmlspecialchars($personal['contact_number']) ?></p>
                            <p><span class="resume-label">Address:</span> <?= htmlspecialchars($personal['street_purok']) ?>, Brgy. <?= htmlspecialchars($personal['barangay']) ?>, <?= htmlspecialchars($personal['city']) ?>, <?= htmlspecialchars($personal['province']) ?>, <?= htmlspecialchars($personal['region']) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No personal information found.</p>
                <?php endif; ?>
            </div>

            <!-- Academic Background -->
                <div class="resume-section">
                <h5><i class="fas fa-graduation-cap me-2"></i>Academic Background</h5>
                               <?php if ($academic): ?>
                    <div class="row">
                        <div class="col-md-6">
                    <p><span class="resume-label">Last School Attended:</span> <?= htmlspecialchars($academic['last_school_attended']) ?></p>
                    <p><span class="resume-label">Strand:</span> <?= htmlspecialchars($academic['strand_name'] ?? 'N/A') ?></p>
                    <p><span class="resume-label">Year Graduated:</span> <?= htmlspecialchars($academic['year_graduated']) ?></p>
                        </div>
                        <div class="col-md-6">
                    <p><span class="resume-label">G11 1st Sem Avg:</span> <?= htmlspecialchars($academic['g11_1st_avg']) ?></p>
                    <p><span class="resume-label">G11 2nd Sem Avg:</span> <?= htmlspecialchars($academic['g11_2nd_avg']) ?></p>
                    <p><span class="resume-label">G12 1st Sem Avg:</span> <?= htmlspecialchars($academic['g12_1st_avg']) ?></p>
                    <p><span class="resume-label">Academic Award:</span> <?= htmlspecialchars($academic['academic_award']) ?></p>
                </div>
                </div>
                <?php else: ?>
                    <p>No academic background found.</p>
                <?php endif; ?>
            </div>
            <!-- Program Application -->
            <div class="resume-section">
                <h5><i class="fas fa-book me-2"></i>Program Application</h5>
                <?php if ($program): ?>
                    <p><span class="resume-label">Campus Choice:</span> <?= htmlspecialchars($program['campus']) ?></p>
                    <p><span class="resume-label">College:</span> <?= htmlspecialchars($program['college']) ?></p>
                    <p><span class="resume-label">Program Choice:</span> <?= htmlspecialchars($program['program']) ?></p>
                <?php else: ?>
                    <p>No program application found.</p>
                <?php endif; ?>
            </div>

<!-- Socio-Demographic -->
            <div class="resume-section">
                <h5><i class="fas fa-users me-2"></i>Socio-Demographic Profile</h5>
                <?php if ($socio): ?>
                    <div class="row">
                                <div class="col-md-6">
                            <p><span class="resume-label">Marital Status:</span> <?= htmlspecialchars($socio['marital_status']) ?></p>
                            <p><span class="resume-label">Religion:</span> <?= htmlspecialchars($socio['religion']) ?></p>
                            <p><span class="resume-label">Orientation:</span> <?= htmlspecialchars($socio['orientation']) ?></p>
                            <p><span class="resume-label">Father Status:</span> <?= htmlspecialchars($socio['father_status']) ?></p>
                            <p><span class="resume-label">Father Education:</span> <?= htmlspecialchars($socio['father_education']) ?></p>
                            <p><span class="resume-label">Father Employment:</span> <?= htmlspecialchars($socio['father_employment']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="resume-label">Mother Status:</span> <?= htmlspecialchars($socio['mother_status']) ?></p>
                            <p><span class="resume-label">Mother Education:</span> <?= htmlspecialchars($socio['mother_education']) ?></p>
                            <p><span class="resume-label">Mother Employment:</span> <?= htmlspecialchars($socio['mother_employment']) ?></p>
                            <p><span class="resume-label">Siblings:</span> <?= htmlspecialchars($socio['siblings']) ?></p>
                            <p><span class="resume-label">Living With:</span> <?= htmlspecialchars($socio['living_with']) ?></p>
                        </div>
                    </div>
                 <h5 style="margin-top: 1.5rem;"><i class="fas fa-laptop me-2"></i>Technology Access</h5>
                            <p><span class="resume-label">The student applicant has access to a personal computer at home:</span> <?= htmlspecialchars($socio['access_computer']) ?></p>
                            <p><span class="resume-label">The student applicant has internet access at home:</span> <?= htmlspecialchars($socio['access_internet']) ?></p>
                            <p><span class="resume-label">The student applicant has access to mobile device(s):</span> <?= htmlspecialchars($socio['access_mobile']) ?></p>
            
                    <h5 style="margin-top: 1.5rem;"><i class="fas fa-info-circle me-2"></i>Other Details</h5>
                            <p><span class="resume-label">The student applicant is part of an indigenous group in the Philippines:</span> <?= htmlspecialchars($socio['indigenous_group']) ?></p>
                            <p><span class="resume-label">The student applicant is the first in their family to attend college:</span> <?= htmlspecialchars($socio['first_gen_college']) ?></p>
                            <p><span class="resume-label">The student applicant was a scholar:</span> <?= htmlspecialchars($socio['was_scholar']) ?></p>
                            <p><span class="resume-label">The student applicant received any academic honors in high school:</span> <?= htmlspecialchars($socio['received_honors']) ?></p>
                            <p><span class="resume-label">The student applicant has a disability:</span> <?= htmlspecialchars($socio['has_disability']) ?></p>
                            <p><span class="resume-label">Disability Detail:</span> <?= htmlspecialchars($socio['disability_detail']) ?></p>
                    
                            
                <?php else: ?>
                    <p>No socio-demographic information found.</p>
                <?php endif; ?>
            </div>

            <!-- Documents -->
            <div class="resume-section">
                <h5><i class="fas fa-file-upload me-2"></i>Uploaded Documents</h5>
                <?php if ($documents): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center">Document</th>
                                <th class="text-center">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $doc_fields = [
                                'g11_1st' => 'G11 1st Sem Report Card',
                                'g11_2nd' => 'G11 2nd Sem Report Card',
                                'g12_1st' => 'G12 1st Sem Report Card',
                                'ncii' => 'NCII Certificate',
                                'guidance_cert' => 'Guidance Certificate',
                                'additional_file' => 'Additional File'
                            ];
                            foreach ($doc_fields as $field => $label):
                                $file_data = $documents[$field];
                                $status = $documents[$field . '_status'] ?? 'Pending';
                                
                                // Handle JSON-encoded file arrays (multiple files) or single filename
                                $files = [];
                                if (!empty($file_data)) {
                                    if (is_string($file_data)) {
                                        $decoded = json_decode($file_data, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            // Multiple files stored as JSON array
                                            $files = array_filter($decoded, function($f) { return !empty($f); });
                                        } else {
                                            // Single filename (not JSON)
                                            $files = [$file_data];
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?= $label ?></td>
                                <td class="text-center">
                                    <?php if (!empty($files)): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewDocumentModal"
                                            data-files="<?php echo htmlspecialchars(json_encode(array_map(function($f) { return '../uploads/' . $f; }, $files))); ?>"
                                            data-label="<?= htmlspecialchars($label) ?>">
                                            View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No documents uploaded.</p>
                <?php endif; ?>
            </div>
            </div>
    </div>
</div>
<!-- Document View Modal with Carousel -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #00692a; color: white;">
        <h5 class="modal-title" id="viewDocumentModalLabel">
          <i class="fas fa-file-alt me-2"></i>View Document
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center" id="documentModalBody" style="padding: 0; margin: 0;">
        <!-- Bootstrap Carousel -->
        <div id="documentCarousel" class="carousel slide">
          <!-- Carousel Indicators -->
          <div class="carousel-indicators" id="carouselIndicators">
            <!-- Indicators will be generated by JavaScript -->
          </div>
          
          <!-- Carousel Inner -->
          <div class="carousel-inner" id="carouselInner">
            <!-- Carousel items will be generated by JavaScript -->
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px; padding: 20px;">
              <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
          
          <!-- Carousel Controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#documentCarousel" data-bs-slide="prev" id="carouselPrevBtn" style="display: none;">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#documentCarousel" data-bs-slide="next" id="carouselNextBtn" style="display: none;">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
      <div class="modal-footer" style="display: none;">
      </div>
    </div>
  </div>
</div>

<style>
  /* Document Modal Styles */
  #viewDocumentModal .modal-dialog {
    max-width: 800px;
  }
  
  #viewDocumentModal .modal-body {
    position: relative;
    padding: 0;
    margin: 0;
  }
  
  /* Carousel Styles */
  #documentCarousel {
    width: 100%;
    position: relative;
  }
  
  #documentCarousel .carousel-inner {
    width: 100%;
    position: relative;
    padding: 0;
    margin: 0;
  }
  
  #documentCarousel .carousel-item {
    text-align: center;
    width: 100%;
    display: flex !important;
    align-items: flex-start !important;
    justify-content: center !important;
    padding: 0;
    margin: 0;
    transition: none !important;
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
  }
  
  #documentCarousel .carousel-item.active {
    position: relative;
    opacity: 1;
    display: flex !important;
  }
  
  /* Remove carousel transition animation */
  #documentCarousel.carousel {
    transition: none !important;
  }
  
  #documentCarousel .carousel-inner {
    transition: none !important;
  }
  
  #documentCarousel .carousel-item-next:not(.carousel-item-start),
  #documentCarousel .active.carousel-item-end,
  #documentCarousel .carousel-item-prev:not(.carousel-item-end),
  #documentCarousel .active.carousel-item-start {
    transform: translateX(0) !important;
    transition: none !important;
  }
  
  #documentCarousel .carousel-item-next,
  #documentCarousel .carousel-item-prev,
  #documentCarousel .carousel-item.active {
    transform: translateX(0) !important;
    transition: none !important;
  }
  
  #documentCarousel .carousel-item img {
    width: 100%;
    max-width: 100%;
    height: auto;
    max-height: 80vh;
    object-fit: contain;
    margin: 0;
    padding: 0;
    display: block;
  }
  
  #documentCarousel .carousel-item embed {
    width: 100%;
    max-width: 100%;
    height: auto;
    max-height: 80vh;
    min-height: 400px;
    margin: 0;
    padding: 0;
    display: block;
  }
  
  /* Mobile responsive styles */
  @media (max-width: 768px) {
    #viewDocumentModal .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem);
    }
    #viewDocumentModal .modal-content {
      border-radius: 0.5rem;
    }
    #documentCarousel .carousel-item img {
      max-height: 70vh;
    }
  }
  
  @media (max-width: 576px) {
    #viewDocumentModal .modal-dialog {
      margin: 0.25rem;
      max-width: calc(100% - 0.5rem);
    }
    #documentCarousel .carousel-item img {
      max-height: 65vh;
    }
  }
  
  /* Carousel Controls */
  #documentCarousel .carousel-control-prev,
  #documentCarousel .carousel-control-next {
    width: 50px;
    height: 50px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.8;
    transition: opacity 0.3s ease;
    z-index: 10;
    background-color: transparent;
    border: none;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  #documentCarousel .carousel-control-prev:hover,
  #documentCarousel .carousel-control-next:hover {
    opacity: 1;
  }
  
  #documentCarousel .carousel-control-prev {
    left: 15px;
  }
  
  #documentCarousel .carousel-control-next {
    right: 15px;
  }
  
  #documentCarousel .carousel-control-prev-icon,
  #documentCarousel .carousel-control-next-icon {
    background-color: #00692a;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    background-size: 60%;
    opacity: 1;
  }
  
  /* Carousel Indicators */
  #documentCarousel .carousel-indicators {
    margin-bottom: 15px;
  }
  
  #documentCarousel .carousel-indicators button {
    background-color: #00692a;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin: 0 4px;
    opacity: 0.5;
    transition: all 0.3s ease;
  }
  
  #documentCarousel .carousel-indicators button.active {
    opacity: 1;
    transform: scale(1.2);
  }
  
  #documentCarousel .carousel-indicators button:hover {
    opacity: 0.8;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Document View Modal with Bootstrap Carousel functionality
var documentCarousel = null;

document.addEventListener('DOMContentLoaded', function() {
    var viewModal = document.getElementById('viewDocumentModal');
    
    if (viewModal) {
    viewModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
            if (!button) {
                console.error('Modal opened without related target');
                return;
            }
            
            var filesData = button.getAttribute('data-files');
            var singleFile = button.getAttribute('data-file');
            var label = button.getAttribute('data-label') || 'Document';
        var modalTitle = viewModal.querySelector('.modal-title');
            var carouselInner = document.getElementById('carouselInner');
            var carouselIndicators = document.getElementById('carouselIndicators');
            var carouselPrevBtn = document.getElementById('carouselPrevBtn');
            var carouselNextBtn = document.getElementById('carouselNextBtn');
            
            // Parse files - can be single file or JSON array
            var files = [];
            if (filesData) {
                try {
                    // Try to parse as JSON first
                    var parsed = JSON.parse(filesData);
                    if (Array.isArray(parsed)) {
                        files = parsed;
                    } else if (typeof parsed === 'string') {
                        files = [parsed];
                    } else {
                        files = [];
                    }
                } catch(e) {
                    // If JSON parsing fails, treat as single file path string
                    console.warn('Failed to parse filesData as JSON, treating as string:', e);
                    if (filesData.trim() !== '') {
                        files = [filesData];
                    }
                }
            } else if (singleFile) {
                // Fallback: check for single file attribute
                files = [singleFile];
            }
            
            // Filter out empty file paths
            files = files.filter(function(file) {
                return file && file.trim() !== '';
            });
            
            console.log('Parsed files:', files);
            
            // Update modal title
            modalTitle.innerHTML = '<i class="fas fa-file-alt me-2"></i>View: ' + label;
            
            // Clear existing carousel content
            carouselInner.innerHTML = '';
            carouselIndicators.innerHTML = '';
            
            if (files.length === 0) {
                console.error('No files found for preview');
                carouselInner.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No files to display. The document may not have been uploaded correctly.</div>';
                carouselPrevBtn.style.display = 'none';
                carouselNextBtn.style.display = 'none';
                return;
            }
            
            // Show/hide navigation buttons and indicators
            if (files.length > 1) {
                carouselPrevBtn.style.display = 'block';
                carouselNextBtn.style.display = 'block';
            } else {
                carouselPrevBtn.style.display = 'none';
                carouselNextBtn.style.display = 'none';
            }
            
            // Initialize or refresh Bootstrap carousel first
            var carouselElement = document.getElementById('documentCarousel');
            if (documentCarousel) {
                // Dispose existing carousel instance
                var bsCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
            }
            
            // Preload all images for faster transitions
            var preloadImages = [];
            files.forEach(function(file) {
                var filePath = file.split('?')[0];
                var ext = filePath.split('.').pop().toLowerCase();
                if(['jpg','jpeg','png','gif','bmp','webp','svg'].includes(ext)) {
                    var preloadImg = new Image();
                    preloadImg.src = file;
                    preloadImages.push(preloadImg);
                }
            });
            
            // Create carousel items and indicators
            files.forEach(function(file, index) {
                // Improved file extension extraction
                var ext = '';
                var filePath = file.split('?')[0]; // Remove query parameters
                var parts = filePath.split('.');
                if (parts.length > 1) {
                    ext = parts.pop().toLowerCase().trim();
                }
                
                // Create indicator
                var indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#documentCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.setAttribute('aria-label', 'Slide ' + (index + 1));
                if (index === 0) {
                    indicator.classList.add('active');
                    indicator.setAttribute('aria-current', 'true');
                }
                carouselIndicators.appendChild(indicator);
                
                // Create carousel item
                var carouselItem = document.createElement('div');
                carouselItem.className = 'carousel-item' + (index === 0 ? ' active' : '');
                
                // Load content - try image first if extension suggests it, or if no extension, try as image
                var imageExtensions = ['jpg','jpeg','png','gif','bmp','webp','svg'];
                var isImage = ext && imageExtensions.includes(ext);
                var isPdf = ext === 'pdf';
                
                // If no extension or unknown extension, try to load as image first (browser will handle it)
                if (isImage || (!ext && !isPdf)) {
                    var img = document.createElement('img');
                    img.className = 'img-fluid';
                    img.alt = 'Document Image';
                    img.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; object-fit: contain; margin: 0; padding: 0; display: block;';
                    
                    // Check if image is already preloaded
                    var preloadedImg = preloadImages[index];
                    if (preloadedImg && preloadedImg.complete) {
                        // Image already loaded, show immediately
                        carouselItem.appendChild(img);
                        img.src = file;
                    } else {
                        // Show spinner while loading
                        carouselItem.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="width: 100%; min-height: 200px; padding: 20px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        img.onload = function() {
                            carouselItem.innerHTML = '';
                            carouselItem.appendChild(img);
                        };
                        
                        img.onerror = function() {
                            carouselItem.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load image. <a href="' + file + '" target="_blank" class="btn btn-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                        };
                        
                        // Start loading immediately
                        img.src = file;
                    }
                    carouselInner.appendChild(carouselItem);
                } else if (isPdf) {
                    var embed = document.createElement('embed');
                    embed.src = file;
                    embed.type = 'application/pdf';
                    embed.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; min-height: 400px; margin: 0; padding: 0; display: block;';
                    carouselItem.appendChild(embed);
                    carouselInner.appendChild(carouselItem);
                } else {
                    // For unknown file types, try to load as iframe or provide download link
                    // First, try to detect if it might be an image by checking the file path
                    var lowerFile = file.toLowerCase();
                    if (lowerFile.includes('image') || lowerFile.includes('photo') || lowerFile.includes('picture') || lowerFile.includes('img')) {
                        // Try loading as image even without extension
                        var img = document.createElement('img');
                        img.className = 'img-fluid';
                        img.alt = 'Document Image';
                        img.style.cssText = 'width: 100%; max-width: 100%; height: auto; max-height: 80vh; object-fit: contain; margin: 0; padding: 0; display: block;';
                        carouselItem.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="width: 100%; min-height: 200px; padding: 20px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        img.onload = function() {
                            carouselItem.innerHTML = '';
                            carouselItem.appendChild(img);
                        };
                        
                        img.onerror = function() {
                            carouselItem.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Preview not available for this file type. <a href="' + file + '" target="_blank" class="btn btn-success ms-2" download><i class="fas fa-download me-1"></i>Download File</a></div>';
                        };
                        
                        img.src = file;
                        carouselInner.appendChild(carouselItem);
        } else {
                        // For other file types, provide download option
                        carouselItem.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Preview not available for this file type. <a href="' + file + '" target="_blank" class="btn btn-success ms-2" download><i class="fas fa-download me-1"></i>Download File</a> or <a href="' + file + '" target="_blank" class="btn btn-outline-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                        carouselInner.appendChild(carouselItem);
                    }
                }
            });
            
            // Initialize carousel after items are added
            documentCarousel = new bootstrap.Carousel(carouselElement, {
                interval: false,
                wrap: false,
                ride: false
            });
        });
        
        // Clean up carousel instance when modal is hidden
        viewModal.addEventListener('hidden.bs.modal', function() {
            if (documentCarousel) {
                var carouselElement = document.getElementById('documentCarousel');
                var bsCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
                documentCarousel = null;
            }
        });
    }
});
</script>
</body>
</html> 