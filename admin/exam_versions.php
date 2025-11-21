<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/error_handler.php';
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    handleError("System error. Please try again later.", $e->getMessage(), 500, true, 'exam-management.php');
}

if (!isset($_SESSION['chair_id'])) {
    require_once __DIR__ . '/../config/error_handler.php';
    handleError("Unauthorized access.", "Unauthorized exam version access attempt", 403, true, 'exam-management.php');
}

$chairperson_id = $_SESSION['chair_id']; // ✅ use consistent session key
$success_message = '';
$error_message = '';

if (isset($_POST['submit_new_version'])) {
    $version_name = trim($_POST['version_name']);
    $chairperson_id = $_SESSION['chair_id']; // assuming you store logged chair's ID in session

    // 1. Check for duplicate version_name for this chair
    $stmt = $conn->prepare("SELECT COUNT(*) FROM exam_versions WHERE version_name = ? AND chair_id = ?");
    $stmt->bind_param("si", $version_name, $chairperson_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Duplicate exists, show error and stop
        echo "<div class='alert alert-danger'>You already have an exam version with this name. Please choose a different name.</div>";
    } else {
        // No duplicate, proceed with insert
        $stmt = $conn->prepare("INSERT INTO exam_versions (version_name, chair_id, status, is_published) VALUES (?, ?, 'Unpublished', 0)");
        $stmt->bind_param("si", $version_name, $chairperson_id);
        $stmt->execute();
        $stmt->close();

        echo "<div class='alert alert-success'>New exam version created successfully.</div>";
    }
}


// Handle creating a new exam version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_version'])) {
    // Check for duplicate submission using session
    if (isset($_SESSION['last_form_submission']) && 
        (time() - $_SESSION['last_form_submission']) < 5) {
        $error_message = "Please wait before submitting again.";
    } else {
        $_SESSION['last_form_submission'] = time();
        
        $version_name = trim($_POST['version_name']);

        if (!empty($version_name)) {
        $check_stmt = $conn->prepare("SELECT id FROM exam_versions WHERE version_name = ? AND chair_id = ?");
        $check_stmt->bind_param("si", $version_name, $chairperson_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Exam version name already exists. Please choose a different name.";
        } else {
            $stmt = $conn->prepare("INSERT INTO exam_versions (version_name, status, is_published, chair_id) VALUES (?, 'Unpublished', 0, ?)");
            $stmt->bind_param("si", $version_name, $chairperson_id);

            if ($stmt->execute()) {
                $success_message = "New exam version created successfully.";
            } else {
                $error_message = "Failed to create new exam version.";
            }
            $stmt->close();
        }
        $check_stmt->close();
        }
    }

    $redirect_url = "chair_main.php?page=exam_versions";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    } elseif (!empty($success_message)) {
        $redirect_url .= "&success=" . urlencode($success_message);
    }
    header("Location: $redirect_url");
    exit;
}


