<?php
header("Content-Type: application/json");
require_once '../db.php';

// Get the role from headers or POST data
$role = isset($_SERVER['HTTP_X_USER_ROLE']) ? $_SERVER['HTTP_X_USER_ROLE'] : 'job_seeker';
$db = new Database($role);
$conn = $db->getConnection();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle skills addition
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['user_id']) || !isset($data['skills'])) {
            $response['message'] = 'Missing required fields';
            echo json_encode($response);
            exit;
        }

        $userId = $data['user_id'];
        $skills = $data['skills'];

        // Prepare the insert statement
        $stmt = $conn->prepare("INSERT INTO skills (user_id, name, level) VALUES (?, ?, ?)");
        
        // Insert each skill
        foreach ($skills as $skill) {
            $stmt->execute([
                $userId,
                $skill['name'],
                $skill['level']
            ]);
        }

        $response['success'] = true;
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle skills retrieval
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        
        if (!$userId) {
            $response['message'] = 'User ID is required';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, name, level FROM skills WHERE user_id = ? ORDER BY level, name");
        $stmt->execute([$userId]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['skills'] = $skills;
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Handle skill deletion
        $data = json_decode(file_get_contents('php://input'), true);
        $skillId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$skillId || !isset($data['user_id'])) {
            $response['message'] = 'Skill ID and User ID are required';
            echo json_encode($response);
            exit;
        }

        $userId = $data['user_id'];

        // Verify the skill belongs to the user before deleting
        $stmt = $conn->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
        $stmt->execute([$skillId, $userId]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Skill not found or not owned by user';
        }
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
$db->closeConnection();
?>