<?php
require_once __DIR__ . '/../config/security.php';
initSecureSession();
setSecurityHeaders();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/functions.php';

$success_message = '';
$error_message = '';

// Show messages from session if redirected
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load Google OAuth config if available (must be loaded before POST handlers)
$google_oauth_enabled = false;
$google_auth_url = '';
$google_oauth_error = '';

try {
    // Check if Google Client class is available
    if (!class_exists('Google\Client')) {
        $google_oauth_error = 'Google API Client library is not installed. Please run: composer require google/apiclient';
    } else {
        if (file_exists(__DIR__ . '/../config/google_oauth.php')) {
            $google_oauth_config = require __DIR__ . '/../config/google_oauth.php';
            if (!empty($google_oauth_config['client_id']) && 
                $google_oauth_config['client_id'] !== 'YOUR_GOOGLE_CLIENT_ID_HERE' &&
                !empty($google_oauth_config['client_secret']) && 
                $google_oauth_config['client_secret'] !== 'YOUR_GOOGLE_CLIENT_SECRET_HERE') {
                
                $google_oauth_enabled = true;
                $client = new \Google\Client();
                $client->setClientId($google_oauth_config['client_id']);
                $client->setClientSecret($google_oauth_config['client_secret']);
                
                // Dynamically generate redirect URI to ensure it matches exactly
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
                $redirectUri = $protocol . '://' . $host . $scriptPath . '/google_callback.php';
                
                // Remove any trailing slashes and ensure proper formatting
                $redirectUri = rtrim($redirectUri, '/');
                if (substr($redirectUri, -4) !== '.php') {
                    $redirectUri .= '/google_callback.php';
                }
                
                $client->setRedirectUri($redirectUri);
                $client->addScope($google_oauth_config['scopes']);
                
                
                $google_auth_url = $client->createAuthUrl();
            } else {
                $google_oauth_error = 'Google OAuth credentials are not configured in config/google_oauth.php';
            }
        } else {
            $google_oauth_error = 'Google OAuth config file not found: config/google_oauth.php';
        }
    }
} catch (Exception $e) {
    // Google OAuth not configured or error - continue without it
    $google_oauth_enabled = false;
    $google_oauth_error = 'Error: ' . $e->getMessage();
}

// Handle Google OAuth status selection
if (isset($_GET['google_auth']) && isset($_GET['need_status']) && isset($_SESSION['google_user'])) {
    // User needs to select applicant status
    $google_user = $_SESSION['google_user'];
}

// Handle Google OAuth form submission (save form data before OAuth)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_oauth_submit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
        header("Location: register.php");
        exit();
    }
    
    // Rate limiting for registration
    if (!checkRateLimit('registration', 3, 300)) { // 3 attempts per 5 minutes
        $_SESSION['error_message'] = "Too many registration attempts. Please try again later.";
        header("Location: register.php");
        exit();
    }
    
    $last_name = validateInput(trim($_POST['last_name'] ?? ''), 'string', 100);
    $first_name = validateInput(trim($_POST['first_name'] ?? ''), 'string', 100);
    $app_status = validateInput(trim($_POST['applicant_status'] ?? ''), 'string', 100);
    
    // Validate required fields
    if (empty($last_name) || empty($first_name) || empty($app_status)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }
    
    // Save form data to session
    $_SESSION['applicant_status'] = $app_status;
    $_SESSION['form_last_name'] = $last_name;
    $_SESSION['form_first_name'] = $first_name;
    
    // Redirect to Google OAuth
    if ($google_oauth_enabled && !empty($google_auth_url)) {
        header("Location: " . $google_auth_url);
        exit();
    } else {
        $_SESSION['error_message'] = "Google OAuth is not configured. Please contact administrator.";
        header("Location: register.php");
        exit();
    }
}

