<?php
/**
 * Comments Controller
 * 
 * This controller handles comment-related operations.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/ActionPlan.php';

class CommentsController extends Controller {
    private $commentModel;
    private $actionPlanModel;
    
    public function __construct() {
        $this->commentModel = new Comment();
        $this->actionPlanModel = new ActionPlan();
    }
    
    /**
     * Add a comment to an action plan
     */
    public function add() {
        // Require login
        $this->requireLogin();
        
        $actionPlanId = intval($_POST['action_plan_id'] ?? 0);
        $commentText = $this->sanitize($_POST['comment_text'] ?? '');
        
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
        
        // Check if the comment text is empty
        if (empty($commentText)) {
            $this->setFlashMessage('error', 'Comment text cannot be empty.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Add the comment
        $userId = $this->getCurrentUserId();
        $commentId = $this->commentModel->create($actionPlanId, $userId, $commentText);
        
        if ($commentId) {
            $this->setFlashMessage('success', 'Comment added successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while adding the comment. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
    }
    
    /**
     * Delete a comment
     */
    public function delete() {
        // Require login
        $this->requireLogin();
        
        $commentId = intval($_GET['id'] ?? 0);
        $actionPlanId = intval($_GET['action_plan_id'] ?? 0);
        
        if ($commentId <= 0 || $actionPlanId <= 0) {
            $this->setFlashMessage('error', 'Invalid comment or action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the comment
        $comment = $this->commentModel->findById($commentId);
        
        if (!$comment) {
            $this->setFlashMessage('error', 'Comment not found.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check if the user is the comment author
        $currentUserId = $this->getCurrentUserId();
        if ($comment['user_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to delete this comment.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Delete the comment
        $success = $this->commentModel->deleteById($commentId);
        
        if ($success) {
            $this->setFlashMessage('success', 'Comment deleted successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while deleting the comment. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
    }
    
    /**
     * Edit a comment
     */
    public function edit() {
        // Require login
        $this->requireLogin();
        
        $commentId = intval($_GET['id'] ?? 0);
        $actionPlanId = intval($_GET['action_plan_id'] ?? 0);
        
        if ($commentId <= 0 || $actionPlanId <= 0) {
            $this->setFlashMessage('error', 'Invalid comment or action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the comment
        $comment = $this->commentModel->findById($commentId);
        
        if (!$comment) {
            $this->setFlashMessage('error', 'Comment not found.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check if the user is the comment author
        $currentUserId = $this->getCurrentUserId();
        if ($comment['user_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to edit this comment.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Get the action plan
        $actionPlan = $this->actionPlanModel->getDetails($actionPlanId);
        
        $this->render('action_plans/edit_comment', [
            'comment' => $comment,
            'actionPlan' => $actionPlan
        ]);
    }
    
    /**
     * Update a comment
     */
    public function update() {
        // Require login
        $this->requireLogin();
        
        $commentId = intval($_POST['comment_id'] ?? 0);
        $actionPlanId = intval($_POST['action_plan_id'] ?? 0);
        $commentText = $this->sanitize($_POST['comment_text'] ?? '');
        
        if ($commentId <= 0 || $actionPlanId <= 0) {
            $this->setFlashMessage('error', 'Invalid comment or action plan ID.');
            $this->redirect('index.php?page=action_plans');
        }
        
        // Get the comment
        $comment = $this->commentModel->findById($commentId);
        
        if (!$comment) {
            $this->setFlashMessage('error', 'Comment not found.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check if the user is the comment author
        $currentUserId = $this->getCurrentUserId();
        if ($comment['user_id'] != $currentUserId) {
            $this->setFlashMessage('error', 'You do not have permission to edit this comment.');
            $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
        }
        
        // Check if the comment text is empty
        if (empty($commentText)) {
            $this->setFlashMessage('error', 'Comment text cannot be empty.');
            $this->redirect('index.php?page=comments&action=edit&id=' . $commentId . '&action_plan_id=' . $actionPlanId);
        }
        
        // Update the comment
        $success = $this->commentModel->update($commentId, $commentText);
        
        if ($success) {
            $this->setFlashMessage('success', 'Comment updated successfully.');
        } else {
            $this->setFlashMessage('error', 'An error occurred while updating the comment. Please try again.');
        }
        
        $this->redirect('index.php?page=action_plans&action=view&id=' . $actionPlanId);
    }
} 