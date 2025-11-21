<?php 
/**
 * General Reports
 * 
 * Generates reports for all applicants
 * 
 * @package Admin
 */

require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/error_handler.php';

// Database connection
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>System error. Please contact administrator.</div>";
    exit;
}

$sql = "
    SELECT
        pi.id AS personal_info_id,
        pi.last_name, pi.first_name, pi.contact_number,
        s.name as strand, ab_agg.g11_1st_avg, ab_agg.g11_2nd_avg, ab_agg.g12_1st_avg,
        sr_agg.gwa_score, sr_agg.stanine_result, sr_agg.stanine_score,
        sr_agg.exam_total_score, sr_agg.interview_total_score,
        sr_agg.plus_factor, sr_agg.rank,
        d.ncii_status,
        ((ab_agg.g11_1st_avg + ab_agg.g11_2nd_avg + ab_agg.g12_1st_avg) / 3) AS gwa_avg
    FROM (
        SELECT DISTINCT personal_info_id FROM registration
    ) r
    JOIN personal_info pi ON r.personal_info_id = pi.id
    LEFT JOIN (
        SELECT ab1.*
        FROM academic_background ab1
        WHERE ab1.id = (
            SELECT MAX(ab2.id) FROM academic_background ab2
            WHERE ab2.personal_info_id = ab1.personal_info_id
        )
    ) ab_agg ON ab_agg.personal_info_id = pi.id
    LEFT JOIN strands s ON ab_agg.strand_id = s.id
    LEFT JOIN documents d ON d.personal_info_id = pi.id
    LEFT JOIN (
        SELECT 
            personal_info_id,
            MAX(gwa_score) AS gwa_score,
            MAX(stanine_result) AS stanine_result,
            MAX(stanine_score) AS stanine_score,
            MAX(initial_total) AS initial_total,
            MAX(exam_total_score) AS exam_total_score,
            MAX(interview_total_score) AS interview_total_score,
            MAX(plus_factor) AS plus_factor,
            MAX(rank) AS rank
        FROM screening_results
        GROUP BY personal_info_id
    ) sr_agg ON sr_agg.personal_info_id = pi.id
    ORDER BY pi.last_name, pi.first_name
";

// Use prepared statement (no parameters needed for this query, but safer)
$result = $conn->query($sql);
if (!$result) {
    echo "<div class='alert alert-danger'>Database error. Please contact administrator.</div>";
    exit;
}

?>

