<?php

// Ensure session is started for flash messages and auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
require_once __DIR__ . '/../config/error_handler.php';
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    handleError("System error. Please try again later.", $e->getMessage(), 500, true, 'chair_main.php');
}

// PHPMailer imports
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$chairperson_id = $_SESSION['chair_id'] ?? null;

// Authorization check
if (!$chairperson_id) {
    // Check if session exists but chair_id is not set
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'chairperson') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Invalid user type.']);
        exit;
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Chairperson not logged in.']);
    exit;
}

// Handle Add
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "add") {
  // Verify CSRF token
  require_once __DIR__ . '/../config/security.php';
  if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
    exit;
  }
  
  // Validate and sanitize input
  $last_name = isset($_POST['last_name']) ? strtoupper(trim($_POST['last_name'])) : '';
  $first_name = isset($_POST['first_name']) ? strtoupper(trim($_POST['first_name'])) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  
  // Validate required fields
  if (empty($last_name) || empty($first_name) || empty($email)) {
    $_SESSION['error_message'] = "All fields are required.";
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
    exit;
  }
  
  // Validate email format
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Invalid email format.";
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
    exit;
  }
  
  // Validate name length
  if (strlen($last_name) > 100 || strlen($first_name) > 100) {
    $_SESSION['error_message'] = "Name fields are too long.";
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
    exit;
  }
  
  // Generate 6-character temporary password (3 letters + 3 numbers)
  $letters = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 3);
  $numbers = substr(str_shuffle("0123456789"), 0, 3);
  $temp_password = $letters . $numbers;
  $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

  // Check if email already exists
  $check_stmt = $conn->prepare("SELECT id FROM interviewers WHERE email = ?");
  $check_stmt->bind_param("s", $email);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();
  
  if ($check_result->num_rows > 0) {
    // Email already exists → set flash message and redirect to show toast
    $_SESSION['error_message'] = "Email address already exists!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
    exit;
  } else {
    // Insert new interviewer
    $stmt = $conn->prepare("INSERT INTO interviewers (last_name, first_name, email, password, chairperson_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $last_name, $first_name, $email, $hashed_password, $chairperson_id);
    
    if ($stmt->execute()) {
      // Try to send email using PHPMailer (same config as verify_document.php)
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acregalado.chmsu@gmail.com';
        $mail->Password = 'vvekpeviojyyysfq'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('acregalado.chmsu@gmail.com', 'CHMSU Admissions');
        $mail->addAddress($email, $first_name . ' ' . $last_name);

        $mail->isHTML(true);
        $mail->Subject = 'Interviewer Account - Temporary Password';
        $mail->Body = "
        <h3>Welcome to CHMSU Interviewer Portal</h3>
        <p>Dear " . $first_name . " " . $last_name . ",</p>
        <p>Your interviewer account has been created successfully.</p>
        <p><strong>Your temporary password is: " . $temp_password . "</strong></p>
        <p>Please login using your email address and this temporary password. You will be prompted to change your password upon first login.</p>
        <p>Login URL: <a href='http://localhost/CAPSTONE/admin/chair_login.php'>http://localhost/CAPSTONE/admin/chair_login.php</a></p>
        <p>Best regards,<br>CHMSU Administration</p>
        ";

        $mail->send();
        $_SESSION['success_message'] = "Interviewer added successfully! Temporary password sent to email.";
      } catch (Exception $e) {
        $_SESSION['success_message'] = "Interviewer added successfully! Please provide the temporary password manually: <strong>" . $temp_password . "</strong><br><small>Email could not be sent: " . $e->getMessage() . "</small>";
      }
      
      // Redirect to prevent form resubmission
      header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
      exit;
    } else {
      $_SESSION['error_message'] = "Error adding interviewer!";
      header("Location: " . $_SERVER['PHP_SELF'] . "?page=interviewers");
      exit;
    }
  }
}

