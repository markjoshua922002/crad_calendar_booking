<?php
/**
 * Database Connection Class
 */
class Database {
    private $host;
    private $dbName;
    private $username;
    private $password;
    private $conn;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbName = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbName);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set character set
            $this->conn->set_charset("utf8");
            
            logMessage("Database connection established");
        } catch (Exception $e) {
            logMessage("Database connection error: " . $e->getMessage(), "ERROR");
            die("Database connection failed: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * Execute a query with parameters
     */
    public function executeQuery($sql, $params = [], $types = "") {
        try {
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $this->conn->error);
            }
            
            // Bind parameters if provided
            if (!empty($params) && !empty($types)) {
                $bindParams = array_merge([$types], $params);
                call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
            }
            
            // Execute the statement
            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            logMessage("Query execution error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Helper function to create references for bind_param
     */
    private function refValues($arr) {
        $refs = [];
        
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        
        return $refs;
    }
    
    /**
     * Fetch all rows from a result set
     */
    public function fetchAll($result) {
        $rows = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    /**
     * Fetch a single row from a result set
     */
    public function fetchOne($result) {
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Close the database connection
     */
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
            logMessage("Database connection closed");
        }
    }
} 