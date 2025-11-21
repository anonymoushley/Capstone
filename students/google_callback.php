<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/functions.php';
$google_oauth_config = require __DIR__ . '/../config/google_oauth.php';

// Check if Google Client class is available
if (!class_exists('Google\Client')) {
    $_SESSION['error_message'] = "Google OAuth library is not installed. Please run 'composer install' to install dependencies.";
    header('Location: register.php');
    exit();
}

use Google\Client;
use Google\Service\Oauth2;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';
$success_message = '';

try {
    // Initialize Google Client
    $client = new Client();
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
    
    // Use the dynamically generated URI or fall back to config
    $finalRedirectUri = $redirectUri;
    $client->setRedirectUri($finalRedirectUri);
    $client->addScope($google_oauth_config['scopes']);
    
    // Handle the callback
    if (isset($_GET['code'])) {
        
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            $errorDetails = $token['error'];
            if (isset($token['error_description'])) {
                $errorDetails .= ': ' . $token['error_description'];
            }
            error_log("Google OAuth Token Error: " . $errorDetails);
            throw new Exception('Failed to get access token: ' . $errorDetails);
        }
        
        $client->setAccessToken($token);
        
        // Get user info
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        
        $email = $userInfo->getEmail();
        $firstName = $userInfo->getGivenName();
        $lastName = $userInfo->getFamilyName();
        $googleId = $userInfo->getId();
        
        // Check if user already exists
        $check_sql = "SELECT id, first_name, last_name FROM registration WHERE email_address = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists - check if coming from registration or login
            $user = $result->fetch_assoc();
            
            // If coming from registration form (has applicant_status in session), show error
            if (isset($_SESSION['applicant_status']) || isset($_SESSION['form_first_name']) || isset($_SESSION['form_last_name'])) {
                // Clear registration session data
                unset($_SESSION['applicant_status']);
                unset($_SESSION['form_first_name']);
                unset($_SESSION['form_last_name']);
                unset($_SESSION['google_user']);
                
                $_SESSION['error_message'] = "Email address already registered! Please use a different email or login with your existing account.";
                header('Location: register.php');
                exit();
            }
            
            // Otherwise, log them in (normal login flow)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = 'student';
            
            // Redirect to dashboard
            header('Location: applicant_dashboard.php');
            exit();
        } else {
            // New user - check if applicant_status is set in session (from registration form)
            if (!isset($_SESSION['applicant_status']) || empty($_SESSION['applicant_status'])) {
                // Store Google user info in session and redirect to status selection
                $_SESSION['google_user'] = [
                    'email' => $email,
                    'first_name' => $firstName ?: '',
                    'last_name' => $lastName ?: '',
                    'google_id' => $googleId
                ];
                header('Location: register.php?google_auth=1&need_status=1');
                exit();
            }
            
            // Create new account
            $applicant_status = $_SESSION['applicant_status'];
            
            // Use form data from session if available, otherwise use Google data
            $finalFirstName = isset($_SESSION['form_first_name']) && !empty($_SESSION['form_first_name']) 
                ? $_SESSION['form_first_name'] 
                : $firstName;
            $finalLastName = isset($_SESSION['form_last_name']) && !empty($_SESSION['form_last_name']) 
                ? $_SESSION['form_last_name'] 
                : $lastName;
            
            // Generate temporary password (6 characters like regular registration)
            $temp_password = generateTempPassword();
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            // Insert new record
            $sql = "INSERT INTO registration (email_address, password, first_name, last_name, applicant_status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashed_password, $finalFirstName, $finalLastName, $applicant_status);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = ($finalFirstName ?: '') . ' ' . ($finalLastName ?: '');
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
                    $mail->addAddress($email, "$finalFirstName $finalLastName");

                    $mail->isHTML(false);
                    $mail->Subject = "Your CHMSU Application Account Credentials";
                    $mail->Body = "Dear $finalFirstName $finalLastName,\n\n"
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
                
                // Clear form data and applicant_status from session
                unset($_SESSION['applicant_status']);
                unset($_SESSION['form_first_name']);
                unset($_SESSION['form_last_name']);
                
                $_SESSION['success_message'] = "Registration successful! Welcome to CHMSU Application System.";
                header('Location: applicant_dashboard.php');
                exit();
            } else {
                throw new Exception('Failed to create account. Please try again.');
            }
        }
    } else {
        throw new Exception('Authorization code not provided.');
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Google authentication failed: " . $e->getMessage();
    header('Location: register.php');
    exit();
}
?>

