<?php
/**
 * Database Configuration for Campus Crisis Platform
 * Handles PDO connection to MySQL database
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'campus_crisis';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            
            // Set PDO attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Helper function to get database connection
 * @return PDO|null
 */
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Helper function to send JSON response
 * @param array $data
 * @param int $status_code
 */
function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    echo json_encode($data);
    exit;
}

/**
 * Helper function to sanitize input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Helper to detect blocked/inappropriate content
 * Centralized so multiple endpoints (issues, posts, etc.) can use the same list
 *
 * @param string|null $text
 * @return string|false Returns the blocked word if found, otherwise false
 */
function containsBlockedContent($text) {
    $blockedWords = [
        'abuse', 'idiot', 'stupid', 'fake report', 'prank', 'sexual', 'harass', 'kill',
        'bomb', 'terror', 'xxx', 'nsfw', 'hate', 'racist', 'violence', 'threat',
        'spam', 'scam', 'fraud', 'illegal', 'drugs', 'weapon', 'suicide'
    ];

    $text = mb_strtolower($text ?? '');

    foreach ($blockedWords as $word) {
        if (mb_strpos($text, $word) !== false) {
            return $word;
        }
    }

    return false;
}

/**
 * Helper function to validate email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>
