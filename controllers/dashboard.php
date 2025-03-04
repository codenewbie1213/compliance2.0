<?php
/**
 * Dashboard Controller
 * 
 * This controller handles dashboard analytics and display.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ActionPlan.php';

class DashboardController extends Controller {
    private $actionPlanModel;
    
    public function __construct() {
        $this->actionPlanModel = new ActionPlan();
    }
    
    /**
     * Show the dashboard
     */
    public function index() {
        // Require login
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        
        // Get dashboard statistics
        $globalStats = $this->actionPlanModel->getDashboardStats();
        $userStats = $this->actionPlanModel->getUserStats($userId);
        
        // Get action plans assigned to the user
        $assignedActionPlans = $this->actionPlanModel->findByAssigneeId($userId);
        
        // Get action plans created by the user
        $createdActionPlans = $this->actionPlanModel->findByCreatorId($userId);
        
        // Get overdue action plans assigned to the user
        $overdueActionPlans = array_filter($assignedActionPlans, function($plan) {
            return $plan['due_date'] && strtotime($plan['due_date']) < time() && $plan['status'] != 'Completed';
        });
        
        // Get action plans due soon (within the next day) assigned to the user
        $dueSoonActionPlans = array_filter($assignedActionPlans, function($plan) {
            return $plan['due_date'] && 
                   strtotime($plan['due_date']) >= time() && 
                   strtotime($plan['due_date']) <= strtotime('+1 day') && 
                   $plan['status'] != 'Completed';
        });
        
        $this->render('dashboard/index', [
            'globalStats' => $globalStats,
            'userStats' => $userStats,
            'assignedActionPlans' => $assignedActionPlans,
            'createdActionPlans' => $createdActionPlans,
            'overdueActionPlans' => $overdueActionPlans,
            'dueSoonActionPlans' => $dueSoonActionPlans
        ]);
    }
} 