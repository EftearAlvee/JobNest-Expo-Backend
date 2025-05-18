<?php
header("Content-Type: application/json");
require_once '../db.php';

// Get the role from headers or POST data
$role = isset($_SERVER['HTTP_X_USER_ROLE']) ? $_SERVER['HTTP_X_USER_ROLE'] : 'job_seeker';
$db = new Database($role);
$conn = $db->getConnection();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle complete profile retrieval
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        
        if (!$userId) {
            $response['message'] = 'User ID is required';
            echo json_encode($response);
            exit;
        }

        // Get user basic info
        $stmt = $conn->prepare("SELECT firstName, lastName, email, phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $response['message'] = 'User not found';
            echo json_encode($response);
            exit;
        }

        // Get profile picture
        $stmt = $conn->prepare("SELECT image_url FROM profile_pictures WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profilePicture = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get about me
        $stmt = $conn->prepare("SELECT about_text, title FROM profile_about WHERE user_id = ?");
        $stmt->execute([$userId]);
        $aboutMe = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get skills
        $stmt = $conn->prepare("SELECT name, level FROM skills WHERE user_id = ? ORDER BY level, name");
        $stmt->execute([$userId]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize skills by level
        $skillsByLevel = [
            'Beginner' => [],
            'Intermediate' => [],
            'Expert' => []
        ];

        foreach ($skills as $skill) {
            $skillsByLevel[$skill['level']][] = $skill['name'];
        }

        // Build the complete profile response
        $response['success'] = true;
        $response['profile'] = [
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'profileImage' => $profilePicture ? $profilePicture['image_url'] : null,
            'aboutMe' => $aboutMe ? $aboutMe['about_text'] : '',
            'title' => $aboutMe ? $aboutMe['title'] : '',
            'skills' => $skillsByLevel
        ];
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
$db->closeConnection();
?>