<?php
require_once 'db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Get role from query string, default to 'job_seeker'
$role = $_GET['role'] ?? 'job_seeker';

// Initialize DB with role
$db = new Database($role);
$conn = $db->getConnection();

try {
    // Fetch all jobs
    $stmt = $conn->prepare("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();

    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send response
    echo json_encode([
        'jobs' => $jobs,
    ]); 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
