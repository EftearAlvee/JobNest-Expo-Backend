<?php
header("Content-Type: application/json; charset=UTF-8");


// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data['role']== 'job_seeker' ) {
   include 'db-seeker.php';
}
else if ($data['role']== 'recruiter' ) {
   include 'db-recruiter.php';
}

// Validate input
if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || 
    empty($data['phone']) || empty($data['password']) || empty($data['role']) ) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit();
}



// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    exit();
}

// Validate password strength (optional)
if (strlen($data['password']) < 8) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long'
    ]);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
$stmt->bindParam(':email', $data['email']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email already exists'
    ]);
    exit();
}

// Hash the password
$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

// Set default role
$role = $data['role'] ?? 'job_seeker';

// Insert new user
try {
    $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, phone, password, role) 
                           VALUES (:firstName, :lastName, :email, :phone, :password, :role)");
    
    $stmt->bindParam(':firstName', $data['firstName']);
    $stmt->bindParam(':lastName', $data['lastName']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $role);
    
    $stmt->execute();
    
    $userId = $conn->lastInsertId();
    
    // Get the full user details
    $stmt = $conn->prepare("SELECT id, firstName, lastName, email, phone, role, created_at FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'User registered successfully',
        'user' => $user
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>