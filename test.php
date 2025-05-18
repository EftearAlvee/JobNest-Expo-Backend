<?php
require_once 'db.php';

header("Content-Type: text/plain");

function testDatabase($role) {
    echo "Testing $role database:\n";
    
    try {
        $db = new Database($role);
        $conn = $db->getConnection();
        
        // Test connection
        echo "Connection test: ";
        try {
            $conn->query("SELECT 1");
            echo "SUCCESS\n";
        } catch (PDOException $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
        
        // Check if table exists
        echo "Table check: ";
        try {
            $tableExists = $conn->query("SHOW TABLES LIKE 'profile_about'")->rowCount() > 0;
            echo $tableExists ? "EXISTS\n" : "MISSING\n";
            
            if ($tableExists) {
                // Try to insert test data
                $testUserId = 8;
                echo "Insert test: ";
                try {
                    $stmt = $conn->prepare("INSERT INTO profile_about (user_id, about_text, title) VALUES (?, ?, ?)");
                    $insertResult = $stmt->execute([$testUserId, "Test about text", "Test Title"]);
                    echo $insertResult ? "SUCCESS\n" : "FAILED\n";
                    
                    // Clean up
                    $conn->exec("DELETE FROM profile_about WHERE user_id = '$testUserId'");
                } catch (PDOException $e) {
                    echo "FAILED: " . $e->getMessage() . "\n";
                }
            }
        } catch (PDOException $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
        
        $db->closeConnection();
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo str_repeat("-", 40) . "\n";
}

testDatabase('job_seeker');
testDatabase('recruiter');

echo "Testing complete.\n";
?>