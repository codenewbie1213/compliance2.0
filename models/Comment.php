<?php
/**
 * Comment Model
 * 
 * This class handles comment-related database operations.
 */

require_once __DIR__ . '/Model.php';

class Comment extends Model {
    protected $table = 'comments';
    protected $primaryKey = 'comment_id';
    
    /**
     * Create a new comment
     * 
     * @param int $actionPlanId The ID of the action plan
     * @param int $userId The ID of the user creating the comment
     * @param string $commentText The text of the comment
     * @return int|false The ID of the new comment or false on failure
     */
    public function create($actionPlanId, $userId, $commentText) {
        $sql = "INSERT INTO {$this->table} (action_plan_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'iis', [$actionPlanId, $userId, $commentText]);
        
        if (!$stmt) {
            return false;
        }
        
        $commentId = $stmt->insert_id;
        $stmt->close();
        
        return $commentId;
    }
    
    /**
     * Find comments by action plan ID
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return array An array of comments
     */
    public function findByActionPlanId($actionPlanId) {
        $sql = "SELECT c.*, 
                COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.email) as full_name,
                u.email, u.is_management_staff,
                c.content as comment_text
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.action_plan_id = ?
                ORDER BY c.created_at DESC";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $comments = [];
        
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        $stmt->close();
        
        return $comments;
    }
    
    /**
     * Update a comment
     * 
     * @param int $commentId The ID of the comment to update
     * @param string $commentText The new text of the comment
     * @return bool True if the comment was updated, false otherwise
     */
    public function update($commentId, $commentText) {
        $sql = "UPDATE {$this->table} SET content = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'si', [$commentText, $commentId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Delete comments by action plan ID
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return bool True if the comments were deleted, false otherwise
     */
    public function deleteByActionPlanId($actionPlanId) {
        $sql = "DELETE FROM {$this->table} WHERE action_plan_id = ?";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get the latest comment for an action plan
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return array|false The latest comment or false if none found
     */
    public function getLatestByActionPlanId($actionPlanId) {
        $sql = "SELECT c.*, 
                COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.email) as full_name,
                u.email, u.is_management_staff 
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.action_plan_id = ?
                ORDER BY c.created_at DESC
                LIMIT 1";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        
        $stmt->close();
        
        return $comment;
    }
} 