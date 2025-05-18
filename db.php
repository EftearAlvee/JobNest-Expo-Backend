<?php
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;
    private $role;

    public function __construct($role = 'job_seeker') {
        $this->role = $role;
        error_log("Initializing database connection for role: " . $this->role);
        $this->initializeConnection();
    }

    private function initializeConnection() {
        // Configure database credentials based on role
        if ($this->role === 'recruiter') {
            $this->host = '127.0.0.1';
            $this->username = 'root';
            $this->password = '';
            $this->database = 'jobnest_recruiter';
        } else {
            // Default to job seeker database
            $this->host = '127.0.0.1';
            $this->username = 'root';
            $this->password = '';
            $this->database = 'jobnest_seeker';
        }

        error_log("Attempting to connect to: " . $this->database);

        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            error_log("Successfully connected to database: " . $this->database);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        if (!$this->connection) {
            $this->initializeConnection();
        }
        return $this->connection;
    }

    public function closeConnection() {
        $this->connection = null;
        error_log("Database connection closed");
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt->fetchColumn() === 1;
        } catch (PDOException $e) {
            error_log("Connection test failed: " . $e->getMessage());
            return false;
        }
    }
}
?>