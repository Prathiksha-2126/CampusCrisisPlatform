<?php
/**
 * Update Resource API
 * Handles resource updates from admin.html with admin authentication
 */

session_start();
require_once '../config/database.php';

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Only POST method allowed'], 405);
}

/**
 * Check admin authentication
 * Supports both session-based and header-based authentication
 */
function isAdmin() {
    // Check session-based authentication
    if (isset($_SESSION['ccp_admin_ok']) && $_SESSION['ccp_admin_ok'] === '1') {
        return true;
    }
    
    // Check header-based authentication (X-Admin-Token)
    $headers = getallheaders();
    if (isset($headers['X-Admin-Token'])) {
        // In a real application, you would validate this token against a secure store
        // For this demo, we'll use a simple token check
        // You can set this in your config or environment variables
        $validToken = 'admin123_token'; // Change this to a secure token
        return $headers['X-Admin-Token'] === $validToken;
    }
    
    return false;
}

try {
    // Check admin authentication
    if (!isAdmin()) {
        sendJSONResponse(['success' => false, 'message' => 'Unauthorized - Admin access required'], 401);
    }

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Parse input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['resource_id'])) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid input - resource_id required'], 400);
    }

    $resource_id = (int)$input['resource_id'];

    // Check if resource exists
    $stmt = $conn->prepare("SELECT resource_id FROM resources WHERE resource_id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch();
    
    if (!$resource) {
        sendJSONResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    // Build dynamic update query for partial updates
    $updateFields = [];
    $updateValues = [];

    // Check each possible field and add to update if provided
    if (isset($input['status'])) {
        $updateFields[] = "status = ?";
        $updateValues[] = sanitizeInput($input['status']);
    }

    if (isset($input['quantity'])) {
        $updateFields[] = "quantity = ?";
        $updateValues[] = is_numeric($input['quantity']) ? (int)$input['quantity'] : null;
    }

    if (isset($input['unit'])) {
        $updateFields[] = "unit = ?";
        $updateValues[] = sanitizeInput($input['unit']);
    }

    if (isset($input['is_available'])) {
        $updateFields[] = "is_available = ?";
        $updateValues[] = $input['is_available'] ? 1 : 0;
    }

    if (isset($input['notes'])) {
        $updateFields[] = "notes = ?";
        $updateValues[] = sanitizeInput($input['notes']);
    }

    if (isset($input['updated_by'])) {
        $updateFields[] = "updated_by = ?";
        $updateValues[] = sanitizeInput($input['updated_by']);
    }

    // Always update the timestamp
    $updateFields[] = "last_updated = CURRENT_TIMESTAMP";

    if (empty($updateFields)) {
        sendJSONResponse(['success' => false, 'message' => 'No valid fields provided for update'], 400);
    }

    // Add resource_id to the end for WHERE clause
    $updateValues[] = $resource_id;

    // Execute update
    $sql = "UPDATE resources SET " . implode(', ', $updateFields) . " WHERE resource_id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($updateValues);

    if (!$result) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to update resource'], 500);
    }

    if ($stmt->rowCount() > 0) {
        sendJSONResponse([
            'success' => true, 
            'message' => 'Resource updated successfully',
            'resource_id' => $resource_id
        ]);
    } else {
        sendJSONResponse(['success' => false, 'message' => 'No changes made'], 400);
    }

} catch (Exception $e) {
    error_log("Update resource error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Server error occurred'], 500);
}
?>
