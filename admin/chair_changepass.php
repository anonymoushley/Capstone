<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/error_handler.php';
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    handleError("System error. Please try again later.", $e->getMessage(), 500, true, 'chair_changepass.php');
}

// Use session flash messages for PRG + toasts
if (!isset($_SESSION['success_message'])) {
    $_SESSION['success_message'] = '';
}
if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $_SESSION['error_message'] = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['error_message'] = "New password and confirm password do not match.";
    } else {
        $chair_id = $_SESSION['chair_id'];

        $stmt = $conn->prepare("SELECT password FROM chairperson_accounts WHERE id = ?");
        $stmt->bind_param("i", $chair_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current_pass, $user['password'])) {
            $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE chairperson_accounts SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_hashed, $chair_id);
            $update->execute();
            $_SESSION['success_message'] = "Password changed successfully.";
        } else {
            $_SESSION['error_message'] = "Current password is incorrect.";
        }
    }
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=chair_changepass");
    exit;
}
?>

<style>
    html, body {
        overflow: hidden;
        height: 100%;
    }
    .show-toggle {
        cursor: pointer;
        user-select: none;
        font-size: 0.9em;
        color: rgb(0, 105, 42);
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
    
    /* Hide browser default password reveal button */
    input[type="password"]::-ms-reveal {
        display: none;
    }
    
    /* Hide Chrome/Edge password reveal button */
    input[type="password"]::-webkit-credentials-auto-fill-button {
        display: none !important;
        visibility: hidden;
        pointer-events: none;
    }
    
    /* Hide Firefox password reveal button */
    input[type="password"]::-moz-textfield-decoration-container {
        display: none;
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0" style="margin-top: 10px;"><i class="fas fa-user-cog me-2"></i>CHANGE PASSWORD</h4>
        </div>
    </div>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Update Your Password</h5>
                    </div>
            <div class="card-body">
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
                        <?php $_SESSION['success_message'] = ''; ?>
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
                        <?php $_SESSION['error_message'] = ''; ?>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-header-theme">Change Password

                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Show toast notifications on load
    document.addEventListener('DOMContentLoaded', function() {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            setTimeout(() => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            }, 2000);
        });
    });
</script>

