<?php
/**
 * Action Plan Management Application
 * 
 * This is the main entry point for the application.
 * It handles routing and initializes the application.
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create logs directory if it doesn't exist
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

// Load controllers
require_once 'controllers/auth.php';
require_once 'controllers/dashboard.php';
require_once 'controllers/action_plans.php';
require_once 'controllers/comments.php';
require_once 'controllers/attachments.php';
require_once 'controllers/notifications.php';

// Initialize controllers
$authController = new AuthController();
$dashboardController = new DashboardController();
$actionPlansController = new ActionPlansController();
$commentsController = new CommentsController();
$attachmentsController = new AttachmentsController();
$notificationsController = new NotificationsController();

// Get the requested page and action
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? 'index';

// Route the request to the appropriate controller and action
switch ($page) {
    case 'home':
        // If logged in, redirect to dashboard, otherwise to login
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
        } else {
            header('Location: index.php?page=login');
        }
        exit;
        
    case 'login':
        if ($action === 'process') {
            $authController->login();
        } else {
            $authController->showLoginForm();
        }
        break;
        
    case 'register':
        if ($action === 'process') {
            $authController->register();
        } else {
            $authController->showRegistrationForm();
        }
        break;
        
    case 'logout':
        $authController->logout();
        break;
        
    case 'dashboard':
        $dashboardController->index();
        break;
        
    case 'action_plans':
        switch ($action) {
            case 'index':
                $actionPlansController->index();
                break;
                
            case 'create':
                $actionPlansController->create();
                break;
                
            case 'store':
                $actionPlansController->store();
                break;
                
            case 'view':
                $actionPlansController->view();
                break;
                
            case 'edit':
                $actionPlansController->edit();
                break;
                
            case 'update':
                $actionPlansController->update();
                break;
                
            case 'delete':
                $actionPlansController->delete();
                break;
                
            case 'update_status':
                $actionPlansController->updateStatus();
                break;
                
            default:
                // 404 Not Found
                header('HTTP/1.0 404 Not Found');
                echo '404 Not Found';
                break;
        }
        break;
        
    case 'comments':
        switch ($action) {
            case 'add':
                $commentsController->add();
                break;
                
            case 'edit':
                $commentsController->edit();
                break;
                
            case 'update':
                $commentsController->update();
                break;
                
            case 'delete':
                $commentsController->delete();
                break;
                
            default:
                // 404 Not Found
                header('HTTP/1.0 404 Not Found');
                echo '404 Not Found';
                break;
        }
        break;
        
    case 'attachments':
        switch ($action) {
            case 'upload':
                $attachmentsController->upload();
                break;
                
            case 'download':
                $attachmentsController->download();
                break;
                
            case 'delete':
                $attachmentsController->delete();
                break;
                
            default:
                // 404 Not Found
                header('HTTP/1.0 404 Not Found');
                echo '404 Not Found';
                break;
        }
        break;
        
    case 'reminders':
        // This should be called by a cron job
        if (php_sapi_name() === 'cli' || isset($_GET['cron_key']) && $_GET['cron_key'] === 'your_secret_key_here') {
            $notificationsController->runRemindersCron();
        } else {
            // 403 Forbidden
            header('HTTP/1.0 403 Forbidden');
            echo '403 Forbidden';
        }
        break;
        
    default:
        // 404 Not Found
        header('HTTP/1.0 404 Not Found');
        echo '404 Not Found';
        break;
} 