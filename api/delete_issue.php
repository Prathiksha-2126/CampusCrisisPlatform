<?php
/**
 * Delete Issue API
 * Allows admin to delete issues from admin.html
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
    // Skip authentication for demo purposes
    // In production, you would check admin authentication here

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }

    // Validate required fields
    if (empty($input['issue_id'])) {
        sendJSONResponse(['success' => false, 'message' => 'Issue ID is required'], 400);
    }

    $issue_id = (int)$input['issue_id'];

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Check if issue exists
    $stmt = $conn->prepare("SELECT issue_id, category, location FROM issues WHERE issue_id = ?");
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch();
    
    if (!$issue) {
        sendJSONResponse(['success' => false, 'message' => 'Issue not found'], 404);
    }

    // Delete the issue
    $stmt = $conn->prepare("DELETE FROM issues WHERE issue_id = ?");
    $result = $stmt->execute([$issue_id]);

    if (!$result) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to delete issue'], 500);
    }

    // Also delete corresponding alert if exists
    $stmt = $conn->prepare("
        DELETE FROM alerts 
        WHERE title LIKE CONCAT('%', ?, ' Issue - ', ?, '%')
    ");
    $stmt->execute([$issue['category'], $issue['location']]);

    sendJSONResponse([
        'success' => true,
        'message' => 'Issue deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete issue error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred while deleting the issue'], 500);
}
?>
