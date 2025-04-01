<?php
declare(strict_types=1);

namespace App\Models;

/**
 * AuditAttachment Model
 * Handles file attachments for audits
 */
class AuditAttachment extends Model
{
    protected string $table = 'audit_attachments';
    protected string $primaryKey = 'attachment_id';

    /**
     * Get attachments for a specific audit
     * 
     * @param int $auditId Audit ID
     * @return array|null Array of attachments or null if none found
     */
    public function getAttachmentsByAuditId(int $auditId): ?array
    {
        $sql = "SELECT a.*, u.username as uploaded_by_name 
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.user_id
                WHERE a.audit_id = ? 
                ORDER BY a.created_at DESC";
        
        return $this->fetchAll($sql, [$auditId]);
    }

    /**
     * Create a new attachment
     * 
     * @param array $data Attachment data
     * @return int|bool New attachment ID or false on failure
     */
    public function createAttachment(array $data): int|bool
    {
        // Required fields
        $requiredFields = ['audit_id', 'user_id', 'file_name', 'file_path', 'file_type', 'file_size'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return $this->create($data);
    }

    /**
     * Delete an attachment
     * 
     * @param int $attachmentId Attachment ID
     * @return bool Success or failure
     */
    public function deleteAttachment(int $attachmentId): bool
    {
        // Get attachment details first
        $attachment = $this->getAttachmentById($attachmentId);
        
        if (!$attachment) {
            return false;
        }
        
        // Delete the file
        $filePath = __DIR__ . '/../../uploads/audits/' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete the database record
        return $this->delete($attachmentId);
    }

    /**
     * Get attachment by ID
     * 
     * @param int $attachmentId Attachment ID
     * @return array|null Attachment details or null if not found
     */
    public function getAttachmentById(int $attachmentId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->fetchOne($sql, [$attachmentId]);
    }

    /**
     * Execute a query and return all matching records
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return array|null Array of records or null if none found
     */
    protected function fetchAll(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Error in fetchAll: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute a query and return a single record
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return array|null Record or null if not found
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Error in fetchOne: " . $e->getMessage());
            return null;
        }
    }
} 