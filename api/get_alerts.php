<?php
/**
 * Get Alerts API
 * Fetches all alerts for dashboard display
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    // Fetch only approved alerts (is_approved = 1)
    $stmt = $conn->prepare("
        SELECT alert_id, title, category, severity, status, location, description, created_at 
        FROM alerts 
        WHERE is_approved = 1
        ORDER BY 
            CASE severity 
                WHEN 'red' THEN 1 
                WHEN 'yellow' THEN 2 
                WHEN 'green' THEN 3 
            END,
            created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $alerts = $stmt->fetchAll();

    // Format alerts for frontend
    $formattedAlerts = [];
    foreach ($alerts as $alert) {
        $formattedAlerts[] = [
            'id' => 'alert_' . $alert['alert_id'],
            'title' => $alert['title'],
            'category' => $alert['category'],
            'severity' => $alert['severity'],
            'status' => $alert['status'],
            'location' => $alert['location'],
            'description' => $alert['description'],
            'time' => date('M j, g:i A', strtotime($alert['created_at']))
        ];
    }

    // Get category mapping for icons
    $categories = [
        'power' => ['label' => 'Power', 'icon' => '⚡'],
        'water' => ['label' => 'Water', 'icon' => '💧'],
        'medical' => ['label' => 'Medical', 'icon' => '🏥'],
        'food' => ['label' => 'Food', 'icon' => '🍽️'],
        'transport' => ['label' => 'Transport', 'icon' => '🚌'],
        'other' => ['label' => 'Other', 'icon' => '📌']
    ];

    sendJSONResponse([
        'success' => true,
        'alerts' => $formattedAlerts,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    error_log("Get alerts error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred while fetching alerts'], 500);
}
?>
