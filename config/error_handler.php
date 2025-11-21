<?php
/**
 * Centralized Error Handling
 * Provides consistent error handling across the application
 */

/**
 * Handle errors gracefully without exposing sensitive information
 * @param string $message User-friendly error message
 * @param string $log_message Detailed log message
 * @param int $http_code HTTP status code
 * @param bool $redirect Whether to redirect or output JSON
 * @param string $redirect_url Redirect URL if redirect is true
 */
function handleError($message, $log_message = '', $http_code = 500, $redirect = false, $redirect_url = '') {
    // Log detailed error for debugging (not exposed to user)
    if (!empty($log_message)) {
        error_log("Error: " . $log_message);
    }
    
    http_response_code($http_code);
    
    if ($redirect && !empty($redirect_url)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_message'] = $message;
        header("Location: $redirect_url");
        exit();
    }
    
    // For AJAX requests, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
    
    // For regular requests, show error page
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <h1 class='error'>Error</h1>
        <p>" . htmlspecialchars($message) . "</p>
        <a href='javascript:history.back()'>Go Back</a>
    </body>
    </html>";
    exit();
}

/**
 * Get database connection with proper error handling
 * @return mysqli Database connection
 * @throws Exception If connection fails
 */
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        // Use environment variables if available, otherwise use defaults
        $host = getenv('DB_HOST') ?: 'localhost';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';
        $dbname = getenv('DB_NAME') ?: 'admission';
        
        $conn = new mysqli($host, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Safe database query execution
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param string $types Parameter types
 * @param array $params Parameters
 * @return mysqli_result|bool Query result
 * @throws Exception If query fails
 */
function safeQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Query execution failed: " . $error);
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

