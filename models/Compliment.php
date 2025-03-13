<?php
/**
 * Compliment Model
 * 
 * This class handles compliment-related database operations.
 */

require_once __DIR__ . '/Model.php';

class Compliment extends Model {
    protected $table = 'compliments';
    protected $primaryKey = 'compliment_id';
    
    /**
     * Create a new compliment
     * 
     * @param int $fromUserId The ID of the user giving the compliment
     * @param int $aboutUserId The ID of the user receiving the compliment
     * @param string $fromName The name of the person giving the compliment
     * @param string $description The compliment description
     * @return int|false The ID of the new compliment or false on failure
     */
    public function create($fromUserId, $aboutUserId, $fromName, $description) {
        $sql = "INSERT INTO {$this->table} (from_user_id, about_user_id, from_name, description) VALUES (?, ?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'iiss', [$fromUserId, $aboutUserId, $fromName, $description]);
        
        if (!$stmt) {
            return false;
        }
        
        $complimentId = $stmt->insert_id;
        $stmt->close();
        
        return $complimentId;
    }
    
    /**
     * Get all compliments with user details
     * 
     * @return array An array of compliments
     */
    public function getAllWithDetails() {
        $sql = "SELECT c.*, 
                COALESCE(NULLIF(CONCAT(f.first_name, ' ', f.last_name), ' '), f.email) as from_user_name,
                COALESCE(NULLIF(CONCAT(a.first_name, ' ', a.last_name), ' '), a.email) as about_user_name
                FROM {$this->table} c
                JOIN users f ON c.from_user_id = f.user_id
                JOIN users a ON c.about_user_id = a.user_id
                ORDER BY c.created_at DESC";
        
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $compliments = [];
        
        while ($row = $result->fetch_assoc()) {
            $compliments[] = $row;
        }
        
        $stmt->close();
        
        return $compliments;
    }
    
    /**
     * Get compliments about a specific user
     * 
     * @param int $userId The ID of the user
     * @return array An array of compliments
     */
    public function getComplimentsAboutUser($userId) {
        $sql = "SELECT c.*, 
                c.from_name as from_user_name
                FROM {$this->table} c
                JOIN users f ON c.from_user_id = f.user_id
                WHERE c.about_user_id = ?
                ORDER BY c.created_at DESC";
        
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $compliments = [];
        
        while ($row = $result->fetch_assoc()) {
            $compliments[] = $row;
        }
        
        $stmt->close();
        
        return $compliments;
    }
    
    /**
     * Get compliment statistics
     * 
     * @return array Statistics about compliments
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
                COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
                FROM {$this->table}";
        
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [
                'total' => 0,
                'today' => 0,
                'last_7_days' => 0,
                'last_30_days' => 0
            ];
        }
        
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        
        $stmt->close();
        
        return $stats;
    }
} 