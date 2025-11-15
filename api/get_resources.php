<?php
/**
 * Get Resources API
 * Fetches all resources for dashboard display
 */

// Enable error reporting for debugging (remove in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Ensure limit is within reasonable bounds
    if ($limit < 1) $limit = 50;
    if ($limit > 100) $limit = 100;

    // Fetch all resources (using limit directly in query to avoid prepared statement issues)
    $stmt = $conn->prepare("
        SELECT resource_id, name, category, status, quantity, unit, is_available, notes, last_updated, updated_by
        FROM resources 
        ORDER BY 
            CASE is_available 
                WHEN 1 THEN 1 
                WHEN 0 THEN 2 
            END,
            category ASC,
            name ASC
        LIMIT " . $limit
    );
    $stmt->execute();
    $resources = $stmt->fetchAll();

    // Format resources for frontend
    $formattedResources = [];
    foreach ($resources as $resource) {
        $formattedResources[] = [
            'resource_id' => $resource['resource_id'],
            'name' => $resource['name'],
            'category' => $resource['category'],
            'status' => $resource['status'],
            'quantity' => $resource['quantity'],
            'unit' => $resource['unit'],
            'is_available' => (bool)$resource['is_available'],
            'notes' => $resource['notes'],
            'last_updated' => $resource['last_updated'] ? date('M j, g:i A', strtotime($resource['last_updated'])) : 'Never',
            'updated_by' => $resource['updated_by']
        ];
    }

    sendJSONResponse([
        'success' => true,
        'resources' => $formattedResources
    ]);

} catch (Exception $e) {
    error_log("Get resources error: " . $e->getMessage());
    // For debugging, include the actual error message
    sendJSONResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
