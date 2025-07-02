<?php
header("Content-Type: application/json");
require_once 'db.php';

// Check if database connection is established
if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$job_id = $data['job_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!$job_id || !$user_id) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Check if application already exists
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$job_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'You have already applied for this job']);
        exit;
    }
    
    // Insert new application
    $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, application_date, status) VALUES (?, ?, NOW(), 'pending')");
    $stmt->execute([$job_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>