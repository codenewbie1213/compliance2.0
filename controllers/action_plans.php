<?php
/**
 * Action Plans Controller
 * 
 * This controller handles CRUD operations for action plans.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ActionPlan.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/notifications.php';

class ActionPlansController extends Controller {
    private $actionPlanModel;
    private $userModel;
    private $commentModel;
    private $attachmentModel;
    private $notificationsController;
    
    public function __construct() {
        $this->actionPlanModel = new ActionPlan();
        $this->userModel = new User();
        $this->commentModel = new Comment();
        $this->attachmentModel = new Attachment();
        $this->notificationsController = new NotificationsController();
    }
    
    /**
     * Show all action plans
     */
    public function index() {
        // Require login
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        
        // Get action plans assigned to the user
        $assignedActionPlans = $this->actionPlanModel->findByAssigneeId($userId);
        
        // Get action plans created by the user
        $createdActionPlans = $this->actionPlanModel->findByCreatorId($userId);
        
        // Get filter values
        $statusFilter = isset($_GET['status']) ? $this->sanitize($_GET['status']) : '';
        $searchTerm = isset($_GET['search']) ? $this->sanitize($_GET['search']) : '';
        
        // Apply filters if provided
        if (!empty($statusFilter)) {
            $assignedActionPlans = array_filter($assignedActionPlans, function($plan) use ($statusFilter) {
                return $plan['status'] === $statusFilter;
            });
            
            $createdActionPlans = array_filter($createdActionPlans, function($plan) use ($statusFilter) {
                return $plan['status'] === $statusFilter;
            });
        }
        
        if (!empty($searchTerm)) {
            $assignedActionPlans = array_filter($assignedActionPlans, function($plan) use ($searchTerm) {
                return stripos($plan['name'], $searchTerm) !== false || 
                       stripos($plan['description'], $searchTerm) !== false;
            });
            
            $createdActionPlans = array_filter($createdActionPlans, function($plan) use ($searchTerm) {
                return stripos($plan['name'], $searchTerm) !== false || 
                       stripos($plan['description'], $searchTerm) !== false;
            });
        }
        
        $this->render('action_plans/index', [
            'assignedActionPlans' => $assignedActionPlans,
            'createdActionPlans' => $createdActionPlans,
            'statusFilter' => $statusFilter,
            'searchTerm' => $searchTerm
        ]);
    }
    
    /**
     * Show the form to create a new action plan
     */
    public function create() {
        // Require login
        $this->requireLogin();
        
        // Get all users for the assignee dropdown
        $users = $this->userModel->findAll();
        
        $this->render('action_plans/create', [
            'users' => $users
        ]);
    }
    
    /**
     * Process the form to create a new action plan
     */
    public function store() {
        // Require login
        $this->requireLogin();
        
        $errors = [];
        
        // Validate form data
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $assigneeId = intval($_POST['assignee_id'] ?? 0);
        $dueDate = $this->sanitize($_POST['due_date'] ?? '');
        
        $this->validateRequired($name, 'Name', $errors);
        $this->validateRequired($description, 'Description', $errors);
        $this->validateRequired($assigneeId, 'Assignee', $errors);
        
        // Validate due date format if provided
        if (!empty($dueDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = 'Due date must be in YYYY-MM-DD format.';
        }
        
        if (empty($errors)) {
            // Create the action plan
            $creatorId = $this->getCurrentUserId();
            $actionPlanId = $this->actionPlanModel->create(
                $name,
                $description,
                $creatorId,
                $assigneeId,
                !empty($dueDate) ? $dueDate : null
            );
            
            if ($actionPlanId) {
                // Get the action plan details for notification
                $actionPlan = $this->actionPlanModel->getDetails($actionPlanId);
                
                // Send notification to assignee if they are management staff
                if ($actionPlan['is_management_staff']) {
                    $this->notificationsController->sendActionPlanAssignmentNotification($actionPlan);
                }
                
                $this->setFlashMessage('success', 'Action plan created successfully.');
                $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
            } else {
                $errors[] = 'An error occurred while creating the action plan. Please try again.';
            }
        }
        
        // If we get here, there were errors
        $users = $this->userModel->findAll();
        
        $this->render('action_plans/create', [
            'errors' => $errors,
            'name' => $name,
            'description' => $description,
            'assignee_id' => $assigneeId,
            'due_date' => $dueDate,
            'users' => $users
        ]);
    }
    
    /**
     * Show an action plan
     */
    public function view() {
        // Require login
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($id);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get comments for the action plan
        $comments = $this->commentModel->findByActionPlanId($id);
        
        // Get attachments for the action plan
        $attachments = $this->attachmentModel->findByActionPlanId($id);
        
        $this->render('action_plans/view', [
            'actionPlan' => $actionPlan,
            'comments' => $comments,
            'attachments' => $attachments,
            'currentUserId' => $this->getCurrentUserId()
        ]);
    }
    
    /**
     * Show the form to edit an action plan
     */
    public function edit() {
        // Require login
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($id);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the user is the creator or assignee
        $currentUserId = $this->getCurrentUserId();
        if ($actionPlan['creator_id'] != $currentUserId && $actionPlan['assignee_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to edit this action plan.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get all users for the assignee dropdown
        $users = $this->userModel->findAll();
        
        $this->render('action_plans/edit', [
            'actionPlan' => $actionPlan,
            'users' => $users
        ]);
    }
    
    /**
     * Process the form to update an action plan
     */
    public function update() {
        // Require login
        $this->requireLogin();
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($id);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the user is the creator or assignee
        $currentUserId = $this->getCurrentUserId();
        if ($actionPlan['creator_id'] != $currentUserId && $actionPlan['assignee_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to edit this action plan.');
            $this->redirect('index.php?page=action_plans');
        }
        
        $errors = [];
        
        // Validate form data
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $assigneeId = intval($_POST['assignee_id'] ?? 0);
        $dueDate = $this->sanitize($_POST['due_date'] ?? '');
        $status = $this->sanitize($_POST['status'] ?? '');
        
        $this->validateRequired($name, 'Name', $errors);
        $this->validateRequired($description, 'Description', $errors);
        $this->validateRequired($assigneeId, 'Assignee', $errors);
        $this->validateRequired($status, 'Status', $errors);
        
        // Validate due date format if provided
        if (!empty($dueDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = 'Due date must be in YYYY-MM-DD format.';
        }
        
        if (empty($errors)) {
            // Update the action plan
            $data = [
                'name' => $name,
                'description' => $description,
                'assignee_id' => $assigneeId,
                'due_date' => !empty($dueDate) ? $dueDate : null,
                'status' => $status
            ];
            
            $success = $this->actionPlanModel->update($id, $data);
            
            if ($success) {
                // Check if assignee has changed
                if ($assigneeId != $actionPlan['assignee_id']) {
                    // Get the updated action plan details for notification
                    $updatedActionPlan = $this->actionPlanModel->getDetails($id);
                    
                    // Send notification to new assignee if they are management staff
                    if ($updatedActionPlan['is_management_staff']) {
                        $this->notificationsController->sendActionPlanAssignmentNotification($updatedActionPlan);
                    }
                }
                
                $this->setFlashMessage('success', 'Action plan updated successfully.');
                $this->redirect('index.php?page=action_plans&action=view&id=' . $id);
            } else {
                $errors[] = 'An error occurred while updating the action plan. Please try again.';
            }
        }
        
        // If we get here, there were errors
        $users = $this->userModel->findAll();
        
        $this->render('action_plans/edit', [
            'errors' => $errors,
            'actionPlan' => array_merge($actionPlan, [
                'name' => $name,
                'description' => $description,
                'assignee_id' => $assigneeId,
                'due_date' => $dueDate,
                'status' => $status
            ]),
            'users' => $users
        ]);
    }
    
    /**
     * Delete an action plan
     */
    public function delete() {
        // Require login
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($id);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the user is the creator
        $currentUserId = $this->getCurrentUserId();
        if ($actionPlan['creator_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to delete this action plan.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Delete attachments
        $attachments = $this->attachmentModel->deleteByActionPlanIdWithFileInfo($id);
        
        // Delete physical files
        foreach ($attachments as $attachment) {
            $filePath = __DIR__ . '/../' . $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete comments
        $this->commentModel->deleteByActionPlanId($id);
        
        // Delete the action plan
        $success = $this->actionPlanModel->deleteById($id);
        
        if ($success) {
            $this->setFlashMessage('success', 'Action plan deleted successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while deleting the action plan. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans');
    }
    
    /**
     * Update the status of an action plan
     */
    public function updateStatus() {
        // Require login
        $this->requireLogin();
        
        $id = intval($_POST['id'] ?? 0);
        $status = $this->sanitize($_POST['status'] ?? '');
        
        if ($id <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($id);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the user is the assignee
        $currentUserId = $this->getCurrentUserId();
        if ($actionPlan['assignee_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to update the status of this action plan.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Update the status
        $success = $this->actionPlanModel->update($id, ['status' => $status]);
        
        if ($success) {
            $this->setFlashMessage('success', 'Action plan status updated successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while updating the action plan status. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $id);
    }
} 