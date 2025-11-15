<?php
/**
 * User Login Handler
 * Handles user authentication and session creation
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }

    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        sendJSONResponse(['success' => false, 'message' => 'Email and password are required'], 400);
    }

    $email = sanitizeInput($input['email']);
    $password = $input['password'];

    // Validate email format
    if (!validateEmail($email)) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }

    // Create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // Return success response
    sendJSONResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred during login'], 500);
}
?>
