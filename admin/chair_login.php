<?php
session_start();
require_once '../config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'chairperson') {
        header("Location: chair_main.php");
        exit;
    } elseif ($_SESSION['user_type'] === 'interviewer') {
        header("Location: interviewer_main.php");
        exit;
    }
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Try chairperson login first
    $stmt = $pdo->prepare("SELECT * FROM chairperson_accounts WHERE username = ?");
    $stmt->execute([$username]);
    $chair = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chair && password_verify($password, $chair['password'])) {
        $_SESSION['chair_id'] = $chair['id'];
        $_SESSION['chair_name'] = $chair['name'];
        $_SESSION['designation'] = $chair['designation'];
        $_SESSION['program'] = $chair['program'];
        $_SESSION['campus'] = $chair['campus'];
        $_SESSION['user_type'] = 'chairperson';

        // Redirect to prevent resubmission
        header("Location: chair_main.php");
        exit;
    } else {
        // Try interviewer login
        $stmt = $pdo->prepare("SELECT * FROM interviewers WHERE email = ?");
        $stmt->execute([$username]);
        $interviewer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($interviewer && password_verify($password, $interviewer['password'])) {
            $_SESSION['interviewer_id'] = $interviewer['id'];
            $_SESSION['interviewer_name'] = $interviewer['first_name'] . ' ' . $interviewer['last_name'];
            $_SESSION['interviewer_email'] = $interviewer['email'];
            $_SESSION['user_type'] = 'interviewer';

            // Redirect to prevent resubmission
            header("Location: interviewer_main.php");
            exit;
        } else {
            // Store error in session and redirect to prevent resubmission
            $_SESSION['login_error'] = "Invalid username or password.";
            $_SESSION['saved_username'] = $username;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Get error message from session and clear it
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Get saved username from session and clear it
$saved_username = $_SESSION['saved_username'] ?? '';
unset($_SESSION['saved_username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CHMSU</title>
    <link rel="icon" href="../students/images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: url('../students/images/chmsubg.jpg') no-repeat center center fixed;
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
            margin:auto;
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
        .overlay {
            background-color: rgba(255, 255, 255, 0.5);
            min-height: 100vh;
            min-width: 100vw;
            padding-top: 40px;
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
</head>

<body>
    <div class="overlay">
    <div class="login-container">
        <div class="logo">
            <img src="../students/images/chmsu.png" alt="CHMSU Logo">
        </div>
        <div class="header-text">
            <h4>Carlos Hilado Memorial State University</h4>
            <p>Admin Portal - Chairperson & Interviewer Login</p>
        </div>
        
        <!-- Toast Container (success toasts can use this) -->
        <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

        <!-- Error Toast (copied from login.php) -->
        <?php if (!empty($error)): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
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
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($saved_username) ?>" required>
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
</script>
</body>
</html>
