<?php
require_once __DIR__ . '/../models/User.php';

class UsersController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $password = $_POST['password'];
            
            if (empty($username) || empty($password)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please provide both username and password.'];
                header('Location: index.php?page=login');
                exit;
            }
            
            $user = $this->userModel->findByUsername($username);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'is_management_staff' => $user['is_management_staff']
                ];
                
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Welcome back!'];
                header('Location: index.php?page=dashboard');
                exit;
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid username or password.'];
                header('Location: index.php?page=login');
                exit;
            }
        }
        
        include __DIR__ . '/../views/users/login.php';
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $isManagementStaff = isset($_POST['is_management_staff']) ? 1 : 0;
            
            $errors = [];
            
            if (empty($username)) {
                $errors[] = 'Username is required.';
            }
            if (empty($password)) {
                $errors[] = 'Password is required.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            if (empty($firstName)) {
                $errors[] = 'First name is required.';
            }
            if (empty($lastName)) {
                $errors[] = 'Last name is required.';
            }
            
            // Check if username already exists
            if ($this->userModel->findByUsername($username)) {
                $errors[] = 'Username already exists.';
            }
            
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $userId = $this->userModel->create([
                    'username' => $username,
                    'password' => $hashedPassword,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'is_management_staff' => $isManagementStaff
                ]);
                
                if ($userId) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Registration successful! Please log in.'];
                    header('Location: index.php?page=login');
                    exit;
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Registration failed. Please try again.'];
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            }
        }
        
        include __DIR__ . '/../views/users/register.php';
    }
    
    /**
     * Get all users (for dropdowns, etc.)
     */
    public function getAllUsers() {
        return $this->userModel->findAll();
    }
    
    /**
     * Get user details
     */
    public function getUserDetails($userId) {
        return $this->userModel->findById($userId);
    }
} 