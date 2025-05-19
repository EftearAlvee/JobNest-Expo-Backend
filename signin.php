<?php
// filepath: signin.php
header('Content-Type: application/json');

// Add proper CORS headers if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';
// Get role from query string, default to 'job_seeker'
$role = $_GET['role'] ?? 'job_seeker';

// Initialize DB with role
$db = new Database($role);
$conn = $db->getConnection();

// Function to send consistent error responses
function sendError($message, $code = 400) {
    http_response_code($code);
    die(json_encode(['success' => false, 'message' => $message]));
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Get raw JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON data');
    }
    
    // Validate input
    if (empty($data['email']) || empty($data['password'])) {
        sendError('Email and password are required');
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    // Check if user exists (PDO version)
    $stmt = $conn->prepare("SELECT id, firstName, lastName, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        sendError('Invalid email or password', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        sendError('Invalid email or password', 401);
    }
    
    // // Check if user is a job seeker
    // if ($user['role'] !== 'job_seeker') {
    //     sendError('Access denied. Job seeker account required', 403);
    // }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'User  successfully',
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    sendError('Server error', 500);
}
?>