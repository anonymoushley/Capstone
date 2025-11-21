<?php
/**
 * Security Configuration and Helper Functions
 * Provides centralized security functions for the application
 */

/**
 * Initialize secure session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate and sanitize input
 * @param mixed $data Input data
 * @param string $type Type of validation (email, int, string, etc.)
 * @param int $max_length Maximum length
 * @return mixed Sanitized data or false on failure
 */
function validateInput($data, $type = 'string', $max_length = 255) {
    if ($data === null || $data === '') {
        return false;
    }
    
    $data = trim($data);
    
    if (strlen($data) > $max_length) {
        return false;
    }
    
    switch ($type) {
        case 'email':
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            return filter_var($data, FILTER_VALIDATE_EMAIL) !== false ? $data : false;
            
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) !== false ? (int)$data : false;
            
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) !== false ? (float)$data : false;
            
        case 'url':
            $data = filter_var($data, FILTER_SANITIZE_URL);
            return filter_var($data, FILTER_VALIDATE_URL) !== false ? $data : false;
            
        case 'string':
        default:
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Escape output for HTML
 * @param string $data Data to escape
 * @return string Escaped data
 */
function escapeOutput($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is authenticated
 * @param string $required_role Required role (optional)
 * @return bool True if authenticated
 */
function isAuthenticated($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if ($required_role !== null) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
    }
    
    return true;
}

/**
 * Require authentication or redirect
 * @param string $required_role Required role (optional)
 * @param string $redirect_url Redirect URL if not authenticated
 */
function requireAuth($required_role = null, $redirect_url = 'login.php') {
    if (!isAuthenticated($required_role)) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Rate limiting - check if too many requests
 * @param string $key Unique key for rate limiting
 * @param int $max_requests Maximum requests allowed
 * @param int $time_window Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit($key, $max_requests = 5, $time_window = 60) {
    $rate_limit_key = "rate_limit_$key";
    $current_time = time();
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = [
            'count' => 1,
            'reset_time' => $current_time + $time_window
        ];
        return true;
    }
    
    $rate_data = $_SESSION[$rate_limit_key];
    
    // Reset if time window expired
    if ($current_time > $rate_data['reset_time']) {
        $_SESSION[$rate_limit_key] = [
            'count' => 1,
            'reset_time' => $current_time + $time_window
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_data['count'] >= $max_requests) {
        return false;
    }
    
    // Increment count
    $_SESSION[$rate_limit_key]['count']++;
    return true;
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Only set CSP if not already set
    if (!headers_sent()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
    }
}

/**
 * Log security events
 * @param string $event Event description
 * @param array $details Additional details
 */
function logSecurityEvent($event, $details = []) {
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    $log_entry = "[$timestamp] [$ip] [User: $user_id] $event";
    if (!empty($details)) {
        $log_entry .= " | Details: " . json_encode($details);
    }
    $log_entry .= "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Sanitize file name
 * @param string $filename File name to sanitize
 * @return string Sanitized file name
 */
function sanitizeFileName($filename) {
    // Remove any path components
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Limit length
    $filename = substr($filename, 0, 255);
    return $filename;
}

/**
 * Validate file upload
 * @param array $file $_FILES array element
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['valid' => bool, 'error' => string]
 */
function validateFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'], $max_size = 5242880) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

