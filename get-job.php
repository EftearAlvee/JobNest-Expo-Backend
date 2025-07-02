<?php
require_once 'db.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Get role from query string, default to 'job_seeker'
$role = $_GET['role'] ?? 'job_seeker';

// Initialize DB with role
$db = new Database($role);
$conn = $db->getConnection();

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
$userId = $data['user_id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

try {
    // Fetch active jobs (not expired)
    $stmt = $conn->prepare("
        SELECT j.*, COUNT(a.id) AS applicants
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.user_id = ?
          AND (j.deadline IS NULL OR j.deadline >= CURDATE())
        GROUP BY j.id
        ORDER BY j.created_at DESC LIMIT 3 
    ");
    $stmt->execute([$userId]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get job stats
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT j.id) AS totalPosts,
            COUNT(a.id) AS totalApplications,
            SUM(a.status = 'shortlisted') AS shortlisted,
            SUM(a.status = 'interview') AS interviews
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Send response
    echo json_encode([
        'jobs' => $jobs,
        'totalPosts' => $stats['totalPosts'] ?? 0,
        'totalApplications' => $stats['totalApplications'] ?? 0,
        'shortlisted' => $stats['shortlisted'] ?? 0,
        'interviews' => $stats['interviews'] ?? 0,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}