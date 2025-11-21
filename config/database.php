<?php
/**
 * Database Configuration
 * 
 * Establishes PDO connection to the database with proper error handling
 * 
 * @package Config
 * @var PDO $pdo Global PDO database connection instance
 */

$host = 'localhost';
$dbname = 'admission';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error instead of exposing to user
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed. Please contact the administrator.");
}
?> 