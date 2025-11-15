<?php
// List pending forum posts for admin moderation
require_once '../config/database.php';
header('Content-Type: application/json');
session_start();

// ADMIN ONLY: Temporarily disabled for demo purposes
// TODO: Enable admin authentication when user system is fully implemented
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     sendJSONResponse([
//         'success' => false,
//         'message' => 'Admin access required'
//     ], 403);
// }

try {
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Get all pending forum posts (is_approved = 0)
    $stmt = $conn->prepare("
        SELECT post_id, user_name, message, created_at 
        FROM forum_posts 
        WHERE is_approved = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format posts for admin review
    $pendingPosts = [];
    foreach ($rows as $row) {
        $pendingPosts[] = [
            'post_id' => $row['post_id'],
            'user_name' => $row['user_name'],
            'message' => $row['message'],
            'created_at' => date('M j, Y g:i A', strtotime($row['created_at']))
        ];
    }

    sendJSONResponse([
        'success' => true,
        'posts' => $pendingPosts,
        'count' => count($pendingPosts)
    ]);

} catch (Exception $e) {
    error_log("List pending posts error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error'], 500);
}
?>
