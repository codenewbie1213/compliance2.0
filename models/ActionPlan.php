<?php
/**
 * ActionPlan Model
 * 
 * This class handles action plan-related database operations.
 */

require_once __DIR__ . '/Model.php';

class ActionPlan extends Model {
    protected $table = 'action_plans';
    protected $primaryKey = 'action_plan_id';
    
    /**
     * Create a new action plan
     * 
     * @param string $name The name of the action plan
     * @param string $description The description of the action plan
     * @param int $creatorId The ID of the user creating the action plan
     * @param int $assigneeId The ID of the user assigned to the action plan
     * @param string|null $dueDate The due date of the action plan (YYYY-MM-DD format)
     * @return int|false The ID of the new action plan or false on failure
     */
    public function create($name, $description, $creatorId, $assigneeId, $dueDate = null) {
        $sql = "INSERT INTO {$this->table} (name, description, creator_id, assignee_id, due_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->executeStatement($sql, 'ssiis', [$name, $description, $creatorId, $assigneeId, $dueDate]);
        
        if (!$stmt) {
            return false;
        }
        
        $actionPlanId = $stmt->insert_id;
        $stmt->close();
        
        return $actionPlanId;
    }
    
    /**
     * Update an action plan
     * 
     * @param int $actionPlanId The ID of the action plan to update
     * @param array $data The data to update (keys: name, description, assignee_id, due_date, status)
     * @return bool True if the action plan was updated, false otherwise
     */
    public function update($actionPlanId, $data) {
        $updates = [];
        $types = '';
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $types .= 's';
            $params[] = $data['name'];
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $types .= 's';
            $params[] = $data['description'];
        }
        
        if (isset($data['assignee_id'])) {
            $updates[] = "assignee_id = ?";
            $types .= 'i';
            $params[] = $data['assignee_id'];
        }
        
