<?php
require_once '../config/database.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Get latest 20 approved posts only
    $stmt = $conn->prepare("SELECT post_id, user_name, message, created_at FROM forum_posts WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert DB fields to the ones your JS expects
    $posts = [];
    foreach ($rows as $r) {
        $posts[] = [
            'author' => $r['user_name'],
            'text' => $r['message'],
            'time' => timeAgo($r['created_at']),
            'comments' => []
        ];
    }

    sendJSONResponse(['success' => true, 'posts' => $posts]);
} catch (Exception $e) {
    error_log("Get posts error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

// Helper function to format time into "x min ago"
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' min ago';
    if ($time < 86400) return floor($time / 3600) . ' hr ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
