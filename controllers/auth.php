<?php
/**
 * Auth Controller
 * 
 * This controller handles user authentication (login, registration, logout).
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Show the login form
     */
    public function showLoginForm() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $this->render('auth/login');
    }
    
    /**
     * Process the login form
     */
    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $errors = [];
        
        // Validate form data
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $this->validateRequired($username, 'Username', $errors);
        $this->validateRequired($password, 'Password', $errors);
        
        if (empty($errors)) {
            // Check if user exists
            $user = $this->userModel->findByUsername($username);
            
            if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'is_management_staff' => $user['is_management_staff']
                ];
                
                $this->setFlashMessage('success', 'Login successful. Welcome back!');
                $this->redirect('index.php?page=dashboard');
            } else {
                $errors[] = 'Invalid username or password.';
            }
        }
        
        // If we get here, there were errors
        $this->render('auth/login', [
            'errors' => $errors,
            'username' => $username
        ]);
    }
    
    /**
     * Show the registration form
     */
    public function showRegistrationForm() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $this->render('auth/register');
    }
    
    /**
     * Process the registration form
     */
    public function register() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $errors = [];
        
        // Validate form data
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = $this->sanitize($_POST['email'] ?? '');
        $isManagementStaff = isset($_POST['is_management_staff']) ? true : false;
        
        $this->validateRequired($username, 'Username', $errors);
        $this->validateRequired($password, 'Password', $errors);
        $this->validateRequired($confirmPassword, 'Confirm Password', $errors);
        $this->validateRequired($email, 'Email', $errors);
        $this->validateEmail($email, $errors);
        
        // Check if passwords match
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Check if username already exists
        if ($this->userModel->findByUsername($username)) {
            $errors[] = 'Username already exists.';
        }
        
        // Check if email already exists
        if ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already exists.';
        }
        
        if (empty($errors)) {
            // Create the user
            $userId = $this->userModel->create($username, $password, $email, $isManagementStaff);
            
            if ($userId) {
                // Registration successful
                $this->setFlashMessage('success', 'Registration successful. Please log in.');
                $this->redirect('index.php?page=login');
            } else {
                $errors[] = 'An error occurred during registration. Please try again.';
            }
        }
        
        // If we get here, there were errors
        $this->render('auth/register', [
            'errors' => $errors,
            'username' => $username,
            'email' => $email,
            'is_management_staff' => $isManagementStaff
        ]);
    }
    
    /**
     * Log the user out
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        $this->redirect('index.php?page=login');
    }
} 