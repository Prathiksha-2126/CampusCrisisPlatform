<?php
/**
 * Update Issue Status API
 * Handles status updates from admin.html for issues table
 */

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
    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Parse input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['issue_id']) || empty($input['status'])) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid input - issue_id and status required'], 400);
    }

    $issue_id = (int)$input['issue_id'];
    $status = sanitizeInput($input['status']);

    // Validate status
    $validStatuses = ['Reported', 'Investigating', 'In Progress', 'Resolved', 'Delayed'];
    if (!in_array($status, $validStatuses)) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid status'], 400);
    }

    // Determine severity based on status
    $severity = 'yellow'; // default
    switch ($status) {
        case 'Reported':
            $severity = 'yellow';
            break;
        case 'Investigating':
            $severity = 'red'; // High priority - being investigated
            break;
        case 'In Progress':
            $severity = 'red'; // High priority - actively being worked on
            break;
        case 'Resolved':
            $severity = 'green'; // Low priority - issue is resolved
            break;
        case 'Delayed':
            $severity = 'yellow'; // Medium priority - delayed but not critical
            break;
    }

    // Check if issue exists
    $stmt = $conn->prepare("SELECT issue_id, category, location FROM issues WHERE issue_id = ?");
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch();
    
    if (!$issue) {
        sendJSONResponse(['success' => false, 'message' => 'Issue not found'], 404);
    }

    // Update both status and severity in issues table
    $stmt = $conn->prepare("UPDATE issues SET status = ?, severity = ?, updated_at = CURRENT_TIMESTAMP WHERE issue_id = ?");
    $result = $stmt->execute([$status, $severity, $issue_id]);

    if (!$result) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to update issue status'], 500);
    }

    if ($stmt->rowCount() > 0) {
        // Also update corresponding alert if exists
        $alertTitle = ucfirst($issue['category']) . ' Issue - ' . $issue['location'];
        $stmt = $conn->prepare("UPDATE alerts SET status = ? WHERE title = ?");
        $stmt->execute([$status, $alertTitle]);

        sendJSONResponse([
            'success' => true, 
            'message' => 'Issue status updated successfully',
            'issue_id' => $issue_id,
            'new_status' => $status
        ]);
    } else {
        sendJSONResponse(['success' => false, 'message' => 'No changes made'], 400);
    }

} catch (Exception $e) {
    error_log("Update issue status error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error occurred'], 500);
}
?>