// Handle Google OAuth status submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_status_submit']) && isset($_SESSION['google_user'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
        header("Location: register.php");
        exit();
    }
    
    $app_status = validateInput(trim($_POST['applicant_status'] ?? ''), 'string', 100);
    if (empty($app_status)) {
        $_SESSION['error_message'] = "Please select an application status.";
        header("Location: register.php?google_auth=1&need_status=1");
        exit();
    }
    
    // Set status and redirect to Google OAuth
    $_SESSION['applicant_status'] = $app_status;
    // Redirect back to Google OAuth callback to complete registration
    if (isset($_SESSION['google_user'])) {
        // Create account directly with Google user info
        $google_user = $_SESSION['google_user'];
        $email = $google_user['email'];
        
        // Use form data from session if available, otherwise use Google data
        $firstName = isset($_SESSION['form_first_name']) && !empty($_SESSION['form_first_name']) 
            ? $_SESSION['form_first_name'] 
            : ($google_user['first_name'] ?: '');
        $lastName = isset($_SESSION['form_last_name']) && !empty($_SESSION['form_last_name']) 
            ? $_SESSION['form_last_name'] 
            : ($google_user['last_name'] ?: '');
        
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'admission');
        if ($conn->connect_error) {
            $_SESSION['error_message'] = "Database connection failed.";
            header("Location: register.php");
            exit();
        }
        
        // Check if user already exists
        if (emailExists($conn, $email)) {
            // User exists - show error message instead of logging in
            unset($_SESSION['google_user']);
            unset($_SESSION['applicant_status']);
            unset($_SESSION['form_first_name']);
            unset($_SESSION['form_last_name']);
            $_SESSION['error_message'] = "Email address already registered! Please use a different email or login with your existing account.";
            header('Location: register.php');
            exit();
        } else {
            // Create new account
            // Generate temporary password (6 characters like regular registration)
            $temp_password = generateTempPassword();
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO registration (email_address, password, first_name, last_name, applicant_status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashed_password, $firstName, $lastName, $app_status);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = ($firstName ?: '') . ' ' . ($lastName ?: '');
                $_SESSION['role'] = 'student';
                
                // Send temporary password email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'acregalado.chmsu@gmail.com';
                    $mail->Password   = 'vvekpeviojyyysfq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('acregalado.chmsu@gmail.com', 'CCS Admission Committee');
                    $mail->addAddress($email, "$firstName $lastName");

                    $mail->isHTML(false);
                    $mail->Subject = "Your CHMSU Application Account Credentials";
                    $mail->Body = "Dear $firstName $lastName,\n\n"
                                . "Thank you for registering with CHMSU via Google. Here are your login credentials:\n\n"
                                . "Email: $email\n"
                                . "Temporary Password: $temp_password\n\n"
                                . "Please login at: http://localhost/system/students/login.php\n\n"
                                . "Note: You can continue using Google Sign-In, but you can also use these credentials to login directly.\n\n"
                                . "Best regards,\nCCS Admission Committee";

                    $mail->send();
                    // Set flag to show modal on dashboard
                    $_SESSION['temp_password_sent'] = true;
                    $_SESSION['temp_password_email'] = $email;
                } catch (Exception $e) {
                    // Email failed but account was created - still set flag but log error
                    error_log("Failed to send temp password email: " . $e->getMessage());
                    $_SESSION['temp_password_sent'] = true;
                    $_SESSION['temp_password_email'] = $email;
                    $_SESSION['temp_password_email_error'] = true;
                }
                
                unset($_SESSION['google_user']);
                unset($_SESSION['applicant_status']);
                unset($_SESSION['form_first_name']);
                unset($_SESSION['form_last_name']);
                $_SESSION['success_message'] = "Registration successful! Welcome to CHMSU Application System.";
                header('Location: applicant_dashboard.php');
                exit();
            } else {
                $_SESSION['error_message'] = "Registration failed. Please try again.";
                unset($_SESSION['google_user']);
                header("Location: register.php");
                exit();
            }
        }
    } else {
        $_SESSION['error_message'] = "Google authentication information not found. Please try again.";
        header("Location: register.php");
        exit();
    }
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
        header("Location: register.php");
        exit();
    }
    
    // Rate limiting for registration
    if (!checkRateLimit('registration', 3, 300)) { // 3 attempts per 5 minutes
        $_SESSION['error_message'] = "Too many registration attempts. Please try again later.";
        header("Location: register.php");
        exit();
    }
    
    // Capture and sanitize inputs
    $last_name = validateInput(trim($_POST['last_name'] ?? ''), 'string', 100);
    $first_name = validateInput(trim($_POST['first_name'] ?? ''), 'string', 100);
    $email = validateInput(trim($_POST['email_address'] ?? ''), 'email', 255);
    $app_status = validateInput(trim($_POST['applicant_status'] ?? ''), 'string', 100);

    // Validate required fields
    if ($last_name === false || $first_name === false || $email === false || $app_status === false) {
        $_SESSION['error_message'] = "All fields are required and must be valid.";
        header("Location: register.php");
        exit();
    } else {
        // Set session
        $_SESSION['applicant_status'] = $app_status;

        // Generate password
        $password = generateTempPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check for duplicate email
        if (emailExists($conn, $email)) {
            $_SESSION['error_message'] = "Email address already registered!";
            header("Location: register.php");
            exit();
        } else {
            // Insert new record
            $sql = "INSERT INTO registration (email_address, password, first_name, last_name, applicant_status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashed_password, $first_name, $last_name, $app_status);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;

                // Send email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'acregalado.chmsu@gmail.com';
                    $mail->Password   = 'vvekpeviojyyysfq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('acregalado.chmsu@gmail.com', 'CCS Admission Committee');
                    $mail->addAddress($email, "$first_name $last_name");

                    $mail->isHTML(false);
                    $mail->Subject = "Your CHMSU Application Account Credentials";
                    $mail->Body = "Dear $first_name $last_name,\n\n"
                                . "Thank you for registering with CHMSU. Here are your login credentials:\n\n"
                                . "Email: $email\n"
                                . "Password: $password\n\n"
                                . "Please login at: http://localhost/CAPSTONE/students/login.php\n\n"
                                . "Best regards,\nCCS Admission Committee";

                    $mail->send();
                    $_SESSION['success_message'] = "Registration successful! Please check your email for login credentials.";
                } catch (Exception $e) {
                    $_SESSION['success_message'] = "Registration successful, but email could not be sent. Error: {$mail->ErrorInfo}";
                }
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Registration failed. Please try again.";
                header("Location: register.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carlos Hilado Memorial State University</title>
  <link rel="icon" href="images/chmsu.png" type="image/png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --header-height: 80px;
    }
    html, body {
      background: url('images/chmsubg.jpg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
      height: 100vh;
      width: 100vw;
      overflow: hidden;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }
    body::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }
    .overlay {
      background-color: rgba(255, 255, 255, 0.5);
      height: 100vh;
      width: 100vw;
      padding: 10px;
      padding-top: calc(10px + var(--header-height));
      overflow: hidden;
      box-sizing: border-box;
    }
    .left-panel {
      background-color: rgba(232, 245, 233, 0.88);
      padding: 1.5rem;
      height: calc(100vh - var(--header-height) - 20px);
      overflow-y: auto;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }
    .left-panel::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }
    .right-panel {
      padding: 1rem;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      height: calc(100vh - var(--header-height) - 20px);
      overflow-y: auto;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }
    .right-panel::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }
    
    .register-card {
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 100%;
      border: none;
      margin-top: 0.5rem;
    }
    
    .register-card .card-body {
      padding: 0;
    }
    
    .container-fluid {
      height: calc(100vh - var(--header-height) - 20px);
      overflow: hidden;
      padding: 0;
    }
    
    .row {
      height: 100%;
      margin: 0;
    }

    .header-bar {
      background-color:rgb(0, 105, 42);
      color: white;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      width: 100%;
      z-index: 1050;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* Custom button styles to match header theme */
    .btn[style*="rgb(0, 105, 42)"] {
      transition: all 0.3s ease;
    }
    
    .btn[style*="rgb(0, 105, 42)"]:hover {
      background-color: rgb(0, 82, 35) !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 105, 42, 0.3);
    }
    
    .btn[style*="rgb(0, 105, 42)"]:active {
      transform: translateY(0);
    }
    
    /* Fix form input focus states to match green theme */
    .form-control:focus,
    .form-check-input:focus {
      border-color: rgb(0, 105, 42) !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    
    .form-check-input:checked {
      background-color: rgb(0, 105, 42) !important;
      border-color: rgb(0, 105, 42) !important;
    }
    
    .form-check-input:focus {
      border-color: rgb(0, 105, 42) !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    .header-bar img {
      width: 65px;
      margin-right: 10px;
    }
    .modal-body {
      font-size: 0.95rem;
    }
    .modal-title{
        text-align: center;
    }
    .modal-header img {
      width: 50px;
      align-items: center;
    }
     input.uppercase {
            text-transform: uppercase;
        }
    .google-btn {
      transition: all 0.3s ease;
    }
    .google-btn:hover {
      background-color: rgb(0, 82, 35) !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 105, 42, 0.3);
    }
    .divider {
      position: relative;
      text-align: center;
      margin: 1rem 0;
    }
    .divider::before,
    .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 45%;
      height: 1px;
      background-color: #ddd;
    }
    .divider::before {
      left: 0;
    }
    .divider::after {
      right: 0;
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
    
    /* Toast Styles */
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
      min-width: 280px;
      max-width: 100%;
      margin: 0 auto;
    }
    
    .toast-success {
      background-color: #00692a;
      border: 1px solid #005223;
      color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 105, 42, 0.3);
    }
    
    .toast-error {
      background-color: #dc3545;
      border: 1px solid #c82333;
      color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    .toast-header {
      font-weight: 600;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .toast-success .toast-header {
      background-color: #00692a;
      color: #ffffff;
      border-bottom: 1px solid #005223;
    }
    
    .toast-error .toast-header {
      background-color: #dc3545;
      color: #ffffff;
      border-bottom: 1px solid #c82333;
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
    
    @media (max-width: 768px) {
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
    
    @media (max-width: 576px) {
      .toast-container {
        left: 50% !important;
        transform: translateX(-50%) !important;
        right: auto !important;
        max-width: calc(100% - 20px);
      }
      .toast {
        min-width: 200px;
        font-size: 0.9rem;
      }
    }
    
    @media (max-width: 400px) {
      .toast-container {
        left: 50% !important;
        transform: translateX(-50%) !important;
        right: auto !important;
        max-width: calc(100% - 10px);
      }
      .toast {
        min-width: 200px;
        font-size: 0.85rem;
      }
    }
    
    /* Hamburger Menu Styles */
    .hamburger-btn {
      background-color: transparent;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      padding: 0.5rem;
      margin-right: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.3s ease;
      z-index: 1000;
    }
    
    .hamburger-btn:hover {
      transform: scale(1.1);
    }
    
    .hamburger-btn:focus {
      outline: 2px solid rgba(255, 255, 255, 0.5);
      outline-offset: 2px;
    }
    
    .left-panel {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease-in-out;
      overflow: hidden;
      position: relative;
      z-index: 1;
    }
    
    .left-panel.collapsed {
      transform: translateX(-100%);
      opacity: 0;
      max-height: 0;
      padding: 0;
      margin: 0;
      overflow: hidden;
    }
    
    @media (max-width: 768px) {
      .container-fluid {
        position: relative;
      }
      
      .row {
        position: relative;
      }
      
      .left-panel {
        position: fixed;
        left: 0;
        width: 100%;
        height: 100vh;
        z-index: 1000;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
        background-color: rgba(232, 245, 233, 0.98);
        transform: translateX(-100%);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        will-change: transform;
        opacity: 1;
      }
      
      .left-panel > * {
        padding-top: 0;
      }
      
      .left-panel:not(.collapsed) {
        transform: translateX(0) !important;
      }
      
      .left-panel.collapsed {
        transform: translateX(-100%) !important;
      }
      
      .right-panel {
        width: 100%;
        position: relative;
        z-index: 1;
      }
      
      /* Overlay when panel is open */
      .panel-overlay {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: 100%;
        height: calc(100% - var(--header-height));
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: opacity;
      }
      
      .panel-overlay.active {
        opacity: 1;
        pointer-events: all;
      }
    }
    
    @media (min-width: 769px) {
      .hamburger-btn {
        display: none;
      }
      .left-panel.collapsed {
        transform: none;
        opacity: 1;
        max-height: none;
        padding: 2rem;
        margin: 0;
        display: block;
      }
    }
    @media (max-width: 992px) {
      .left-panel,
      .right-panel {
        padding: 1.5rem;
        height: calc(100vh - var(--header-height) - 20px);
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      .left-panel::-webkit-scrollbar,
      .right-panel::-webkit-scrollbar {
        display: none;
      }
    }
    
    @media (max-width: 768px) {
      :root {
        --header-height: 70px;
      }
      .overlay {
        padding: 0;
        padding-top: var(--header-height);
        height: 100vh;
        overflow: hidden;
      }
      .header-bar {
        padding: 0.75rem 0.5rem;
        flex-wrap: wrap;
        align-items: flex-start;
      }
      .header-bar img {
        width: 50px;
        margin-right: 8px;
        flex-shrink: 0;
      }
      .header-bar > div {
        flex: 1;
        min-width: 0;
      }
      .header-bar h4 {
        font-size: 1rem;
        line-height: 1.3;
        word-wrap: break-word;
      }
      .header-bar p {
        font-size: 0.75rem;
        line-height: 1.2;
        display: block;
        word-wrap: break-word;
      }
      .container-fluid {
        padding: 0;
        height: calc(100vh - var(--header-height));
        overflow: hidden;
      }
      .row {
        margin: 0;
        height: 100%;
      }
      .left-panel,
      .right-panel {
        padding: 1rem;
        height: calc(100vh - var(--header-height));
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      .left-panel::-webkit-scrollbar,
      .right-panel::-webkit-scrollbar {
        display: none;
      }
      .left-panel {
        margin-bottom: 0;
      }
      .left-panel h5 {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
      }
      .left-panel > p {
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
      }
      .left-panel ul {
        font-size: 0.85rem;
        padding-left: 1.2rem;
        margin-bottom: 0;
      }
      .left-panel li {
        margin-bottom: 0.5rem;
        line-height: 1.4;
      }
      .right-panel {
        padding-top: 0.5rem;
        display: flex;
        align-items: flex-start;
        justify-content: center;
      }
      .register-card {
        margin-top: 0.5rem;
        border-radius: 15px;
        padding: 20px 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      }
      .register-card .card-body {
        padding: 0;
      }
      .card-title {
        font-size: 1.1rem;
      }
      .form-control {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 10px 12px;
      }
      .form-label {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
      }
      .form-check {
        margin-bottom: 0.5rem;
      }
      .form-check-label {
        font-size: 0.9rem;
      }
      .btn {
        width: 100%;
        margin-bottom: 10px;
        padding: 10px 15px;
        font-size: 16px;
      }
      .btn-group .btn {
        width: auto;
      }
      .alert {
        font-size: 0.9rem;
        padding: 0.75rem;
      }
      .google-btn {
        font-size: 15px;
      }
      .google-btn svg {
        width: 16px;
        height: 16px;
      }
    }
    
    @media (max-width: 576px) {
      :root {
        --header-height: 65px;
      }
      .overlay {
        padding-top: var(--header-height);
        height: 100vh;
        overflow: hidden;
      }
      .header-bar {
        padding: 0.6rem 0.5rem;
        flex-wrap: wrap;
      }
      .header-bar img {
        width: 45px;
        margin-right: 8px;
        flex-shrink: 0;
      }
      .header-bar > div {
        flex: 1;
        min-width: 0;
      }
      .header-bar h4 {
        font-size: 0.9rem;
        line-height: 1.2;
        word-wrap: break-word;
      }
      .header-bar p {
        font-size: 0.65rem;
        line-height: 1.1;
        display: block;
        word-wrap: break-word;
        margin-top: 2px;
      }
      .container-fluid {
        height: calc(100vh - var(--header-height));
      }
      .left-panel,
      .right-panel {
        padding: 0.75rem;
        height: calc(100vh - var(--header-height));
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      .left-panel::-webkit-scrollbar,
      .right-panel::-webkit-scrollbar {
        display: none;
      }
      .left-panel h5 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
      }
      .left-panel > p {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
      }
      .left-panel ul {
        font-size: 0.8rem;
        padding-left: 1rem;
      }
      .left-panel li {
        margin-bottom: 0.4rem;
        line-height: 1.3;
      }
      .register-card {
        padding: 18px 12px;
        margin-top: 0.25rem;
      }
      .register-card .card-body {
        padding: 0;
      }
      .card-title {
        font-size: 1rem;
        margin-bottom: 1rem;
      }
      .form-control {
        padding: 10px;
        font-size: 16px;
      }
      .form-label {
        font-size: 0.9rem;
      }
      .form-check-label {
        font-size: 0.85rem;
      }
      .btn {
        padding: 10px 12px;
        font-size: 15px;
      }
      .google-btn {
        font-size: 14px;
        padding: 10px;
      }
      .google-btn svg {
        width: 16px;
        height: 16px;
        margin-right: 8px;
      }
      .alert {
        font-size: 0.85rem;
        padding: 0.6rem;
      }
      .auth-link {
        font-size: 0.9rem;
      }
      .modal-dialog {
        margin: 10px;
      }
    }
    
    @media (max-width: 400px) {
      :root {
        --header-height: 60px;
      }
      .overlay {
        padding-top: var(--header-height);
        height: 100vh;
        overflow: hidden;
      }
      .header-bar {
        padding: 0.5rem;
        flex-wrap: wrap;
      }
      .header-bar img {
        width: 40px;
        margin-right: 6px;
        flex-shrink: 0;
      }
      .header-bar > div {
        flex: 1;
        min-width: 0;
      }
      .header-bar h4 {
        font-size: 0.85rem;
        line-height: 1.2;
        word-wrap: break-word;
      }
      .header-bar p {
        font-size: 0.6rem;
        line-height: 1.1;
        display: block;
        word-wrap: break-word;
        margin-top: 2px;
      }
      .container-fluid {
        height: calc(100vh - var(--header-height));
      }
      .left-panel,
      .right-panel {
        padding: 0.5rem;
        height: calc(100vh - var(--header-height));
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      .left-panel::-webkit-scrollbar,
      .right-panel::-webkit-scrollbar {
        display: none;
      }
      .left-panel h5 {
        font-size: 0.95rem;
      }
      .left-panel ul {
        font-size: 0.75rem;
        padding-left: 0.9rem;
      }
      .register-card {
        padding: 15px 10px;
        margin-top: 0.25rem;
      }
      .register-card .card-body {
        padding: 0;
      }
      .form-control {
        padding: 8px 10px;
      }
      .btn {
        padding: 9px 10px;
        font-size: 14px;
      }
      .google-btn {
        font-size: 13px;
        padding: 9px;
      }
      .google-btn svg {
        width: 14px;
        height: 14px;
        margin-right: 6px;
      }
    }
  </style>
</head>
<body>
<div class="overlay">
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 p-3" id="toastContainer" style="z-index: 1060;"></div>
  
  <!-- Overlay for mobile panel -->
  <div class="panel-overlay" id="panelOverlay"></div>
  <!-- Header -->
  <div class="header-bar d-flex align-items-center">
    <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle guidelines">
      <i class="fas fa-bars" id="hamburgerIcon"></i>
    </button>
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University </h4>
      <p class="mb-0">Academic Program Application and Screening Management System</p>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row g-0">
      <!-- Left Panel -->
      <div class="col-12 col-md-6 left-panel" id="leftPanel">
        <h5>Welcome Applicant!</h5>
        <p>Please make sure you have your personal email ready. Your login credentials will be sent there after registration.</p>
        <ul>
          <li>Applicants must have passed the CHMSU Entrance Examination.</li>
          <li>If you are a shiftee or an old student that is enrolled or was enrolled in CHMSU, <b> DO NOT USE THIS SYSTEM</b>.</li>
          <li>Use only one email address in the application. We prohibit the applicant to use multiple email addresses to create multiple account with the same name. Once traced, only the first entry will be acknowledge and the rest will be disregarded. </li>
          <li>Applicants are prohibited from sharing the same email address to other applicants. Use your own email address in applying.</li>
          <li>Input all your information with honesty and integrity. The data encoded and submitted documents will be subjected to verification and validation.</li>
          <li>Prepare your requirements before proceeding to the full application.</li>
          <li>Only one application per department is allowed.</li>
          <li>Applicants who will violate the aforementioned guidelines will be disqualified from the list of application.</li>
        </ul>
      </div>

      <!-- Right Panel: Registration Form -->
      <div class="col-12 col-md-6 right-panel">
        <div class="card shadow register-card">
          <div class="card-body">
            <h5 class="card-title mb-2">Register</h5>
            
            <?php if (isset($_GET['google_auth']) && isset($_GET['need_status']) && isset($_SESSION['google_user'])): ?>
              <!-- Google OAuth Status Selection -->
              <div class="alert alert-info">
                <strong>Continue with Google:</strong> Please select your application status to complete registration.
              </div>
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="google_status_submit" value="1">
                <div class="mb-3">
                  <label class="form-label">Application Status</label><br>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" value="Transferee" required>
                    <label class="form-check-label">Transferee</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" value="New Applicant - Same Academic Year" required>
                    <label class="form-check-label">New Applicant (same academic year)</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" value="New Applicant - Previous Academic Year" required>
                    <label class="form-check-label">New Applicant (previous academic year)</label>
                  </div>
                </div>
                <button type="submit" class="btn w-100" style="background-color: rgb(0, 105, 42); color: white; border: none;">Continue Registration</button>
              </form>
            <?php else: ?>
              <!-- Regular Registration Form -->
              <form id="registrationForm" method="POST" action="">
                <div class="mb-2">
                  <label for="lastName" class="form-label">Last Name</label>
                  <input type="text" class="form-control uppercase" id="lastName" name="last_name" required>
                </div>
                <div class="mb-2">
                  <label for="firstName" class="form-label">First Name</label>
                  <input type="text" class="form-control uppercase" id="firstName" name="first_name" required>
                </div>

                <div class="mb-2">
                  <label class="form-label">Application Status</label><br>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" value="Transferee" required>
                    <label class="form-check-label">Transferee</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" id="new_same_year" value="New Applicant - Same Academic Year" required>
                    <label class="form-check-label">New Applicant (same academic year)</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="applicant_status" value="New Applicant - Previous Academic Year" required>
                    <label class="form-check-label">New Applicant (previous academic year)</label>
                  </div>
                </div>

                <!-- Google Sign In Button -->
                <div class="mb-3 mt-3">
                  <?php if ($google_oauth_enabled): ?>
                    <button type="submit" name="google_oauth_submit" class="btn w-100 d-flex align-items-center justify-content-center google-btn" style="background-color: rgb(0, 105, 42); color: white; border: none; padding: 10px;">
                      <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
                        <g fill="#000" fill-rule="evenodd">
                          <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                          <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.85l2.84 2.2c2.02-1.86 3.18-4.6 3.18-7.55z" fill="#4285F4"/>
                          <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                          <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.84-2.2c-.76.53-1.78.9-3.12.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                        </g>
                      </svg>
                      Continue with Google
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn w-100 d-flex align-items-center justify-content-center google-btn" style="background-color: rgb(0, 105, 42); color: white; border: none; padding: 10px; opacity: 0.6; cursor: not-allowed;" disabled>
                      <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
                        <g fill="#000" fill-rule="evenodd">
                          <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                          <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.85l2.84 2.2c2.02-1.86 3.18-4.6 3.18-7.55z" fill="#4285F4"/>
                          <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                          <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.84-2.2c-.76.53-1.78.9-3.12.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                        </g>
                      </svg>
                      Continue with Google
                    </button>
                    <?php if (!empty($google_oauth_error)): ?>
                      <small class="text-danger d-block mt-1 text-center"><?php echo htmlspecialchars($google_oauth_error); ?></small>
                    <?php else: ?>
                      <small class="text-muted d-block mt-1 text-center">(Configure Google OAuth credentials to enable)</small>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </form>
            <?php endif; ?>
            
            <div class="mt-2 text-center">
              <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Hamburger Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
      const hamburgerBtn = document.getElementById('hamburgerBtn');
      const leftPanel = document.getElementById('leftPanel');
      const hamburgerIcon = document.getElementById('hamburgerIcon');
      const panelOverlay = document.getElementById('panelOverlay');
      
      function togglePanel() {
        if (window.innerWidth <= 768) {
          const isCollapsed = leftPanel.classList.contains('collapsed');
          
          // Use requestAnimationFrame for smooth animation
          requestAnimationFrame(function() {
            if (isCollapsed) {
              // Opening - remove collapsed class
              leftPanel.classList.remove('collapsed');
              leftPanel.style.transform = 'translateX(0)';
            } else {
              // Closing - add collapsed class
              leftPanel.classList.add('collapsed');
              leftPanel.style.transform = 'translateX(-100%)';
            }
          });
          
          // Toggle overlay
          if (panelOverlay) {
            if (isCollapsed) {
              panelOverlay.classList.add('active');
            } else {
              panelOverlay.classList.remove('active');
            }
          }
          
          // Toggle icon between bars and times
          if (hamburgerIcon) {
            if (isCollapsed) {
              hamburgerIcon.classList.remove('fa-bars');
              hamburgerIcon.classList.add('fa-times');
            } else {
              hamburgerIcon.classList.remove('fa-times');
              hamburgerIcon.classList.add('fa-bars');
            }
          }
        }
      }
      
      if (hamburgerBtn && leftPanel) {
        hamburgerBtn.addEventListener('click', togglePanel);
      }
      
      // Close panel when overlay is clicked
      if (panelOverlay) {
        panelOverlay.addEventListener('click', function() {
          if (!leftPanel.classList.contains('collapsed')) {
            togglePanel();
          }
        });
      }
      
      // Set panel top position based on header height
      function setPanelTop() {
        if (window.innerWidth <= 768 && leftPanel) {
          const headerBar = document.querySelector('.header-bar');
          if (headerBar) {
            const headerHeight = headerBar.offsetHeight;
            // Update CSS custom property for header height
            document.documentElement.style.setProperty('--header-height', headerHeight + 'px');
            // Use CSS custom property to avoid inline style conflicts
            leftPanel.style.setProperty('--header-height', headerHeight + 'px');
            leftPanel.style.top = headerHeight + 'px';
            leftPanel.style.height = 'calc(100vh - ' + headerHeight + 'px)';
            // Ensure transform is not overridden
            if (leftPanel.classList.contains('collapsed')) {
              leftPanel.style.transform = 'translateX(-100%)';
            } else {
              leftPanel.style.transform = 'translateX(0)';
            }
          }
        } else {
          // Reset on desktop
          leftPanel.style.top = '';
          leftPanel.style.height = '';
          leftPanel.style.transform = '';
        }
      }
      
      // Update header height CSS variable on load and resize
      function updateHeaderHeight() {
        const headerBar = document.querySelector('.header-bar');
        if (headerBar) {
          const headerHeight = headerBar.offsetHeight;
          document.documentElement.style.setProperty('--header-height', headerHeight + 'px');
        }
      }
      
      // Initial header height update
      updateHeaderHeight();
      
      // On mobile, hide panel by default
      if (window.innerWidth <= 768 && leftPanel) {
        // Force initial collapsed state
        leftPanel.classList.add('collapsed');
        leftPanel.style.transform = 'translateX(-100%)';
        if (panelOverlay) {
          panelOverlay.classList.remove('active');
        }
        // Use setTimeout to ensure DOM is ready
        setTimeout(function() {
          setPanelTop();
        }, 10);
      } else {
        // Initial setup for desktop
        setPanelTop();
      }
      
      // Handle window resize
      window.addEventListener('resize', function() {
        updateHeaderHeight();
        setPanelTop();
        if (window.innerWidth > 768 && leftPanel) {
          leftPanel.classList.remove('collapsed');
          leftPanel.style.top = '';
          leftPanel.style.height = '';
          if (panelOverlay) {
            panelOverlay.classList.remove('active');
          }
          if (hamburgerIcon) {
            hamburgerIcon.classList.remove('fa-times');
            hamburgerIcon.classList.add('fa-bars');
          }
        } else if (window.innerWidth <= 768 && leftPanel) {
          // Keep panel state on mobile, but reset icon
          if (hamburgerIcon && leftPanel.classList.contains('collapsed')) {
            hamburgerIcon.classList.remove('fa-times');
            hamburgerIcon.classList.add('fa-bars');
          }
        }
      });
    });
    
    // Toast notification functions
    function showSuccessToast(message) {
      const toastContainer = document.getElementById('toastContainer');
      const toastId = 'toast-' + Date.now();
      
      const toastHTML = `
        <div id="${toastId}" class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true">
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
    
    function showErrorToast(message) {
      const toastContainer = document.getElementById('toastContainer');
      const toastId = 'toast-' + Date.now();
      
      const toastHTML = `
        <div id="${toastId}" class="toast toast-error" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="toast-header">
            <i class="fas fa-exclamation-circle text-white me-2"></i>
            <strong class="me-auto">Error</strong>
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
    
    // Show toasts if there are messages
    <?php if (!empty($success_message)): ?>
      showSuccessToast('<?php echo addslashes($success_message); ?>');
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
      showErrorToast('<?php echo addslashes($error_message); ?>');
    <?php endif; ?>
    
    <?php if (empty($success_message) && empty($error_message)): ?>
      const usageModal = new bootstrap.Modal(document.getElementById('usageModal'));
      window.addEventListener('load', () => {
        usageModal.show();
      });
    <?php endif; ?>
  </script> 
</body>
</html>