<?php
// Approve or reject forum posts (admin only)
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

// ADMIN ONLY: Temporarily disabled for demo purposes
// TODO: Enable admin authentication when user system is fully implemented
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     sendJSONResponse([
//         'success' => false,
//         'message' => 'Admin access required'
//     ], 403);
// }

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['post_id']) || !isset($input['approve'])) {
        sendJSONResponse(['success' => false, 'message' => 'post_id and approve parameters required'], 400);
    }

    $postId = (int)$input['post_id'];
    $approve = (bool)$input['approve'];

    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    if ($approve) {
        // APPROVE: Set is_approved = 1
        $stmt = $conn->prepare("UPDATE forum_posts SET is_approved = 1 WHERE post_id = ? AND is_approved = 0");
        $result = $stmt->execute([$postId]);
        
        if ($result && $stmt->rowCount() > 0) {
            sendJSONResponse([
                'success' => true,
                'message' => 'Forum post approved successfully! It now appears in the community.'
            ]);
        } else {
            sendJSONResponse(['success' => false, 'message' => 'Post not found or already processed'], 404);
        }
    } else {
        // REJECT: Delete the post
        $stmt = $conn->prepare("DELETE FROM forum_posts WHERE post_id = ? AND is_approved = 0");
        $result = $stmt->execute([$postId]);
        
        if ($result && $stmt->rowCount() > 0) {
            sendJSONResponse([
                'success' => true,
                'message' => 'Forum post rejected and removed successfully.'
            ]);
        } else {
            sendJSONResponse(['success' => false, 'message' => 'Post not found or already processed'], 404);
        }
    }

} catch (Exception $e) {
    error_log("Approve post error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error'], 500);
}
?>
