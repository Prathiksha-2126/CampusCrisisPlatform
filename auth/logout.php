<?php
/**
 * User Logout Handler
 * Handles session destruction
 */

session_start();
require_once '../config/database.php';

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

try {
    // Destroy session
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    sendJSONResponse([
        'success' => true,
        'message' => 'Logout successful'
    ]);

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred during logout'], 500);
}
?>
