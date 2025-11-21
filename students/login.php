<?php
require_once __DIR__ . '/../config/security.php';
initSecureSession();
setSecurityHeaders();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success_message = '';
$saved_email = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['saved_email'])) {
    $saved_email = $_SESSION['saved_email'];
    unset($_SESSION['saved_email']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email_address'];
    $password = $_POST['password'];
    
    // Check if it's admin login
    if ($email === 'admin@chmsu.edu.ph') {
        $sql = "SELECT * FROM registration WHERE email_address = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email_address'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'admin';
                header('Location: ../admin/dashboard.php');
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid admin credentials!";
                $_SESSION['saved_email'] = $email;
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid admin credentials!";
            $_SESSION['saved_email'] = $email;
            header("Location: login.php");
            exit();
        }
    } else {
        // Student login
        $sql = "SELECT * FROM registration WHERE email_address = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email_address'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'student';
                header('Location: applicant_dashboard.php');
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid password!";
                $_SESSION['saved_email'] = $email;
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "No account found with that email address!";
            $_SESSION['saved_email'] = $email;
            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CHMSU</title>
    <link rel="icon" href="images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            margin: auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            width: 100px;
            height: auto;
        }
        .header-text {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-text h4 {
            color: #00692a;
            margin-bottom: 5px;
        }
        .header-text p {
            color: #666;
            font-size: 0.9em;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #00692a;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
        }
        .btn-primary {
            background-color: #00692a;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background-color: #005223;
        }
        
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary:focus-visible {
            background-color: #00692a !important;
            border-color: #00692a !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
        }
        
        .btn-primary:active:focus {
            background-color: #005223 !important;
            border-color: #005223 !important;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.9em;
        }
       .overlay {
            background-color: rgba(255, 255, 255, 0.5);
            min-height: 100vh;
            min-width: 100vw;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Toast Styles - Matching Login Theme */
        .toast-container {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1060;
            max-width: calc(100% - 20px);
            width: auto;
        }
        
        .toast {
            background-color: #00692a;
            border: 1px solid #005223;
            color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 105, 42, 0.3);
            min-width: 300px;
            margin: 0 auto;
        }
        
        .toast-header {
            background-color: #00692a;
            border-bottom: 1px solid #005223;
            color: #ffffff;
            font-weight: 600;
        }
        
        .toast-body {
            padding: 12px 16px;
            color: #ffffff;
        }
        
        .toast .btn-close {
            filter: invert(1);
        }
        
        .toast .btn-close:hover {
            filter: invert(1) brightness(0.8);
        }
        .auth-link {
            color: rgb(0, 105, 42) !important;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .auth-link:hover {
            color: rgb(0, 82, 35) !important;
            text-decoration: underline;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                display: block;
            }
            .overlay {
                padding: 15px;
                min-height: 100vh;
            }
            .login-container {
                padding: 25px 20px;
                border-radius: 10px;
                max-width: 100%;
                margin: 0;
            }
            .logo img {
                width: 80px;
            }
            .header-text h4 {
                font-size: 1.1rem;
            }
            .header-text p {
                font-size: 0.85em;
            }
            .form-control {
                padding: 12px 15px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            .btn-primary {
                padding: 12px 20px;
                font-size: 16px;
            }
            .register-link {
                font-size: 0.9rem;
            }
            .toast-container {
                position: fixed !important;
                top: 10px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
                padding: 0 !important;
                max-width: calc(100% - 20px);
                width: auto;
            }
            .toast {
                min-width: 280px;
                max-width: 100%;
                margin: 0 auto;
            }
        }
        
        @media (max-width: 576px) {
            .overlay {
                padding: 10px;
            }
            .login-container {
                padding: 20px 15px;
                border-radius: 8px;
            }
            .logo img {
                width: 70px;
            }
            .header-text {
                margin-bottom: 20px;
            }
            .header-text h4 {
                font-size: 1rem;
            }
            .header-text p {
                font-size: 0.8em;
            }
            .form-control {
                padding: 10px 12px;
            }
            .input-group-text {
                padding: 10px 12px;
            }
            .btn-primary {
                padding: 10px 15px;
            }
            .register-link {
                font-size: 0.85rem;
                margin-top: 15px;
            }
            .toast-container {
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
                max-width: calc(100% - 20px);
            }
            .toast {
                min-width: 250px;
            }
        }
        
        @media (max-width: 400px) {
            .login-container {
                padding: 15px 12px;
            }
            .logo img {
                width: 60px;
            }
            .header-text h4 {
                font-size: 0.95rem;
            }
            .header-text p {
                font-size: 0.75em;
            }
            .toast-container {
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
                max-width: calc(100% - 10px);
            }
            .toast {
                min-width: 200px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="overlay">
    <div class="login-container">
        <div class="logo">
            <img src="images/chmsu.png" alt="CHMSU Logo">
        </div>
        <div class="header-text">
            <h4>Carlos Hilado Memorial State University</h4>
            <p>Academic Program Application and Screening Management System</p>
        </div>
        
        <!-- Toast Container (success toasts can use this) -->
        <div id="toastContainer" class="toast-container position-fixed top-0 p-3" style="z-index: 1055;"></div>

        <!-- Error Toast (copied pattern from chair login) -->
        <?php if (!empty($error)): ?>
        <div class="toast-container position-fixed top-0 p-3" style="z-index: 1060;">
            <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email_address" class="form-control" value="<?= htmlspecialchars($saved_email) ?>" required>
            </div>
           <div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" name="password" class="form-control" id="passwordInput" required>
        <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
            <i class="fa fa-eye" id="eyeIcon"></i>
        </span>
    </div>
</div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </div>
        </form>
        
       <div class="register-link">
    Don't have an account? <a href="register.php" class="auth-link">Register here</a>
</div>

    </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
    const passwordInput = document.getElementById('passwordInput');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });

    // Show success toast if there's a success message
    <?php if ($success_message): ?>
    showSuccessToast('<?php echo addslashes($success_message); ?>');
    <?php endif; ?>
    
    function showSuccessToast(message) {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();
        
        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-check-circle text-white me-2"></i>
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.innerHTML = toastHTML;
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
    }

    // Auto-show error toast if it exists (same behavior as chair login)
    document.addEventListener('DOMContentLoaded', function() {
        const errorToast = document.getElementById('errorToast');
        if (errorToast) {
            const toast = new bootstrap.Toast(errorToast, {
                autohide: true,
                delay: 5000
            });
            toast.show();
            errorToast.addEventListener('hidden.bs.toast', function() {
                errorToast.remove();
            });
        }
    });

    // End
</script>
</body>
</html> 