<?php
require_once __DIR__ . '/services/MailService.php';

// Test email address
$testEmail = 'oladapo1213@gmail.com';  // Email associated with Resend account

// Create mail service instance
$mailService = new MailService();

// Run the test
$result = $mailService->testEmailService($testEmail);

// Output the result
echo "Test Results:\n";
echo "-------------\n";
echo "Status: " . ($result['success'] ? "Success ✅" : "Failed ❌") . "\n";
echo "Message: " . $result['message'] . "\n";
echo "Test Email: " . $testEmail . "\n\n";

if ($result['success']) {
    echo "Please check your inbox (and spam folder) to confirm receipt.\n";
} else {
    echo "Troubleshooting Tips:\n";
    echo "1. Verify the Resend API key is correct\n";
    echo "2. Check if the 'from' email domain is verified in Resend\n";
    echo "3. Ensure the test email address is valid\n";
    echo "4. Check if curl is enabled in PHP\n";
} 