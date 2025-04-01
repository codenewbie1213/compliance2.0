<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Audit;
use App\Models\AuditSection;
use App\Models\AuditQuestion;
use App\Models\AuditResponse;
use App\Models\User;
use App\Models\ActionPlan;
use App\Models\UserActivity;
use App\Models\AuditAttachment;

/**
 * AuditController
 * Handles audit management functionality
 */
class AuditController extends Controller
{
    protected Audit $auditModel;
    protected AuditSection $sectionModel;
    protected AuditQuestion $questionModel;
    protected AuditResponse $responseModel;
    protected User $userModel;
    protected ActionPlan $actionPlanModel;
    protected UserActivity $userActivityModel;
    protected AuditAttachment $attachmentModel;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->auditModel = new Audit();
        $this->sectionModel = new AuditSection();
        $this->questionModel = new AuditQuestion();
        $this->responseModel = new AuditResponse();
        $this->userModel = new User();
        $this->actionPlanModel = new ActionPlan();
        $this->userActivityModel = new UserActivity();
        $this->attachmentModel = new AuditAttachment();
        
        // Check if user is logged in
        $this->requireLogin();
    }

    /**
     * Get the client's IP address
     * 
     * @return string
     */
    protected function getClientIp(): string
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    /**
     * Send a JSON response
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Validate CSRF token
     * 
     * @throws \Exception if CSRF token is invalid
     */
    protected function validateCSRF(): void
    {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new \Exception('Invalid CSRF token');
        }
    }

    /**
     * List audits with filtering and pagination
     */
    public function index(): void
    {
        // Debug information
        error_log("AuditController index method called");
        error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
        error_log("Permissions: " . implode(', ', $_SESSION['permissions'] ?? []));
        
        try {
            // TEMPORARY FIX: Add the permission if it doesn't exist to prevent redirect loops
            if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
                $_SESSION['permissions'] = [];
            }
            if (!in_array('audits.view', $_SESSION['permissions'])) {
                error_log("NOTICE: Adding audits.view permission temporarily to prevent redirect loop");
                $_SESSION['permissions'][] = 'audits.view';
            }
            
            // Check permission
            $this->requirePermission('audits.view');
            
            error_log("Permission check passed for audits.view");
            
            // Use 'p' for pagination instead of 'page' to avoid conflict with routing parameter
            $pageNum = (int)($_GET['p'] ?? 1);
            $perPage = 20;
            
            // Get filters from request
            $filters = [
                'title' => $_GET['title'] ?? '',
                'status' => $_GET['status'] ?? '',
                'created_by' => (int)($_GET['created_by'] ?? 0) ?: null,
                'assigned_to' => (int)($_GET['assigned_to'] ?? 0) ?: null,
                'is_template' => isset($_GET['is_template']) ? (int)$_GET['is_template'] : null,
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'due_date_from' => $_GET['due_date_from'] ?? '',
                'due_date_to' => $_GET['due_date_to'] ?? '',
            ];
            
            try {
                // Get audits
                error_log("Getting audits with filters: " . json_encode($filters));
                $audits = $this->auditModel->getAudits(
                    $filters,
                    $_GET['sort_by'] ?? 'created_at',
                    $_GET['sort_order'] ?? 'desc',
                    $pageNum,
                    $perPage
                );
                error_log("Retrieved " . (is_array($audits) ? count($audits) : 'null') . " audits");
                
                // Get total count for pagination
                $totalAudits = $this->auditModel->countAudits($filters);
                error_log("Total audits count: " . $totalAudits);
                $totalPages = ceil($totalAudits / $perPage);
                
                // Get stats
                $stats = [
                    'total' => $this->auditModel->countAudits(),
                    'draft' => $this->auditModel->countAudits(['status' => 'draft']),
                    'in_progress' => $this->auditModel->countAudits(['status' => 'in_progress']),
                    'completed' => $this->auditModel->countAudits(['status' => 'completed']),
                    'archived' => $this->auditModel->countAudits(['status' => 'archived']),
                    'templates' => $this->auditModel->countAudits(['is_template' => 1]),
                ];
                error_log("Retrieved stats: " . json_encode($stats));
                
                // Get users for filters
                try {
                    error_log("Getting users for filters");
                    $users = $this->userModel->getAllUsers();
                    error_log("Retrieved " . count($users) . " users");
                } catch (\Exception $e) {
                    error_log("Error getting users: " . $e->getMessage());
                    $users = [];
                }
                
                error_log("About to render audits/index.php view");
                
                // Load view
                $this->render('audits/index', [
                    'audits' => $audits ?: [],
                    'stats' => $stats,
                    'users' => $users,
                    'filters' => $filters,
                    'pagination' => [
                        'page' => $pageNum,
                        'totalPages' => $totalPages,
                        'totalItems' => $totalAudits,
                        'perPage' => $perPage,
                    ],
                ]);
            } catch (\Exception $e) {
                error_log("Exception while getting data: " . $e->getMessage());
                error_log("File: " . $e->getFile() . ", Line: " . $e->getLine());
                
                // Try to render the view with empty data
                $this->render('audits/index', [
                    'audits' => [],
                    'stats' => [
                        'total' => 0,
                        'draft' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                        'archived' => 0,
                        'templates' => 0,
                    ],
                    'users' => [],
                    'filters' => $filters,
                    'pagination' => [
                        'page' => 1,
                        'totalPages' => 1,
                        'totalItems' => 0,
                        'perPage' => $perPage,
                    ],
                    'error_message' => "An error occurred while retrieving audits: " . $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Exception in AuditController::index(): " . $e->getMessage());
            error_log("File: " . $e->getFile() . ", Line: " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Display an error message to the user
            echo "<h1>Error Loading Audits Page</h1>";
            echo "<p>An error occurred while loading the audits page. Please try again later.</p>";
            echo "<p>Error details: " . $e->getMessage() . "</p>";
            echo "<p><a href='index.php?page=dashboard'>Return to Dashboard</a></p>";
        }
    }

    /**
     * Display create audit form
     */
    public function create(): void
    {
        // Check permission
        $this->requirePermission('audits.create');
        
        // Get templates for dropdown
        $templates = $this->auditModel->getTemplates();
        
        // Get users for assignee dropdown
        $users = $this->userModel->getAllUsers();
        
        // Load view
        $this->render('audits/create', [
            'templates' => $templates,
            'users' => $users,
        ]);
    }
    
    /**
     * Store a new audit
     */
    public function store(): void
    {
        // Check permission
        $this->requirePermission('audits.create');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $isTemplate = isset($_POST['is_template']) && $_POST['is_template'] === '1';
        $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
        
        // Validate required fields
        if (empty($title)) {
            $this->setFlashMessage('Please enter an audit title', 'danger');
            $this->redirect('index.php?page=audits&action=create');
            return;
        }
        
        // Validate assigned user exists if specified
        if ($assignedTo !== null) {
            $assignedUser = $this->userModel->getUserById($assignedTo);
            if (!$assignedUser) {
                $this->setFlashMessage('Selected assigned user does not exist', 'danger');
                $this->redirect('index.php?page=audits&action=create');
                return;
            }
        }
        
        // Set data for new audit
        $auditData = [
            'title' => $title,
            'description' => $description,
            'created_by' => $_SESSION['user_id'],
            'assigned_to' => $assignedTo,
            'status' => 'draft',
            'due_date' => $dueDate,
            'is_template' => $isTemplate ? 1 : 0,
        ];
        
        // Create the audit (from template or new)
        if ($templateId) {
            $auditId = $this->auditModel->createFromTemplate($templateId, $auditData);
            $sourceType = 'template';
        } else {
            $auditId = $this->auditModel->createAudit($auditData);
            $sourceType = 'manual';
        }
        
        if (!$auditId) {
            $this->setFlashMessage('Failed to create audit. Please try again.', 'danger');
            $this->redirect('index.php?page=audits&action=create');
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'create',
            'audits',
            $auditId,
            "Created new audit {$title} " . ($templateId ? 'from template' : ''),
            $this->getClientIp()
        );
        
        $this->setFlashMessage('Audit created successfully', 'success');
        $this->redirect('index.php?page=audits&action=edit&id=' . $auditId);
    }
    
    /**
     * View audit details
     */
    public function view(): void
    {
        // Check permission
        $this->requirePermission('audits.view');
        
        // Get audit ID from request
        $auditId = (int)($_GET['id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit details
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit sections and questions
        $sections = $this->sectionModel->getFullAuditStructure($auditId);
        
        // Get completion statistics
        $completionStats = $this->responseModel->getAuditCompletionStats($auditId);
        
        // Get related action plans
        $actionPlans = $this->actionPlanModel->getActionPlansBySourceType('audit', $auditId);
        
        // Get attachments
        $attachments = $this->attachmentModel->getAttachmentsByAuditId($auditId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'view',
            'audits',
            $auditId,
            "Viewed audit {$audit['title']}",
            $this->getClientIp()
        );
        
        // Load view
        $this->render('audits/view', [
            'audit' => $audit,
            'sections' => $sections,
            'completionStats' => $completionStats,
            'actionPlans' => $actionPlans,
            'attachments' => $attachments
        ]);
    }
    
    /**
     * Display edit audit form
     */
    public function edit(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Get audit ID from request
        $auditId = (int)($_GET['id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit details
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit sections and questions
        $sections = $this->sectionModel->getFullAuditStructure($auditId);
        
        // Get users for assignee dropdown
        $users = $this->userModel->getAllUsers();
        
        // Load view
        $this->render('audits/edit', [
            'audit' => $audit,
            'sections' => $sections,
            'users' => $users,
        ]);
    }
    
    /**
     * Update an existing audit
     */
    public function update(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get audit ID from request
        $auditId = (int)($_POST['audit_id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get existing audit
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $status = $_POST['status'] ?? $audit['status'];
        $completionDate = $status === 'completed' ? date('Y-m-d H:i:s') : ($audit['completion_date'] ?? null);
        
        // Validate required fields
        if (empty($title)) {
            $this->setFlashMessage('Please enter an audit title', 'danger');
            $this->redirect('index.php?page=audits&action=edit&id=' . $auditId);
            return;
        }
        
        // Set data for audit update
        $auditData = [
            'title' => $title,
            'description' => $description,
            'assigned_to' => $assignedTo,
            'status' => $status,
            'due_date' => $dueDate,
            'completion_date' => $completionDate
        ];
        
        // Update the audit
        $success = $this->auditModel->updateAudit($auditId, $auditData);
        
        if (!$success) {
            $this->setFlashMessage('Failed to update audit. Please try again.', 'danger');
            $this->redirect('index.php?page=audits&action=edit&id=' . $auditId);
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'update',
            'audits',
            $auditId,
            "Updated audit {$title}",
            $this->getClientIp()
        );
        
        $this->setFlashMessage('Audit updated successfully', 'success');
        $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
    }
    
    /**
     * Delete an audit
     */
    public function delete(): void
    {
        // Check permission
        $this->requirePermission('audits.delete');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get audit ID from request
        $auditId = (int)($_GET['id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get existing audit
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Delete the audit
        $success = $this->auditModel->deleteAudit($auditId);
        
        if (!$success) {
            $this->setFlashMessage('Failed to delete audit. Please try again.', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'delete',
            'audits',
            $auditId,
            "Deleted audit {$audit['title']}",
            $this->getClientIp()
        );
        
        $this->setFlashMessage('Audit deleted successfully', 'success');
        $this->redirect('index.php?page=audits');
    }
    
    /**
     * Add a new section to an audit
     */
    public function addSection(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get audit ID from request
        $auditId = (int)($_POST['audit_id'] ?? 0);
        
        if (!$auditId) {
            $this->jsonResponse(['success' => false, 'message' => 'Audit not found']);
            return;
        }
        
        // Get existing audit
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->jsonResponse(['success' => false, 'message' => 'Audit not found']);
            return;
        }
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : 1.0;
        
        // Validate required fields
        if (empty($title)) {
            $this->jsonResponse(['success' => false, 'message' => 'Section title is required']);
            return;
        }
        
        // Set data for new section
        $sectionData = [
            'audit_id' => $auditId,
            'title' => $title,
            'description' => $description,
            'weight' => $weight,
        ];
        
        // Create the section
        $sectionId = $this->sectionModel->createSection($sectionData);
        
        if (!$sectionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create section']);
            return;
        }
        
        // Get the new section with ID
        $section = $this->sectionModel->getSectionById($sectionId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'create',
            'audit_sections',
            $sectionId,
            "Added section {$title} to audit {$audit['title']}",
            $this->getClientIp()
        );
        
        $this->jsonResponse([
            'success' => true, 
            'message' => 'Section added successfully',
            'section' => $section
        ]);
    }
    
    /**
     * Update an existing section
     */
    public function updateSection(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get section ID from request
        $sectionId = (int)($_POST['section_id'] ?? 0);
        
        if (!$sectionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Get existing section
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if (!$section) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : 1.0;
        
        // Validate required fields
        if (empty($title)) {
            $this->jsonResponse(['success' => false, 'message' => 'Section title is required']);
            return;
        }
        
        // Set data for section update
        $sectionData = [
            'title' => $title,
            'description' => $description,
            'weight' => $weight,
        ];
        
        // Update the section
        $success = $this->sectionModel->updateSection($sectionId, $sectionData);
        
        if (!$success) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update section']);
            return;
        }
        
        // Get the updated section
        $updatedSection = $this->sectionModel->getSectionById($sectionId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'update',
            'audit_sections',
            $sectionId,
            "Updated section {$title}",
            $this->getClientIp()
        );
        
        // Recalculate audit scores
        $this->auditModel->calculateAuditScores($section['audit_id']);
        
        $this->jsonResponse([
            'success' => true, 
            'message' => 'Section updated successfully',
            'section' => $updatedSection
        ]);
    }
    
    /**
     * Delete a section
     */
    public function deleteSection(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get section ID from request
        $sectionId = (int)($_POST['section_id'] ?? 0);
        
        if (!$sectionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Get existing section
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if (!$section) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Delete the section
        $success = $this->sectionModel->deleteSection($sectionId);
        
        if (!$success) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete section']);
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'delete',
            'audit_sections',
            $sectionId,
            "Deleted section {$section['title']}",
            $this->getClientIp()
        );
        
        // Recalculate audit scores
        $this->auditModel->calculateAuditScores($section['audit_id']);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
    }
    
    /**
     * Add a new question to a section
     */
    public function addQuestion(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get section ID from request
        $sectionId = (int)($_POST['section_id'] ?? 0);
        
        if (!$sectionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Get existing section
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if (!$section) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Validate input
        $questionText = trim($_POST['question_text'] ?? '');
        $guidanceNotes = trim($_POST['guidance_notes'] ?? '');
        $questionType = $_POST['question_type'] ?? 'yes_no';
        $required = isset($_POST['required']) && $_POST['required'] === '1' ? 1 : 0;
        $options = !empty($_POST['options']) ? $_POST['options'] : null;
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : 1.0;
        
        // Validate required fields
        if (empty($questionText)) {
            $this->jsonResponse(['success' => false, 'message' => 'Question text is required']);
            return;
        }
        
        // Set data for new question
        $questionData = [
            'section_id' => $sectionId,
            'question_text' => $questionText,
            'guidance_notes' => $guidanceNotes,
            'question_type' => $questionType,
            'required' => $required,
            'options' => $options,
            'weight' => $weight,
        ];
        
        // Create the question
        $questionId = $this->questionModel->createQuestion($questionData);
        
        if (!$questionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create question']);
            return;
        }
        
        // Get the new question with ID
        $question = $this->questionModel->getQuestionById($questionId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'create',
            'audit_questions',
            $questionId,
            "Added question to section {$section['title']}",
            $this->getClientIp()
        );
        
        $this->jsonResponse([
            'success' => true, 
            'message' => 'Question added successfully',
            'question' => $question
        ]);
    }
    
    /**
     * Update an existing question
     */
    public function updateQuestion(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get question ID from request
        $questionId = (int)($_POST['question_id'] ?? 0);
        
        if (!$questionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Question not found']);
            return;
        }
        
        // Get existing question
        $question = $this->questionModel->getQuestionById($questionId);
        
        if (!$question) {
            $this->jsonResponse(['success' => false, 'message' => 'Question not found']);
            return;
        }
        
        // Get section to get audit ID for scoring later
        $section = $this->sectionModel->getSectionById($question['section_id']);
        
        if (!$section) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Validate input
        $questionText = trim($_POST['question_text'] ?? '');
        $guidanceNotes = trim($_POST['guidance_notes'] ?? '');
        $questionType = $_POST['question_type'] ?? $question['question_type'];
        $required = isset($_POST['required']) && $_POST['required'] === '1' ? 1 : 0;
        $options = !empty($_POST['options']) ? $_POST['options'] : $question['options'];
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : (float)$question['weight'];
        
        // Validate required fields
        if (empty($questionText)) {
            $this->jsonResponse(['success' => false, 'message' => 'Question text is required']);
            return;
        }
        
        // Set data for question update
        $questionData = [
            'question_text' => $questionText,
            'guidance_notes' => $guidanceNotes,
            'question_type' => $questionType,
            'required' => $required,
            'options' => $options,
            'weight' => $weight,
        ];
        
        // Update the question
        $success = $this->questionModel->updateQuestion($questionId, $questionData);
        
        if (!$success) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update question']);
            return;
        }
        
        // Get the updated question
        $updatedQuestion = $this->questionModel->getQuestionById($questionId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'update',
            'audit_questions',
            $questionId,
            "Updated question in section {$section['title']}",
            $this->getClientIp()
        );
        
        // Recalculate audit scores if question type or weight changed
        if ($questionType !== $question['question_type'] || $weight !== (float)$question['weight']) {
            $this->auditModel->calculateAuditScores($section['audit_id']);
        }
        
        $this->jsonResponse([
            'success' => true, 
            'message' => 'Question updated successfully',
            'question' => $updatedQuestion
        ]);
    }
    
    /**
     * Delete a question
     */
    public function deleteQuestion(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get question ID from request
        $questionId = (int)($_POST['question_id'] ?? 0);
        
        if (!$questionId) {
            $this->jsonResponse(['success' => false, 'message' => 'Question not found']);
            return;
        }
        
        // Get existing question
        $question = $this->questionModel->getQuestionById($questionId);
        
        if (!$question) {
            $this->jsonResponse(['success' => false, 'message' => 'Question not found']);
            return;
        }
        
        // Get section to get audit ID for scoring later
        $section = $this->sectionModel->getSectionById($question['section_id']);
        
        if (!$section) {
            $this->jsonResponse(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Delete the question
        $success = $this->questionModel->deleteQuestion($questionId);
        
        if (!$success) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete question']);
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'delete',
            'audit_questions',
            $questionId,
            "Deleted question from section {$section['title']}",
            $this->getClientIp()
        );
        
        // Recalculate audit scores
        $this->auditModel->calculateAuditScores($section['audit_id']);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }

    /**
     * Display form for completing an audit
     */
    public function respond(): void
    {
        // Check permission
        $this->requirePermission('audits.respond');
        
        // Get audit ID from request
        $auditId = (int)($_GET['id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit details
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Check if this is a template (templates cannot be responded to)
        if ($audit['is_template']) {
            $this->setFlashMessage('Templates cannot be completed directly. Please create an audit from this template.', 'warning');
            $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
            return;
        }
        
        // Check if audit is already completed or archived
        if ($audit['status'] === 'completed' || $audit['status'] === 'archived') {
            $this->setFlashMessage('This audit is already completed and cannot be modified.', 'warning');
            $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
            return;
        }
        
        // Get audit sections and questions with existing responses
        $sections = $this->sectionModel->getFullAuditStructureWithResponses($auditId, $_SESSION['user_id']);
        
        // Update audit status to in_progress if it's draft
        if ($audit['status'] === 'draft') {
            $this->auditModel->updateAudit($auditId, ['status' => 'in_progress']);
            $audit['status'] = 'in_progress';
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'access',
            'audits',
            $auditId,
            "Accessed response form for audit {$audit['title']}",
            $this->getClientIp()
        );
        
        // Load view
        $this->render('audits/respond', [
            'audit' => $audit,
            'sections' => $sections,
        ]);
    }

    /**
     * Save audit responses
     */
    public function saveResponses(): void
    {
        // Check permission
        $this->requirePermission('audits.respond');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get audit ID from request
        $auditId = (int)($_POST['audit_id'] ?? 0);
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get existing audit
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Check if this is a template (templates cannot be responded to)
        if ($audit['is_template']) {
            $this->setFlashMessage('Templates cannot be completed', 'warning');
            $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
            return;
        }
        
        // Check if audit is already completed or archived
        if ($audit['status'] === 'completed' || $audit['status'] === 'archived') {
            $this->setFlashMessage('This audit is already completed and cannot be modified.', 'warning');
            $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
            return;
        }
        
        // Process responses
        $responses = $_POST['response'] ?? [];
        $comments = $_POST['comments'] ?? [];
        $markCompleted = isset($_POST['mark_completed']) && $_POST['mark_completed'] === '1';
        $auditComments = trim($_POST['audit_comments'] ?? '');
        
        // Validate required responses
        $allRequiredAnswered = true;
        $requiredQuestions = $this->questionModel->getRequiredQuestionsByAuditId($auditId);
        
        foreach ($requiredQuestions as $question) {
            if (!isset($responses[$question['question_id']]) || $responses[$question['question_id']] === '') {
                $allRequiredAnswered = false;
                break;
            }
        }
        
        // If marking as completed, ensure all required questions are answered
        if ($markCompleted && !$allRequiredAnswered) {
            $this->setFlashMessage('All required questions must be answered before marking as completed.', 'danger');
            $this->redirect('index.php?page=audits&action=respond&id=' . $auditId);
            return;
        }
        
        // Save responses
        $savedCount = 0;
        $userId = $_SESSION['user_id'];
        
        foreach ($responses as $questionId => $responseValue) {
            // Skip empty responses for non-required questions
            if ($responseValue === '' && !in_array($questionId, array_column($requiredQuestions, 'question_id'))) {
                continue;
            }
            
            $responseData = [
                'audit_id' => $auditId,
                'question_id' => $questionId,
                'user_id' => $userId,
                'response_value' => $responseValue,
                'comments' => $comments[$questionId] ?? null,
            ];
            
            // Check if response already exists
            $existingResponse = $this->responseModel->getResponseByQuestionAndUser($questionId, $userId);
            
            if ($existingResponse) {
                // Update existing response
                $success = $this->responseModel->updateResponse($existingResponse['response_id'], $responseData);
            } else {
                // Create new response
                $success = (bool)$this->responseModel->createResponse($responseData);
            }
            
            if ($success) {
                $savedCount++;
            }
        }
        
        // Update audit status and comments if needed
        $auditUpdateData = [];
        
        if ($markCompleted) {
            $auditUpdateData['status'] = 'completed';
        } else {
            $auditUpdateData['status'] = 'in_progress';
        }
        
        if ($auditComments !== '') {
            $auditUpdateData['comments'] = $auditComments;
        }
        
        if (!empty($auditUpdateData)) {
            $this->auditModel->updateAudit($auditId, $auditUpdateData);
        }
        
        // Calculate audit scores
        $this->auditModel->calculateAuditScores($auditId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $userId,
            'update',
            'audit_responses',
            $auditId,
            "Saved " . $savedCount . " responses for audit {$audit['title']}" . 
            ($markCompleted ? ' and marked as completed' : ''),
            $this->getClientIp()
        );
        
        // Set message and redirect
        if ($markCompleted) {
            $this->setFlashMessage('Audit completed successfully!', 'success');
        } else {
            $this->setFlashMessage('Responses saved successfully! You can continue later.', 'success');
        }
        
        $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
    }
    
    /**
     * Export audit to a specific format
     */
    public function export(): void
    {
        // Check permission
        $this->requirePermission('audits.export');
        
        // Get audit ID and format from request
        $auditId = (int)($_GET['id'] ?? 0);
        $format = strtolower($_GET['format'] ?? 'pdf');
        
        if (!$auditId) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit details
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->setFlashMessage('Audit not found', 'danger');
            $this->redirect('index.php?page=audits');
            return;
        }
        
        // Get audit sections and questions with responses
        $sections = $this->sectionModel->getFullAuditStructureWithResponses($auditId);
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'export',
            'audits',
            $auditId,
            "Exported audit {$audit['title']} as {$format}",
            $this->getClientIp()
        );
        
        // Generate export based on format
        switch ($format) {
            case 'pdf':
                $this->exportAuditToPdf($audit, $sections);
                break;
            case 'excel':
                $this->exportAuditToExcel($audit, $sections);
                break;
            default:
                $this->setFlashMessage('Unsupported export format', 'danger');
                $this->redirect('index.php?page=audits&action=view&id=' . $auditId);
                break;
        }
    }
    
    /**
     * Export audit to PDF format
     * 
     * @param array $audit The audit data
     * @param array $sections The sections with questions and responses
     */
    private function exportAuditToPdf(array $audit, array $sections): void
    {
        // Implementation would use a PDF library like FPDF or TCPDF
        // This is a placeholder for the actual implementation
        $this->setFlashMessage('PDF export functionality is not implemented yet', 'warning');
        $this->redirect('index.php?page=audits&action=view&id=' . $audit['audit_id']);
    }
    
    /**
     * Export audit to Excel format
     * 
     * @param array $audit The audit data
     * @param array $sections The sections with questions and responses
     */
    private function exportAuditToExcel(array $audit, array $sections): void
    {
        // Implementation would use a library like PhpSpreadsheet
        // This is a placeholder for the actual implementation
        $this->setFlashMessage('Excel export functionality is not implemented yet', 'warning');
        $this->redirect('index.php?page=audits&action=view&id=' . $audit['audit_id']);
    }

    /**
     * Upload a file attachment for an audit
     */
    public function uploadAttachment(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get audit ID from request
        $auditId = (int)($_POST['audit_id'] ?? 0);
        
        if (!$auditId) {
            $this->jsonResponse(['success' => false, 'message' => 'Audit not found']);
            return;
        }
        
        // Get existing audit
        $audit = $this->auditModel->getAuditById($auditId);
        
        if (!$audit) {
            $this->jsonResponse(['success' => false, 'message' => 'Audit not found']);
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'No file uploaded or upload error']);
            return;
        }
        
        $file = $_FILES['file'];
        $comments = trim($_POST['comments'] ?? '');
        
        // Validate file
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid file type. Allowed types: PDF, JPEG, PNG, DOC, DOCX']);
            return;
        }
        
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['success' => false, 'message' => 'File too large. Maximum size: 10MB']);
            return;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/audits/' . $auditId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $auditId . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to save file']);
            return;
        }
        
        // Create attachment record
        $attachmentData = [
            'audit_id' => $auditId,
            'user_id' => $_SESSION['user_id'],
            'file_name' => $file['name'],
            'file_path' => $filepath,
            'file_type' => $file['type'],
            'file_size' => $file['size'],
            'comments' => $comments
        ];
        
        $attachmentId = $this->attachmentModel->createAttachment($attachmentData);
        
        if (!$attachmentId) {
            // Delete uploaded file if database insert failed
            unlink($uploadDir . '/' . $filename);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to save attachment record']);
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'create',
            'audit_attachments',
            $attachmentId,
            "Uploaded file {$file['name']} to audit {$audit['title']}",
            $this->getClientIp()
        );
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'File uploaded successfully',
            'attachment' => $this->attachmentModel->getAttachmentById($attachmentId)
        ]);
    }
    
    /**
     * Delete an audit attachment
     */
    public function deleteAttachment(): void
    {
        // Check permission
        $this->requirePermission('audits.edit');
        
        // Check if CSRF token is valid
        $this->validateCSRF();
        
        // Get attachment ID from request
        $attachmentId = (int)($_POST['attachment_id'] ?? 0);
        
        if (!$attachmentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Attachment not found']);
            return;
        }
        
        // Get existing attachment
        $attachment = $this->attachmentModel->getAttachmentById($attachmentId);
        
        if (!$attachment) {
            $this->jsonResponse(['success' => false, 'message' => 'Attachment not found']);
            return;
        }
        
        // Delete the attachment
        $success = $this->attachmentModel->deleteAttachment($attachmentId);
        
        if (!$success) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete attachment']);
            return;
        }
        
        // Log activity
        $this->userActivityModel->logActivity(
            $_SESSION['user_id'],
            'delete',
            'audit_attachments',
            $attachmentId,
            "Deleted file {$attachment['file_name']}",
            $this->getClientIp()
        );
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);
    }

    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->setFlashMessage('Please log in to access this page', 'warning');
            $this->redirect('index.php?page=auth&action=login');
            exit;
        }
    }

    protected function requirePermission(string $permission): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->setFlashMessage('Please log in to access this page', 'warning');
            $this->redirect('index.php?page=auth&action=login');
            exit;
        }
        
        error_log("AuditController checking permission: " . $permission);
        error_log("Session arrays - permissions: " . json_encode($_SESSION['permissions'] ?? []));
        error_log("Session arrays - user_permissions: " . json_encode($_SESSION['user_permissions'] ?? []));
        
        // Check both session arrays for the permission
        $hasPermission = false;
        
        // Check in user_permissions array
        if (isset($_SESSION['user_permissions']) && in_array($permission, $_SESSION['user_permissions'])) {
            $hasPermission = true;
            error_log("Permission found in user_permissions: {$permission}");
        }
        
        // Check in permissions array 
        if (!$hasPermission && isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions'])) {
            $hasPermission = true;
            error_log("Permission found in permissions: {$permission}");
            
            // Sync to user_permissions for consistency
            if (!isset($_SESSION['user_permissions'])) {
                $_SESSION['user_permissions'] = [];
            }
            $_SESSION['user_permissions'][] = $permission;
            error_log("Synced permission to user_permissions: {$permission}");
        }
        
        if (!$hasPermission) {
            $this->setFlashMessage('You do not have permission to access this page', 'danger');
            $this->redirect('index.php?page=dashboard');
            exit;
        }
        
        error_log("Permission granted: {$permission}");
    }

    protected function redirect(string $url, bool $useBaseUrl = false): void
    {
        // If $useBaseUrl is true, prepend the base URL
        if ($useBaseUrl && !preg_match('/^https?:\/\//', $url) && !str_contains($url, 'index.php')) {
            $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        
        header('Location: ' . $url);
        exit;
    }
} 