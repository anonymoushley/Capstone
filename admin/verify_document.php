<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PHPMailer and DB setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';
require_once '../config/database.php';

// Correct: use consistent naming
$personal_info_id = $_POST['personal_info_id'] ?? null;
$field = $_POST['field'] ?? null;
$action = $_POST['action'] ?? null;

// Validate required inputs
if (!$personal_info_id || !$field || !$action) {
    die('Missing required information.');
}

// Update document status immediately
$stmt = $pdo->prepare("UPDATE documents SET {$field}_status = ? WHERE personal_info_id = ?");
$stmt->execute([$action, $personal_info_id]);

// Fetch data for response and email
$query = $pdo->prepare("
    SELECT r.id AS registration_id, r.email_address, pi.first_name, pi.last_name 
    FROM registration r 
    JOIN personal_info pi ON r.personal_info_id = pi.id 
    WHERE pi.id = ?
    LIMIT 1
");
$query->execute([$personal_info_id]);
$info = $query->fetch(PDO::FETCH_ASSOC);

// Prepare email data (will be sent after response)
$emailData = null;
if ($info && !empty($info['email_address'])) {
    $emailData = [
        'email' => $info['email_address'],
        'full_name' => trim($info['first_name'] . ' ' . $info['last_name']),
        'action' => $action,
        'field' => $field
    ];
}

// Return JSON response immediately - no email blocking
$docLabels = [
    'g11_1st' => 'G11 1st Sem Report Card',
    'g11_2nd' => 'G11 2nd Sem Report Card',
    'g12_1st' => 'G12 1st Sem Report Card',
    'ncii' => 'NC II Certificate',
    'guidance_cert' => 'Certification from Guidance Office',
    'additional_file' => 'Additional File'
];
$docLabel = $docLabels[$field] ?? ucfirst($field);

$response = [
    'success' => true,
    'message' => "Document '{$docLabel}' has been {$action} successfully.",
    'action' => $action,
    'document' => $docLabel,
    'registration_id' => $info['registration_id'] ?? null
];

// Prepare response
$responseJson = json_encode($response);

// Send response immediately and close connection
header('Content-Type: application/json');
header('Connection: close');
header('Content-Length: ' . strlen($responseJson));

// Disable output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Send response
echo $responseJson;

// Flush all output buffers
if (ob_get_level()) {
    ob_end_flush();
}
flush();

// Close the connection to client
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    // For non-FastCGI (like XAMPP), manually close connection
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
    // Set to ignore user abort and continue processing
    ignore_user_abort(true);
    set_time_limit(0);
    
    // Close session if open
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

// Send email in background (connection already closed, user won't wait)
if ($emailData) {
    $mail = null;
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acregalado.chmsu@gmail.com';
        $mail->Password = 'vvekpeviojyyysfq';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->SMTPKeepAlive = false;

        $mail->setFrom('acregalado.chmsu@gmail.com', 'CHMSU Admissions');
        $mail->addAddress($emailData['email'], $emailData['full_name']);

        $statusMessage = $emailData['action'] === 'Accepted' ? 'accepted' : 'rejected';
        $docLabel = $docLabels[$emailData['field']] ?? ucfirst($emailData['field']);

        $mail->isHTML(true);
        $mail->Subject = "CHMSU Document Verification Update";
        $mail->Body = "
            <p>Dear <strong>{$emailData['full_name']}</strong>,</p>
            <p>Your document <strong>{$docLabel}</strong> has been <strong>{$statusMessage}</strong> by the admissions office.</p>
            <p>Thank you,<br>CHMSU Admissions Team</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        $errorMsg = ($mail && isset($mail->ErrorInfo)) ? $mail->ErrorInfo : $e->getMessage();
        error_log("Email could not be sent. Mailer Error: " . $errorMsg);
    }
}

exit;