// Handle publishing
if (isset($_GET['action']) && $_GET['action'] == 'publish') {
    $id = intval($_GET['id']);
    $force = isset($_GET['force']) && $_GET['force'] == 1;

    // Check if there's already a published exam (using prepared statement)
    $published_stmt = $conn->prepare("SELECT id, version_name FROM exam_versions WHERE is_published = 1 AND is_archived = 0 AND chair_id = ?");
    $published_stmt->bind_param("i", $chairperson_id);
    $published_stmt->execute();
    $published_check = $published_stmt->get_result();

    if ($published_check->num_rows > 0 && !$force) {
        // Show confirmation modal for replacing published exam
        $published_exam = $published_check->fetch_assoc();
        $pending_stmt = $conn->prepare("SELECT version_name FROM exam_versions WHERE id = ?");
        $pending_stmt->bind_param("i", $id);
        $pending_stmt->execute();
        $pending_result = $pending_stmt->get_result();
        $pending_exam = $pending_result->fetch_assoc();
        $pending_stmt->close();
        header("Location: chair_main.php?page=exam_versions&pending_publish=" . $id . "&current_published=" . $published_exam['id'] . "&current_name=" . urlencode($published_exam['version_name']) . "&pending_name=" . urlencode($pending_exam['version_name']));
        $published_stmt->close();
        exit;
    }

    // If force is true, unpublish the current exam first (using prepared statement)
    if ($force && $published_check->num_rows > 0) {
        $unpublish_stmt = $conn->prepare("UPDATE exam_versions SET status = 'Unpublished', is_published = 0, published_at = NULL WHERE is_published = 1 AND chair_id = ?");
        $unpublish_stmt->bind_param("i", $chairperson_id);
        $unpublish_stmt->execute();
        $unpublish_stmt->close();
    }
    $published_stmt->close();
    }

    $stmt = $conn->prepare("UPDATE exam_versions SET status = 'Published', is_published = 1, published_at = NOW() WHERE id = ? AND chair_id = ?");
    $stmt->bind_param("ii", $id, $chairperson_id);

    if ($stmt->execute()) {
        $success_message = "Exam version published successfully!";
    } else {
        $error_message = "Error publishing exam version: " . $conn->error;
    }
    $stmt->close();

    $redirect_url = "chair_main.php?page=exam_versions";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    } elseif (!empty($success_message)) {
        $redirect_url .= "&success=" . urlencode($success_message);
    }
    header("Location: $redirect_url");
    exit;
}

// Handle unpublishing
if (isset($_GET['action']) && $_GET['action'] == 'unpublish') {
    $id = intval($_GET['id']);
$stmt = $conn->prepare("UPDATE exam_versions SET status = 'Unpublished', is_published = 0, published_at = NULL WHERE id = ? AND chair_id = ?");
$stmt->bind_param("ii", $id, $chairperson_id);

    if ($stmt->execute()) {
        $success_message = "Exam version unpublished successfully.";
    } else {
        $error_message = "Error unpublishing exam version: " . $conn->error;
    }
    $stmt->close();

    $redirect_url = "chair_main.php?page=exam_versions";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    } elseif (!empty($success_message)) {
        $redirect_url .= "&success=" . urlencode($success_message);
    }
    header("Location: $redirect_url");
    exit;
}

// Handle archiving
if (isset($_GET['archive'])) {
    $id = intval($_GET['archive']);
    
    // Check if exam is published before archiving
    $check_stmt = $conn->prepare("SELECT is_published, version_name FROM exam_versions WHERE id = ? AND chair_id = ?");
    $check_stmt->bind_param("ii", $id, $chairperson_id);
    $check_stmt->execute();
    $exam_data = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($exam_data && $exam_data['is_published'] == 1) {
        $error_message = "Cannot archive published exam version '" . $exam_data['version_name'] . "'. Please unpublish it first.";
    } else {
        $stmt = $conn->prepare("UPDATE exam_versions SET is_archived = 1 WHERE id = ? AND chair_id = ?");
        $stmt->bind_param("ii", $id, $chairperson_id);

        if ($stmt->execute()) {
            $success_message = "Exam version archived successfully.";
        } else {
            $error_message = "Error archiving exam version: " . $conn->error;
        }
        $stmt->close();
    }

    $redirect_url = "chair_main.php?page=exam_versions";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    } elseif (!empty($success_message)) {
        $redirect_url .= "&success=" . urlencode($success_message);
    }

    header("Location: $redirect_url");
    exit;
}

// Handle restoring
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
$stmt = $conn->prepare("UPDATE exam_versions SET is_archived = 0 WHERE id = ? AND chair_id = ?");
$stmt->bind_param("ii", $id, $chairperson_id);


    if ($stmt->execute()) {
        $success_message = "Exam version restored successfully.";
    } else {
        $error_message = "Failed to restore exam version: " . $conn->error;
    }
    $stmt->close();

    $redirect_url = "chair_main.php?page=exam_versions";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    } elseif (!empty($success_message)) {
        $redirect_url .= "&success=" . urlencode($success_message);
    }
    header("Location: $redirect_url");
    exit;
}

