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
        // Handle profile picture upload
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['user_id']) || !isset($data['image_url'])) {
            $response['message'] = 'Missing required fields';
            echo json_encode($response);
            exit;
        }

        $userId = $data['user_id'];
        $imageUrl = $data['image_url'];

        // Check if user already has a profile picture
        $stmt = $conn->prepare("SELECT id FROM profile_pictures WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing picture
            $stmt = $conn->prepare("UPDATE profile_pictures SET image_url = ? WHERE user_id = ?");
            $stmt->execute([$imageUrl, $userId]);
        } else {
            // Insert new picture
            $stmt = $conn->prepare("INSERT INTO profile_pictures (user_id, image_url) VALUES (?, ?)");
            $stmt->execute([$userId, $imageUrl]);
        }

        $response['success'] = true;
        $response['image_url'] = $imageUrl;
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle profile picture retrieval
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        
        if (!$userId) {
            $response['message'] = 'User ID is required';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("SELECT image_url FROM profile_pictures WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        if ($result) {
            $response['success'] = true;
            $response['image_url'] = $result['image_url'];
        } else {
            $response['message'] = 'Profile picture not found';
        }
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
$db->closeConnection();
?>