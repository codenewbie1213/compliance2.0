<?php
/**
 * Notifications Controller
 * 
 * This controller handles email notifications and reminders.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ActionPlan.php';
require_once __DIR__ . '/../models/User.php';

class NotificationsController extends Controller {
    private $actionPlanModel;
    private $userModel;
    
    public function __construct() {
        $this->actionPlanModel = new ActionPlan();
        $this->userModel = new User();
    }
    
    /**
     * Send a notification to a management staff member when they are assigned an action plan
     * 
     * @param array $actionPlan The action plan details
     * @return bool True if the notification was sent, false otherwise
     */
    public function sendActionPlanAssignmentNotification($actionPlan) {
        // Check if the assignee is management staff
        if (!$actionPlan['is_management_staff']) {
            return false;
        }
        
        $to = $actionPlan['assignee_email'];
        $subject = 'New Action Plan Assigned: ' . $actionPlan['name'];
        
        $message = '
        <html>
        <head>
            <title>New Action Plan Assigned</title>
        </head>
        <body>
            <h2>New Action Plan Assigned</h2>
            <p>Hello ' . htmlspecialchars($actionPlan['assignee_name']) . ',</p>
            <p>You have been assigned a new action plan:</p>
            <table border="0" cellpadding="5">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>' . htmlspecialchars($actionPlan['name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Description:</strong></td>
                    <td>' . htmlspecialchars($actionPlan['description']) . '</td>
                </tr>
                <tr>
                    <td><strong>Created By:</strong></td>
                    <td>' . htmlspecialchars($actionPlan['creator_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td>' . ($actionPlan['due_date'] ? date('F j, Y', strtotime($actionPlan['due_date'])) : 'No due date') . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>' . htmlspecialchars($actionPlan['status']) . '</td>
                </tr>
            </table>
            <p>Please log in to the Action Plan Management System to view and update this action plan.</p>
            <p>Thank you,<br>Action Plan Management System</p>
        </body>
        </html>
        ';
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    /**
     * Send due date reminders for action plans
     * 
     * @return array Statistics about the reminders sent
     */
    public function sendDueDateReminders() {
        $stats = [
            'due_soon' => 0,
            'overdue' => 0
        ];
        
        // Get action plans due soon (within the next day)
        $dueSoonPlans = $this->actionPlanModel->findDueSoon(1);
        
        foreach ($dueSoonPlans as $plan) {
            $to = $plan['assignee_email'];
            $subject = 'Action Plan Due Soon: ' . $plan['name'];
            
            $message = '
            <html>
            <head>
                <title>Action Plan Due Soon</title>
            </head>
            <body>
                <h2>Action Plan Due Soon</h2>
                <p>Hello ' . htmlspecialchars($plan['assignee_name']) . ',</p>
                <p>This is a reminder that the following action plan is due soon:</p>
                <table border="0" cellpadding="5">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>' . htmlspecialchars($plan['name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td>' . htmlspecialchars($plan['description']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Created By:</strong></td>
                        <td>' . htmlspecialchars($plan['creator_name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>' . date('F j, Y', strtotime($plan['due_date'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>' . htmlspecialchars($plan['status']) . '</td>
                    </tr>
                </table>
                <p>Please log in to the Action Plan Management System to update this action plan.</p>
                <p>Thank you,<br>Action Plan Management System</p>
            </body>
            </html>
            ';
            
            if ($this->sendEmail($to, $subject, $message)) {
                $stats['due_soon']++;
            }
        }
        
        // Get overdue action plans
        $overduePlans = $this->actionPlanModel->findOverdue();
        
        foreach ($overduePlans as $plan) {
            $to = $plan['assignee_email'];
            $subject = 'Action Plan Overdue: ' . $plan['name'];
            
            $message = '
            <html>
            <head>
                <title>Action Plan Overdue</title>
            </head>
            <body>
                <h2>Action Plan Overdue</h2>
                <p>Hello ' . htmlspecialchars($plan['assignee_name']) . ',</p>
                <p>This is a reminder that the following action plan is overdue:</p>
                <table border="0" cellpadding="5">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>' . htmlspecialchars($plan['name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td>' . htmlspecialchars($plan['description']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Created By:</strong></td>
                        <td>' . htmlspecialchars($plan['creator_name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>' . date('F j, Y', strtotime($plan['due_date'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>' . htmlspecialchars($plan['status']) . '</td>
                    </tr>
                </table>
                <p>Please log in to the Action Plan Management System to update this action plan as soon as possible.</p>
                <p>Thank you,<br>Action Plan Management System</p>
            </body>
            </html>
            ';
            
            if ($this->sendEmail($to, $subject, $message)) {
                $stats['overdue']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Run the reminders cron job
     * 
     * This method should be called by a cron job to send reminders
     */
    public function runRemindersCron() {
        // Send due date reminders
        $stats = $this->sendDueDateReminders();
        
        // Log the results
        $logMessage = date('Y-m-d H:i:s') . ' - Reminders sent: ' . 
                      $stats['due_soon'] . ' due soon, ' . 
                      $stats['overdue'] . ' overdue' . PHP_EOL;
        
        file_put_contents(__DIR__ . '/../logs/reminders.log', $logMessage, FILE_APPEND);
        
        // Output the results if called from the command line
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
} 