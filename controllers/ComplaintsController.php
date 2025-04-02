<?php
declare(strict_types=1);

/**
 * Complaints & Compliments Controller
 * 
 * Handles all actions related to complaints and compliments management
 */

namespace App\Controllers;

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActionPlan.php';
require_once __DIR__ . '/../models/UserNotification.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Mailer.php';

use App\Models\Complaint;
use App\Models\User;
use App\Models\ActionPlan;

class ComplaintsController extends Controller {
    private $complaintModel;
    private $userModel;
    private $actionPlanModel;
    
    public function __construct() {
        parent::__construct();
        $this->complaintModel = new Complaint();
        $this->userModel = new User();
        $this->actionPlanModel = new ActionPlan();
    }
    
    /**
     * Display a list of complaints and compliments
     */
    public function index() {
        $this->requireAuth();
        
        // Get filter parameters
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        $assignedTo = isset($_GET['assigned_to']) ? (int)$_GET['assigned_to'] : null;
        
        // Get page parameters
        $current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $perPage = 10;
        $offset = ($current_page - 1) * $perPage;
        
        // Prepare filters
        $filters = [];
        if (!empty($status)) $filters['status'] = $status;
        if (!empty($type)) $filters['type'] = $type;
        if (!empty($category)) $filters['category'] = $category;
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($assignedTo)) $filters['assigned_to'] = $assignedTo;
        
        // Get entries with pagination - fetch from both tables
        $entries = $this->getComplaintsAndCompliments(
            $filters,
            ['date_submitted' => 'DESC'],
            $perPage,
            $offset
        );
        
        // Log entries for debugging
        error_log("Combined entries retrieved: " . count($entries));
        
        // Get total count for pagination
        $total_count = $this->countAllComplaintsAndCompliments($filters);
        $total_pages = ceil($total_count / $perPage);
        
        // Get users for filter dropdown
        $users = $this->userModel->findAll();
        
        // Get categories
        $categories = $this->complaintModel->getCategories();
        
