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
        $this->fromEmail = 'onboarding@resend.dev';  // Using Resend's default verified domain
    }
    
    /**
     * Send an email using Resend API
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $html HTML content of the email
     * @return array Response with status and message
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
        
        if (curl_errno($ch)) {
            $error = 'Curl error: ' . curl_error($ch);
            error_log($error);
            curl_close($ch);
            return ['success' => false, 'message' => $error];
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            $error = isset($responseData['message']) ? $responseData['message'] : 'Unknown error occurred';
            error_log('Email sending failed: ' . $error);
            return ['success' => false, 'message' => $error];
        }
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
                <a href='https://acp.hadethealthcare.co.uk/index.php?page=action_plans&action=view&id={$actionPlan['action_plan_id']}' 
                   style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>
                    View Action Plan
                </a>
            </p>
            <p>Best regards,<br>Action Plan System</p>
        ";
        
        return $this->send($assignee['email'], $subject, $html);
    }

    /**
     * Test the email service
     * 
     * @param string $testEmail Email address to send test message to
     * @return array Response with status and message
     */
    public function testEmailService($testEmail) {
        $subject = "Test Email from Action Plan System";
        $html = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #0d6efd;'>Test Email</h2>
                    <p>This is a test email from your Action Plan Management System.</p>
                    <p>If you received this email, it means the email service is working correctly.</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p>Time sent: " . date('Y-m-d H:i:s') . "</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return $this->send($testEmail, $subject, $html);
    }
} 