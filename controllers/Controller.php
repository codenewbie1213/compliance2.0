<?php
/**
 * Base Controller Class
 * 
 * This class provides common functionality for all controllers.
 */

class Controller {
    /**
     * Redirect to a URL
     * 
     * @param string $url The URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    /**
     * Check if the user is logged in
     * 
     * @return bool True if the user is logged in, false otherwise
     */
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Require the user to be logged in
     * 
     * @return void
     */
    protected function requireLogin() {
        if (!$this->isLoggedIn()) {
            $this->redirect('index.php?page=login');
        }
    }
    
    /**
     * Get the current user ID
     * 
     * @return int|null The current user ID or null if not logged in
     */
    protected function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get the current user data
     * 
     * @return array|null The current user data or null if not logged in
     */
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Check if the current user is management staff
     * 
     * @return bool True if the current user is management staff, false otherwise
     */
    protected function isManagementStaff() {
        $user = $this->getCurrentUser();
        return $user && $user['is_management_staff'];
    }
    
    /**
     * Set a flash message
     * 
     * @param string $type The type of message (success, error, info, warning)
     * @param string $message The message text
     * @return void
     */
    protected function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get and clear the flash message
     * 
     * @return array|null The flash message or null if none
     */
    protected function getFlashMessage() {
        $message = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    /**
     * Sanitize input data
     * 
     * @param string $data The data to sanitize
     * @return string The sanitized data
     */
    protected function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Validate a required field
     * 
     * @param string $field The field to validate
     * @param string $fieldName The name of the field for error messages
     * @param array $errors The errors array to add to
     * @return void
     */
    protected function validateRequired($field, $fieldName, &$errors) {
        if (empty($field)) {
            $errors[] = "$fieldName is required.";
        }
    }
    
    /**
     * Validate an email field
     * 
     * @param string $email The email to validate
     * @param array $errors The errors array to add to
     * @return void
     */
    protected function validateEmail($email, &$errors) {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
    }
    
    /**
     * Render a view
     * 
     * @param string $view The view to render
     * @param array $data The data to pass to the view
     * @return void
     */
    protected function render($view, $data = []) {
        // Extract the data to make it available in the view
        extract($data);
        
        // Get the flash message
        $flashMessage = $this->getFlashMessage();
        
        // Include the view file
        require_once __DIR__ . "/../views/{$view}.php";
    }
    
    /**
     * Send an email
     * 
     * @param string $to The recipient email
     * @param string $subject The email subject
     * @param string $message The email message
     * @param string $headers Additional headers
     * @return bool True if the email was sent, false otherwise
     */
    protected function sendEmail($to, $subject, $message, $headers = '') {
        // Set default headers if none provided
        if (empty($headers)) {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Action Plan Management <noreply@example.com>" . "\r\n";
        }
        
        // Send the email
        return mail($to, $subject, $message, $headers);
    }
} 