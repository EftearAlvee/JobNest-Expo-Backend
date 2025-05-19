<?php
require_once 'db.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$role = $_GET['role'] ?? 'job_seeker';
$userId = $_GET['user_id'] ?? null;

$db = new Database($role);
$conn = $db->getConnection();

switch ($method) {
    case 'POST':
        if ($path === '/post-job') {
            postJob($conn);
        } else {
            sendResponse(404, ["error" => "Invalid endpoint"]);
        }
        break;

    case 'GET':
        if ($path === '/jobs') {
            if ($userId) {
                getJobsByUser($conn, $userId);
            } else {
                getAllJobs($conn);
            }
        } else {
            sendResponse(404, ["error" => "Invalid endpoint"]);
        }
        break;

    default:
        sendResponse(405, ["error" => "Method Not Allowed"]);
}

function postJob($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    $requiredFields = ['job_type', 'title', 'company', 'description', 'requirements', 'user_id'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(400, ["error" => "$field is required"]);
            return;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO jobs (
            job_type, title, company, location, salary,
            description, requirements, skills, experience,
            deadline, contact_email, user_id
        ) VALUES (
            :job_type, :title, :company, :location, :salary,
            :description, :requirements, :skills, :experience,
            :deadline, :contact_email, :user_id
        )
    ");

    try {
        $stmt->execute([
            ':job_type' => $data['job_type'],
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
            ':user_id' => $data['user_id']
        ]);

        sendResponse(201, ["message" => "Job posted successfully"]);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Failed to post job: " . $e->getMessage()]);
    }
}

function getAllJobs($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");
        $jobs = $stmt->fetchAll();
        sendResponse(200, $jobs);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Failed to fetch jobs: " . $e->getMessage()]);
    }
}

function getJobsByUser($conn, $userId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM jobs WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        $jobs = $stmt->fetchAll();
        sendResponse(200, $jobs);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Failed to fetch user's jobs: " . $e->getMessage()]);
    }
}

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>
