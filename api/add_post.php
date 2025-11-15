<?php
// Enable debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
header('Content-Type: application/json');
session_start();

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

// AUTHENTICATION: Temporarily disabled for demo purposes
// TODO: Enable authentication when user login system is fully implemented
// if (!isset($_SESSION['user_id'])) {
//     sendJSONResponse([
//         'success' => false,
//         'message' => 'Authentication required. Please log in to post in the community forum.'
//     ], 401);
// }

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }

    if (empty($input['user_name']) || empty($input['message'])) {
        sendJSONResponse(['success' => false, 'message' => 'User name and message are required'], 400);
    }

    $user_name = sanitizeInput($input['user_name']);
    $message = sanitizeInput($input['message']);

    // CONTENT FILTERING: Check for inappropriate content
    if (containsBlockedContent($message) || containsBlockedContent($user_name)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Inappropriate content detected. Please revise your post and try again.'
        ], 400);
    }

    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // MODERATION: Insert post with is_approved = 0 (pending admin approval)
    $stmt = $conn->prepare("INSERT INTO forum_posts (user_name, message, is_approved, created_at) VALUES (?, ?, 0, NOW())");
    $result = $stmt->execute([$user_name, $message]);

    if (!$result) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to add post'], 500);
    }

    // SUCCESS: Post submitted for moderation
    sendJSONResponse([
        'success' => true, 
        'message' => 'Post submitted successfully! It will appear in the community after admin approval.'
    ]);
    
} catch (Exception $e) {
    error_log("Add post error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>