// ✅ Final SELECT query for versions
$stmt = $conn->prepare("SELECT * FROM exam_versions WHERE is_archived = 1 AND chair_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $chairperson_id);
$stmt->execute();
$archived_versions = $stmt->get_result();

// Display success or error messages
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
}
if (isset($_GET['success'])) {
    $successMsg = htmlspecialchars($_GET['success']);
    echo '<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">'
        . '<div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">'
        .   '<div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">'
        .     '<i class="fas fa-check-circle text-success me-2"></i>'
        .     '<strong class="me-auto text-success">Success</strong>'
        .     '<button type="button" class="btn-close" data-bs-dismiss="toast"></button>'
        .   '</div>'
        .   '<div class="toast-body" style="background-color: #d4edda;">' . $successMsg . '</div>'
        . '</div>'
        . '</div>';
    
    // Clear URL parameters to prevent toast from showing on refresh
    echo '<script>
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete("success");
            url.searchParams.delete("error");
            window.history.replaceState({}, "", url);
        }
    </script>';
}

$stmt = $conn->prepare("SELECT * FROM exam_versions WHERE is_archived = 0 AND chair_id = ?");
$stmt->bind_param("i", $chairperson_id);
$stmt->execute();
$versions = $stmt->get_result();

?>

<style>
    .card{width:285px; height: 130px; border-left: 5px solid #198754;}
    .container {
        padding-top: 30px;
    }
    .card-body {
        overflow: hidden;
        word-wrap: break-word;
    }
    .card-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .status-text {
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #createVersionBtn:disabled {
        background-color: white !important;
        border-color: white !important;
        color: rgb(0, 105, 42) !important;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .green-container {
        background-color: rgb(0, 105, 42);
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .green-container .form-control {
        background-color: white;
    }
    .green-container .btn {
        background-color: white;
        color: rgb(0, 105, 42);
        border: 1px solid white;
    }
    .green-container .btn:hover {
        background-color: #f8f9fa;
        color: rgb(0, 105, 42);
    }
    </style>
<div class="container">
    <h2>EXAMINATION MANAGEMENT</h2>

    <div class="green-container">
        <form method="POST" class="row g-3 mb-0" id="createVersionForm">
            <input type="hidden" name="create_version" value="1">
            <div class="col-md-6">
                <input type="text" name="version_name" id="versionNameInput" class="form-control" placeholder="Enter new exam version name" required>
            </div>
            <div class="col-md-3">
                <button type="button" id="createVersionBtn" class="btn w-100" style="background-color: white; color: rgb(0, 105, 42); border: 1px solid white;" data-bs-toggle="modal" data-bs-target="#createVersionModal" disabled>Create Version</button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn w-100" style="background-color: white; color: #dc3545; border: 1px solid white;" data-bs-toggle="modal" data-bs-target="#archivedVersionsModal" id="viewArchivedBtn">
                    <i class="fas fa-archive me-1"></i>View Archived
                </button>
            </div>
        </form>
    </div>

    <div class="row">
        <?php if ($versions && $versions->num_rows > 0): ?>
            <?php while($row = $versions->fetch_assoc()): ?>
                <div class="col-md-4 mb-2">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="card-title" title="<?= htmlspecialchars($row['version_name']) ?>"><?= htmlspecialchars($row['version_name']) ?></h5>
                                    <p class="status-text">Status: <strong><?= $row['status'] ?></strong></p>
                                </div>
                                <div class="col-md-5">
                                    <?php if ($row['status'] === 'Published' || $row['is_published'] == 1): ?>
                                        <button type="button" class="btn btn-primary btn-sm mb-2 w-100" disabled title="Cannot manage questions when exam is published. Unpublish first.">Questions</button>
                                    <?php else: ?>
                                        <a href="exam-management.php?version_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mb-2 w-100">Questions</a>
                                    <?php endif; ?>
                                    <?php if ($row['status'] === 'Published'): ?>
                                        <button type="button" class="btn btn-warning btn-sm w-100" 
   data-bs-toggle="modal" data-bs-target="#unpublishModal"
   data-version-id="<?= $row['id'] ?>" 
   data-version-name="<?= htmlspecialchars($row['version_name']) ?>">Unpublish</button>

                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm w-100" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" 
   data-bs-toggle="modal" data-bs-target="#publishModal"
   data-version-id="<?= $row['id'] ?>" 
   data-version-name="<?= htmlspecialchars($row['version_name']) ?>">Publish</button>

                                    <?php endif; ?>
                                    <?php if ($row['status'] === 'Published'): ?>
                                        <button type="button" class="btn btn-secondary btn-sm mt-2 w-100" disabled title="Cannot archive published exam. Unpublish first.">
                                            <i class="fas fa-archive me-1"></i>Archive
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm mt-2 w-100" style="background-color: #dc3545; color: white; border: 1px solid #dc3545;" 
           data-bs-toggle="modal" data-bs-target="#archiveModal"
           data-version-id="<?= $row['id'] ?>" 
           data-version-name="<?= htmlspecialchars($row['version_name']) ?>">
                                            <i class="fas fa-archive me-1"></i>Archive
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">No active exam versions. Create one above!</div>
        <?php endif; ?>
    </div>

</div>

<?php if (isset($_GET['pending_publish'])): ?>
<!-- Modal for confirm override -->
<div class="modal show d-block" tabindex="-1" role="dialog" id="overrideModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #dc3545; color: white;">
        <h5 class="modal-title">⚠️ Another Exam Is Already Published</h5>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <strong>Warning:</strong> Only one exam version can be published at a time.
        </div>
        <p><strong>Currently Published:</strong> <?= htmlspecialchars($_GET['current_name'] ?? 'Unknown') ?></p>
        <p><strong>Want to Publish:</strong> <?= htmlspecialchars($_GET['pending_name'] ?? 'Unknown') ?></p>
        <p>Do you want to unpublish the current exam and publish the new version?</p>
      </div>
      <div class="modal-footer">
        <a href="chair_main.php?page=exam_versions&action=publish&id=<?= intval($_GET['pending_publish']) ?>&force=1" class="btn btn-danger">Yes, Replace</a>
        <a href="chair_main.php?page=exam_versions" class="btn btn-secondary">Cancel</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Create Version Confirmation Modal -->
<div class="modal fade" id="createVersionModal" tabindex="-1" aria-labelledby="createVersionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="createVersionModalLabel">Confirm Create Exam Version</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to create a new exam version?</p>
        <p><strong>Version Name:</strong> <span id="createVersionName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" onclick="submitCreateForm()">Create Version</button>
      </div>
    </div>
  </div>
</div>

<!-- Publish Confirmation Modal -->
<div class="modal fade" id="publishModal" tabindex="-1" aria-labelledby="publishModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="publishModalLabel">Confirm Publish Exam Version</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to publish this exam version?</p>
        <p><strong>Version Name:</strong> <span id="publishVersionName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="publishConfirmLink" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);">Publish</a>
      </div>
    </div>
  </div>
</div>

<!-- Unpublish Confirmation Modal -->
<div class="modal fade" id="unpublishModal" tabindex="-1" aria-labelledby="unpublishModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="unpublishModalLabel">Confirm Unpublish Exam Version</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to unpublish this exam version?</p>
        <p><strong>Version Name:</strong> <span id="unpublishVersionName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="unpublishConfirmLink" class="btn btn-warning">Unpublish</a>
      </div>
    </div>
  </div>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="archiveModalLabel">Confirm Archive Exam Version</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to archive this exam version?</p>
        <p><strong>Version Name:</strong> <span id="archiveVersionName"></span></p>
        <p class="text-muted">Archived versions can be restored later.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="archiveConfirmLink" class="btn" style="background-color: #dc3545; color: white; border: 1px solid #dc3545;">Archive</a>
      </div>
    </div>
  </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true" data-bs-backdrop="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="restoreModalLabel">Confirm Restore Exam Version</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to restore this exam version?</p>
        <p><strong>Version Name:</strong> <span id="restoreVersionName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="restoreConfirmLink" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);">Restore</a>
      </div>
    </div>
  </div>
</div>

<!-- Archived Versions Modal -->
<div class="modal fade" id="archivedVersionsModal" tabindex="-1" aria-labelledby="archivedVersionsModalLabel" aria-hidden="true" data-bs-backdrop="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #dc3545; color: white;">
        <h5 class="modal-title" id="archivedVersionsModalLabel">
          <i class="fas fa-archive me-2"></i>Archived Exam Versions
        </h5>
      </div>
      <div class="modal-body">
        <?php
        $archived_stmt = $conn->prepare("SELECT * FROM exam_versions WHERE is_archived = 1 AND chair_id = ? ORDER BY id DESC");
        $archived_stmt->bind_param("i", $chairperson_id);
        $archived_stmt->execute();
        $archived_versions = $archived_stmt->get_result();
        if ($archived_versions && $archived_versions->num_rows > 0): ?>
        <div class="row">
            <?php while($row = $archived_versions->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card bg-light border-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title text-muted mb-0"><?= htmlspecialchars($row['version_name']) ?></h6>
                                <span class="badge bg-secondary">Archived</span>
                            </div>
                            <p class="card-text small text-muted mb-2">
                                <strong>Status:</strong> <?= $row['status'] ?><br>
                                <strong>Archived:</strong> <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </p>
                            <div class="d-grid">
                                <button type="button" class="btn btn-sm" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);"
                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                    data-version-id="<?= $row['id'] ?>" 
                                    data-version-name="<?= htmlspecialchars($row['version_name']) ?>">
                                    <i class="fas fa-undo me-1"></i>Restore
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Archived Versions</h5>
                <p class="text-muted">There are no archived exam versions to display.</p>
            </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-show Bootstrap toast for success message
document.addEventListener('DOMContentLoaded', function () {
    // Clear URL parameters immediately to prevent toast from showing on refresh
    if (window.location.search.includes('success=') || window.location.search.includes('error=')) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        url.searchParams.delete('error');
        window.history.replaceState({}, '', url);
    }
    
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
    
    // Legacy code for success toast (if exists)
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        // Remove toast from DOM after it's hidden to prevent re-showing
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
    
    // Enable/disable Create Version button based on input
    var versionNameInput = document.getElementById('versionNameInput');
    var createVersionBtn = document.getElementById('createVersionBtn');
    
    if (versionNameInput && createVersionBtn) {
        // Initial state - button should be disabled
        createVersionBtn.disabled = true;
        createVersionBtn.style.opacity = '0.6';
        
        // Add event listener for real-time validation
        versionNameInput.addEventListener('input', function() {
            var hasText = this.value.trim().length > 0;
            
            if (hasText) {
                createVersionBtn.disabled = false;
                createVersionBtn.style.opacity = '1';
                createVersionBtn.style.backgroundColor = 'white';
                createVersionBtn.style.color = 'rgb(0, 105, 42)';
            } else {
                createVersionBtn.disabled = true;
                createVersionBtn.style.opacity = '0.6';
            }
        });
    }
});
</script>
<script>
// Create Version Modal
document.getElementById('createVersionModal').addEventListener('show.bs.modal', function (event) {
    var versionName = document.getElementById('versionNameInput').value;
    document.getElementById('createVersionName').textContent = versionName;
});

// Reset form when modal is hidden
document.getElementById('createVersionModal').addEventListener('hidden.bs.modal', function (event) {
    // Reset form state
    var form = document.getElementById('createVersionForm');
    form.dataset.submitting = 'false';
    
    // Reset submit button
    var submitBtn = document.querySelector('#createVersionModal .btn[onclick="submitCreateForm()"]');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Create Version';
    }
    
    // Clear the input field and reset main button state
    var versionNameInput = document.getElementById('versionNameInput');
    var createVersionBtn = document.getElementById('createVersionBtn');
    
    if (versionNameInput) {
        versionNameInput.value = '';
    }
    
    if (createVersionBtn) {
        createVersionBtn.disabled = true;
        createVersionBtn.style.opacity = '0.6';
    }
});

function submitCreateForm() {
    var form = document.getElementById('createVersionForm');
    var versionNameInput = document.getElementById('versionNameInput');
    
    // Validate form input
    if (!versionNameInput.value.trim()) {
        alert('Please enter a version name.');
        return false;
    }
    
    // Add a flag to prevent multiple submissions
    if (form.dataset.submitting === 'true') {
        return false;
    }
    form.dataset.submitting = 'true';
    
    // Disable the submit button to prevent resubmission
    var submitBtn = document.querySelector('#createVersionModal .btn[onclick="submitCreateForm()"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating...';
    }
    
    // Submit the form
    form.submit();
}

// Publish Modal
document.getElementById('publishModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var versionId = button.getAttribute('data-version-id');
    var versionName = button.getAttribute('data-version-name');
    
    document.getElementById('publishVersionName').textContent = versionName;
    document.getElementById('publishConfirmLink').href = 'chair_main.php?page=exam_versions&action=publish&id=' + versionId;
    
    // Check if there's already a published exam and show warning
    fetch('check_published_exam.php')
        .then(response => response.json())
        .then(data => {
            if (data.hasPublished) {
                var modalBody = document.querySelector('#publishModal .modal-body');
                
                // Remove any existing warning first
                var existingWarning = modalBody.querySelector('.alert.alert-warning');
                if (existingWarning) {
                    existingWarning.remove();
                }
                
                var warningDiv = document.createElement('div');
                warningDiv.className = 'alert alert-warning';
                warningDiv.innerHTML = '<strong>Warning:</strong> There is currently a published exam: <strong>' + data.publishedName + '</strong>. Publishing this exam will unpublish the current one.';
                modalBody.insertBefore(warningDiv, modalBody.firstChild);
            }
        })
        .catch(error => console.log('Error checking published exam:', error));
});

