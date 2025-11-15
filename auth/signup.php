<?php
/**
 * User Registration Handler
 * Handles user registration and inserts new users
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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }

    // Validate required fields
    if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
        sendJSONResponse(['success' => false, 'message' => 'Name, email, and password are required'], 400);
    }

    $name = sanitizeInput($input['name']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $role = isset($input['role']) ? sanitizeInput($input['role']) : 'student';

    // Validate email format
    if (!validateEmail($email)) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    // Validate password length
    if (strlen($password) < 6) {
        sendJSONResponse(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
    }

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        sendJSONResponse(['success' => false, 'message' => 'Email already registered'], 409);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $hashedPassword, $role]);

    // Confirm insertion worked
    $userId = $conn->lastInsertId();
    if (!$userId) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to insert new user'], 500);
    }

    // Create session
    $_SESSION['user_id'] = $userId;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;

    sendJSONResponse([
        'success' => true,
        'message' => 'Registration successful',
        'redirect' => '../dashboard.html',
        'user' => [
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]
    ], 200);

} catch (PDOException $e) {
    // Database-specific error
    sendJSONResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    // General error
    sendJSONResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
?>

