<?php
/**
 * User Model
 * 
 * This class handles user-related database operations.
 */

require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    
    /**
     * Create a new user
     * 
     * @param string $username The username
     * @param string $password The password (will be hashed)
     * @param string $email The email address
     * @param bool $isManagementStaff Whether the user is management staff
     * @return int|false The ID of the new user or false on failure
     */
    public function create($username, $password, $email, $isManagementStaff = false) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO {$this->table} (username, password, email, is_management_staff) VALUES (?, ?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'sssi', [$username, $hashedPassword, $email, $isManagementStaff ? 1 : 0]);
        
        if (!$stmt) {
            return false;
        }
        
        $userId = $stmt->insert_id;
        $stmt->close();
        
        return $userId;
    }
    
    /**
     * Find a user by username
     * 
     * @param string $username The username to search for
     * @return array|false The user as an associative array or false if not found
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->executeStatement($sql, 's', [$username]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email The email to search for
     * @return array|false The user as an associative array or false if not found
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->executeStatement($sql, 's', [$email]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Verify a user's password
     * 
     * @param string $password The password to verify
     * @param string $hashedPassword The hashed password to compare against
     * @return bool True if the password is correct, false otherwise
     */
    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
    
    /**
     * Get all management staff users
     * 
     * @return array An array of management staff users
     */
    public function getAllManagementStaff() {
        $sql = "SELECT * FROM {$this->table} WHERE is_management_staff = 1";
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        
        return $users;
    }
    
    /**
     * Update a user's information
     * 
     * @param int $userId The ID of the user to update
     * @param array $data The data to update (keys: email, is_management_staff)
     * @return bool True if the user was updated, false otherwise
     */
    public function update($userId, $data) {
        $updates = [];
        $types = '';
        $params = [];
        
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $types .= 's';
            $params[] = $data['email'];
        }
        
        if (isset($data['is_management_staff'])) {
            $updates[] = "is_management_staff = ?";
            $types .= 'i';
            $params[] = $data['is_management_staff'] ? 1 : 0;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE {$this->primaryKey} = ?";
        $types .= 'i';
        $params[] = $userId;
        
        $stmt = $this->executeStatement($sql, $types, $params);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Update a user's password
     * 
     * @param int $userId The ID of the user to update
     * @param string $newPassword The new password (will be hashed)
     * @return bool True if the password was updated, false otherwise
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET password = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'si', [$hashedPassword, $userId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
} 