// Unpublish Modal
document.getElementById('unpublishModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var versionId = button.getAttribute('data-version-id');
    var versionName = button.getAttribute('data-version-name');
    
    document.getElementById('unpublishVersionName').textContent = versionName;
    document.getElementById('unpublishConfirmLink').href = 'chair_main.php?page=exam_versions&action=unpublish&id=' + versionId;
});

// Archive Modal
document.getElementById('archiveModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var versionId = button.getAttribute('data-version-id');
    var versionName = button.getAttribute('data-version-name');
    
    document.getElementById('archiveVersionName').textContent = versionName;
    document.getElementById('archiveConfirmLink').href = 'chair_main.php?page=exam_versions&archive=' + versionId;
});

// Restore Modal
document.getElementById('restoreModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var versionId = button.getAttribute('data-version-id');
    var versionName = button.getAttribute('data-version-name');
    
    document.getElementById('restoreVersionName').textContent = versionName;
    document.getElementById('restoreConfirmLink').href = 'chair_main.php?page=exam_versions&restore=' + versionId;
});


</script>
<?php
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    require_once '../config/connection.php'; // or wherever $conn is defined

    $stmt = $conn->prepare("UPDATE exam_versions SET is_archived = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $execute_result = $stmt->execute();

    if ($execute_result) {
        header("Location: chair_main_dashboard.php?page=exam_versions&success=" . urlencode("Exam version restored successfully."));
    } else {
        header("Location: chair_main.php?page=exam_versions&error=" . urlencode("Failed to restore exam version."));
    }
    $stmt->close();
    exit;
}
?>
