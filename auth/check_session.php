<?php
/**
 * Session Check Handler
 * Checks if user is logged in and returns user info
 */

session_start();
require_once '../config/database.php';

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Not logged in',
            'logged_in' => false
        ]);
    }

    // Return user session data
    sendJSONResponse([
        'success' => true,
        'message' => 'User is logged in',
        'logged_in' => true,
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ]
    ]);

} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred'], 500);
}
?>
