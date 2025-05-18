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
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['user_id']) || !isset($data['about_text']) || !isset($data['title'])) {
            throw new Exception('Missing required fields');
        }

        $userId = $data['user_id'];
        $aboutText = $data['about_text'];
        $title = $data['title'];

        // Begin transaction for atomic operation
        $conn->beginTransaction();

        // Check if user exists (important!)
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            throw new Exception('User does not exist');
        }

        // Check/update about section
        $stmt = $conn->prepare("SELECT id FROM profile_about WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE profile_about SET about_text = ?, title = ? WHERE user_id = ?");
            $stmt->execute([$aboutText, $title, $userId]);
            $response['message'] = 'About section updated';
        } else {
            $stmt = $conn->prepare("INSERT INTO profile_about (user_id, about_text, title) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $aboutText, $title]);
            $response['message'] = 'About section created';
        }

        // Verify row was affected
        if ($stmt->rowCount() === 0) {
            throw new Exception('No rows affected - insert/update failed');
        }

        $conn->commit();
        $response['success'] = true;
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ... existing GET code ...
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log($response['message']); // Log the error
} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = $e->getMessage();
    error_log($response['message']); // Log the error
}

echo json_encode($response);
$db->closeConnection();
?>