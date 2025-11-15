<?php
/**
 * Session Check API
 * Returns current user session information
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
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendJSONResponse([
            'logged_in' => false
        ]);
    } else {
        // User is logged in, return user info
        sendJSONResponse([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['name'] ?? 'User',
                'email' => $_SESSION['email'] ?? '',
                'role' => $_SESSION['role'] ?? 'student'
            ]
        ]);
    }

} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendJSONResponse(['logged_in' => false, 'error' => 'Session check failed'], 500);
}
?>
