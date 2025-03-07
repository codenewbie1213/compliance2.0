<?php
/**
 * Complaint Model
 * 
 * This class handles complaint-related database operations.
 */

require_once __DIR__ . '/Model.php';

class Complaint extends Model {
    protected $table = 'complaints';
    protected $primaryKey = 'complaint_id';
    
    /**
     * Create a new complaint
     * 
     * @param int $fromUserId The ID of the user submitting the complaint
     * @param string $fromName The name of the person submitting the complaint
     * @param string $description The complaint description
     * @return int|false The ID of the new complaint or false on failure
     */
    public function create($fromUserId, $fromName, $description) {
        $sql = "INSERT INTO {$this->table} (from_user_id, from_name, description) VALUES (?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'iss', [$fromUserId, $fromName, $description]);
        
        if (!$stmt) {
            return false;
        }
        
        $complaintId = $stmt->insert_id;
        $stmt->close();
        
        return $complaintId;
    }
    
    /**
     * Add an action plan to a complaint
     * 
     * @param int $complaintId The ID of the complaint
     * @param int $actionPlanId The ID of the action plan
     * @return bool True if added successfully, false otherwise
     */
    public function addActionPlan($complaintId, $actionPlanId) {
        $sql = "INSERT INTO complaint_action_plans (complaint_id, action_plan_id) VALUES (?, ?)";
        $stmt = $this->executeStatement($sql, 'ii', [$complaintId, $actionPlanId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }

    /**
     * Update complaint status
     * 
     * @param int $complaintId The ID of the complaint
     * @param string $status The new status
     * @return bool True if updated successfully, false otherwise
     */
    public function updateStatus($complaintId, $status) {
        $sql = "UPDATE {$this->table} SET status = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'si', [$status, $complaintId]);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get all complaints with user details and action plans
     * 
     * @return array An array of complaints with user details
     */
    public function getAllWithDetails() {
        $sql = "SELECT c.*, 
                c.from_name as from_user_name,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        ap.name, 
                        ' (', 
                        ap.status,
                        ')'
                    ) SEPARATOR ', '
                ) as action_plan_names,
                GROUP_CONCAT(DISTINCT ap.action_plan_id) as action_plan_ids
                FROM {$this->table} c
                LEFT JOIN complaint_action_plans cap ON c.complaint_id = cap.complaint_id
                LEFT JOIN action_plans ap ON cap.action_plan_id = ap.action_plan_id
                GROUP BY c.complaint_id
                ORDER BY c.created_at DESC";
        
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $complaints = [];
        
        while ($row = $result->fetch_assoc()) {
            $complaints[] = $row;
        }
        
        $stmt->close();
        
        return $complaints;
    }
    
    /**
     * Get complaint details by ID
     * 
     * @param int $complaintId The ID of the complaint
     * @return array|false The complaint details or false if not found
     */
    public function getDetails($complaintId) {
        $sql = "SELECT c.*, 
                c.from_name as from_user_name,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        ap.name, 
                        ' (', 
                        ap.status,
                        ')'
                    ) SEPARATOR ', '
                ) as action_plan_names,
                GROUP_CONCAT(DISTINCT ap.action_plan_id) as action_plan_ids
                FROM {$this->table} c
                LEFT JOIN complaint_action_plans cap ON c.complaint_id = cap.complaint_id
                LEFT JOIN action_plans ap ON cap.action_plan_id = ap.action_plan_id
                WHERE c.{$this->primaryKey} = ?
                GROUP BY c.complaint_id";
        
        $stmt = $this->executeStatement($sql, 'i', [$complaintId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        
        $stmt->close();
        
        return $complaint;
    }

    /**
     * Get complaint statistics
     * 
     * @return array Statistics about complaints
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'Resolved' THEN 1 END) as resolved,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
                COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
                FROM {$this->table}";
        
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'resolved' => 0,
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