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

$chairProgram = $_SESSION['program'] ?? '';
$chairCampus = $_SESSION['campus'] ?? '';

if (!$chairProgram || !$chairCampus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStrand = $_GET['filter_strand'] ?? '';
$filterEligibility = $_GET['filter_eligibility'] ?? '';
$isAjax = isset($_GET['ajax']);



$sql = "SELECT 
            COALESCE(
                (SELECT r.id FROM registration r WHERE r.personal_info_id = pi.id LIMIT 1),
                (SELECT r.id FROM registration r INNER JOIN program_application pa_reg ON pa_reg.registration_id = r.id WHERE pa_reg.personal_info_id = pi.id LIMIT 1),
                (SELECT r.id FROM registration r 
                 WHERE LOWER(TRIM(r.last_name)) = LOWER(TRIM(pi.last_name))
                 AND LOWER(TRIM(r.first_name)) = LOWER(TRIM(pi.first_name))
                 AND NOT EXISTS (SELECT 1 FROM registration r2 WHERE r2.personal_info_id = pi.id)
                 LIMIT 1),
                (SELECT pa_reg.registration_id FROM program_application pa_reg WHERE pa_reg.personal_info_id = pi.id LIMIT 1)
            ) AS registration_id,
            COALESCE(
                (SELECT r.applicant_status FROM registration r WHERE r.personal_info_id = pi.id LIMIT 1),
                (SELECT r.applicant_status FROM registration r INNER JOIN program_application pa_reg ON pa_reg.registration_id = r.id WHERE pa_reg.personal_info_id = pi.id LIMIT 1),
                (SELECT r.applicant_status FROM registration r 
                 WHERE LOWER(TRIM(r.last_name)) = LOWER(TRIM(pi.last_name))
                 AND LOWER(TRIM(r.first_name)) = LOWER(TRIM(pi.first_name))
                 AND NOT EXISTS (SELECT 1 FROM registration r2 WHERE r2.personal_info_id = pi.id)
                 LIMIT 1),
                ''
            ) AS applicant_status,
            COALESCE(
                (SELECT r.email_address FROM registration r WHERE r.personal_info_id = pi.id LIMIT 1),
                (SELECT r.email_address FROM registration r INNER JOIN program_application pa_reg ON pa_reg.registration_id = r.id WHERE pa_reg.personal_info_id = pi.id LIMIT 1),
                (SELECT r.email_address FROM registration r 
                 WHERE LOWER(TRIM(r.last_name)) = LOWER(TRIM(pi.last_name))
                 AND LOWER(TRIM(r.first_name)) = LOWER(TRIM(pi.first_name))
                 AND NOT EXISTS (SELECT 1 FROM registration r2 WHERE r2.personal_info_id = pi.id)
                 LIMIT 1),
                ''
            ) AS email_address,
            pi.last_name,
            pi.first_name,
            pi.middle_name,
            pi.contact_number,
            (SELECT s.name FROM strands s 
             INNER JOIN academic_background ab ON s.id = ab.strand_id 
             WHERE ab.personal_info_id = pi.id 
             ORDER BY ab.id DESC LIMIT 1) as strand,
            (SELECT ab.year_graduated FROM academic_background ab 
             WHERE ab.personal_info_id = pi.id 
             ORDER BY ab.id DESC LIMIT 1) as year_graduated,
            (SELECT pa.program FROM program_application pa 
             WHERE pa.personal_info_id = pi.id
             AND LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
             ORDER BY pa.id DESC LIMIT 1) as program,
            (SELECT pa.campus FROM program_application pa 
             WHERE pa.personal_info_id = pi.id
             AND LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
             ORDER BY pa.id DESC LIMIT 1) as campus,
            COALESCE((SELECT d.g11_1st_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as g11_1st_status, 
            COALESCE((SELECT d.g11_2nd_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as g11_2nd_status, 
            COALESCE((SELECT d.g12_1st_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as g12_1st_status, 
            COALESCE((SELECT d.ncii_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as ncii_status, 
            COALESCE((SELECT d.guidance_cert_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as guidance_cert_status, 
            COALESCE((SELECT d.additional_file_status FROM documents d 
                      WHERE d.personal_info_id = pi.id 
                      ORDER BY d.id DESC LIMIT 1), 'Pending') as additional_file_status
        FROM personal_info pi
        INNER JOIN program_application pa ON pa.personal_info_id = pi.id
        WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)";

$params = [$chairProgram, $chairCampus, $chairProgram, $chairCampus, $chairProgram, $chairCampus];

// Add search conditions BEFORE GROUP BY
if ($search !== '') {
    $search = trim($search);
    
    $searchTerm = '%' . $search . '%';
    $searchLower = '%' . strtolower($search) . '%';
    
    // Build search conditions
    $searchConditions = [];
    $searchParams = [];
    
    // Search by registration ID - check multiple ways registration can be linked
    $searchConditions[] = "(
        EXISTS (SELECT 1 FROM registration r WHERE r.personal_info_id = pi.id AND CAST(r.id AS CHAR) LIKE ?)
        OR EXISTS (SELECT 1 FROM registration r INNER JOIN program_application pa_reg ON pa_reg.registration_id = r.id WHERE pa_reg.personal_info_id = pi.id AND CAST(r.id AS CHAR) LIKE ?)
        OR EXISTS (SELECT 1 FROM registration r 
                   WHERE LOWER(TRIM(r.last_name)) = LOWER(TRIM(pi.last_name))
                   AND LOWER(TRIM(r.first_name)) = LOWER(TRIM(pi.first_name))
                   AND CAST(r.id AS CHAR) LIKE ?)
        OR EXISTS (SELECT 1 FROM program_application pa_reg WHERE pa_reg.personal_info_id = pi.id AND pa_reg.registration_id IS NOT NULL AND CAST(pa_reg.registration_id AS CHAR) LIKE ?)
    )";
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
    
    // Search by last name
    $searchConditions[] = "COALESCE(pi.last_name, '') != '' AND LOWER(pi.last_name) LIKE ?";
    $searchParams[] = $searchLower;
    
    // Search by first name
    $searchConditions[] = "COALESCE(pi.first_name, '') != '' AND LOWER(pi.first_name) LIKE ?";
    $searchParams[] = $searchLower;
    
    // Search by middle name
    $searchConditions[] = "COALESCE(pi.middle_name, '') != '' AND LOWER(pi.middle_name) LIKE ?";
    $searchParams[] = $searchLower;
    
    // Search by full name concatenated
    $searchConditions[] = "COALESCE(pi.last_name, '') != '' AND COALESCE(pi.first_name, '') != '' AND LOWER(CONCAT_WS(' ', pi.last_name, pi.first_name, COALESCE(pi.middle_name, ''))) LIKE ?";
    $searchParams[] = $searchLower;
    
    // Add search conditions to SQL
    if (!empty($searchConditions) && !empty($searchParams)) {
        $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
        $params = array_merge($params, $searchParams);
        
    }
}

// Add filter conditions
if (!empty($filterStrand)) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM strands s 
        INNER JOIN academic_background ab ON s.id = ab.strand_id 
        WHERE ab.personal_info_id = pi.id 
        AND s.name = ?
        ORDER BY ab.id DESC LIMIT 1
    )";
    $params[] = $filterStrand;
}

// Add GROUP BY
$sql .= " GROUP BY pi.id, pi.last_name, pi.first_name, pi.middle_name, pi.contact_number";

// Add ORDER BY
$sql .= " ORDER BY pi.last_name ASC, pi.first_name ASC, pi.middle_name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("SQL Error in chair_applicants.php: " . $e->getMessage());
    error_log("SQL: " . $sql);
    error_log("Params count: " . count($params));
    error_log("Params: " . print_r($params, true));
    if ($isAjax) {
        echo '<div class="alert alert-danger m-3">Database error: ' . htmlspecialchars($e->getMessage()) . '<br><small>Check browser console and server logs for details.</small></div>';
        exit;
    }
    $applicants = [];
}

// Apply eligibility filter if specified
if (!empty($filterEligibility)) {
    $filteredApplicants = [];
    foreach ($applicants as $applicant) {
        // Calculate exam eligibility - only consider report cards (required documents)
        $accepted_count = 0;
        $total_docs = 0;
        $doc_statuses = [
            'g11_1st_status' => 'G11 1st Sem',
            'g11_2nd_status' => 'G11 2nd Sem', 
            'g12_1st_status' => 'G12 1st Sem'
        ];
        
        foreach ($doc_statuses as $status_field => $doc_name) {
            $status = $applicant[$status_field] ?? 'Pending';
            if ($status === null || $status === '') {
                $status = 'Pending';
            }
            if ($status === 'Accepted') {
                $accepted_count++;
            }
            $total_docs++;
        }
        
        $isEligible = ($accepted_count === $total_docs);
        
        if ($filterEligibility === 'eligible' && $isEligible) {
            $filteredApplicants[] = $applicant;
        } elseif ($filterEligibility === 'not_eligible' && !$isEligible) {
            $filteredApplicants[] = $applicant;
        }
    }
    $applicants = $filteredApplicants;
}

// If it's an AJAX request, only return the table rows
if ($isAjax):
?>
     <div class="table-container">
         <table class="table table-bordered table-striped table-hover align-middle mb-0">
             <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                 <tr>
                     <th style="width: 10%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Applicant No.</th>
                     <th style="width: 20%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                     <th style="width: 10%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Strand</th>
                     <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Contact Number</th>
                     <th style="width: 15%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Email</th>
                     <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Document Status</th>
                     <th style="width: 16%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Action</th>
                 </tr>
             </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            <?php if ($search !== ''): ?>
                                No applicants found matching "<?= htmlspecialchars($search) ?>".
                            <?php else: ?>
                                No applicants found for this program and campus.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $applicant): ?>
                        <?php
                            $applicant_status = strtolower(trim($applicant['applicant_status']));
                            $rowClass = ($applicant_status === 'new applicant - previous academic year') ? 'table-danger' : '';
                            
                            // Calculate exam eligibility - only consider report cards (required documents)
                            $accepted_count = 0;
                            $total_docs = 0;
                            $doc_statuses = [
                                'g11_1st_status' => 'G11 1st Sem',
                                'g11_2nd_status' => 'G11 2nd Sem', 
                                'g12_1st_status' => 'G12 1st Sem'
                            ];
                            
                            foreach ($doc_statuses as $status_field => $doc_name) {
                                $status = $applicant[$status_field] ?? 'Pending';
                                // Handle NULL values from database
                                if ($status === null || $status === '') {
                                    $status = 'Pending';
                                }
                                if ($status === 'Accepted') {
                                    $accepted_count++;
                                }
                                $total_docs++;
                            }
                            
                            $eligibility_text = $accepted_count . '/' . $total_docs;
                            $eligibility_class = ($accepted_count === $total_docs) ? 'success' : (($accepted_count > 0) ? 'warning' : 'danger');
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="text-center">
                                <strong><?= htmlspecialchars($applicant['registration_id']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                            </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['strand'] ?? 'N/A') ?></td>
                             <td class="text-center"><?= htmlspecialchars($applicant['contact_number'] ?? 'N/A') ?></td>
                             <td class="text-center"><?= htmlspecialchars($applicant['email_address'] ?? 'N/A') ?></td>
                             <td class="text-center">
                                <span class="badge bg-<?= $eligibility_class ?>">
                                    <?= $eligibility_text ?>
                                </span>
                                <?php if ($accepted_count === $total_docs): ?>
                                    <br><small class="text-success">✓ Verified</small>
                                <?php else: ?>
                                    <br><small class="text-danger">✗ Unverified</small>
                                <?php endif; ?>
                            </td>
                             <td class="text-center">
                                <a href="view_applicant.php?id=<?= $applicant['registration_id'] ?>" 
                                   class="btn btn-sm modern-view-btn" 
                                   title="View Details">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
     </div>
<?php
exit;
endif;
?>

<!-- FULL HTML PAGE -->
<div class="container-fluid" style="padding-top: 30px; padding-left: 15px; padding-right: 15px; padding-bottom: 0;">
    <!-- Header with Back Button -->
    <div class="row mb-2" style="margin-left: 0; margin-right: 0;">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="chair_main.php?page=chair_dashboard" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i>APPLICANT LIST</h4>
                </div>
                <div class="badge fs-6" style="background-color: rgb(0, 105, 42);">
                    <?php 
                    $total_applicants = count($applicants);
                    echo $total_applicants . " Applicant" . ($total_applicants != 1 ? 's' : '');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm" style="margin-left: -15px; margin-right: -15px; margin-top: 0; margin-bottom: 0; border-radius: 0; width: calc(100% + 30px);">
        <div class="card-header border-bottom" style="background-color: rgb(0, 105, 42) !important; color: white !important;">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label for="search" class="form-label small fw-bold">Search:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="search" class="form-control" placeholder="Search by name or applicant number" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label for="filter_strand" class="form-label small fw-bold">Filter by Strand:</label>
                    <select id="filter_strand" class="form-select">
                        <option value="">All Strands</option>
                        <option value="STEM" <?= $filterStrand === 'STEM' ? 'selected' : '' ?>>STEM</option>
                        <option value="ABM" <?= $filterStrand === 'ABM' ? 'selected' : '' ?>>ABM</option>
                        <option value="HUMSS" <?= $filterStrand === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                        <option value="GAS" <?= $filterStrand === 'GAS' ? 'selected' : '' ?>>GAS</option>
                        <option value="TVL" <?= $filterStrand === 'TVL' ? 'selected' : '' ?>>TVL</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="filter_eligibility" class="form-label small fw-bold">Filter by Document Status:</label>
                    <select id="filter_eligibility" class="form-select">
                        <option value="">All Status</option>
                        <option value="eligible" <?= $filterEligibility === 'eligible' ? 'selected' : '' ?>>Verified</option>
                        <option value="not_eligible" <?= $filterEligibility === 'not_eligible' ? 'selected' : '' ?>>Unverified</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="table-container">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                             <tr>
                                 <th style="width: 10%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Applicant No.</th>
                                 <th style="width: 20%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                                 <th style="width: 10%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Strand</th>
                                 <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Contact Number</th>
                                 <th style="width: 15%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Email</th>
                                 <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Document Status</th>
                                 <th style="width: 16%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Action</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applicants)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-users fa-2x mb-2"></i><br>
                                        <?php if ($search !== ''): ?>
                                            No applicants found matching "<?= htmlspecialchars($search) ?>".
                                        <?php else: ?>
                                            No applicants found for this program and campus.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applicants as $applicant): ?>
                                    <?php
                                        $applicant_status = strtolower(trim($applicant['applicant_status']));
                                        $rowClass = ($applicant_status === 'new applicant - previous academic year') ? 'table-danger' : '';
                                        
                                        // Calculate exam eligibility - only consider report cards (required documents)
                                        $accepted_count = 0;
                                        $total_docs = 0;
                                        $doc_statuses = [
                                            'g11_1st_status' => 'G11 1st Sem',
                                            'g11_2nd_status' => 'G11 2nd Sem', 
                                            'g12_1st_status' => 'G12 1st Sem'
                                        ];
                                        
                                        foreach ($doc_statuses as $status_field => $doc_name) {
                                            $status = $applicant[$status_field] ?? 'Pending';
                                            // Handle NULL values from database
                                            if ($status === null || $status === '') {
                                                $status = 'Pending';
                                            }
                                            if ($status === 'Accepted') {
                                                $accepted_count++;
                                            }
                                            $total_docs++;
                                        }
                                        
                                        $eligibility_text = $accepted_count . '/' . $total_docs;
                                        $eligibility_class = ($accepted_count === $total_docs) ? 'success' : (($accepted_count > 0) ? 'warning' : 'danger');
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="text-center">
                                            <strong><?= htmlspecialchars($applicant['registration_id']) ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                        </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['strand'] ?? 'N/A') ?></td>
                             <td class="text-center"><?= htmlspecialchars($applicant['contact_number'] ?? 'N/A') ?></td>
                             <td class="text-center"><?= htmlspecialchars($applicant['email_address'] ?? 'N/A') ?></td>
                             <td class="text-center">
                                <span class="badge bg-<?= $eligibility_class ?>">
                                    <?= $eligibility_text ?>
                                </span>
                                <?php if ($accepted_count === $total_docs): ?>
                                    <br><small class="text-success">✓ Verified</small>
                                <?php else: ?>
                                    <br><small class="text-danger">✗ Unverified</small>
                                <?php endif; ?>
                            </td>
                             <td class="text-center">
                                <a href="view_applicant.php?id=<?= $applicant['registration_id'] ?>" 
                                   class="btn btn-sm modern-view-btn" 
                                   title="View Details">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
html, body {
    overflow: hidden;
    height: 100%;
}
body {
    background-color: #f8f9fa;
}
.card {
    border: none;
    border-radius: 0;
}

.card.shadow-sm {
    border-radius: 0;
}
.table th {
    background-color: #28a745;
    color: white;
    border: none;
    font-weight: 600;
}
.table td {
    border-color: #dee2e6;
    vertical-align: middle;
}
.btn-outline-secondary {
    border-radius: 8px;
    font-weight: 500;
}
.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Simple Green View Button - Matching Header Color */
.modern-view-btn {
    background-color: rgb(0, 105, 42);
    border: 1px solid rgb(0, 105, 42);
    color: white;
    border-radius: 6px;
    padding: 6px 12px;
    font-weight: 500;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.modern-view-btn:hover {
    background-color: rgb(0, 85, 34);
    border-color: rgb(0, 85, 34);
    color: white;
    text-decoration: none;
}

.modern-view-btn:active {
    background-color: rgb(0, 65, 26);
    border-color: rgb(0, 65, 26);
}

        /* Back Button Theme Styling - Matching Header Color */
        .btn-outline-success {
            border-color: rgb(0, 105, 42);
            color: rgb(0, 105, 42);
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
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

        /* Scrollable Table Styling */
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0;
            border-left: none;
            border-right: none;
            border-bottom: none;
        }
        
        .card.shadow-sm {
            margin-left: -15px !important;
            margin-right: -15px !important;
            margin-bottom: 0 !important;
        }
        
        .card.shadow-sm .card-header {
            border-radius: 0;
        }
        
        .card.shadow-sm .card-body {
            padding: 0 !important;
        }

        /* Custom Scrollbar Styling */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: rgb(0, 105, 42);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgb(0, 85, 34);
        }

        /* Hide scrollbar for Firefox */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: rgb(0, 105, 42) #f1f1f1;
        }

        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: rgb(0, 105, 42) !important;
            color: white !important;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            opacity: 1 !important;
        }

        .table-container thead tr {
            background-color: rgb(0, 105, 42) !important;
        }

        .table-container thead {
            background-color: rgb(0, 105, 42) !important;
        }

        .table-container tbody {
            background-color: white;
        }

        /* Custom badge colors to match header */
        .badge.bg-success {
            background-color: rgb(0, 105, 42) !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
</style>

<!-- ✅ JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    
    function updateTable() {
        const searchTerm = document.getElementById('search').value.trim();
        const filterStrand = document.getElementById('filter_strand').value;
        const filterEligibility = document.getElementById('filter_eligibility').value;
        
        const params = new URLSearchParams({ 
            ajax: 1, 
            search: searchTerm,
            filter_strand: filterStrand,
            filter_eligibility: filterEligibility
        });

        const tableContainer = document.getElementById('tableContainer');
        if (!tableContainer) {
            console.error('tableContainer element not found');
            return;
        }
        
        // Show loading indicator
        tableContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        const url = 'chair_applicants.php?' + params.toString();
        console.log('Fetching:', url); // Debug log
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-cache'
        })
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                console.log('Response received, length:', html.length); // Debug log
                
                // Check if response contains error messages
                if (html.includes('alert alert-danger') || html.includes('Database error')) {
                    console.error('Error in response:', html);
                    tableContainer.innerHTML = html;
                    return;
                }
                
                // Extract debug info from HTML comment if present
                const debugMatch = html.match(/<!-- Search: ([^|]+) \| Results: (\d+) -->/);
                if (debugMatch) {
                    console.log('Search term:', debugMatch[1], '| Results count:', debugMatch[2]);
                }
                
                if (html && html.trim()) {
                    tableContainer.innerHTML = html;
                } else {
                    tableContainer.innerHTML = '<div class="alert alert-warning m-3">No results found.</div>';
                }
            })
            .catch(error => {
                console.error('Error updating table:', error);
                tableContainer.innerHTML = '<div class="alert alert-danger m-3">Error loading data: ' + error.message + '. Please refresh the page and try again.</div>';
            });
    }
    
    // Debounced search function
    function debouncedUpdateTable() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(updateTable, 300); // Wait 300ms after user stops typing
    }

    // Add event listeners
    const searchInput = document.getElementById('search');
    const filterStrandSelect = document.getElementById('filter_strand');
    const filterEligibilitySelect = document.getElementById('filter_eligibility');
    
    if (searchInput) {
        searchInput.addEventListener('input', debouncedUpdateTable);
    }
    if (filterStrandSelect) {
        filterStrandSelect.addEventListener('change', updateTable);
    }
    if (filterEligibilitySelect) {
        filterEligibilitySelect.addEventListener('change', updateTable);
    }
});
</script>
