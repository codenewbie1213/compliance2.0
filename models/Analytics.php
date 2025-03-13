<?php
/**
 * Analytics Model
 * 
 * This class handles analytics and performance metrics calculations.
 */

require_once __DIR__ . '/Model.php';

class Analytics extends Model {
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    
    /**
     * Get performance metrics for all users
     * 
     * @return array Array of user performance metrics
     */
    public function getUserPerformanceMetrics() {
        $sql = "SELECT 
                u.user_id,
                u.email,
                COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.email) as full_name,
                COUNT(DISTINCT ap.action_plan_id) as total_assigned,
                COUNT(DISTINCT CASE WHEN ap.status = 'Completed' THEN ap.action_plan_id END) as completed_tasks,
                COUNT(DISTINCT CASE WHEN ap.status = 'In Progress' THEN ap.action_plan_id END) as in_progress_tasks,
                COUNT(DISTINCT CASE WHEN ap.status = 'Pending' THEN ap.action_plan_id END) as pending_tasks,
                ROUND(COUNT(DISTINCT CASE WHEN ap.status = 'Completed' THEN ap.action_plan_id END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT ap.action_plan_id), 0), 2) as completion_rate,
                COUNT(DISTINCT c.comment_id) as total_comments,
                COUNT(DISTINCT att.attachment_id) as total_attachments,
                COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    AND ap.updated_at <= ap.due_date 
                    THEN ap.action_plan_id 
                END) as completed_on_time,
                COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    THEN ap.action_plan_id 
                END) as total_with_due_date,
                ROUND(COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    AND ap.updated_at <= ap.due_date 
                    THEN ap.action_plan_id 
                END) * 100.0 / 
                NULLIF(COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    THEN ap.action_plan_id 
                END), 0), 2) as on_time_completion_rate,
                AVG(CASE 
                    WHEN ap.status = 'Completed' 
                    THEN DATEDIFF(ap.updated_at, ap.created_at)
                END) as avg_completion_days
                FROM users u
                LEFT JOIN action_plans ap ON u.user_id = ap.assignee_id
                LEFT JOIN comments c ON ap.action_plan_id = c.action_plan_id AND c.user_id = u.user_id
                LEFT JOIN attachments att ON ap.action_plan_id = att.action_plan_id AND att.user_id = u.user_id
                GROUP BY u.user_id, u.email, u.first_name, u.last_name
                ORDER BY completion_rate DESC";
        
        $stmt = $this->executeStatement($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $result = $stmt->get_result();
        $metrics = [];
        
        while ($row = $result->fetch_assoc()) {
            $metrics[] = $row;
        }
        
        $stmt->close();
        
        return $metrics;
    }
    
    /**
     * Get detailed performance metrics for a specific user
     * 
     * @param int $userId The ID of the user
     * @return array|false User performance metrics or false if not found
     */
    public function getUserDetailedMetrics($userId) {
        // Get basic metrics
        $sql = "SELECT 
                u.user_id,
                u.email,
                COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.email) as full_name,
                COUNT(DISTINCT ap.action_plan_id) as total_assigned,
                COUNT(DISTINCT CASE WHEN ap.status = 'Completed' THEN ap.action_plan_id END) as completed_tasks,
                COUNT(DISTINCT CASE WHEN ap.status = 'In Progress' THEN ap.action_plan_id END) as in_progress_tasks,
                COUNT(DISTINCT CASE WHEN ap.status = 'Pending' THEN ap.action_plan_id END) as pending_tasks,
                ROUND(COUNT(DISTINCT CASE WHEN ap.status = 'Completed' THEN ap.action_plan_id END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT ap.action_plan_id), 0), 2) as completion_rate,
                COUNT(DISTINCT c.comment_id) as total_comments,
                COUNT(DISTINCT att.attachment_id) as total_attachments,
                ROUND(COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    AND ap.updated_at <= ap.due_date 
                    THEN ap.action_plan_id 
                END) * 100.0 / 
                NULLIF(COUNT(DISTINCT CASE 
                    WHEN ap.due_date IS NOT NULL 
                    AND ap.status = 'Completed' 
                    THEN ap.action_plan_id 
                END), 0), 2) as on_time_completion_rate,
                AVG(CASE 
                    WHEN ap.status = 'Completed' 
                    THEN DATEDIFF(ap.updated_at, ap.created_at)
                END) as avg_completion_days
                FROM users u
                LEFT JOIN action_plans ap ON u.user_id = ap.assignee_id
                LEFT JOIN comments c ON ap.action_plan_id = c.action_plan_id AND c.user_id = u.user_id
                LEFT JOIN attachments att ON ap.action_plan_id = att.action_plan_id AND att.user_id = u.user_id
                WHERE u.user_id = ?
                GROUP BY u.user_id, u.email, u.first_name, u.last_name";
        
        $stmt = $this->executeStatement($sql, 'i', [$userId]);
        
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->get_result();
        $metrics = $result->fetch_assoc();
        $stmt->close();
        
        if (!$metrics) {
            return false;
        }
        
        // Get recent activity
        $sql = "SELECT 
                'action_plan' as type,
                ap.name as title,
                ap.status,
                ap.updated_at as date
                FROM action_plans ap
                WHERE ap.assignee_id = ?
                UNION ALL
                SELECT 
                'comment' as type,
                ap.name as title,
                NULL as status,
                c.created_at as date
                FROM comments c
                JOIN action_plans ap ON c.action_plan_id = ap.action_plan_id
                WHERE c.user_id = ?
                UNION ALL
                SELECT 
                'attachment' as type,
                ap.name as title,
                NULL as status,
                att.created_at as date
                FROM attachments att
                JOIN action_plans ap ON att.action_plan_id = ap.action_plan_id
                WHERE att.user_id = ?
                ORDER BY date DESC
                LIMIT 10";
        
        $stmt = $this->executeStatement($sql, 'iii', [$userId, $userId, $userId]);
        
        if ($stmt) {
            $result = $stmt->get_result();
            $metrics['recent_activity'] = [];
            
            while ($row = $result->fetch_assoc()) {
                $metrics['recent_activity'][] = $row;
            }
            
            $stmt->close();
        }
        
        return $metrics;
    }
    
    /**
     * Get performance trends over time
     * 
     * @param int $userId Optional user ID to get trends for a specific user
     * @param string $period 'daily', 'weekly', or 'monthly'
     * @return array Array of trend data
     */
    public function getPerformanceTrends($userId = null, $period = 'weekly') {
        // Set the date format and interval based on period
        switch ($period) {
            case 'daily':
                $dateFormat = '%Y-%m-%d';
                $interval = '30 DAY';
                break;
            case 'monthly':
                $dateFormat = '%Y-%m-01';
                $interval = '12 MONTH';
                break;
            default: // weekly
                $dateFormat = '%Y-%m-%d'; // Store as date, we'll group by week in PHP
                $interval = '12 WEEK';
                break;
        }
        
        $sql = "SELECT 
                DATE_FORMAT(COALESCE(ap.updated_at, ap.created_at), ?) as period,
                COUNT(DISTINCT ap.action_plan_id) as total_tasks,
                COUNT(DISTINCT CASE WHEN ap.status = 'Completed' THEN ap.action_plan_id END) as completed_tasks
                FROM action_plans ap
                WHERE COALESCE(ap.updated_at, ap.created_at) >= DATE_SUB(CURDATE(), INTERVAL " . $interval . ")
                " . ($userId ? "AND ap.assignee_id = ? " : "") . "
                GROUP BY period
                ORDER BY period ASC";
        
        $params = $userId ? 'si' : 's';
        $values = $userId ? [$dateFormat, $userId] : [$dateFormat];
        
        $stmt = $this->executeStatement($sql, $params, $values);
        
        if (!$stmt) {
            error_log("Failed to execute trends query: " . $this->db->error);
            return [];
        }
        
        $result = $stmt->get_result();
        $trends = [];
        
        // Get the start date for our date range
        $startDate = new DateTime();
        $startDate->modify("-" . $interval);
        
        // Create a map of all periods with zero values
        $periodMap = [];
        $currentDate = clone $startDate;
        $endDate = new DateTime();
        
        while ($currentDate <= $endDate) {
            $key = $period === 'monthly' 
                ? $currentDate->format('Y-m-01')
                : $currentDate->format('Y-m-d');
            
            $periodMap[$key] = [
                'period' => $key,
                'total_tasks' => 0,
                'completed_tasks' => 0
            ];
            
            $currentDate->modify('+1 ' . ($period === 'monthly' ? 'month' : ($period === 'weekly' ? 'week' : 'day')));
        }
        
        // Fill in actual values
        while ($row = $result->fetch_assoc()) {
            if ($period === 'weekly') {
                // Convert date to start of week
                $date = new DateTime($row['period']);
                $row['period'] = $date->modify('monday this week')->format('Y-m-d');
            }
            $periodMap[$row['period']] = $row;
        }
        
        $stmt->close();
        
        // Convert to array and ensure all periods have values
        $trends = array_values($periodMap);
        
        // For weekly data, filter to just get complete weeks
        if ($period === 'weekly') {
            $trends = array_filter($trends, function($trend) use ($endDate) {
                $trendDate = new DateTime($trend['period']);
                return $trendDate <= $endDate;
            });
            $trends = array_values($trends); // Reset array keys
        }
        
        return $trends;
    }
} 