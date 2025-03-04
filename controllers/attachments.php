<?php
/**
 * Attachments Controller
 * 
 * This controller handles file attachment operations.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/ActionPlan.php';

class AttachmentsController extends Controller {
    private $attachmentModel;
    private $actionPlanModel;
    
    // Allowed file types
    private $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/plain' => 'txt'
    ];
    
    // Maximum file size (5MB)
    private $maxFileSize = 5 * 1024 * 1024;
    
    public function __construct() {
        $this->attachmentModel = new Attachment();
        $this->actionPlanModel = new ActionPlan();
    }
    
    /**
     * Upload a file attachment
     */
    public function upload() {
        // Require login
        $this->requireLogin();
        
        $actionPlanId = intval($_POST['action_plan_id'] ?? 0);
        
        if ($actionPlanId <= 0) {
            $this->setFlashMessage('error', 'Invalid action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($actionPlanId);
        
        if (!$actionPlan) {
            $this->setFlashMessage('error', 'Action plan not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if a file was uploaded
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->setFlashMessage('error', 'No file was uploaded.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        $file = $_FILES['attachment'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            
            $errorMessage = $errorMessages[$file['error']] ?? 'Unknown upload error.';
            $this->setFlashMessage('error', $errorMessage);
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->setFlashMessage('error', 'The file is too large. Maximum file size is 5MB.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($file['tmp_name']);
        
        if (!array_key_exists($fileType, $this->allowedTypes)) {
            $this->setFlashMessage('error', 'Invalid file type. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Generate a unique filename
        $extension = $this->allowedTypes[$fileType];
        $fileName = $file['name'];
        $uniqueName = uniqid() . '.' . $extension;
        $uploadDir = 'uploads/';
        $uploadPath = $uploadDir . $uniqueName;
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $this->setFlashMessage('error', 'Failed to move uploaded file.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Save the attachment in the database
        $userId = $this->getCurrentUserId();
        $attachmentId = $this->attachmentModel->create($actionPlanId, $userId, $uploadPath, $fileName);
        
        if ($attachmentId) {
            $this->setFlashMessage('success', 'File uploaded successfully.');
        } else {
            // Delete the uploaded file if the database insert failed
            unlink($uploadPath);
            $this->setFlashMessage('error', 'An error occurred while saving the attachment. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
    }
    
    /**
     * Download a file attachment
     */
    public function download() {
        // Require login
        $this->requireLogin();
        
        $attachmentId = intval($_GET['id'] ?? 0);
        
        if ($attachmentId <= 0) {
            $this->setFlashMessage('error', 'Invalid attachment ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the attachment
        $attachment = $this->attachmentModel->getById($attachmentId);
        
        if (!$attachment) {
            $this->setFlashMessage('error', 'Attachment not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the file exists
        $filePath = $attachment['file_path'];
        if (!file_exists($filePath)) {
            $this->setFlashMessage('error', 'File not found.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $attachment['action_plan_id']);
        }
        
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Read the file and output it
        readfile($filePath);
        exit;
    }
    
    /**
     * Delete a file attachment
     */
    public function delete() {
        // Require login
        $this->requireLogin();
        
        $attachmentId = intval($_GET['id'] ?? 0);
        
        if ($attachmentId <= 0) {
            $this->setFlashMessage('error', 'Invalid attachment ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the attachment
        $attachment = $this->attachmentModel->getById($attachmentId);
        
        if (!$attachment) {
            $this->setFlashMessage('error', 'Attachment not found.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Check if the user is the attachment uploader or the action plan creator
        $currentUserId = $this->getCurrentUserId();
        $actionPlan = $this->actionPlanModel->getDetails($attachment['action_plan_id']);
        
        if ($attachment['user_id'] != $currentUserId && $actionPlan['creator_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to delete this attachment.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $attachment['action_plan_id']);
        }
        
        // Delete the attachment
        $deletedAttachment = $this->attachmentModel->deleteWithFileInfo($attachmentId);
        
        if ($deletedAttachment) {
            // Delete the physical file
            $filePath = $deletedAttachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->setFlashMessage('success', 'Attachment deleted successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while deleting the attachment. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $attachment['action_plan_id']);
    }
} 