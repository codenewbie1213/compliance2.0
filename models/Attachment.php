<?php
/**
 * Attachment Model
 * 
 * This class handles attachment-related database operations.
 */

require_once __DIR__ . '/Model.php';

class Attachment extends Model {
    protected $table = 'attachments';
    protected $primaryKey = 'attachment_id';
    
    /**
     * Create a new attachment
     * 
     * @param int $actionPlanId The ID of the action plan
     * @param int $userId The ID of the user uploading the attachment
     * @param string $filePath The path to the uploaded file
     * @param string $fileName The original name of the file
     * @return int|false The ID of the new attachment or false on failure
     */
    public function create($actionPlanId, $userId, $filePath, $fileName) {
        $sql = "INSERT INTO {$this->table} (action_plan_id, user_id, file_path, file_name) VALUES (?, ?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'iiss', [$actionPlanId, $userId, $filePath, $fileName]);
        
        if (!$stmt) {
            return false;
        }
        
        $attachmentId = $stmt->insert_id;
        $stmt->close();
        
        return $attachmentId;
    }
    
    /**
     * Find attachments by action plan ID
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return array An array of attachments
     */
    public function findByActionPlanId($actionPlanId) {
        $sql = "SELECT a.*, u.username 
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.user_id
                WHERE a.action_plan_id = ?
                ORDER BY a.uploaded_at DESC";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $attachments = [];
        
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        
        $stmt->close();
        
        return $attachments;
    }
    
    /**
     * Delete an attachment by ID
     * 
     * @param int $attachmentId The ID of the attachment to delete
     * @return array|false The deleted attachment data or false on failure
     */
    public function deleteWithFileInfo($attachmentId) {
        // First, get the attachment data
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$attachmentId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $attachment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$attachment) {
            return false;
        }
        
        // Then delete the record
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$attachmentId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success ? $attachment : false;
    }
    
    /**
     * Delete attachments by action plan ID
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return array An array of deleted attachment data
     */
    public function deleteByActionPlanIdWithFileInfo($actionPlanId) {
        // First, get all attachments for this action plan
        $attachments = $this->findByActionPlanId($actionPlanId);
        
        if (empty($attachments)) {
            return [];
        }
        
        // Then delete the records
        $sql = "DELETE FROM {$this->table} WHERE action_plan_id = ?";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return [];
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success ? $attachments : [];
    }
    
    /**
     * Get an attachment by ID
     * 
     * @param int $attachmentId The ID of the attachment
     * @return array|false The attachment data or false if not found
     */
    public function getById($attachmentId) {
        $sql = "SELECT a.*, u.username 
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.user_id
                WHERE a.{$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$attachmentId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $attachment = $result->fetch_assoc();
        
        $stmt->close();
        
        return $attachment;
    }
} 