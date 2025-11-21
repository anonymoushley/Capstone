<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate form token if not exists
if (!isset($_SESSION['password_change_token'])) {
    $_SESSION['password_change_token'] = bin2hex(random_bytes(32));
}

// Use session flash messages for PRG + toasts
// Don't initialize empty strings - just check if they exist and are not empty

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Check for duplicate submission using session
    if (isset($_SESSION['last_password_change']) && 
        (time() - $_SESSION['last_password_change']) < 5) {
        $_SESSION['error_message'] = "Please wait before submitting again.";
    } elseif (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['password_change_token']) {
        $_SESSION['error_message'] = "Invalid form submission. Please try again.";
    } else {
        $_SESSION['last_password_change'] = time();
        // Regenerate token after successful submission
        $_SESSION['password_change_token'] = bin2hex(random_bytes(32));
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error_message'] = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = "New password and confirm password do not match.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error_message'] = "New password must be at least 6 characters long.";
        } else {
        // Get current password from database
        $interviewer_id = $_SESSION['interviewer_id'];
        $stmt = $conn->prepare("SELECT password FROM interviewers WHERE id = ?");
        
        if (!$stmt) {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("i", $interviewer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }
        
        if ($stmt && $user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE interviewers SET password = ? WHERE id = ?");
            
            if (!$update_stmt) {
                $_SESSION['error_message'] = "Database error: " . $conn->error;
            } else {
                $update_stmt->bind_param("si", $hashed_password, $interviewer_id);
                
                if ($update_stmt->execute()) {
                    // Store success message in session and redirect
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: ?page=interviewer_dashboard");
                    exit;
                } else {
                    $_SESSION['error_message'] = "Error updating password. Please try again.";
                }
                $update_stmt->close();
            }
        } elseif ($stmt && $user) {
            $_SESSION['error_message'] = "Current password is incorrect.";
        } elseif ($stmt) {
            $_SESSION['error_message'] = "User not found. Please contact administrator.";
        }
        
        if ($stmt) {
            $stmt->close();
        }
        }
    }
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=change_password");
    exit;
}
?>

<style>
    html, body {
        overflow: hidden;
        height: 100%;
    }
    .password-card {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-control:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }
    
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
    
    .input-group .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
    }
    
    .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #495057;
    }
    
    /* Validation Styles */
    .form-control.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    /* Toast styling */
    .toast {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
    }
    
    .toast-body {
        font-weight: 500;
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0" style="margin-top: 10px;"><i class="fas fa-user-cog me-2"></i>CHANGE PASSWORD</h4>
        </div>
    </div>

    <!-- Password Change Form -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Update Your Password</h5>
                </div>
                <div class="card-body">
                    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
                        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
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

                        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
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

                    <form id="changePasswordForm" method="POST">
                        <input type="hidden" name="form_token" value="<?= $_SESSION['password_change_token'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                    <i class="fas fa-eye" id="currentPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye" id="newPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="button" class="btn btn-header-theme" id="changePasswordBtn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
// Auto-show error toast if it exists
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }, 2000);
    });
});

// Form validation before showing modal
document.getElementById('changePasswordBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();
    
    // Clear previous validation
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    
    let isValid = true;
    let errorMessage = '';
    
    // Validate current password
    if (!currentPassword) {
        errorMessage = 'Current password is required.';
        isValid = false;
    }
    // Validate new password
    else if (!newPassword) {
        errorMessage = 'New password is required.';
        isValid = false;
    } else if (newPassword.length < 6) {
        errorMessage = 'Password must be at least 6 characters long.';
        isValid = false;
    }
    // Validate confirm password
    else if (!confirmPassword) {
        errorMessage = 'Please confirm your new password.';
        isValid = false;
    } else if (newPassword !== confirmPassword) {
        errorMessage = 'Passwords do not match.';
        isValid = false;
    }
    
    // Show error toast if validation fails
    if (!isValid) {
        showErrorToast(errorMessage);
        return;
    }
    
    // If validation passes, submit the form directly
    if (isValid) {
        // Prevent double submission
        const submitButton = document.getElementById('changePasswordBtn');
        submitButton.disabled = true;
        
        // Add loading state
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing Password...';
        
        // Add hidden input to indicate password change
        const form = document.getElementById('changePasswordForm');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'change_password';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
        
        // Submit form
        form.submit();
    }
});

// Helper function to show error toast
function showErrorToast(message) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;
    
    // Remove any existing error toasts
    const existingToasts = toastContainer.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Create error toast
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
            <i class="fas fa-exclamation-circle text-danger me-2"></i>
            <strong class="me-auto text-danger">Error</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" style="background-color: #f8d7da;">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-hide after 3 seconds
    const bsToast = new bootstrap.Toast(toast);
    setTimeout(() => {
        bsToast.hide();
    }, 3000);
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const iconId = fieldId + 'Icon';
    const icon = document.getElementById(iconId);
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
