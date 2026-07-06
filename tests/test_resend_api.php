<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/mail.php';

echo "<pre>";
echo "=== Resend API Integration Test ===\n";
echo "From Name    : " . ($config['from_name'] ?? '') . "\n";
echo "From Email   : " . ($config['from_email'] ?? '') . "\n";
echo "Resend Key   : " . (empty($config['resend_api_key']) ? "NOT CONFIGURED (Empty)" : "CONFIGURED (" . substr($config['resend_api_key'], 0, 6) . "..." . ")") . "\n";

$to = isset($_GET['to']) ? $_GET['to'] : ($config['from_email'] ?? '');
echo "To Email     : " . $to . "\n\n";

if (empty($config['resend_api_key'])) {
    echo "❌ Error: Please configure 'resend_api_key' in config/mail.php before running this test.\n";
    exit;
}

echo "Sending test email via Resend...\n";
$subject = "Resend Integration Test - Mr.Genie Perfumes";
$message = "Hello,\n\nIf you are reading this email, the Resend.com API integration works successfully!\n\nRegards,\nMr.Genie Perfumes";

$result = sendMailResendAPI($to, $subject, $message, $config);

if ($result) {
    echo "✅ SUCCESS: The email has been sent successfully!\n";
} else {
    echo "❌ FAILED: Check your key and Resend sender domain verification. You can check config/otp_log.txt for detailed error response.\n";
}

echo "=== Test Complete ===\n";
echo "</pre>";