// Handle Delete via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    // Clear any previous output
    ob_clean();
    
    // Set proper headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Disable error display to prevent HTML in JSON response
    ini_set('display_errors', 0);
    
    try {
        $delete_id = intval($_POST['interviewer_id']);
        
        // Check if chairperson_id is valid
        if (!$chairperson_id) {
            echo json_encode(['success' => false, 'message' => 'Chairperson not logged in!']);
            exit;
        }
        
        // Check if interviewer exists and belongs to this chairperson
        $check_stmt = $conn->prepare("SELECT id FROM interviewers WHERE id = ? AND chairperson_id = ?");
        $check_stmt->bind_param("ii", $delete_id, $chairperson_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Interviewer not found or access denied!']);
            exit;
        }
        
        // Delete the interviewer
        $stmt = $conn->prepare("DELETE FROM interviewers WHERE id = ? AND chairperson_id = ?");
        $stmt->bind_param("ii", $delete_id, $chairperson_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Interviewer deleted successfully!', 'redirect' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch interviewers linked to this chairperson
$stmt = $conn->prepare("SELECT * FROM interviewers WHERE chairperson_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $chairperson_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<style>
    input.uppercase {
        text-transform: uppercase;
    }
    .interviewer-container {
        padding: 20px;
    }
    
    /* Header Theme Button Styling */
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
    
    /* Equal height cards */
    .interviewer-container .row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .interviewer-container .col-lg-8,
    .interviewer-container .col-lg-4 {
        display: flex;
    }
    
    .interviewer-container .card {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .interviewer-container .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
</style>

<div class="interviewer-container">
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
        <i class="fas fa-user-plus"></i> Add Interviewer
      </div>
      <div class="card-body">
        <!-- Display success/error messages as toasts -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
            <?php if (!empty($_SESSION['success_message'])): ?>
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong class="me-auto text-success">Success</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body" style="background-color: #d4edda;">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION['error_message'])): ?>
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                        <strong class="me-auto text-danger">Error</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body" style="background-color: #f8d7da;">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        </div>
    
        
        <form method="post" class="row g-3" id="addInterviewerForm">
          <input type="hidden" name="action" value="add">
          <div class="col-md-6">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control uppercase" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control uppercase" required>
          </div>
          <div class="col-12">
            <label class="form-label" style="color: black;">Email Address</label>
            <input type="email" name="email" class="form-control" required>
            <div class="form-text" style="color: black;">A temporary password will be sent to this email address.</div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-header-theme"><i class="fas fa-plus-circle"></i> Add Interviewer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
        <i class="fas fa-info-circle"></i> Instructions
      </div>
      <div class="card-body">
        <h6 style="color: rgb(0, 105, 42);">How to add an interviewer:</h6>
        <ol class="small" style="color: rgb(0, 105, 42);">
          <li>Enter the interviewer's full name</li>
          <li>Provide a valid email address</li>
          <li>Click "Add Interviewer"</li>
          <li>A temporary password will be sent to their email</li>
          <li>They can login using their email and temporary password</li>
        </ol>
        <div class="alert small" style="background-color: #d4edda; border-color: #00692a; color: #155724;">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Note:</strong> The temporary password is 6 characters long and contains letters and numbers.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Interviewer Confirmation Modal (green theme like delete modal) -->
<div class="modal fade" id="confirmAddModal" tabindex="-1" aria-labelledby="confirmAddModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
        <h5 class="modal-title" id="confirmAddModalLabel">
          <i class="fas fa-user-check me-2"></i>Confirm Add
        </h5>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="fas fa-user-check fa-3x" style="color: rgb(0, 105, 42);"></i>
        </div>
        <p class="text-center">Are you sure you want to add this interviewer?</p>
        <div class="alert" style="background-color: rgba(0, 105, 42, 0.1); border-color: rgb(0, 105, 42); color: rgb(0, 105, 42);">
          <p class="mb-1"><strong>Interviewer:</strong> <span id="confirmFirstName">-</span> <span id="confirmLastName">-</span></p>
          <p class="mb-0"><strong>Email:</strong> <span id="confirmEmail">-</span></p>
        </div>
        <p class="text-muted small text-center">
          A temporary password will be generated and sent to this email.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-header-theme" id="confirmAddBtn">
          <i class="fas fa-check me-1"></i>Confirm
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: rgb(0, 105, 42);">
    <div>
      <i class="fas fa-users"></i> Interviewer List
    </div>
    <div class="badge text-white" style="background-color: rgb(0, 105, 42);">
      <?php 
      $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM interviewers WHERE chairperson_id = ?");
      $count_stmt->bind_param("i", $chairperson_id);
      $count_stmt->execute();
      $count_result = $count_stmt->get_result();
      $total_interviewers = $count_result->fetch_assoc()['total'];
      echo $total_interviewers . " Interviewer" . ($total_interviewers != 1 ? 's' : '');
      ?>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0">
        <thead style="background-color: rgb(0, 105, 42); color: white;">
          <tr>
            <th class="text-center" style="width: 60px;">ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Email Address</th>
            <th class="text-center" style="width: 150px;">Created At</th>
            <th class="text-center" style="width: 100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="text-center"><?= $row['id'] ?></td>
                <td><strong><?= strtoupper($row['last_name']) ?></strong></td>
                <td><?= strtoupper($row['first_name']) ?></td>
                <td>
                  <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-decoration-none" style="color: rgb(0, 105, 42);">
                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($row['email']) ?>
                  </a>
                </td>
                <td class="text-center">
                  <small class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                </td>
                <td class="text-center">
                  <button type="button" 
                          class="btn btn-sm btn-danger" 
                          data-bs-toggle="modal" 
                          data-bs-target="#deleteModal"
                          data-interviewer-id="<?= $row['id'] ?>"
                          data-interviewer-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                          title="Delete Interviewer">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="fas fa-users fa-2x mb-2"></i><br>
                No interviewers added yet. Add your first interviewer using the form above.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel">
          <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
        </h5>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
        </div>
        <p class="text-center">Are you sure you want to delete this interviewer?</p>
        <div class="alert alert-danger">
          <strong>Interviewer:</strong> <span id="interviewerName"></span>
        </div>
        <p class="text-muted small text-center">
          This action cannot be undone. The interviewer will lose access to the system immediately.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Cancel
        </button>
        <form id="deleteForm" method="POST" style="display: inline;">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="interviewer_id" id="interviewerIdInput">
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash me-1"></i>Delete Interviewer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Interviewer confirmation modal wiring
    const addForm = document.getElementById('addInterviewerForm');
    const confirmAddModal = document.getElementById('confirmAddModal');
    const confirmLastName = document.getElementById('confirmLastName');
    const confirmFirstName = document.getElementById('confirmFirstName');
    const confirmEmail = document.getElementById('confirmEmail');
    const confirmAddBtn = document.getElementById('confirmAddBtn');
    let addFormConfirmed = false;

    if (addForm && confirmAddModal) {
        addForm.addEventListener('submit', function(e) {
            if (addFormConfirmed) {
                // Allow actual submit after confirmation
                return;
            }
            e.preventDefault();
            // Populate modal with current form values
            const ln = addForm.querySelector('input[name="last_name"]').value.trim();
            const fn = addForm.querySelector('input[name="first_name"]').value.trim();
            const em = addForm.querySelector('input[name="email"]').value.trim();
            confirmLastName.textContent = ln || '-';
            confirmFirstName.textContent = fn || '-';
            confirmEmail.textContent = em || '-';
            // Show modal
            const modal = new bootstrap.Modal(confirmAddModal);
            modal.show();
        });

        if (confirmAddBtn) {
            confirmAddBtn.addEventListener('click', function() {
                addFormConfirmed = true;
                addForm.submit();
            });
        }
    }
    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const interviewerNameSpan = document.getElementById('interviewerName');
    const interviewerIdInput = document.getElementById('interviewerIdInput');
    
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const interviewerId = button.getAttribute('data-interviewer-id');
        const interviewerName = button.getAttribute('data-interviewer-name');
        
        // Update modal content
        interviewerNameSpan.textContent = interviewerName;
        interviewerIdInput.value = interviewerId;
    });
    
    // Handle form submission via AJAX
    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(deleteForm);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned HTML instead of JSON. Response: ' + text.substring(0, 200));
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Show success toast
                showToast('success', data.message);
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(deleteModal);
                modal.hide();
                
                // Reload page to update the list and prevent resubmission
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Show error toast
                showToast('error', data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            showToast('error', 'Error: ' + error.message);
        });
    });
    
    // Show toast notifications on page load (for add functionality)
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
    
    // Function to show toast messages
    function showToast(type, message) {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1055';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toastId = 'toast_' + Date.now();
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        const headerBg = type === 'success' ? '#d4edda' : '#f8d7da';
        const headerBorder = type === 'success' ? '#c3e6cb' : '#f5c6cb';
        const bodyBg = type === 'success' ? '#d4edda' : '#f8d7da';
        const textColor = type === 'success' ? 'text-success' : 'text-danger';
        const title = type === 'success' ? 'Success' : 'Error';
        
        const toastDiv = document.createElement('div');
        toastDiv.id = toastId;
        toastDiv.className = 'toast show';
        toastDiv.setAttribute('role', 'alert');
        toastDiv.setAttribute('aria-live', 'assertive');
        toastDiv.setAttribute('aria-atomic', 'true');
        
        toastDiv.innerHTML = `
            <div class="toast-header" style="background-color: ${headerBg}; border-color: ${headerBorder};">
                <i class="fas ${iconClass} ${textColor} me-2"></i>
                <strong class="me-auto ${textColor}">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: ${bodyBg};">
                ${message}
            </div>
        `;
        
        // Add to container
        toastContainer.appendChild(toastDiv);
        
        // Auto-hide after 2 seconds
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toastDiv);
            bsToast.hide();
        }, 2000);
        
        // Show toast
        const toast = new bootstrap.Toast(toastDiv);
        toast.show();
        
        // Remove toast element after it's hidden
        toastDiv.addEventListener('hidden.bs.toast', function() {
            if (toastDiv.parentNode) {
                toastDiv.remove();
            }
        });
    }
});
</script>