        // Render the view
        include __DIR__ . '/../views/complaints/index.php';
    }
    
    /**
     * Retrieve both complaints and compliments with filtering
     * 
     * @param array $filters Optional filters
     * @param array $orderBy Sort order
     * @param int|null $limit Maximum results to return
     * @param int|null $offset Result offset for pagination
     * @return array Combined data from both tables
     */
    private function getComplaintsAndCompliments(
        array $filters = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        // Get complaints
        $complaints = $this->complaintModel->findAll($filters, $orderBy, $limit, $offset);
        
        // Create a compliment model to fetch compliments
        require_once __DIR__ . '/../models/Compliment.php';
        $complimentModel = new \App\Models\Compliment();
        
        // Get compliments - note that the structure may be different
        $compliments = [];
        
        try {
            // For simplicity, we'll get all compliments and manually filter them
            // In a production system, you'd want to implement filtering in the Compliment model
            $allCompliments = $complimentModel->getAllWithDetails();
            
            // Filter and format compliments to match the structure expected by the view
            foreach ($allCompliments as $compliment) {
                // Apply filters if needed
                if (!empty($filters['type']) && $filters['type'] !== 'Compliment') {
                    continue;
                }
                
                // Map the compliment fields to match the complaints structure
                $compliments[] = [
                    'id' => $compliment['compliment_id'],
                    'title' => 'Compliment from ' . ($compliment['from_user_name'] ?? $compliment['from_name'] ?? 'Anonymous'),
                    'description' => $compliment['description'] ?? '',
                    'type' => 'Compliment',
                    'category' => 'Compliment', // You may want to adjust this based on your data structure
                    'status' => 'Completed',
                    'submitted_by' => $compliment['from_name'] ?? $compliment['from_user_name'] ?? 'Anonymous',
                    'date_submitted' => $compliment['created_at'] ?? date('Y-m-d H:i:s'),
                    'created_by_name' => $compliment['from_user_name'] ?? '',
                    'assigned_to_name' => $compliment['about_user_name'] ?? ''
                ];
            }
        } catch (\Exception $e) {
            error_log("Error retrieving compliments: " . $e->getMessage());
        }
        
        // Combine the results
        $combined = array_merge($complaints, $compliments);
        
        // Sort the combined results by date
        usort($combined, function($a, $b) {
            $dateA = strtotime($a['date_submitted'] ?? '0');
            $dateB = strtotime($b['date_submitted'] ?? '0');
            return $dateB - $dateA; // Descending order
        });
        
        // Apply manual pagination if needed
        if ($limit !== null && $offset !== null) {
            $combined = array_slice($combined, 0, $limit);
        }
        
        return $combined;
    }
    
    /**
     * Count all complaints and compliments with filtering
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    private function countAllComplaintsAndCompliments(array $filters = []): int {
        // Count complaints
        $complaintCount = $this->complaintModel->countAll($filters);
        
        // Count compliments (basic implementation)
        $complimentCount = 0;
        try {
            require_once __DIR__ . '/../models/Compliment.php';
            $complimentModel = new \App\Models\Compliment();
            $stats = $complimentModel->getStats();
            $complimentCount = $stats['total'] ?? 0;
            
            // Filter by type if specified
            if (!empty($filters['type']) && $filters['type'] !== 'Compliment') {
                $complimentCount = 0;
            }
        } catch (\Exception $e) {
            error_log("Error counting compliments: " . $e->getMessage());
        }
        
        return $complaintCount + $complimentCount;
    }
    
    /**
     * Show the form to create a new complaint/compliment
     */
    public function create() {
        $this->requireAuth();
        
        // Get users for assignee dropdown
        $users = $this->userModel->findAll();
        
        // Get categories
        $categories = $this->complaintModel->getCategories();
        
        // Render the view
        include __DIR__ . '/../views/complaints/create.php';
    }
    
    /**
     * Display the public submission form
     */
    public function publicForm() {
        // No authentication required for public form
        
        // Get categories
        $categories = $this->complaintModel->getCategories();
        
        // Render the view
        include __DIR__ . '/../views/complaints/public_form.php';
    }
    
    /**
     * Store a new complaint/compliment
     */
    public function store() {
        // Check if internal or external submission
        $isInternal = !isset($_POST['public_submission']) || $_POST['public_submission'] !== '1';
        
        if ($isInternal) {
            $this->requireAuth();
        }
        
        // Debug information
        error_log("Starting complaints store method");
        error_log("POST data: " . print_r($_POST, true));
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'type', 'category'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errorMessage = "The {$field} field is required.";
                error_log("Validation error: $errorMessage");
                if ($isInternal) {
                    $this->setFlashMessage('danger', $errorMessage);
                    header('Location: index.php?page=complaints&action=create');
                } else {
                    // Handle public submission error
                    $error = $errorMessage;
                    include __DIR__ . '/../views/complaints/public_form.php';
                }
                exit;
            }
        }
        
        // Process form data
        $title = $_POST['title'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        $category = $_POST['category'];
        $submittedBy = $_POST['submitted_by'] ?? null;
        $contactEmail = $_POST['contact_email'] ?? null;
        $contactPhone = $_POST['contact_phone'] ?? null;
        $anonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1';
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        
        // Get current user ID for internal submissions
        $userId = $isInternal ? $this->getCurrentUserId() : null;
        
        try {
            // Determine which model to use based on type
            $entryId = 0;
            
            if ($type === 'Compliment') {
                // Use the Compliment model for compliments
                require_once __DIR__ . '/../models/Compliment.php';
                $complimentModel = new \App\Models\Compliment();
                
                // Create the compliment data
                $complimentData = [
                    'from_user_id' => $userId ?? 0,
                    'about_user_id' => $assignedTo ?? 0,
                    'from_name' => $submittedBy,
                    'description' => $description,
                    'is_anonymous' => $anonymous ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                error_log("Attempting to create compliment with data: " . print_r($complimentData, true));
                
                // Create the compliment
                $entryId = $complimentModel->create($complimentData);
                error_log("Create compliment result: " . ($entryId ? "Success, ID: $entryId" : "Failed"));
            } else {
                // Use the Complaint model for complaints
                // Create the entry
                $data = [
                    'title' => $title,
                    'description' => $description,
                    'type' => $type,
                    'category' => $category,
                    'status' => 'New',
                    'submitted_by' => $submittedBy,
                    'contact_email' => $contactEmail,
                    'contact_phone' => $contactPhone,
                    'is_anonymous' => $anonymous ? 1 : 0,
                    'created_by' => $userId,
                    'assigned_to' => $assignedTo,
                    'date_submitted' => date('Y-m-d H:i:s')
                ];
                
                error_log("Attempting to create complaint with data: " . print_r($data, true));
                
                $entryId = $this->complaintModel->create($data);
                error_log("Create complaint result: " . ($entryId ? "Success, ID: $entryId" : "Failed"));
            }
            
            if (!$entryId) {
                $errorMessage = "Failed to submit the {$type}.";
                error_log("Error creating entry: $errorMessage");
                if ($isInternal) {
                    $this->setFlashMessage('danger', $errorMessage);
                    header('Location: index.php?page=complaints&action=create');
                } else {
                    // Handle public submission error
                    $error = $errorMessage;
                    include __DIR__ . '/../views/complaints/public_form.php';
                }
                exit;
            }
            
            // Success message
            $successMessage = ucfirst($type) . " submitted successfully.";
            error_log("Success: $successMessage");
            
            if ($isInternal) {
                $this->setFlashMessage('success', $successMessage);
                
                // Add type parameter for compliments
                $viewUrl = "index.php?page=complaints&action=view&id={$entryId}";
                if ($type === 'Compliment') {
                    $viewUrl .= "&type=compliment";
                }
                
                header("Location: {$viewUrl}");
            } else {
                // Show success page for public submissions
                $success = $successMessage;
                
                // Generate a reference number for the submission
                $reference_number = strtoupper(date('Ymd') . '-' . substr(md5($entryId . time()), 0, 6));
                
                // Pass the anonymous flag to the view
                $anonymous = $anonymous;
                
                // Update the entry with the reference number
                try {
                    if ($type !== 'Compliment') {
                        $updateResult = $this->complaintModel->update($entryId, ['reference_number' => $reference_number]);
                        error_log("Update reference number result: " . ($updateResult ? "Success" : "Failed"));
                    }
                } catch (\Exception $e) {
                    error_log("Error updating reference number: " . $e->getMessage());
                }
                
                // Include the success view
                include __DIR__ . '/../views/complaints/public_success.php';
            }
        } catch (\Exception $e) {
            error_log("Exception in complaints store: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $errorMessage = "An error occurred while processing your submission: " . $e->getMessage();
            if ($isInternal) {
                $this->setFlashMessage('danger', $errorMessage);
                header('Location: index.php?page=complaints&action=create');
            } else {
                // Handle public submission error
                $error = $errorMessage;
                include __DIR__ . '/../views/complaints/public_form.php';
            }
        }
        exit;
    }
    
    /**
     * Display a specific complaint/compliment
     */
    public function view() {
        $this->requireAuth();
        
        // Check if we're viewing a complaint or compliment
        $type = $_GET['type'] ?? '';
        $isCompliment = ($type === 'compliment');
        
        // Get the ID based on the type
        if ($isCompliment) {
            // For compliments, use compliment_id parameter
            $id = isset($_GET['compliment_id']) ? (int)$_GET['compliment_id'] : 0;
            error_log("Viewing compliment with compliment_id: $id");
        } else {
            // For complaints, use id parameter
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            error_log("Viewing complaint with id: $id");
        }
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get the entry with details
        $entry = null;
        
        if ($isCompliment) {
            // It's a compliment, use the Compliment model
            require_once __DIR__ . '/../models/Compliment.php';
            $complimentModel = new \App\Models\Compliment();
            
            // Get compliment by ID
            try {
                $compliment = $complimentModel->findById($id);
                error_log("Compliment query result: " . ($compliment ? "Found" : "Not found"));
                
                if ($compliment) {
                    // Map compliment fields to the structure expected by the view
                    $entry = [
                        'id' => $compliment['compliment_id'],
                        'title' => 'Compliment from ' . ($compliment['from_user_name'] ?? $compliment['from_name'] ?? 'Anonymous'),
                        'description' => $compliment['description'] ?? '',
                        'type' => 'Compliment',
                        'category' => 'Compliment',
                        'status' => 'Completed',
                        'submitted_by' => $compliment['from_name'] ?? $compliment['from_user_name'] ?? 'Anonymous',
                        'date_submitted' => $compliment['created_at'] ?? date('Y-m-d H:i:s'),
                        'created_by_name' => $compliment['from_user_name'] ?? '',
                        'assigned_to_name' => $compliment['about_user_name'] ?? ''
                    ];
                }
            } catch (\Exception $e) {
                error_log("Error retrieving compliment: " . $e->getMessage());
            }
        } else {
            // It's a regular complaint
            $entry = $this->complaintModel->findById($id);
        }
        
        if (!$entry) {
            $this->setFlashMessage('danger', 'Entry not found.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get responses - only for complaints, not compliments
        $responses = !$isCompliment ? $this->complaintModel->getResponsesByComplaintId($id) : [];
        
        // Get action plans - only for complaints, not compliments
        $actionPlans = !$isCompliment ? $this->complaintModel->getActionPlansByComplaintId($id) : [];
        
        // Get users for assignee dropdown
        $users = $this->userModel->findAll();
        
        // Render the view
        include __DIR__ . '/../views/complaints/view.php';
    }
    
    /**
     * Show the form to edit a complaint/compliment
     */
    public function edit() {
        $this->requireAuth();
        
        // Check if we're editing a complaint or compliment
        $type = $_GET['type'] ?? '';
        $isCompliment = ($type === 'compliment');
        
        // Get the ID based on the type
        if ($isCompliment) {
            // For compliments, use compliment_id parameter
            $id = isset($_GET['compliment_id']) ? (int)$_GET['compliment_id'] : 0;
            error_log("Editing compliment with compliment_id: $id");
        } else {
            // For complaints, use id parameter
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            error_log("Editing complaint with id: $id");
        }
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get the entry
        $entry = null;
        
        if ($isCompliment) {
            // It's a compliment
            require_once __DIR__ . '/../models/Compliment.php';
            $complimentModel = new \App\Models\Compliment();
            
            try {
                $compliment = $complimentModel->findById($id);
                
                if ($compliment) {
                    // Map compliment fields to the structure expected by the view
                    $entry = [
                        'id' => $compliment['compliment_id'],
                        'title' => 'Compliment from ' . ($compliment['from_user_name'] ?? $compliment['from_name'] ?? 'Anonymous'),
                        'description' => $compliment['description'] ?? '',
                        'type' => 'Compliment',
                        'category' => 'Compliment',
                        'status' => 'Completed',
                        'submitted_by' => $compliment['from_name'] ?? $compliment['from_user_name'] ?? 'Anonymous',
                        'date_submitted' => $compliment['created_at'] ?? date('Y-m-d H:i:s'),
                        'created_by_name' => $compliment['from_user_name'] ?? '',
                        'assigned_to_name' => $compliment['about_user_name'] ?? '',
                        'about_user_id' => $compliment['about_user_id'] ?? '',
                        'from_user_id' => $compliment['from_user_id'] ?? ''
                    ];
                }
            } catch (\Exception $e) {
                error_log("Error retrieving compliment for edit: " . $e->getMessage());
            }
        } else {
            // It's a regular complaint
            $entry = $this->complaintModel->findById($id);
        }
        
        if (!$entry) {
            $this->setFlashMessage('danger', 'Entry not found.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get users for assignee dropdown
        $users = $this->userModel->findAll();
        
        // Get categories
        $categories = $this->complaintModel->getCategories();
        
        // Render the view
        include __DIR__ . '/../views/complaints/edit.php';
    }
    
    /**
     * Update a complaint/compliment
     */
    public function update() {
        $this->requireAuth();
        
        // Check if we're updating a complaint or compliment
        $type = $_POST['type'] ?? '';
        $isCompliment = ($type === 'compliment');
        
        // Get the ID based on the type
        if ($isCompliment) {
            // For compliments, use compliment_id parameter
            $id = isset($_POST['compliment_id']) ? (int)$_POST['compliment_id'] : 0;
            error_log("Updating compliment with compliment_id: $id");
        } else {
            // For complaints, use id parameter
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            error_log("Updating complaint with id: $id");
        }
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        $success = false;
        
        if ($isCompliment) {
            // Update a compliment
            require_once __DIR__ . '/../models/Compliment.php';
            $complimentModel = new \App\Models\Compliment();
            
            // Process compliment form data
            $data = [
                'description' => $_POST['description'] ?? null,
                'from_name' => $_POST['submitted_by'] ?? null,
                'about_user_id' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'is_anonymous' => isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1' ? 1 : 0
            ];
            
            // Remove null/empty values
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });
            
            try {
                // Update the compliment
                $success = $complimentModel->update($id, $data);
                error_log("Update compliment result: " . ($success ? "Success" : "Failed"));
            } catch (\Exception $e) {
                error_log("Error updating compliment: " . $e->getMessage());
                $success = false;
            }
        } else {
            // Update a complaint
            // Process form data
            $data = [
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'type' => $_POST['type'] ?? null,
                'category' => $_POST['category'] ?? null,
                'status' => $_POST['status'] ?? null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'resolution_notes' => $_POST['resolution_notes'] ?? null
            ];
            
            // Remove null/empty values
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });
            
            // If status changed to Resolved, add resolution date
            if (isset($data['status']) && $data['status'] === 'Resolved') {
                $data['date_resolved'] = date('Y-m-d H:i:s');
            }
            
            // Update the complaint
            $success = $this->complaintModel->update($id, $data);
            error_log("Update complaint result: " . ($success ? "Success" : "Failed"));
        }
        
        if (!$success) {
            $this->setFlashMessage('danger', 'Failed to update entry.');
            $editUrl = "index.php?page=complaints&action=edit&id={$id}";
            if ($isCompliment) {
                $editUrl = "index.php?page=complaints&action=edit&compliment_id={$id}&type=compliment";
            }
            header("Location: " . $editUrl);
            exit;
        }
        
        $this->setFlashMessage('success', 'Entry updated successfully.');
        $viewUrl = "index.php?page=complaints&action=view&id={$id}";
        if ($isCompliment) {
            $viewUrl = "index.php?page=complaints&action=view&compliment_id={$id}&type=compliment";
        }
        header("Location: " . $viewUrl);
        exit;
    }
    
    /**
     * Add a response to a complaint/compliment
     */
    public function addResponse() {
        $this->requireAuth();
        
        $id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Validate required fields
        if (empty($_POST['response'])) {
            $this->setFlashMessage('danger', 'Response text is required.');
            header("Location: index.php?page=complaints&action=view&id={$id}");
            exit;
        }
        
        // Process form data
        $data = [
            'complaint_id' => $id,
            'response' => $_POST['response'],
            'created_by' => $this->getCurrentUserId(),
            'is_public' => isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Add the response
        $responseId = $this->complaintModel->addResponse($data);
        
        if (!$responseId) {
            $this->setFlashMessage('danger', 'Failed to add response.');
            header("Location: index.php?page=complaints&action=view&id={$id}");
            exit;
        }
        
        // Update the complaint status to In Progress if it's currently New
        $complaint = $this->complaintModel->findById($id);
        if ($complaint && $complaint['status'] === 'New') {
            $this->complaintModel->update($id, ['status' => 'In Progress']);
        }
        
        $this->setFlashMessage('success', 'Response added successfully.');
        header("Location: index.php?page=complaints&action=view&id={$id}");
        exit;
    }
    
    /**
     * Create an action plan for a complaint
     */
    public function createActionPlan() {
        $this->requireAuth();
        
        $id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get the complaint
        $complaint = $this->complaintModel->findById($id);
        
        if (!$complaint) {
            $this->setFlashMessage('danger', 'Entry not found.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Validate required fields
        $requiredFields = ['title', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->setFlashMessage('danger', "The {$field} field is required.");
                header("Location: index.php?page=complaints&action=view&id={$id}");
                exit;
            }
        }
        
        // Create action plan data
        $actionPlanData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'created_by' => $this->getCurrentUserId(),
            'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
            'status' => 'Draft',
            'priority' => $_POST['priority'] ?? 'Medium',
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+30 days'))
        ];
        
        // Create the action plan
        $actionPlanId = $this->actionPlanModel->create($actionPlanData);
        
        if (!$actionPlanId) {
            $this->setFlashMessage('danger', 'Failed to create action plan.');
            header("Location: index.php?page=complaints&action=view&id={$id}");
            exit;
        }
        
        // Link the action plan to the complaint
        $result = $this->complaintModel->linkActionPlan($id, $actionPlanId);
        
        if (!$result) {
            $this->setFlashMessage('danger', 'Failed to link action plan to complaint.');
            header("Location: index.php?page=complaints&action=view&id={$id}");
            exit;
        }
        
        $this->setFlashMessage('success', 'Action plan created successfully.');
        header("Location: index.php?page=complaints&action=view&id={$id}");
        exit;
    }
    
    /**
     * Delete a complaint/compliment
     */
    public function delete() {
        $this->requireAuth();
        
        // Check if deleting a complaint or compliment
        $isCompliment = isset($_GET['type']) && $_GET['type'] === 'compliment';
        
        // Get the ID based on the type
        if ($isCompliment) {
            // For compliments, use compliment_id parameter
            $id = isset($_GET['compliment_id']) ? (int)$_GET['compliment_id'] : 0;
            error_log("Deleting compliment with compliment_id: $id");
        } else {
            // For complaints, use id parameter
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            error_log("Deleting complaint with id: $id");
        }
        
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid entry ID.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        $success = false;
        
        if ($isCompliment) {
            // Delete a compliment
            require_once __DIR__ . '/../models/Compliment.php';
            $complimentModel = new \App\Models\Compliment();
            
            try {
                // Override the primary key field
                $complimentModel->setPrimaryKey('compliment_id');
                $success = $complimentModel->delete($id);
                error_log("Delete compliment result: " . ($success ? "Success" : "Failed"));
            } catch (\Exception $e) {
                error_log("Error deleting compliment: " . $e->getMessage());
                $success = false;
            }
        } else {
            // Delete a complaint
            $success = $this->complaintModel->delete($id);
            error_log("Delete complaint result: " . ($success ? "Success" : "Failed"));
        }
        
        if (!$success) {
            $this->setFlashMessage('danger', 'Failed to delete entry.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        $this->setFlashMessage('success', 'Entry deleted successfully.');
        header('Location: index.php?page=complaints');
        exit;
    }
    
    /**
     * Display analytics dashboard for complaints and compliments
     */
    public function dashboard() {
        $this->requireAuth();
        
        // Get date range parameters (default to last 30 days)
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        
        // Validate dates
        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->setFlashMessage('danger', 'Invalid date range provided.');
            header('Location: index.php?page=complaints&action=dashboard');
            exit;
        }
        
        // Get comparison period (same length of time before the start date)
        $comparisonEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $comparisonStartDate = date('Y-m-d', strtotime($startDate . ' -' . $this->daysBetween($startDate, $endDate) . ' days'));
        
        // Get summary statistics
        $stats = $this->complaintModel->getDashboardStats($startDate, $endDate);
        
        // Get comparison statistics
        $comparisonStats = $this->complaintModel->getDashboardStats($comparisonStartDate, $comparisonEndDate);
        
        // Get time series data for charts
        $timeSeriesData = $this->complaintModel->getTimeSeriesData($startDate, $endDate);
        
        // Get category distribution
        $categoryData = $this->complaintModel->getCategoryDistribution($startDate, $endDate);
        
        // Get status distribution
        $statusData = $this->complaintModel->getStatusDistribution($startDate, $endDate);
        
        // Get resolution time averages
        $resolutionTimes = $this->complaintModel->getResolutionTimesByCategory($startDate, $endDate);
        
        // Get top complaint categories
        $topComplaintCategories = $this->complaintModel->getTopCategories('Complaint', $startDate, $endDate, 5);
        
        // Get top compliment categories
        $topComplimentCategories = $this->complaintModel->getTopCategories('Compliment', $startDate, $endDate, 5);
        
        // Get recent submissions
        $recentSubmissions = $this->complaintModel->getRecentSubmissions(10);
        
        // Render the view
        include __DIR__ . '/../views/complaints/dashboard.php';
    }
    
    /**
     * Export data to various formats
     */
    public function export() {
        $this->requireAuth();
        
        // Check permission for exports
        if (!$this->checkPermission('export_data')) {
            $this->setFlashMessage('danger', 'You do not have permission to export data.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Get export parameters
        $format = $_GET['format'] ?? 'csv';
        $type = $_GET['type'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // Validate dates
        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->setFlashMessage('danger', 'Invalid date range provided.');
            header('Location: index.php?page=complaints');
            exit;
        }
        
        // Prepare filters
        $filters = [];
        if (!empty($type)) $filters['type'] = $type;
        if (!empty($category)) $filters['category'] = $category;
        if (!empty($status)) $filters['status'] = $status;
        $filters['date_from'] = $startDate;
        $filters['date_to'] = $endDate;
        
        // Get data
        $data = $this->complaintModel->getExportData($filters);
        
        // Generate file name
        $fileName = 'feedback_export_' . date('Y-m-d') . '.' . $format;
        
        // Process by format
        switch ($format) {
            case 'csv':
                $this->exportToCsv($data, $fileName);
                break;
            case 'excel':
                $this->exportToExcel($data, $fileName);
                break;
            case 'pdf':
                $this->exportToPdf($data, $fileName);
                break;
            default:
                $this->setFlashMessage('danger', 'Unsupported export format.');
                header('Location: index.php?page=complaints');
                exit;
        }
    }
    
    /**
     * Export data to CSV format
     * 
     * @param array $data Data to export
     * @param string $fileName File name for the download
     */
    private function exportToCsv(array $data, string $fileName) {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, [
            'ID', 'Type', 'Category', 'Title', 'Description', 'Status', 
            'Submitted By', 'Contact Email', 'Contact Phone', 'Is Anonymous',
            'Date Submitted', 'Date Resolved', 'Assigned To', 'Resolution Notes'
        ]);
        
        // Write data rows
        foreach ($data as $row) {
            // Sanitize data for CSV
            $exportRow = [
                $row['id'],
                $row['type'],
                $row['category'],
                $row['title'],
                $row['description'],
                $row['status'],
                $row['is_anonymous'] ? 'Anonymous' : $row['submitted_by'],
                $row['is_anonymous'] ? '' : $row['contact_email'],
                $row['is_anonymous'] ? '' : $row['contact_phone'],
                $row['is_anonymous'] ? 'Yes' : 'No',
                $row['date_submitted'],
                $row['date_resolved'],
                $row['assigned_to_name'] ?? 'Unassigned',
                $row['resolution_notes']
            ];
            
            fputcsv($output, $exportRow);
        }
        
        // Close output stream
        fclose($output);
        exit;
    }
    
    /**
     * Export data to Excel format
     * 
     * @param array $data Data to export
     * @param string $fileName File name for the download
     */
    private function exportToExcel(array $data, string $fileName) {
        // This would typically use a library like PhpSpreadsheet
        // Simplified implementation for now - redirect to CSV
        $this->setFlashMessage('info', 'Excel export redirected to CSV format.');
        $this->exportToCsv($data, str_replace('excel', 'csv', $fileName));
    }
    
    /**
     * Export data to PDF format
     * 
     * @param array $data Data to export
     * @param string $fileName File name for the download
     */
    private function exportToPdf(array $data, string $fileName) {
        // This would typically use a library like FPDF or TCPDF
        // Simplified implementation for now - redirect to CSV
        $this->setFlashMessage('info', 'PDF export redirected to CSV format.');
        $this->exportToCsv($data, str_replace('pdf', 'csv', $fileName));
    }
    
    /**
     * Validate date in Y-m-d format
     * 
     * @param string $date Date string to validate
     * @return bool True if valid date
     */
    private function validateDate(string $date): bool {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Calculate days between two dates
     * 
     * @param string $start Start date in Y-m-d format
     * @param string $end End date in Y-m-d format
     * @return int Number of days
     */
    private function daysBetween(string $start, string $end): int {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $interval = $startDate->diff($endDate);
        return $interval->days;
    }
    
    public function notificationSettings()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "You must be logged in to access this page.";
            header('Location: index.php?page=login');
            exit;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // Get user notification settings
        $userSettings = [];
        $notificationModel = new UserNotification();
        $userSettings = $notificationModel->getUserSettings($userId);
        
        // For admin users, also get system settings and users list for recipient selection
        $systemSettings = [];
        $allUsers = [];
        if ($userRole === 'admin') {
            $settingsModel = new Setting();
            $systemSettings = $settingsModel->getSettingsByCategory('notifications');
            
            $userModel = new User();
            $allUsers = $userModel->getAllActiveUsers();
        }
        
        // Include the view
        include_once __DIR__ . '/../views/complaints/notifications_settings.php';
    }
    
    public function saveNotificationSettings()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "You must be logged in to access this page.";
            header('Location: index.php?page=login');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid request.";
            header('Location: index.php?page=complaints&action=notificationSettings');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $notifications = $_POST['notifications'] ?? [];
        
        // Sanitize and validate
        $validatedSettings = [];
        
        // Email notifications
        $validatedSettings['email_new_assigned'] = isset($notifications['email_new_assigned']) ? 1 : 0;
        $validatedSettings['email_status_change'] = isset($notifications['email_status_change']) ? 1 : 0;
        $validatedSettings['email_response_added'] = isset($notifications['email_response_added']) ? 1 : 0;
        $validatedSettings['email_new_submission'] = isset($notifications['email_new_submission']) ? 1 : 0;
        $validatedSettings['email_digest'] = isset($notifications['email_digest']) ? 1 : 0;
        
        // App notifications
        $validatedSettings['app_new_assigned'] = isset($notifications['app_new_assigned']) ? 1 : 0;
        $validatedSettings['app_status_change'] = isset($notifications['app_status_change']) ? 1 : 0;
        $validatedSettings['app_response_added'] = isset($notifications['app_response_added']) ? 1 : 0;
        $validatedSettings['app_new_submission'] = isset($notifications['app_new_submission']) ? 1 : 0;
        $validatedSettings['app_overdue_reminders'] = isset($notifications['app_overdue_reminders']) ? 1 : 0;
        
        // Reminder settings
        $validatedSettings['reminder_frequency'] = filter_var($notifications['reminder_frequency'] ?? 'weekly', 
                                                    FILTER_SANITIZE_STRING);
        $validatedSettings['unresolved_threshold'] = filter_var($notifications['unresolved_threshold'] ?? 7, 
                                                     FILTER_VALIDATE_INT, 
                                                     ['options' => ['min_range' => 1, 'max_range' => 30]]);
        
        if ($validatedSettings['unresolved_threshold'] === false) {
            $validatedSettings['unresolved_threshold'] = 7; // Default if invalid
        }
        
        // Save settings
        $notificationModel = new UserNotification();
        $success = $notificationModel->saveUserSettings($userId, $validatedSettings);
        
        if ($success) {
            $_SESSION['success'] = "Notification settings saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save notification settings.";
        }
        
        header('Location: index.php?page=complaints&action=notificationSettings');
        exit;
    }
    
    public function saveSystemSettings()
    {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access this feature.";
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid request.";
            header('Location: index.php?page=complaints&action=notificationSettings');
            exit;
        }
        
        $settings = $_POST['system_settings'] ?? [];
        
        // Sanitize and validate
        $validatedSettings = [];
        
        // Public notifications
        $validatedSettings['enable_public_notifications'] = isset($settings['enable_public_notifications']) ? 1 : 0;
        
        // Recipients
        $validatedSettings['notification_recipients'] = [];
        if (isset($settings['notification_recipients']) && is_array($settings['notification_recipients'])) {
            foreach ($settings['notification_recipients'] as $recipient) {
                $recipientId = filter_var($recipient, FILTER_VALIDATE_INT);
                if ($recipientId !== false) {
                    $validatedSettings['notification_recipients'][] = $recipientId;
                }
            }
        }
        
        // Auto assignment
        $validatedSettings['auto_assignment'] = isset($settings['auto_assignment']) ? 1 : 0;
        
        // Escalation threshold
        $validatedSettings['escalation_threshold'] = filter_var($settings['escalation_threshold'] ?? 14, 
                                                     FILTER_VALIDATE_INT, 
                                                     ['options' => ['min_range' => 1, 'max_range' => 30]]);
        
        if ($validatedSettings['escalation_threshold'] === false) {
            $validatedSettings['escalation_threshold'] = 14; // Default if invalid
        }
        
        // Save settings
        $settingsModel = new Setting();
        $success = true;
        
        foreach ($validatedSettings as $key => $value) {
            if ($key === 'notification_recipients') {
                $success = $success && $settingsModel->saveSetting('notifications', $key, json_encode($value));
            } else {
                $success = $success && $settingsModel->saveSetting('notifications', $key, $value);
            }
        }
        
        if ($success) {
            $_SESSION['success'] = "System notification settings saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save system notification settings.";
        }
        
        header('Location: index.php?page=complaints&action=notificationSettings');
        exit;
    }
    
    public function editTemplate()
    {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access this feature.";
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $allowedTypes = ['new_submission', 'status_change', 'response_added', 'weekly_digest'];
        
        if (!in_array($type, $allowedTypes)) {
            $_SESSION['error'] = "Invalid template type.";
            header('Location: index.php?page=complaints&action=notificationSettings');
            exit;
        }
        
        // Get template content
        $settingsModel = new Setting();
        $template = $settingsModel->getSetting('email_templates', $type);
        
        // Default templates if none exists
        if (!$template) {
            $defaultTemplates = [
                'new_submission' => [
                    'subject' => 'New [type] Submission: [title]',
                    'body' => "Dear [recipient],\n\nA new [type] has been submitted with the following details:\n\nTitle: [title]\nCategory: [category]\nSubmitted by: [submitter]\nDate: [date]\n\nDescription:\n[description]\n\nPlease log in to the system to view and respond to this submission.\n\nThank you,\n[organization] Team"
                ],
                'status_change' => [
                    'subject' => 'Status Update for [type] #[id]: [status]',
                    'body' => "Dear [recipient],\n\nThe status of [type] #[id] ([title]) has been updated to [status].\n\n[notes]\n\nYou can view the full details by logging into the system.\n\nThank you,\n[organization] Team"
                ],
                'response_added' => [
                    'subject' => 'New Response to [type] #[id]',
                    'body' => "Dear [recipient],\n\nA new response has been added to [type] #[id] ([title]).\n\nResponse from [responder]:\n[response]\n\nPlease log in to the system to view the full conversation.\n\nThank you,\n[organization] Team"
                ],
                'weekly_digest' => [
                    'subject' => 'Weekly Feedback Digest: [date_range]',
                    'body' => "Dear [recipient],\n\nHere is your weekly digest of feedback activity for the period [date_range]:\n\nNew Complaints: [new_complaints]\nResolved Complaints: [resolved_complaints]\nPending Complaints: [pending_complaints]\n\nNew Compliments: [new_compliments]\n\nTop Complaint Categories:\n[complaint_categories]\n\nTop Compliment Categories:\n[compliment_categories]\n\nYour assigned items requiring attention: [assigned_count]\n\nPlease log in to the system to view more details.\n\nThank you,\n[organization] Team"
                ]
            ];
            
            $template = $defaultTemplates[$type];
        } else {
            $template = json_decode($template, true);
        }
        
        // Include the view
        include_once __DIR__ . '/../views/complaints/edit_template.php';
    }
    
    public function saveTemplate()
    {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access this feature.";
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid request.";
            header('Location: index.php?page=complaints&action=notificationSettings');
            exit;
        }
        
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $allowedTypes = ['new_submission', 'status_change', 'response_added', 'weekly_digest'];
        
        if (!in_array($type, $allowedTypes)) {
            $_SESSION['error'] = "Invalid template type.";
            header('Location: index.php?page=complaints&action=notificationSettings');
            exit;
        }
        
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $body = filter_input(INPUT_POST, 'body', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        
        if (empty($subject) || empty($body)) {
            $_SESSION['error'] = "Subject and body cannot be empty.";
            header('Location: index.php?page=complaints&action=editTemplate&type=' . $type);
            exit;
        }
        
        $template = [
            'subject' => $subject,
            'body' => $body
        ];
        
        // Save template
        $settingsModel = new Setting();
        $success = $settingsModel->saveSetting('email_templates', $type, json_encode($template));
        
        if ($success) {
            $_SESSION['success'] = "Email template saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save email template.";
        }
        
        header('Location: index.php?page=complaints&action=editTemplate&type=' . $type);
        exit;
    }
    
    // Add the method to check for unresolved complaints that need notification
    public function checkUnresolvedComplaints()
    {
        // This method could be called via a cron job
        $complaintModel = new Complaint();
        $notificationModel = new UserNotification();
        
        // Get all notifications that need to be sent
        $notifications = $complaintModel->getUnresolvedNotifications();
        
        if (empty($notifications)) {
            return "No notifications to send.";
        }
        
        // Process each notification
        $count = 0;
        foreach ($notifications as $notification) {
            // Get user settings to check if they want notifications
            $userSettings = $notificationModel->getUserSettings($notification['assigned_to']);
            
            // Check if user has enabled overdue reminders
            if (isset($userSettings['app_overdue_reminders']) && $userSettings['app_overdue_reminders']) {
                // Create the notification
                $message = "Complaint #" . $notification['id'] . " (" . $notification['title'] . ") has been unresolved for " 
                         . $notification['days_old'] . " days.";
                
                $notificationData = [
                    'user_id' => $notification['assigned_to'],
                    'type' => 'complaint_overdue',
                    'message' => $message,
                    'link' => 'index.php?page=complaints&action=view&id=' . $notification['id'],
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Save the notification
                $notificationModel->createNotification($notificationData);
                $count++;
                
                // If email notifications are enabled, send an email
                if (isset($userSettings['email_status_change']) && $userSettings['email_status_change']) {
                    // Get user email
                    $userModel = new User();
                    $user = $userModel->getUserById($notification['assigned_to']);
                    
                    if ($user && isset($user['email'])) {
                        $settingsModel = new Setting();
                        $emailTemplateJson = $settingsModel->getSetting('email_templates', 'status_change');
                        
                        if ($emailTemplateJson) {
                            $emailTemplate = json_decode($emailTemplateJson, true);
                            
                            // Replace placeholders in the template
                            $subject = str_replace(
                                ['[type]', '[id]', '[status]', '[title]'],
                                ['Complaint', $notification['id'], 'Overdue', $notification['title']],
                                $emailTemplate['subject']
                            );
                            
                            $body = str_replace(
                                ['[recipient]', '[type]', '[id]', '[title]', '[status]', '[notes]', '[organization]'],
                                [
                                    $user['name'],
                                    'Complaint',
                                    $notification['id'],
                                    $notification['title'],
                                    'Overdue',
                                    'This complaint has been unresolved for ' . $notification['days_old'] . ' days and requires your attention.',
                                    'Care Compliance'
                                ],
                                $emailTemplate['body']
                            );
                            
                            // Send email
                            $mailer = new Mailer();
                            $mailer->sendEmail($user['email'], $subject, $body);
                        }
                    }
                }
            }
        }
        
        return "Sent " . $count . " notifications for unresolved complaints.";
    }
} 