<?php
// Enable error visibility
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Get Issues API
 * Fetches all active issues for dashboard.html
 */
require_once '../config/database.php';




// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

try {
    // Get database connection
    $conn = getDBConnection();
    

    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Get filter parameters
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    // Build query dynamically
    $query = "SELECT issue_id, category, location, description, contact_info, status, severity, image_path, created_at 
              FROM issues WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
    }

    if (!empty($category)) {
        $query .= " AND category = ?";
        $params[] = $category;
    }

    // Add order and limit (LIMIT cannot be bound in PDO)
    $query .= " ORDER BY created_at DESC LIMIT " . $limit;

    $stmt = $conn->prepare($query);
    

    $stmt->execute($params);
    
    $issues = $stmt->fetchAll();

    // Format issues for frontend
    $formattedIssues = [];
    foreach ($issues as $issue) {
        $formattedIssues[] = [
            'id' => 'issue_' . $issue['issue_id'],
            'title' => ucfirst($issue['category']) . ' Issue - ' . $issue['location'],
            'category' => $issue['category'],
            'location' => $issue['location'],
            'description' => $issue['description'],
            'contact' => $issue['contact_info'],
            'status' => $issue['status'],
            'severity' => $issue['severity'],
            'image_path' => $issue['image_path'],
            'time' => date('M j, g:i A', strtotime($issue['created_at']))
        ];
    }

    // Get summary statistics
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN severity = 'red' THEN 1 ELSE 0 END) as urgent,
        SUM(CASE WHEN status != 'Resolved' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Resolved' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as resolved_today
        FROM issues";

    $stmt = $conn->prepare($statsQuery);
    $stmt->execute();
    $stats = $stmt->fetch();

    sendJSONResponse([
        'success' => true,
        'issues' => $formattedIssues,
        'stats' => [
            'total' => (int)$stats['total'],
            'urgent' => (int)$stats['urgent'],
            'active' => (int)$stats['active'],
            'resolved_today' => (int)$stats['resolved_today']
        ]
    ]);

} catch (Exception $e) {
    error_log("Get issues error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred while fetching issues'], 500);
}
?>

