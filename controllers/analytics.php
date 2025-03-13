<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Analytics.php';

class AnalyticsController extends Controller {
    private $analyticsModel;
    
    public function __construct() {
        $this->analyticsModel = new Analytics();
    }
    
    /**
     * Display the performance analytics dashboard
     */
    public function index() {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get performance metrics for all users
        $metrics = $this->analyticsModel->getUserPerformanceMetrics();
        
        // Get overall trends
        $trends = $this->analyticsModel->getPerformanceTrends();
        
        // Calculate overall statistics
        $totalTasks = 0;
        $totalCompleted = 0;
        $totalOnTime = 0;
        $totalWithDueDate = 0;
        
        foreach ($metrics as $metric) {
            $totalTasks += $metric['total_assigned'];
            $totalCompleted += $metric['completed_tasks'];
            $totalOnTime += $metric['completed_on_time'];
            $totalWithDueDate += $metric['total_with_due_date'];
        }
        
        $overallStats = [
            'total_tasks' => $totalTasks,
            'total_completed' => $totalCompleted,
            'completion_rate' => $totalTasks > 0 ? round(($totalCompleted / $totalTasks) * 100, 2) : 0,
            'on_time_rate' => $totalWithDueDate > 0 ? round(($totalOnTime / $totalWithDueDate) * 100, 2) : 0
        ];
        
        // Load the view
        require_once __DIR__ . '/../views/analytics/index.php';
    }
    
    /**
     * Display detailed metrics for a specific user
     */
    public function userDetails($userId = null) {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // If no user ID provided, use the logged-in user's ID
        if (!$userId) {
            $userId = $_SESSION['user_id'];
        }
        
        // Get detailed metrics for the user
        $metrics = $this->analyticsModel->getUserDetailedMetrics($userId);
        
        if (!$metrics) {
            $this->setFlashMessage('error', 'User not found.');
            $this->redirect('/analytics');
            return;
        }
        
        // Get performance trends for the user
        $trends = $this->analyticsModel->getPerformanceTrends($userId);
        
        // Load the view
        require_once __DIR__ . '/../views/analytics/user_details.php';
    }
    
    /**
     * Get performance trends data for AJAX requests
     */
    public function getTrends() {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        $period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
        
        if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
            $period = 'weekly';
        }
        
        $trends = $this->analyticsModel->getPerformanceTrends($userId, $period);
        
        header('Content-Type: application/json');
        echo json_encode($trends);
    }
} 