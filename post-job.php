<?php
require_once 'db.php';
header("Content-Type: application/json");

$role = $_GET['role'] ?? 'job_seeker';
$db = new Database($role);
$conn = $db->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$requiredFields = ['job_type', 'title', 'company', 'description', 'requirements', 'user_id'];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(["error" => "$field is required"]);
        exit;
    }
}

try {
    $stmt = $conn->prepare("INSERT INTO jobs (
        job_type, title, company, location, salary,
        description, requirements, skills, experience,
        deadline, contact_email, user_id
    ) VALUES (
        :job_type, :title, :company, :location, :salary,
        :description, :requirements, :skills, :experience,
        :deadline, :contact_email, :user_id
    )");

    $stmt->execute([
        ':job_type' => $data['job_type'],
        ':user_id' => $data['user_id'],
        ':title' => $data['title'],
        ':company' => $data['company'],
        ':location' => $data['location'] ?? null,
        ':salary' => $data['salary'] ?? null,
        ':description' => $data['description'],
        ':requirements' => $data['requirements'],
        ':skills' => $data['skills'] ?? null,
        ':experience' => $data['experience'] ?? null,
        ':deadline' => $data['deadline'] ?? null,
        ':contact_email' => $data['contact_email'] ?? null,
    ]);

    echo json_encode(["message" => "Job posted successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to post job: " . $e->getMessage()]);
}
