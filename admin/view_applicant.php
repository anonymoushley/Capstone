<?php
require_once '../config/database.php';

// Get applicant ID from URL
$applicant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($applicant_id <= 0) {
    echo '<div class="alert alert-danger">Invalid applicant ID.</div>';
    exit();
}

// Fetch registration to get personal_info_id
$stmt = $pdo->prepare('SELECT * FROM registration WHERE id = ?');
$stmt->execute([$applicant_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$registration) {
    echo '<div class="alert alert-danger">Applicant not found.</div>';
    exit();
}
$personal_info_id = $registration['personal_info_id'] ?? null;
if (!$personal_info_id) {
    echo '<div class="alert alert-danger">No profiling data found for this applicant.</div>';
    exit();
}

// Fetch all profiling data
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

// Fetch uploaded images
$uploads = $pdo->prepare('SELECT * FROM uploads WHERE applicant_id = ?');
$uploads->execute([$applicant_id]);
$uploads = $uploads->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details - CHMSU</title>
    <link rel="icon" href="images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .resume-section { margin-bottom: 2rem; }
        .resume-section h5 { border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; margin-bottom: 1rem; background-color: rgb(0, 105, 42); color: white; padding: 0.5rem; border-radius: 2px; }
        .resume-label { font-weight: bold; color: #000; font-family: "Ice", sans-serif; }
        .id-picture {border-radius: 5px; width: 2in;
    height: 2in;
    object-fit: cover; /* crop/stretch while maintaining coverage */
    border: 1px solid #ccc; /* optional border */
    display: block;}
        .card {max-width: 90%; margin: auto; }
        .modal-dialog {max-width: 50%; margin: auto; }
        
        /* Header Color Theme Buttons */
        .btn-header-theme {
            background-color: rgb(0, 105, 42);
            color: white;
            border: 1px solid rgb(0, 105, 42);
            transition: all 0.2s ease;
        }
        
        .btn-header-theme:hover {
            background-color: rgb(0, 85, 34);
            border-color: rgb(0, 85, 34);
            color: white;
        }
        
        .btn-header-theme:active {
            background-color: rgb(0, 65, 26);
            border-color: rgb(0, 65, 26);
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="card shadow mb-3 mt-2">
        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
         <h3 class="mb=0">Applicant: <?= htmlspecialchars($registration['id']) ?></h3>
        </div>
        <div class="card-body">
            <!-- Personal Info -->
            <div class="resume-section">
                <h5>Personal Information</h5>
                <?php if ($personal): ?>
                    <div class="row">
                        <div class="col-md-3 mt-2">
                            <?php if (!empty($personal['id_picture'])): ?>
                                <img src="../uploads/id_pictures/<?= htmlspecialchars($personal['id_picture']) ?>" class="id-picture mb-2" alt="ID Picture">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <p><span class="resume-label">Name:</span> <?= htmlspecialchars($personal['last_name'] . ', ' . $personal['first_name'] . ' ' . $personal['middle_name']) ?></p>
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
                <h5>Academic Background</h5>
                               <?php if ($academic): ?>
                    <div class=row>
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
                <h5>Program Application</h5>
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
                <h5>Socio-Demographic Profile</h5>
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
                 <h5>Technology Access</h5>
                            <p><span class="resume-label">The student applicant has access to a personal computer at home:</span> <?= htmlspecialchars($socio['access_computer']) ?></p>
                            <p><span class="resume-label">The student applicant has internet access at home:</span> <?= htmlspecialchars($socio['access_internet']) ?></p>
                            <p><span class="resume-label">The student applicant has access to mobile device(s):</span> <?= htmlspecialchars($socio['access_mobile']) ?></p>
            
                    <h5>Other Details</h5>
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
                <h5>Uploaded Documents</h5>
                <?php if ($documents): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center">Document</th>
                                <th class="text-center">View</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
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
                            
                            // Define which documents are required vs optional
                            $required_docs = ['g11_1st', 'g11_2nd', 'g12_1st'];
                            $optional_docs = ['ncii', 'guidance_cert', 'additional_file'];
                            $no_action_docs = ['guidance_cert', 'additional_file']; // Documents that don't need status or actions
                            foreach ($doc_fields as $field => $label):
                                $file_data = $documents[$field];
                                // Get status only for documents that need it
                                if (in_array($field, $no_action_docs)) {
                                    $status = null; // No status for these documents
                                } else {
                                    $status = $documents[$field . '_status'] ?? 'Pending';
                                }
                                
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
                                            class="btn btn-sm btn-header-theme"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewDocumentModal"
                                            data-files="<?php echo htmlspecialchars(json_encode(array_map(function($f) { return '../uploads/' . $f; }, $files))); ?>"
                                            data-label="<?= htmlspecialchars($label) ?>">
                                            View
                                        </button>
                                    <?php else: ?>
                                        <?php if (in_array($field, $optional_docs)): ?>
                                            <span class="text-muted">No document</span>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    // No status display for guidance_cert and additional_file
                                    if (!in_array($field, $no_action_docs)): ?>
                                        <span class="badge bg-<?= $status === 'Accepted' ? 'success' : ($status === 'Rejected' ? 'danger' : 'secondary') ?>">
                                            <?= $status ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($files)): ?>
                                        <?php 
                                        // No actions needed for guidance_cert and additional_file
                                        if (in_array($field, $no_action_docs)): ?>
                                            <span class="text-muted">
                                                <i class="fas fa-info-circle"></i> No action needed
                                            </span>
                                        <?php elseif ($status !== 'Accepted'): ?>
                                    <form method="post" action="verify_document.php" style="display:inline;">
    <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
    <input type="hidden" name="field" value="<?= $field ?>">

    <button 
        type="button"
        class="btn btn-sm btn-header-theme" 
        data-bs-toggle="modal"
        data-bs-target="#confirmAcceptModal"
        data-document="<?= htmlspecialchars($label) ?>"
        data-field="<?= $field ?>"
    >
        Accept
    </button>

    <button 
        type="button"
        class="btn btn-danger btn-sm" 
        data-bs-toggle="modal"
        data-bs-target="#confirmRejectModal"
        data-document="<?= htmlspecialchars($label) ?>"
        data-field="<?= $field ?>"
    >
        Reject
    </button>
</form>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i> No action needed
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (in_array($field, $optional_docs)): ?>
                                            <span class="text-muted">
                                                <i class="fas fa-info-circle"></i> No document
                                            </span>
                                        <?php else: ?>
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Required
                                            </span>
                                        <?php endif; ?>
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
        <a href="chair_main.php?page=chair_applicants" class="btn mb-3 btn-header-theme">Back to List</a>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <!-- Toasts will be dynamically added here -->
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

<!-- Accept Document Confirmation Modal -->
<div class="modal fade" id="confirmAcceptModal" tabindex="-1" aria-labelledby="confirmAcceptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="confirmAcceptModalLabel">Confirm Document Acceptance</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>ACCEPT</strong> this document?</p>
        <p><strong>Document:</strong> <span id="acceptDocumentName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="verify_document.php" style="display:inline;" id="acceptForm">
          <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
          <input type="hidden" name="action" value="Accepted">
          <input type="hidden" name="field" id="acceptField" value="">
          <button type="submit" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);">Accept Document</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Reject Document Confirmation Modal -->
<div class="modal fade" id="confirmRejectModal" tabindex="-1" aria-labelledby="confirmRejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmRejectModalLabel">Confirm Document Rejection</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>REJECT</strong> this document?</p>
        <p><strong>Document:</strong> <span id="rejectDocumentName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="verify_document.php" style="display:inline;" id="rejectForm">
          <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
          <input type="hidden" name="action" value="Rejected">
          <input type="hidden" name="field" id="rejectField" value="">
          <button type="submit" class="btn btn-danger">Reject Document</button>
        </form>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
document.addEventListener('DOMContentLoaded', function() {
    // Document View Modal with Bootstrap Carousel functionality
    var documentCarousel = null;
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
                    var parsed = JSON.parse(filesData);
                    if (Array.isArray(parsed)) {
                        files = parsed;
                    } else if (typeof parsed === 'string') {
                        files = [parsed];
                    } else {
                        files = [];
                    }
                } catch(e) {
                    console.warn('Failed to parse filesData as JSON, treating as string:', e);
                    if (filesData.trim() !== '') {
                        files = [filesData];
                    }
                }
            } else if (singleFile) {
                files = [singleFile];
            }
            
            // Filter out empty file paths
            files = files.filter(function(file) {
                return file && file.trim() !== '';
            });
            
            // Update modal title
            modalTitle.innerHTML = '<i class="fas fa-file-alt me-2"></i>View: ' + label;
            
            // Clear existing carousel content
            carouselInner.innerHTML = '';
            carouselIndicators.innerHTML = '';
            
            if (files.length === 0) {
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
            
            // Initialize or refresh Bootstrap carousel
            var carouselElement = document.getElementById('documentCarousel');
            if (documentCarousel) {
                var bsCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
            }
            
            // Preload all images
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
                    
                    var preloadedImg = preloadImages[index];
                    if (preloadedImg && preloadedImg.complete) {
                        carouselItem.appendChild(img);
                        img.src = file;
                    } else {
                        carouselItem.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="width: 100%; min-height: 200px; padding: 20px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        img.onload = function() {
                            carouselItem.innerHTML = '';
                            carouselItem.appendChild(img);
                        };
                        
                        img.onerror = function() {
                            carouselItem.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load image. <a href="' + file + '" target="_blank" class="btn btn-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                        };
                        
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

    // Accept Document Modal
    var acceptModal = document.getElementById('confirmAcceptModal');
    acceptModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var documentName = button.getAttribute('data-document');
        var field = button.getAttribute('data-field');
        
        document.getElementById('acceptDocumentName').textContent = documentName;
        document.getElementById('acceptField').value = field;
    });

    // Reject Document Modal
    var rejectModal = document.getElementById('confirmRejectModal');
    rejectModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var documentName = button.getAttribute('data-document');
        var field = button.getAttribute('data-field');
        
        document.getElementById('rejectDocumentName').textContent = documentName;
        document.getElementById('rejectField').value = field;
    });

    // Handle Accept Document Form Submission
    document.getElementById('acceptForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('verify_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmAcceptModal'));
                modal.hide();
                // Reload page after a short delay to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing request', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Handle Reject Document Form Submission
    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('verify_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmRejectModal'));
                modal.hide();
                // Reload page after a short delay to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing request', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

});

// Toast notification function
function showToast(message, type = 'success', duration = 2000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    const headerBg = type === 'success' ? '#d4edda' : '#f8d7da';
    const headerBorder = type === 'success' ? '#c3e6cb' : '#f5c6cb';
    const bodyBg = type === 'success' ? '#d4edda' : '#f8d7da';
    const textColor = type === 'success' ? 'text-success' : 'text-danger';
    const typeLabel = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: ${headerBg}; border-color: ${headerBorder};">
                <i class="${iconClass} ${textColor} me-2"></i>
                <strong class="me-auto ${textColor}">${typeLabel}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: ${bodyBg};">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
        const toast = new bootstrap.Toast(toastElement);
        toast.hide();
    }, duration);
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>
</body>
</html> 