<?php
/**
 * Add Issue API - Secured with Login + Moderation + Keyword Filtering
 * Handles new issue reports from report.html
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

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 🔒 SECURITY CHECK: Require login
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendJSONResponse([
            'success' => false, 
            'message' => 'Login required. Please log in to report an issue.'
        ], 401);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }

    // Validate required fields
    if (empty($input['category']) || empty($input['location']) || 
        empty($input['description']) || empty($input['contact_info'])) {
        sendJSONResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }

    $category = sanitizeInput($input['category']);
    $location = sanitizeInput($input['location']);
    $description = sanitizeInput($input['description']);
    $contact_info = sanitizeInput($input['contact_info']);
    $severity = isset($input['severity']) ? sanitizeInput($input['severity']) : 'yellow';

    // 🛡️ CONTENT FILTERING: Check for inappropriate content
    $badWordInDescription = containsBlockedContent($description);
    $badWordInLocation = containsBlockedContent($location);
    
    if ($badWordInDescription || $badWordInLocation) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Inappropriate content detected. Please revise and resubmit.'
        ], 400);
    }

    // Validate category
    $validCategories = ['power', 'water', 'medical', 'food', 'transport', 'other'];
    if (!in_array($category, $validCategories)) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid category'], 400);
    }

    // Validate severity
    $validSeverities = ['green', 'yellow', 'red'];
    if (!in_array($severity, $validSeverities)) {
        $severity = 'yellow';
    }

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        sendJSONResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    // Insert new issue (keep existing issues table as is)
    $stmt = $conn->prepare("
        INSERT INTO issues (category, location, description, contact_info, severity, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'Reported', NOW())
    ");
    
    $result = $stmt->execute([$category, $location, $description, $contact_info, $severity]);

    if (!$result) {
        sendJSONResponse(['success' => false, 'message' => 'Failed to add issue'], 500);
    }

    $issueId = $conn->lastInsertId();

    // IMMEDIATE POSTING: Add to alerts table with is_approved = 1 (appears immediately)
    $title = ucfirst($category) . " Issue - " . $location;
    $stmt = $conn->prepare("
        INSERT INTO alerts (title, category, severity, status, location, description, is_approved, created_at) 
        VALUES (?, ?, ?, 'Reported', ?, ?, 1, NOW())
    ");
    $stmt->execute([$title, $category, $severity, $location, $description]);

    // SUCCESS: Issue posted immediately
    sendJSONResponse([
        'success' => true,
        'message' => 'Issue reported successfully! It now appears on the dashboard.',
        'issue_id' => $issueId
    ]);

} catch (Exception $e) {
    error_log("Add issue error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'An error occurred while adding the issue'], 500);
}
?>
