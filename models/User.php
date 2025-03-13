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
     * @param array $data User data (email, password, first_name, last_name, is_management_staff)
     * @return int|false The ID of the new user or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (email, password, first_name, last_name, is_management_staff) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->executeStatement(
            $sql, 
            'ssssi',
            [
                $data['email'],
                $data['password'],
                $data['first_name'],
                $data['last_name'],
                $data['is_management_staff']
            ]
        );
        
        if (!$stmt) {
            return false;
        }
        
        $userId = $stmt->insert_id;
        $stmt->close();
        
        return $userId;
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email The email to search for
     * @return array|false The user data or false if not found
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
     * Find a user by ID
     * 
     * @param int $userId The user ID to search for
     * @return array|false The user data or false if not found
     */
    public function findById($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Get all users
     * 
     * @return array Array of users
     */
    public function findAll() {
        $sql = "SELECT user_id, email, first_name, last_name, is_management_staff 
                FROM {$this->table} 
                ORDER BY first_name, last_name";
        
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
     * Update a user's details
     * 
     * @param int $userId The ID of the user to update
     * @param array $data The data to update (keys: email, first_name, last_name, is_management_staff)
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
        
        if (isset($data['first_name'])) {
            $updates[] = "first_name = ?";
            $types .= 's';
            $params[] = $data['first_name'];
        }
        
        if (isset($data['last_name'])) {
            $updates[] = "last_name = ?";
            $types .= 's';
            $params[] = $data['last_name'];
        }
        
        if (isset($data['is_management_staff'])) {
            $updates[] = "is_management_staff = ?";
            $types .= 'i';
            $params[] = $data['is_management_staff'];
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
     * @param int $userId The ID of the user
     * @param string $hashedPassword The new hashed password
     * @return bool True if the password was updated, false otherwise
     */
    public function updatePassword($userId, $hashedPassword) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'si', [$hashedPassword, $userId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Check if a user exists by ID
     * 
     * @param int $userId The user ID to check
     * @return bool True if the user exists, false otherwise
     */
    public function exists($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $stmt->close();
        
        return $row['count'] > 0;
    }
} 