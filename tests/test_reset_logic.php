<?php
// Test Reset Logic — Remove after testing!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
define('BASE_URL', '');


$email = 'satyadigital7@gmail.com';
echo "<pre>";
echo "=== Testing Reset Logic for: $email ===\n";

try {
    $db = getDB();
    echo "Connected to database.\n";

    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "User NOT found in database. Creating temporary user for testing...\n";
        $db->prepare("INSERT INTO users (full_name, email, mobile, password) VALUES (?, ?, ?, ?)")
           ->execute(['Satya Test', $email, '7661885757', password_hash('12345678', PASSWORD_BCRYPT)]);
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        echo "Temporary user created.\n";
    }

    echo "User found: " . print_r($user, true) . "\n";

    echo "Deleting existing reset tokens...\n";
    $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    echo "Generating token...\n";
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 1800);

    echo "Inserting token into password_resets table...\n";
    $ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $ins->execute([$email, $token, $expiresAt]);
    echo "Token inserted successfully.\n";

    $resetUrl = 'http://mrgenieperfumes.in' . BASE_URL . '/reset-password?token=' . $token;
    $subject = "Reset Your Password — Mr.Genie Perfumes";
    $message = "Hello " . $user['full_name'] . ",\n\nTest Reset URL:\n$resetUrl";

    echo "Sending email via sendMail()...\n";
    $mailResult = sendMail($email, $subject, $message);
    echo "Mail result: " . ($mailResult ? "SUCCESS" : "FAILED") . "\n";

} catch (Exception $e) {
    echo "❌ EXCEPTION THROWN: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "=== Test Complete ===\n";
echo "</pre>";
