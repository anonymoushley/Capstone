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

// Get chairperson's assigned program and campus
$chairProgram = $_SESSION['program'] ?? '';
$chairCampus = $_SESSION['campus'] ?? '';

if (!$chairProgram || !$chairCampus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

// AJAX handler: return table only
if (isset($_GET['ajax'])) {
    // Debug: Log the request
    error_log("AJAX request received - search: " . ($_GET['search'] ?? '') . ", sort: " . ($_GET['sort'] ?? '') . ", filter_value: " . ($_GET['filter_value'] ?? ''));
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'last_name';
    $filterValue = isset($_GET['filter_value']) ? trim($_GET['filter_value']) : '';

    $allowed_sorts = ['last_name', 'program', 'campus', 'applicant_status', 'strand', 'year_graduated'];
    $sort = in_array($sort, $allowed_sorts) ? $sort : 'last_name';

    $sql = 'SELECT r.id, r.last_name, r.first_name, r.applicant_status, pa.program, pa.campus, s.name as strand, ab.year_graduated,
            COALESCE(d.g11_1st_status, "Pending") as g11_1st_status, 
            COALESCE(d.g11_2nd_status, "Pending") as g11_2nd_status, 
            COALESCE(d.g12_1st_status, "Pending") as g12_1st_status, 
            COALESCE(d.ncii_status, "Pending") as ncii_status, 
            COALESCE(d.guidance_cert_status, "Pending") as guidance_cert_status, 
            COALESCE(d.additional_file_status, "Pending") as additional_file_status
            FROM registration r
            LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
            LEFT JOIN program_application pa ON pa.personal_info_id = pi.id
            LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
            LEFT JOIN strands s ON ab.strand_id = s.id
            LEFT JOIN documents d ON d.personal_info_id = pi.id';

    $where = ['LOWER(pa.program) = LOWER(?)', 'LOWER(pa.campus) = LOWER(?)'];
    $params = [$chairProgram, $chairCampus];

    if ($search !== '') {
        $where[] = '(r.last_name LIKE ? OR r.first_name LIKE ? OR pa.program LIKE ? OR pa.campus LIKE ? OR s.name LIKE ? OR ab.year_graduated LIKE ?)';
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    }

    if ($filterValue !== '') {
        if ($sort === 'program') {
            $where[] = 'pa.program = ?';
            $params[] = $filterValue;
        } elseif ($sort === 'campus') {
            $where[] = 'pa.campus = ?';
            $params[] = $filterValue;
        } elseif ($sort === 'applicant_status') {
            $where[] = 'r.applicant_status = ?';
            $params[] = $filterValue;
        } elseif ($sort === 'strand') {
            $where[] = 's.name = ?';
            $params[] = $filterValue;
        } elseif ($sort === 'year_graduated') {
            $where[] = 'ab.year_graduated = ?';
            $params[] = $filterValue;
        }
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY $sort, r.last_name, r.first_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table class="table table-bordered align-middle">
        <thead class="table-success">
        <tr>
            <th class="text-white">Last Name</th>
            <th class="text-white">First Name</th>
            <th class="text-white">Status</th>
            <th class="text-white">Program</th>
            <th class="text-white">Campus</th>
            <th class="text-white">Strand</th>
            <th class="text-white">Year Graduated</th>
            <th class="text-white">Exam Eligibility</th>
            <th class="text-white">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($applicants) === 0): ?>
            <tr><td colspan="9" class="text-center text-muted">No applicants found.</td></tr>
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
                
                // Debug: Check if document status fields exist
                $debug_info = '';
                if (empty($applicant['g11_1st_status']) && empty($applicant['g11_2nd_status'])) {
                    $debug_info = ' (No docs)';
                }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($applicant['last_name']) ?></td>
                    <td><?= htmlspecialchars($applicant['first_name']) ?></td>
                    <td><?= htmlspecialchars($applicant['applicant_status']) ?></td>
                    <td><?= htmlspecialchars($applicant['program']) ?></td>
                    <td><?= htmlspecialchars($applicant['campus']) ?></td>
                    <td><?= htmlspecialchars($applicant['strand'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($applicant['year_graduated'] ?? 'N/A') ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $eligibility_class ?>">
                            <?= $eligibility_text ?><?= $debug_info ?>
                        </span>
                        <?php if ($accepted_count === $total_docs): ?>
                            <br><small class="text-success">✓ Eligible</small>
                        <?php elseif ($accepted_count > 0): ?>
                            <br><small class="text-warning">⚠ Partial</small>
                        <?php else: ?>
                            <br><small class="text-danger">✗ Pending</small>
                        <?php endif; ?>
                    </td>
                    <td><a href="view_applicant.php?id=<?= $applicant['id'] ?>" class="btn btn-sm btn-success">View</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
    exit;
}
?>

<style>
    body {
        background: url('../students/images/chmsubg.jpg') no-repeat center center fixed;
        background-size: cover;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    .overlay {
        background-color: rgba(255, 255, 255, 0.8);
        min-height: 100vh;
        padding-top: 20px;
    }
    .progress-bar-custom {
        background-color: rgb(0, 105, 42);
    }
    .card-body {
        background-color: rgb(237, 244, 237);
    }
    .container {
        width: 950px;
    }
    .btn-success {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    .btn-success:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
    .table-success {
        background-color: rgb(0, 105, 42);
    }
    .bg-success {
        background-color: rgb(0, 105, 42) !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }
    .input-group-text {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
        color: white;
    }
    .table tbody tr:hover {
        background-color: rgba(0, 105, 42, 0.1);
    }
    .table tbody tr.table-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    .table tbody tr.table-danger:hover {
        background-color: rgba(220, 53, 69, 0.2);
    }
    .card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
        border-radius: 8px;
    }
    .card-header {
        border-radius: 8px 8px 0 0 !important;
    }
    .alert-info {
        background-color: #e7f3ff;
        border-color: #b8daff;
        color: #004085;
        border-radius: 6px;
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

<div class="overlay">
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header" style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                <h5 class="mb-0"><i class="fas fa-users"></i> Applicants List</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white"><i class="fas fa-search"></i></span>
                            <input type="text" id="search" class="form-control" placeholder="Search by name, program, campus, strand, or year">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="sort" class="form-select">
                            <option value="last_name">Sort by Last Name</option>
                            <option value="program">Filter by Program</option>
                            <option value="campus">Filter by Campus</option>
                            <option value="applicant_status">Filter by Status</option>
                            <option value="strand">Filter by Strand</option>
                            <option value="year_graduated">Filter by Year Graduated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterValue" class="form-select d-none"></select>
                    </div>
                </div>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> Those who are highlighted are subject for verification
                </div>
                <div class="table-responsive" id="applicant-table">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading applicants...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search');
    const sortSelect = document.getElementById('sort');
    const filterValueSelect = document.getElementById('filterValue');
    const tableContainer = document.getElementById('applicant-table');

    const filterOptions = {
        program: ['BSIT', 'BSIS'],
        campus: ['Talisay', 'Alijis', 'Fortune Towne', 'Binalbagan'],
        applicant_status: ['New Applicant - Same Academic Year', 'New Applicant - Previous Academic Year', 'Transferee'],
        strand: ['STEM', 'ABM', 'HUMSS', 'GAS', 'TVL'],
        year_graduated: ['2020', '2021', '2022', '2023', '2024', '2025']
    };

    function populateFilterValues(type) {
        const options = filterOptions[type] || [];
        filterValueSelect.innerHTML = options.map(val => `<option value="${val}">${val}</option>`).join('');
        filterValueSelect.classList.toggle('d-none', options.length === 0);
    }

    function fetchApplicants() {
        const search = searchInput.value;
        const sort = sortSelect.value;
        const filterValue = !filterValueSelect.classList.contains('d-none') ? filterValueSelect.value : '';
        const params = new URLSearchParams({ ajax: 1, search, sort, filter_value: filterValue });

        console.log('Fetching applicants with params:', params.toString());

        fetch('applicant.php?' + params.toString())
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                console.log('Received HTML length:', html.length);
                tableContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching applicants:', error);
                tableContainer.innerHTML = '<div class="alert alert-danger">Error loading applicants: ' + error.message + '</div>';
            });
    }

    searchInput.addEventListener('input', fetchApplicants);
    sortSelect.addEventListener('change', () => {
        const selected = sortSelect.value;
        populateFilterValues(selected);
        fetchApplicants();
    });
    filterValueSelect.addEventListener('change', fetchApplicants);

    populateFilterValues(sortSelect.value);
    
    // Initial load
    console.log('Page loaded, starting initial fetch...');
    fetchApplicants();
});

</script>
</body>
</html>
