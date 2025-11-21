<?php
/**
 * Common utility functions used across the application
 * 
 * This file contains reusable functions that are used throughout the system
 * to reduce code duplication and improve maintainability.
 * 
 * @package Config
 * @version 1.0
 */

/**
 * Calculate plus factor based on strand and NCII status
 * @param string $strand The applicant's strand
 * @param string $ncii_status The NCII certificate status
 * @return int The plus factor value (0, 1, 2, 3, or 5)
 */
function calculatePlusFactor($strand, $ncii_status) {
    $strand = strtolower(trim($strand ?? ''));
    $ncii_status = strtolower(trim($ncii_status ?? ''));
    
    // Check if applicant has NCII certificate (status is 'Accepted')
    $has_ncii = ($ncii_status === 'accepted');
    
    // Check if applicant is from STEM or specific TVL strands (TVL-ICT, TVL-CSS, TVL-PROGRAMMING)
    $is_stem_it = in_array($strand, ['stem', 'tvl-ict', 'tvl-css', 'tvl-programming', 'stem/it']);
    
    // Apply plus factor logic
    if ($is_stem_it && $has_ncii) {
        return 5; // STEM/IT strand + NCII = 5
    } elseif ($is_stem_it && !$has_ncii) {
        return 3; // STEM/IT strand only = 3
    } elseif (!$is_stem_it && $has_ncii) {
        return 2; // NCII only = 2
    } else {
        return 0; // None = 0
    }
}

/**
 * Sanitize input data
 * @param mixed $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Get database connection (mysqli)
 * @return mysqli The database connection
 * @throws Exception If connection fails
 */
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'admission');
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Check if email already exists in registration table
 * @param mysqli $conn Database connection
 * @param string $email Email address to check
 * @return bool True if email exists, false otherwise
 */
function emailExists($conn, $email) {
    $check_sql = "SELECT id FROM registration WHERE email_address = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result->num_rows > 0;
    $check_stmt->close();
    return $exists;
}

/**
 * Get personal_info_id from registration table
 * @param mysqli $conn Database connection
 * @param int $user_id Registration ID
 * @return int|null Personal info ID or null if not found
 */
function getPersonalInfoIdFromRegistration($conn, $user_id) {
    $stmt = $conn->prepare("SELECT personal_info_id FROM registration WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($personal_info_id);
    $stmt->fetch();
    $stmt->close();
    return $personal_info_id ?: null;
}

/**
 * Generate a random temporary password
 * @param int $length Password length (default: 6)
 * @return string Generated password
 */
function generateTempPassword($length = 6) {
    return substr(str_shuffle('abcdefghijkmnopqrstuvwxyz0123456789'), 0, $length);
}

