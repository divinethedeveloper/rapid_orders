<?php
// backend/db.php
// Database configuration and connection handler

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in output

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: 'rapidorders');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONNECTION CLASS
// ============================================
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Create MySQLi connection
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset(DB_CHARSET);
            
        } catch (Exception $e) {
            // Log error silently
            error_log("Database Connection Error: " . $e->getMessage());
            $this->connection = null;
        }
    }
    
    // Singleton pattern - get instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Get connection object
    public function getConnection() {
        if ($this->connection === null) {
            throw new Exception("Database connection not available");
        }
        return $this->connection;
    }
    
    // Check if connection is valid
    public function isConnected() {
        return $this->connection !== null && $this->connection->ping();
    }
    
    // Prepare statement with error handling
    public function prepare($sql) {
        if (!$this->isConnected()) {
            throw new Exception("No database connection");
        }
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->connection->error);
            throw new Exception("Database query preparation failed");
        }
        return $stmt;
    }
    
    // Execute query and return result
    public function query($sql) {
        if (!$this->isConnected()) {
            throw new Exception("No database connection");
        }
        $result = $this->connection->query($sql);
        if (!$result) {
            error_log("Query failed: " . $this->connection->error);
            throw new Exception("Database query failed");
        }
        return $result;
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    // Begin transaction
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        $this->connection->rollback();
    }
    
    // Close connection
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// ============================================
// GLOBAL FUNCTIONS
// ============================================

// Global function to get DB connection (for backward compatibility)
function getDB() {
    $db = Database::getInstance();
    return $db->getConnection();
}

// Check if database is available
function isDatabaseAvailable() {
    $db = Database::getInstance();
    return $db->isConnected();
}
?>