<style>
    th, td { vertical-align: middle !important; font-size: 11px; }
    .card-header { background-color: #28a745 !important; }
    body { background-color: #f8f9fa; }
    .card{width:950px; }
    
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

    /* Stanine input styling */
    .stanine-input {
        width: 80px;
        text-align: center;
        font-size: 12px;
        padding: 2px 5px;
    }

    .stanine-input:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }

    /* Print Styling */
    @media print {
        @page {
            margin: 0.5in;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .no-print {
            display: none !important;
        }
        
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            page-break-inside: avoid;
            position: relative;
            top: 0;
            left: 0;
            width: 100%;
        }
        
        .print-header img {
            height: 60px;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .print-header h3 {
            margin: 0;
            color: #000 !important;
            font-size: 18px;
            font-weight: bold;
        }
        
        .print-header h4 {
            margin: 10px 0 0 0;
            color: #000 !important;
            font-size: 16px;
            font-weight: bold;
        }
        
        .print-header p {
            margin: 5px 0 0 0;
            color: #000 !important;
            font-size: 14px;
        }
        
        body {
            background: white !important;
            color: black !important;
            font-size: 12px;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
        
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
        
        .table {
            font-size: 10px !important;
        }
        
        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
        }
    }
    
    .print-header {
        display: none;
    }
    
    /* Hide DataTables buttons container */
    .dt-buttons,
    .dt-button-collection,
    .buttons-print,
    .buttons-colvis {
        display: none !important;
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Print Header (hidden on screen, visible when printing) -->
    <div class="print-header">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>Applicant Screening Report</h4>
    </div>
    <!-- Header with Back Button -->
    <div class="row mb-4 no-print">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="admin_dashboard.php" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0">Applicant Screening Report</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Screening Results</h5>
        </div>
        <div class="card-body table-responsive">
            <table id="screeningTable" class="table table-bordered table-striped text-center">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Strand</th>
                        <th>Contact</th>
                        <th>G11 1st</th>
                        <th>G11 2nd</th>
                        <th>G12 1st</th>
                        <th>GWA (10%)</th>
                        <th>Stanine</th>
                        <th>Stanine Score (15%)</th>
                        <th>Initial Total</th>
                        <th>Exam Score</th>
                        <th>Exam (40%)</th>
                        <th>Interview Score</th>
                        <th>Interview (35%)</th>
                        <th>Plus Factor</th>
                        <th>Final Rating</th>
                        <th>Rank</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        // Use 0 if NULL
                        $gwa_score = is_numeric($row['gwa_score']) ? $row['gwa_score'] : 0;
                        $stanine_score = is_numeric($row['stanine_score']) ? $row['stanine_score'] : 0;
                        $exam_score = is_numeric($row['exam_total_score']) ? $row['exam_total_score'] : 0;
                        $interview_score = is_numeric($row['interview_total_score']) ? $row['interview_total_score'] : 0;
                        // Calculate plus factor based on strand and NCII status
                        $plus_factor = calculatePlusFactor($row['strand'], $row['ncii_status']);

                        // Calculate weights
                        $gwa_pct = ($gwa_score / 100) * 10;
                        $stanine_pct = $stanine_score * 0.15;
                        $initial_total = is_numeric($row['initial_total']) ? $row['initial_total'] : ($gwa_pct + $stanine_pct);
                        $exam_pct = ($exam_score / 100) * 40;
                        $interview_pct = ($interview_score / 100) * 35;
                        $final_rating = $initial_total + $exam_pct + $interview_pct + $plus_factor;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                        <td><?= $row['strand'] ?: '-' ?></td>
                        <td><?= $row['contact_number'] ?: '-' ?></td>
                        <td><?= is_numeric($row['g11_1st_avg']) ? number_format($row['g11_1st_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g11_2nd_avg']) ? number_format($row['g11_2nd_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g12_1st_avg']) ? number_format($row['g12_1st_avg'], 2) : '-' ?></td>
                        <td><?= number_format($gwa_pct, 2) ?></td>
                        <td>
                            <input type="text" class="form-control form-control-sm stanine-input" 
                                   value="<?= htmlspecialchars($row['stanine_result'] ?: '') ?>" 
                                   data-applicant-id="<?= $row['personal_info_id'] ?? '' ?>"
                                   placeholder="Enter stanine">
                        </td>
                        <td><?= number_format($stanine_pct, 2) ?></td>
                        <td><?= number_format($initial_total, 2) ?></td>
                        <td><?= number_format($exam_score, 2) ?></td>
                        <td><?= number_format($exam_pct, 2) ?></td>
                        <td><?= number_format($interview_score, 2) ?></td>
                        <td><?= number_format($interview_pct, 2) ?></td>
                        <td><?= number_format($plus_factor, 2) ?></td>
                        <td><strong><?= number_format($final_rating, 2) ?></strong></td>
                        <td><?= $row['rank'] ?: '-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JS and DataTables scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    // Prevent DataTables Buttons extension from being used even if scripts are loaded
    if (typeof $.fn.dataTable !== 'undefined' && $.fn.dataTable.Buttons) {
        // Override the buttons initialization
        var originalButtons = $.fn.dataTable.Buttons;
        $.fn.dataTable.Buttons = function() {
            return this;
        };
    }
    
    $(document).ready(function() {
        // Ensure DB has synced computed values so frontend reflects stored numbers
        fetch('sync_gwa_initial.php', { method: 'POST' }).finally(() => {
            // Destroy existing DataTable instance if it exists
            if ($.fn.DataTable.isDataTable('#screeningTable')) {
                $('#screeningTable').DataTable().destroy();
            }
            
            // Remove any existing button containers BEFORE initialization
            $('.dt-buttons').remove();
            $('.buttons-print').remove();
            $('.buttons-colvis').remove();
            $('.dt-button-collection').remove();
            
            // Initialize DataTable without buttons - explicitly prevent buttons
            var table = $('#screeningTable').DataTable({
                dom: 'frtip', // No 'B' means no buttons container
                order: [[15, 'desc']],
                buttons: false // Explicitly disable buttons
            });
            
            // Remove buttons immediately after initialization
            setTimeout(function() {
                $('.dt-buttons').remove();
                $('.buttons-print').remove();
                $('.buttons-colvis').remove();
                $('.dt-button-collection').remove();
                // Also remove from the DataTable wrapper
                $('#screeningTable').closest('.dataTables_wrapper').find('.dt-buttons').remove();
            }, 50);
            
            // Remove buttons on DataTable draw events
            table.on('draw', function() {
                $('.dt-buttons').remove();
                $('.buttons-print').remove();
                $('.buttons-colvis').remove();
                $('.dt-button-collection').remove();
            });
        });
        
        // Continuously monitor and remove buttons (in case they're added dynamically)
        setInterval(function() {
            $('.dt-buttons').remove();
            $('.buttons-print').remove();
            $('.buttons-colvis').remove();
            $('.dt-button-collection').remove();
            $('#screeningTable').closest('.dataTables_wrapper').find('.dt-buttons').remove();
        }, 200);
    });

    // Handle stanine input updates
    document.addEventListener('DOMContentLoaded', function() {
        const stanineInputs = document.querySelectorAll('.stanine-input');
        
        function toNumber(value) {
            const n = parseFloat((value || '').toString().replace(/[^0-9.\-]/g, ''));
            return isNaN(n) ? 0 : n;
        }

        function computeStanineScore(rawValue) {
            const v = toNumber(rawValue);
            if (v >= 0 && v <= 100) return v; // already a percentage
            if (v >= 1 && v <= 9) return v; // return raw stanine value 1-9
            return 0;
        }

        function recalcRow(input) {
            const tr = input.closest('tr');
            if (!tr) return;

            const tds = tr.querySelectorAll('td');
            // Column indices based on header
            const gwaPct = toNumber(tds[6]?.textContent);
            const examPct = toNumber(tds[11]?.textContent);
            const interviewPct = toNumber(tds[13]?.textContent);
            const plusFactor = toNumber(tds[14]?.textContent);

            const stanineScore = computeStanineScore(input.value);
            const staninePct = stanineScore * 0.15;
            const initialTotal = gwaPct + staninePct;
            const finalRating = initialTotal + examPct + interviewPct + plusFactor;

            // Update cells
            if (tds[8]) tds[8].textContent = staninePct.toFixed(2);
            if (tds[9]) tds[9].textContent = initialTotal.toFixed(2);
            if (tds[15]) tds[15].querySelector('strong')
                ? tds[15].querySelector('strong').textContent = finalRating.toFixed(2)
                : tds[15].textContent = finalRating.toFixed(2);
        }

        function recomputeAllRanks() {
            const rows = Array.from(document.querySelectorAll('#screeningTable tbody tr'));
            const scored = rows.map((tr) => {
                const finalCell = tr.querySelectorAll('td')[15];
                const finalText = finalCell?.querySelector('strong')?.textContent || finalCell?.textContent || '0';
                const finalVal = toNumber(finalText);
                return { tr, finalVal };
            });

            scored.sort((a, b) => b.finalVal - a.finalVal);

            let currentRank = 0;
            let lastScore = null;
            let position = 0;
            for (const item of scored) {
                position += 1;
                if (lastScore === null || item.finalVal !== lastScore) {
                    currentRank = position;
                    lastScore = item.finalVal;
                }
                const rankCell = item.tr.querySelectorAll('td')[16];
                if (rankCell) rankCell.textContent = currentRank.toString();
            }
        }

        function updateStanine(input) {
            const stanineValue = input.value.trim();
            const applicantId = input.getAttribute('data-applicant-id');
            
            if (applicantId && stanineValue) {
                // Update stanine in database
                fetch('update_stanine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `applicant_id=${applicantId}&stanine=${encodeURIComponent(stanineValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success indicator
                        input.style.borderColor = '#28a745';
                        setTimeout(() => {
                            input.style.borderColor = '';
                        }, 2000);
                    } else {
                        alert('Error updating stanine: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating stanine');
                });
            }
        }
        
        stanineInputs.forEach(input => {
            // Recalculate row values as user types (real-time UI update)
            input.addEventListener('input', function() {
                recalcRow(this);
                recomputeAllRanks();
            });
            // Initialize current values on load
            recalcRow(input);
            recomputeAllRanks();

            // Save on blur (clicking outside)
            input.addEventListener('blur', function() {
                updateStanine(this);
            });
            
            // Save on Enter key press
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateStanine(this);
                    this.blur(); // Remove focus from input
                    recomputeAllRanks();
                }
            });
        });
    });
</script>
