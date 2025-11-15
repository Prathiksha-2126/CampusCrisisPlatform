<?php
/**
 * Admin Login API
 * Sets server-side session when admin authenticates
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Only POST method allowed'], 405);
}

try {
    // Parse input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['password'])) {
        sendJSONResponse(['success' => false, 'message' => 'Password required'], 400);
    }

    $password = sanitizeInput($input['password']);

    // Check admin password (same as existing system)
    if ($password === 'admin123') {
        $_SESSION['ccp_admin_ok'] = '1';
        sendJSONResponse([
            'success' => true, 
            'message' => 'Admin authenticated successfully'
        ]);
    } else {
        sendJSONResponse(['success' => false, 'message' => 'Invalid password'], 401);
    }

} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error occurred'], 500);
}
?>