        if (isset($data['due_date'])) {
            $updates[] = "due_date = ?";
            $types .= 's';
            $params[] = $data['due_date'];
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $types .= 's';
            $params[] = $data['status'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE {$this->primaryKey} = ?";
        $types .= 'i';
        $params[] = $actionPlanId;
        
        $stmt = $this->executeStatement($sql, $types, $params);
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Find action plans by creator ID
     * 
     * @param int $creatorId The ID of the creator
     * @return array An array of action plans
     */
    public function findByCreatorId($creatorId) {
        $sql = "SELECT ap.*, u.username as assignee_name 
                FROM {$this->table} ap
                JOIN users u ON ap.assignee_id = u.user_id
                WHERE ap.creator_id = ?
                ORDER BY ap.created_at DESC";
        $stmt = $this->executeStatement($sql, 'i', [$creatorId]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Find action plans by assignee ID
     * 
     * @param int $assigneeId The ID of the assignee
     * @return array An array of action plans
     */
    public function findByAssigneeId($assigneeId) {
        $sql = "SELECT ap.*, u.username as creator_name 
                FROM {$this->table} ap
                JOIN users u ON ap.creator_id = u.user_id
                WHERE ap.assignee_id = ?
                ORDER BY ap.created_at DESC";
        $stmt = $this->executeStatement($sql, 'i', [$assigneeId]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Find action plans by status
     * 
     * @param string $status The status to search for (Pending, In Progress, Completed)
     * @return array An array of action plans
     */
    public function findByStatus($status) {
        $sql = "SELECT ap.*, u1.username as creator_name, u2.username as assignee_name 
                FROM {$this->table} ap
                JOIN users u1 ON ap.creator_id = u1.user_id
                JOIN users u2 ON ap.assignee_id = u2.user_id
                WHERE ap.status = ?
                ORDER BY ap.created_at DESC";
        $stmt = $this->executeStatement($sql, 's', [$status]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Find overdue action plans
     * 
     * @return array An array of overdue action plans
     */
    public function findOverdue() {
        $sql = "SELECT ap.*, u1.username as creator_name, u2.username as assignee_name 
                FROM {$this->table} ap
                JOIN users u1 ON ap.creator_id = u1.user_id
                JOIN users u2 ON ap.assignee_id = u2.user_id
                WHERE ap.due_date < CURDATE() AND ap.status != 'Completed'
                ORDER BY ap.due_date ASC";
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Find action plans due soon (within the next X days)
     * 
     * @param int $days The number of days to look ahead
     * @return array An array of action plans due soon
     */
    public function findDueSoon($days = 1) {
        $sql = "SELECT ap.*, u1.username as creator_name, u2.username as assignee_name 
                FROM {$this->table} ap
                JOIN users u1 ON ap.creator_id = u1.user_id
                JOIN users u2 ON ap.assignee_id = u2.user_id
                WHERE ap.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND ap.status != 'Completed'
                ORDER BY ap.due_date ASC";
        $stmt = $this->executeStatement($sql, 'i', [$days]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Search for action plans by name
     * 
     * @param string $searchTerm The search term
     * @return array An array of matching action plans
     */
    public function searchByName($searchTerm) {
        $searchTerm = "%{$searchTerm}%";
        
        $sql = "SELECT ap.*, u1.username as creator_name, u2.username as assignee_name 
                FROM {$this->table} ap
                JOIN users u1 ON ap.creator_id = u1.user_id
                JOIN users u2 ON ap.assignee_id = u2.user_id
                WHERE ap.name LIKE ?
                ORDER BY ap.created_at DESC";
        $stmt = $this->executeStatement($sql, 's', [$searchTerm]);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $actionPlans = [];
        
        while ($row = $result->fetch_assoc()) {
            $actionPlans[] = $row;
        }
        
        $stmt->close();
        
        return $actionPlans;
    }
    
    /**
     * Get action plan details with creator and assignee information
     * 
     * @param int $actionPlanId The ID of the action plan
     * @return array|false The action plan details or false if not found
     */
    public function getDetails($actionPlanId) {
        $sql = "SELECT ap.*, u1.username as creator_name, u2.username as assignee_name,
                       u1.email as creator_email, u2.email as assignee_email,
                       u2.is_management_staff
                FROM {$this->table} ap
                JOIN users u1 ON ap.creator_id = u1.user_id
                JOIN users u2 ON ap.assignee_id = u2.user_id
                WHERE ap.{$this->primaryKey} = ?";
        $stmt = $this->executeStatement($sql, 'i', [$actionPlanId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $actionPlan = $result->fetch_assoc();
        
        $stmt->close();
        
        return $actionPlan;
    }
    
    /**
     * Get dashboard statistics
     * 
     * @return array Statistics for the dashboard
     */
    public function getDashboardStats() {
        $stats = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'total' => 0
        ];
        
        // Get counts by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = $this->executeStatement($sql);
        
        if ($stmt) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $status = strtolower(str_replace(' ', '_', $row['status']));
                $stats[$status] = $row['count'];
                $stats['total'] += $row['count'];
            }
            
            $stmt->close();
        }
        
        // Get overdue count
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE due_date < CURDATE() AND status != 'Completed'";
        $stmt = $this->executeStatement($sql);
        
        if ($stmt) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['overdue'] = $row['count'];
            
            $stmt->close();
        }
        
        return $stats;
    }
    
    /**
     * Get user-specific statistics
     * 
     * @param int $userId The ID of the user
     * @return array User-specific statistics
     */
    public function getUserStats($userId) {
        $stats = [
            'assigned_total' => 0,
            'assigned_completed' => 0,
            'assigned_overdue' => 0,
            'assigned_completed_on_time' => 0,
            'created_total' => 0,
            'created_completed' => 0
        ];
        
        // Get assigned stats
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN due_date < CURDATE() AND status != 'Completed' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = 'Completed' AND (due_date IS NULL OR due_date >= updated_at) THEN 1 ELSE 0 END) as completed_on_time
                FROM {$this->table}
                WHERE assignee_id = ?";
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if ($stmt) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $stats['assigned_total'] = $row['total'];
            $stats['assigned_completed'] = $row['completed'];
            $stats['assigned_overdue'] = $row['overdue'];
            $stats['assigned_completed_on_time'] = $row['completed_on_time'];
            
            $stmt->close();
        }
        
        // Get created stats
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
                FROM {$this->table}
                WHERE creator_id = ?";
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if ($stmt) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $stats['created_total'] = $row['total'];
            $stats['created_completed'] = $row['completed'];
            
            $stmt->close();
        }
        
        return $stats;
    }
} 