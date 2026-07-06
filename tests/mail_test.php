<?php
// SMTP Debug Test — Remove this file after testing!
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . '/../config/mail.php';

$host     = $config['smtp_host'];
$port     = $config['smtp_port'];
$username = $config['smtp_username'];
$password = $config['smtp_password'];
$to       = isset($_GET['to']) ? $_GET['to'] : $username; // send to self by default

echo "<pre>";
echo "=== SMTP Debug Test ===\n";
echo "Host    : $host\n";
echo "Port    : $port\n";
echo "User    : $username\n";
echo "Pass    : " . str_repeat('*', strlen($password)) . "\n";
echo "Send To : $to\n\n";

// Step 1: Test socket connection
echo "--- Step 1: Connecting to $host:$port ---\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 15);
if (!$socket) {
    echo "FAILED: Cannot open socket!\n";
    echo "Error $errno: $errstr\n";
    echo "\n>>> Port 465 is BLOCKED by server firewall. Trying port 587 with STARTTLS...\n\n";

    // Try port 587
    echo "--- Step 1b: Connecting to smtp.gmail.com:587 ---\n";
    $socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 15);
    if (!$socket) {
        echo "FAILED: Port 587 also blocked!\n";
        echo "Error $errno: $errstr\n";
        echo "\nSMTP is completely blocked on this server. Need to use a different mail method.\n";
        exit;
    }
    echo "SUCCESS: Connected on port 587\n\n";
    $useStartTLS = true;
} else {
    echo "SUCCESS: Connected on port $port\n\n";
    $useStartTLS = false;
}

stream_set_timeout($socket, 15);

$read = function($s) {
    $buf = '';
    while ($line = fgets($s, 512)) {
        $buf .= $line;
        echo "  S: " . $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    return $buf;
};

$write = function($s, $cmd) {
    $display = (strpos($cmd, base64_encode('')) !== false) ? '[BASE64_DATA]' : trim($cmd);
    echo "  C: " . $display . "\n";
    fwrite($s, $cmd);
};

// Step 2: Read greeting
echo "--- Step 2: Server Greeting ---\n";
$read($socket);

// Step 3: EHLO
echo "\n--- Step 3: EHLO ---\n";
$write($socket, "EHLO localhost\r\n");
$read($socket);

// Step 4: STARTTLS if port 587
if ($useStartTLS) {
    echo "\n--- Step 4: STARTTLS ---\n";
    $write($socket, "STARTTLS\r\n");
    $read($socket);
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $write($socket, "EHLO localhost\r\n");
    $read($socket);
}

// Step 5: AUTH LOGIN
echo "\n--- Step 5: AUTH LOGIN ---\n";
$write($socket, "AUTH LOGIN\r\n");
$read($socket);

echo "\n--- Step 6: Username ---\n";
$write($socket, base64_encode($username) . "\r\n");
$read($socket);

echo "\n--- Step 7: Password ---\n";
$write($socket, base64_encode($password) . "\r\n");
$authResp = $read($socket);

if (strpos($authResp, '235') === false) {
    echo "\nAUTH FAILED! Response did not contain 235.\n";
    echo "Possible reasons:\n";
    echo "  1. Wrong email or app password\n";
    echo "  2. App password not generated for this account\n";
    echo "  3. 2-Step Verification not enabled on the account\n";
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    exit;
}

echo "\nAUTH SUCCESS!\n\n";

// Step 8: Send test email
echo "--- Step 8: Sending Test Email to $to ---\n";
$write($socket, "MAIL FROM:<$username>\r\n");
$read($socket);

$write($socket, "RCPT TO:<$to>\r\n");
$read($socket);

$write($socket, "DATA\r\n");
$read($socket);

$msg  = "MIME-Version: 1.0\r\n";
$msg .= "Content-Type: text/plain; charset=utf-8\r\n";
$msg .= "From: Mr.Genie Perfumes <$username>\r\n";
$msg .= "To: <$to>\r\n";
$msg .= "Subject: Test OTP Email - Mr.Genie Perfumes\r\n\r\n";
$msg .= "This is a test email from mrgenieperfumes.in SMTP debug.\n\nIf you see this, email is working!\n\n-- Mr.Genie Perfumes\r\n.\r\n";

$write($socket, $msg);
$dataResp = $read($socket);

if (strpos($dataResp, '250') !== false) {
    echo "\nEMAIL SENT SUCCESSFULLY!\n";
} else {
    echo "\nEMAIL SEND FAILED!\n";
}

$write($socket, "QUIT\r\n");
fclose($socket);

echo "\n=== Test Complete ===\n";
echo "</pre>";
