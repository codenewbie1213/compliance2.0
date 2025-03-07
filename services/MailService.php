<?php
/**
 * Mail Service
 * 
 * This class handles email sending using Resend API
 */

class MailService {
    private $apiKey;
    private $fromEmail;
    
    public function __construct() {
        $this->apiKey = 're_8a3rZxCN_6yamqqJ6HsmzDxzS5HiiKEnn';
        $this->fromEmail = 'notifications@actionplan.com';
    }
    
    /**
     * Send an email using Resend API
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $html HTML content of the email
     * @return bool True if email was sent successfully, false otherwise
     */
    public function send($to, $subject, $html) {
        $url = 'https://api.resend.com/emails';
        
        $data = [
            'from' => $this->fromEmail,
            'to' => $to,
            'subject' => $subject,
            'html' => $html
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Send action plan assignment notification
     * 
     * @param array $actionPlan Action plan details
     * @param array $assignee User assigned to the action plan
     * @param array $complaint Related complaint details
     * @return bool True if email was sent successfully, false otherwise
     */
    public function sendActionPlanAssignment($actionPlan, $assignee, $complaint) {
        $subject = "New Action Plan Assignment: {$actionPlan['name']}";
        
        $html = "
            <h2>New Action Plan Assignment</h2>
            <p>Hello {$assignee['first_name']},</p>
            <p>You have been assigned a new action plan:</p>
            <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                <h3 style='margin-top: 0;'>{$actionPlan['name']}</h3>
                <p><strong>Description:</strong> {$actionPlan['description']}</p>
                <p><strong>Due Date:</strong> " . date('F j, Y', strtotime($actionPlan['due_date'])) . "</p>
                <p><strong>Related Complaint:</strong> {$complaint['description']}</p>
            </div>
            <p>Please review and take necessary actions.</p>
            <p>
                <a href='http://localhost/action_plan/index.php?page=action_plans&action=view&id={$actionPlan['action_plan_id']}' 
                   style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>
                    View Action Plan
                </a>
            </p>
            <p>Best regards,<br>Action Plan System</p>
        ";
        
        return $this->send($assignee['email'], $subject, $html);
    }
} 