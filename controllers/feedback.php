<?php
require_once __DIR__ . '/../models/Compliment.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/ActionPlan.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/MailService.php';

class FeedbackController {
    private $complimentModel;
    private $complaintModel;
    private $actionPlanModel;
    private $userModel;
    private $mailService;
    
    public function __construct() {
        $this->complimentModel = new Compliment();
        $this->complaintModel = new Complaint();
        $this->actionPlanModel = new ActionPlan();
        $this->userModel = new User();
        $this->mailService = new MailService();
    }
    
    /**
     * Display the feedback page with compliments and complaints tabs
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please log in to access this page.'];
            header('Location: index.php?page=login');
            exit;
        }
        
        $compliments = $this->complimentModel->getAllWithDetails();
        $complaints = $this->complaintModel->getAllWithDetails();
        $users = $this->userModel->findAll();
        
        include __DIR__ . '/../views/feedback/index.php';
    }
    
    /**
     * Handle compliment submission
     */
    public function addCompliment() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please log in to submit a compliment.'];
            header('Location: index.php?page=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromName = filter_input(INPUT_POST, 'from_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
            
            $errors = [];
            if (empty($fromName)) {
                $errors[] = 'Please provide your name.';
            }
            if (empty($description)) {
                $errors[] = 'Please provide a description for your compliment.';
            }
            
            if (empty($errors)) {
                $userId = $_SESSION['user_id'];
                $result = $this->complimentModel->create($userId, $userId, $fromName, $description);
                if ($result) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Compliment submitted successfully!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to submit compliment.'];
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            }
        }
        
        header('Location: index.php?page=feedback');
        exit;
    }
    
    /**
     * Handle complaint submission
     */
    public function addComplaint() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please log in to submit a complaint.'];
            header('Location: index.php?page=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromUserId = $_SESSION['user_id'];
            $fromName = filter_input(INPUT_POST, 'from_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
            
            $errors = [];
            if (empty($fromName)) {
                $errors[] = 'Please provide your name.';
            }
            if (empty($description)) {
                $errors[] = 'Please provide a description for your complaint.';
            }
            
            if (empty($errors)) {
                $result = $this->complaintModel->create($fromUserId, $fromName, $description);
                if ($result) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Complaint submitted successfully!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to submit complaint.'];
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            }
        }
        
        header('Location: index.php?page=feedback');
        exit;
    }
    
    /**
     * Create action plan for a complaint
     */
    public function createActionPlan() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please log in to create an action plan.'];
            header('Location: index.php?page=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_SANITIZE_NUMBER_INT);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
            $assigneeId = filter_input(INPUT_POST, 'assignee_id', FILTER_SANITIZE_NUMBER_INT);
            $dueDate = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_SPECIAL_CHARS);
            
            $errors = [];
            if (empty($name)) $errors[] = 'Name is required.';
            if (empty($description)) $errors[] = 'Description is required.';
            if (empty($assigneeId)) $errors[] = 'Assignee is required.';
            if (empty($dueDate)) $errors[] = 'Due date is required.';
            
            if (empty($errors)) {
                $actionPlanId = $this->actionPlanModel->create(
                    $name,
                    $description,
                    $_SESSION['user_id'],
                    $assigneeId,
                    $dueDate
                );
                
                if ($actionPlanId) {
                    // Update complaint status to In Progress if it's the first action plan
                    $complaint = $this->complaintModel->getDetails($complaintId);
                    if ($complaint['status'] === 'Pending') {
                        $this->complaintModel->updateStatus($complaintId, 'In Progress');
                    }
                    
                    // Link the action plan to the complaint
                    $this->complaintModel->addActionPlan($complaintId, $actionPlanId);
                    
                    // Get assignee details for email notification
                    $assignee = $this->userModel->findById($assigneeId);
                    
                    // Send email notification
                    $actionPlan = [
                        'action_plan_id' => $actionPlanId,
                        'name' => $name,
                        'description' => $description,
                        'due_date' => $dueDate
                    ];
                    
                    $emailSent = $this->mailService->sendActionPlanAssignment($actionPlan, $assignee, $complaint);
                    
                    $_SESSION['flash_message'] = [
                        'type' => 'success', 
                        'message' => 'Action plan created successfully!' . 
                                   ($emailSent ? ' Notification email sent.' : ' Could not send notification email.')
                    ];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to create action plan.'];
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            }
        }
        
        header('Location: index.php?page=feedback');
        exit;
    }
} 