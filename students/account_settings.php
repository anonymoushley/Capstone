<?php 
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// No server-side form handling needed - using AJAX
?>
<style>
    /* Base/Desktop Styles */
    .password-settings-wrapper {
        margin-top: 1.5rem;
        padding-top: 1rem;
    }
    .settings-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
        display: block;
    }
    .settings-header {
        background-color: #00692a;
        color: white;
        padding: 15px;
        border-radius: 8px 8px 0 0;
    }
    .settings-header h5 {
        font-size: 1.25rem;
    }
    .settings-body {
        padding: 20px;
    }
    .btn-header-theme {
        background-color: #00692a !important;
        color: white !important;
        border: 1px solid #00692a !important;
        transition: all 0.2s ease;
    }
    .btn-header-theme:hover {
        background-color: #005223 !important;
        border-color: #005223 !important;
        color: white !important;
    }
    .btn-header-theme:active {
        background-color: #004a1f !important;
        border-color: #004a1f !important;
        color: white !important;
    }
    .btn-header-theme:focus {
        background-color: #00692a !important;
        border-color: #00692a !important;
        color: white !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    .input-group {
        position: relative;
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }
    .input-group > .form-control {
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        position: relative;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 0;
    }
    .input-group .btn-outline-secondary i {
        font-size: 1rem;
    }
    .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #495057;
    }
    .form-label {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }
    .form-control {
        font-size: 1rem;
        padding: 0.375rem 0.75rem;
    }
    .form-text {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    .btn {
        width: auto;
        margin-bottom: 0;
        font-size: 1rem;
        padding: 0.375rem 0.75rem;
    }
    .btn-group .btn {
        width: auto;
    }

    /* Tablet Styles (max-width: 768px) - Complete and Independent */
    @media (max-width: 768px) {
        .password-settings-wrapper {
            margin-top: 1rem;
            padding-top: 0.75rem;
        }
        .settings-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            margin: 10px auto;
            display: block;
        }
        .settings-header {
            background-color: #00692a;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .settings-header h5 {
            font-size: 1.25rem;
        }
        .settings-body {
            padding: 15px;
        }
        .btn-header-theme {
            background-color: #00692a !important;
            color: white !important;
            border: 1px solid #00692a !important;
            transition: all 0.2s ease;
        }
        .btn-header-theme:hover {
            background-color: #005223 !important;
            border-color: #005223 !important;
            color: white !important;
        }
        .btn-header-theme:active {
            background-color: #004a1f !important;
            border-color: #004a1f !important;
            color: white !important;
        }
        .btn-header-theme:focus {
            background-color: #00692a !important;
            border-color: #00692a !important;
            color: white !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
        }
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            width: 100%;
        }
        .input-group > .form-control {
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
            position: relative;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            box-sizing: border-box;
            border-width: 1px;
            padding: 0.5rem 0.75rem;
            line-height: 1.5;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group .btn-outline-secondary {
            border-color: #ced4da;
            color: #6c757d;
            padding: 0.5rem 0.75rem;
            width: auto;
            min-width: auto;
            max-width: 50px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: 0;
            box-sizing: border-box;
            border-width: 1px;
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group .btn-outline-secondary i {
            font-size: 0.875rem;
            margin: 0;
        }
        .input-group .btn-outline-secondary:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }
        .form-label {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .input-group > .form-control {
            padding: 0.5rem 0.75rem;
            line-height: 1;
            font-size: 0.95rem;
        }
        .form-control {
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            line-height: 1;
            box-sizing: border-box;
            border-width: 1px;
        }
        .form-text {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        .btn {
            width: 100%;
            margin-bottom: 10px;
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .btn-group .btn {
            width: auto;
        }
    }

    /* Mobile Styles (max-width: 576px) - Complete and Independent */
    @media (max-width: 576px) {
        .password-settings-wrapper {
            margin-top: 0.75rem;
            padding-top: 0.5rem;
        }
        .settings-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            margin: 5px auto;
            display: block;
        }
        .settings-header {
            background-color: #00692a;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }
        .settings-header h5 {
            font-size: 1rem;
        }
        .settings-body {
            padding: 10px;
        }
        .btn-header-theme {
            background-color: #00692a !important;
            color: white !important;
            border: 1px solid #00692a !important;
            transition: all 0.2s ease;
        }
        .btn-header-theme:hover {
            background-color: #005223 !important;
            border-color: #005223 !important;
            color: white !important;
        }
        .btn-header-theme:active {
            background-color: #004a1f !important;
            border-color: #004a1f !important;
            color: white !important;
        }
        .btn-header-theme:focus {
            background-color: #00692a !important;
            border-color: #00692a !important;
            color: white !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
        }
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            width: 100%;
        }
        .input-group > .form-control {
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
            position: relative;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            box-sizing: border-box;
            border-width: 1px;
            padding: 0.625rem 0.75rem;
            line-height: 1.5;
            font-size: 16px;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group > .form-control:focus {
            z-index: 3;
            position: relative;
        }
        .input-group .btn-outline-secondary {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: 0;
            padding: 0.625rem 0.75rem;
            width: auto;
            min-width: auto;
            max-width: 50px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
            touch-action: manipulation;
            border-color: #ced4da;
            color: #6c757d;
            box-sizing: border-box;
            border-width: 1px;
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group .btn-outline-secondary i {
            font-size: 1rem;
            margin: 0;
        }
        .input-group .btn-outline-secondary:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }
        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            font-size: 16px;
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            -webkit-appearance: none;
            appearance: none;
            line-height: 1.5;
            box-sizing: border-box;
            border-width: 1px;
        }
        .form-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            line-height: 1.4;
        }
        .mb-3 {
            margin-bottom: 1.25rem !important;
        }
        .input-group .btn-outline-secondary:active {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
        .btn {
            width: 100%;
            margin-bottom: 0.75rem;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            min-height: 44px;
            touch-action: manipulation;
        }
        .btn-group .btn {
            width: auto;
        }
        .d-grid {
            margin-top: 1rem;
        }
    }

    /* Extra Small Mobile Styles (max-width: 360px) - Complete and Independent */
    @media (max-width: 360px) {
        .password-settings-wrapper {
            margin-top: 0.5rem;
            padding-top: 0.25rem;
        }
        .settings-card {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            display: block;
        }
        .settings-header {
            background-color: #00692a;
            color: white;
            padding: 8px;
            border-radius: 6px 6px 0 0;
        }
        .settings-header h5 {
            font-size: 0.9rem;
        }
        .settings-body {
            padding: 8px;
        }
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
            font-weight: 500;
        }
        .form-control {
            font-size: 16px;
            padding: 0.5rem 0.625rem;
            border-radius: 0.25rem;
            line-height: 1.5;
            box-sizing: border-box;
            border-width: 1px;
        }
        .form-text {
            font-size: 0.7rem;
            margin-top: 0.2rem;
        }
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            width: 100%;
        }
        .input-group > .form-control {
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
            position: relative;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            box-sizing: border-box;
            border-width: 1px;
            padding: 0.5rem 0.625rem;
            line-height: 1.5;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group .btn-outline-secondary {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: 0;
            padding: 0.5rem 0.625rem;
            width: auto;
            min-width: auto;
            max-width: 45px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
            touch-action: manipulation;
            border-color: #ced4da;
            color: #6c757d;
            box-sizing: border-box;
            border-width: 1px;
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            margin: 0;
            height: auto;
            min-height: 38px;
        }
        .input-group .btn-outline-secondary i {
            font-size: 0.875rem;
            margin: 0;
        }
        .input-group .btn-outline-secondary:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }
        .input-group .btn-outline-secondary:active {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
        .input-group > .form-control {
            padding: 0.5rem 0.625rem;
            line-height: 1;
            font-size: 16px;
        }
        .btn {
            font-size: 0.9rem;
            padding: 0.625rem 0.75rem;
            min-height: 40px;
        }
    }
</style>

<div class="password-settings-wrapper">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-6">
            <!-- Toast Container -->
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

            <div class="settings-card">
                <div class="settings-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Update Your Password</h5>
                </div>
                <div class="settings-body">
                    <form id="passwordForm" method="POST" action="">
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
                            <div class="form-text">At least 8 characters long.</div>
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
                            <button type="submit" name="change_password" class="btn btn-header-theme">Change Password</button>
                        </div>
                    </form>
                </div>
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

// Toast Functions
function showSuccessToast(message) {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    // Remove any existing toasts
    const existingToasts = toastContainer.querySelectorAll('.toast');
    existingToasts.forEach(toast => {
        const bsToast = bootstrap.Toast.getInstance(toast);
        if (bsToast) {
            bsToast.hide();
        }
        toast.remove();
    });
    
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto text-success">Success</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: #d4edda;">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 2000
    });
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
    
    toast.show();
}

function showErrorToast(message) {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    // Remove any existing toasts
    const existingToasts = toastContainer.querySelectorAll('.toast');
    existingToasts.forEach(toast => {
        const bsToast = bootstrap.Toast.getInstance(toast);
        if (bsToast) {
            bsToast.hide();
        }
        toast.remove();
    });
    
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                <strong class="me-auto text-danger">Error</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="background-color: #f8d7da;">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 2000
    });
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
    
    toast.show();
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // No initial messages to show - using AJAX for real-time feedback
});

// AJAX Form Submission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    // Add the change_password field that the PHP script expects
    formData.append('change_password', '1');
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    fetch('change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            // Clear form after successful password change
            document.getElementById('passwordForm').reset();
        } else {
            showErrorToast(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('An error occurred while updating your password.');
    });
});
</script>

