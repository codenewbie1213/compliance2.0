<?php
/**
 * Base Model Class
 * 
 * This class provides common database functionality for all models.
 */

require_once __DIR__ . '/../config/database.php';

class Model {
    protected $conn;
    protected $table;
    
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Prepare and execute a SQL statement with parameters
     * 
     * @param string $sql The SQL statement to prepare
     * @param string $types The types of the parameters (i = integer, s = string, d = double, b = blob)
     * @param array $params The parameters to bind
     * @return mysqli_stmt|false The prepared statement or false on failure
     */
    protected function executeStatement($sql, $types = '', $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Error preparing statement: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Error executing statement: " . $stmt->error);
            return false;
        }
        
        return $stmt;
    }
    
    /**
     * Find a record by ID
     * 
     * @param int $id The ID of the record to find
     * @return array|false The record as an associative array or false if not found
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$id]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        
        $stmt->close();
        
        return $record;
    }
    
    /**
     * Find all records
     * 
     * @return array An array of records as associative arrays
     */
    public function findAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $records = [];
        
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        
        $stmt->close();
        
        return $records;
    }
    
    /**
     * Delete a record by ID
     * 
     * @param int $id The ID of the record to delete
     * @return bool True if the record was deleted, false otherwise
     */
    public function deleteById($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$id]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Close the database connection
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
} 