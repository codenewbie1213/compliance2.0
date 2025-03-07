<?php
/**
 * Base Model Class
 * 
 * This class provides common database functionality for all models.
 */

require_once __DIR__ . '/../config/database.php';

abstract class Model {
    protected $conn;
    protected $table;
    protected $primaryKey;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Execute a prepared statement
     * 
     * @param string $sql The SQL query
     * @param string|null $types The types of parameters (e.g., 'ssi' for string, string, integer)
     * @param array|null $params The parameters to bind
     * @return mysqli_stmt|false The statement object or false on failure
     */
    protected function executeStatement($sql, $types = null, $params = null) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Error preparing statement: " . $this->conn->error);
            return false;
        }
        
        if ($params !== null) {
            if (!$stmt->bind_param($types, ...$params)) {
                error_log("Error binding parameters: " . $stmt->error);
                $stmt->close();
                return false;
            }
        }
        
        if (!$stmt->execute()) {
            error_log("Error executing statement: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        return $stmt;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return int|string The last inserted ID
     */
    protected function getLastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Get the number of affected rows from the last query
     * 
     * @return int The number of affected rows
     */
    protected function getAffectedRows() {
        return $this->conn->affected_rows;
    }
    
    /**
     * Escape a string for use in a query
     * 
     * @param string $string The string to escape
     * @return string The escaped string
     */
    protected function escapeString($string) {
        return $this->conn->real_escape_string($string);
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
     * We don't want to close the connection in the destructor anymore
     * as it's a shared connection
     */
    public function __destruct() {
        // Connection will be closed when the script ends
    }
} 