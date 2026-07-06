<?php
// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment Detection
function isProduction() {
    // If the path contains xampp/htdocs, it's local XAMPP (even for CLI runs)
    if (strpos(str_replace('\\', '/', __DIR__), 'xampp/htdocs') !== false) {
        return false;
    }
    $host = $_SERVER['HTTP_HOST'] ?? gethostname();
    return !in_array($host, ['localhost', '127.0.0.1', '::1']);
}

// Database Connection function
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            if (isProduction()) {
                // Load credentials from env.php (not tracked by git)
                $envFile = __DIR__ . '/env.php';
                if (!file_exists($envFile)) {
                    die("Server config missing: env.php not found. Please contact the administrator.");
                }
                $env  = require $envFile;
                $dsn  = "mysql:host={$env['db_host']};dbname={$env['db_name']};charset=utf8";
                $user = $env['db_user'];
                $pass = $env['db_pass'];
            } else {
                // Local XAMPP credentials
                $dsn  = "mysql:host=localhost;dbname=elixir;charset=utf8";
                $user = "root";
                $pass = "";
            }
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            if (!isProduction()) {
                // Local: show helpful XAMPP-specific message
                die("<div style='font-family:sans-serif;padding:20px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin:20px'>
                    <h3 style='color:#856404'>⚠️ Local Database Error</h3>
                    <p><strong>Fix:</strong> Open XAMPP Control Panel and click <strong>Start</strong> next to <strong>MySQL</strong>.</p>
                    <p>Then visit <a href='http://localhost/phpmyadmin'>phpMyAdmin</a> and create database: <code>elixir</code></p>
                    <p>Then run: <a href='http://localhost/Perfume/database/seed.php'>seed.php</a> to populate data.</p>
                    <small style='color:#666'>Error: " . htmlspecialchars($e->getMessage()) . "</small>
                </div>");
            } else {
                die("Database connection failed. Please contact the administrator.");
            }
        }
    }
    return $pdo;
}



// XSS Protection helper
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// CSRF Token generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token verification
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get logged-in user details
function getLoggedInUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, mobile, address, city, state, pincode, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Admin role validation helper
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Flash notification helpers
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'text' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

// Mail Dispatcher Helper
function sendMail($to, $subject, $message) {
    $configFile = __DIR__ . '/mail.php';
    if (!file_exists($configFile)) {
        return false;
    }
    
    $config = require $configFile;
    
    // 1. Prioritize Resend HTTP API (secure, bypasses SMTP port blocks)
    if (!empty($config['resend_api_key'])) {
        return sendMailResendAPI($to, $subject, $message, $config);
    }
    
    // 2. Prioritize Brevo HTTP API (secure, bypasses SMTP port blocks)
    if (!empty($config['brevo_api_key'])) {
        return sendMailBrevoAPI($to, $subject, $message, $config);
    }
    
    // If no external SMTP credentials, use server's local Postfix/Exim (localhost:25)
    if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
        return sendMailLocalSMTP($to, $subject, $message, $config);
    }
    
    // Connect to secure SMTP server
    $host = $config['smtp_host'];
    $port = $config['smtp_port'];
    
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) {
        // Fallback to native mail if socket fails
        $headers = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        return @mail($to, $subject, $message, $headers);
    }
    
    // helper to read responses
    $read = function($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };
    
    $read($socket); // read greeting
    
    // HELO/EHLO
    fwrite($socket, "EHLO localhost\r\n");
    $read($socket);
    
    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    $read($socket);
    
    // Send Username
    fwrite($socket, base64_encode($config['smtp_username']) . "\r\n");
    $read($socket);
    
    // Send Password
    fwrite($socket, base64_encode($config['smtp_password']) . "\r\n");
    $authResponse = $read($socket);
    
    // Verify auth success (235 is SMTP Authentication Successful)
    if (strpos($authResponse, '235') === false) {
        fclose($socket);
        return false;
    }
    
    // MAIL FROM
    fwrite($socket, "MAIL FROM:<" . $config['smtp_username'] . ">\r\n");
    $read($socket);
    
    // RCPT TO
    fwrite($socket, "RCPT TO:<" . $to . ">\r\n");
    $read($socket);
    
    // DATA
    fwrite($socket, "DATA\r\n");
    $read($socket);
    
    // Format headers and body (UTF-8 support)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "To: <" . $to . ">\r\n";
    $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n\r\n";
    
    fwrite($socket, $headers . $message . "\r\n.\r\n");
    $read($socket);
    
    // QUIT
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}

// Local SMTP sender — uses server's Postfix/Exim on localhost:25 (no auth needed)
function sendMailLocalSMTP($to, $subject, $message, $config) {
    // Try localhost:25 (plain SMTP, no TLS/SSL needed for local relay)
    $socket = @fsockopen('127.0.0.1', 25, $errno, $errstr, 5);
    
    if (!$socket) {
        // Last resort: native mail()
        $headers  = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        return @mail($to, $subject, $message, $headers);
    }
    
    $read = function($s) {
        $buf = '';
        while ($line = fgets($s, 512)) {
            $buf .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $buf;
    };
    
    $read($socket); // greeting
    
    fwrite($socket, "EHLO localhost\r\n");
    $read($socket);
    
    fwrite($socket, "MAIL FROM:<" . $config['from_email'] . ">\r\n");
    $read($socket);
    
    fwrite($socket, "RCPT TO:<{$to}>\r\n");
    $rcpt = $read($socket);
    
    // If recipient rejected, bail out
    if (strpos($rcpt, '250') === false && strpos($rcpt, '251') === false) {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }
    
    fwrite($socket, "DATA\r\n");
    $read($socket);
    
    $body  = "MIME-Version: 1.0\r\n";
    $body .= "Content-Type: text/plain; charset=utf-8\r\n";
    $body .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
    $body .= "To: <{$to}>\r\n";
    $body .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n\r\n";
    $body .= $message . "\r\n.\r\n";
    
    fwrite($socket, $body);
    $dataResponse = $read($socket);
    
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    return strpos($dataResponse, '250') !== false;
}

// Send Email via Brevo (Sendinblue) Transactional HTTP API (Port 443 HTTPS)
function sendMailBrevoAPI($to, $subject, $message, $config) {
    $url = 'https://api.brevo.com/v3/smtp/email';
    
    $payload = [
        'sender' => [
            'name'  => $config['from_name'],
            'email' => $config['from_email']
        ],
        'to' => [
            [
                'email' => $to
            ]
        ],
        'subject'     => $subject,
        'textContent' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $config['brevo_api_key'],
        'content-type: application/json',
        'accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log response if it fails for debugging
    if ($httpCode < 200 || $httpCode >= 300) {
        @file_put_contents(__DIR__ . '/../otp_log.txt', date('Y-m-d H:i:s') . " | Brevo Error: HTTP $httpCode | Resp: $response\n", FILE_APPEND);
        return false;
    }
    
    return true;
}

// Send Email via Resend Transactional HTTP API (Port 443 HTTPS)
function sendMailResendAPI($to, $subject, $message, $config) {
    $url = 'https://api.resend.com/emails';
    
    $payload = [
        'from'    => $config['from_name'] . ' <' . $config['from_email'] . '>',
        'to'      => [$to],
        'subject' => $subject,
        'text'    => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['resend_api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log response if it fails for debugging
    if ($httpCode < 200 || $httpCode >= 300) {
        @file_put_contents(__DIR__ . '/../otp_log.txt', date('Y-m-d H:i:s') . " | Resend Error: HTTP $httpCode | Resp: $response\n", FILE_APPEND);
        return false;
    }
    
    return true;
}

// Helper to get a database setting dynamically (self-healing migration)
function getSetting($key, $default = '') {
    static $settingsCache = [];
    if (empty($settingsCache)) {
        try {
            $db = getDB();
            
            // Auto-create settings table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(50) PRIMARY KEY,
                `value` VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB;");
            
            // Self-healing products table image columns migration
            $checkImg = $db->query("SHOW COLUMNS FROM products LIKE 'image_url_2'")->fetch();
            if (!$checkImg) {
                $db->exec("ALTER TABLE products 
                    ADD COLUMN image_url_2 VARCHAR(255) NULL AFTER image_url,
                    ADD COLUMN image_url_3 VARCHAR(255) NULL AFTER image_url_2,
                    ADD COLUMN image_url_4 VARCHAR(255) NULL AFTER image_url_3;");
            }
            
            // Seed defaults if table is empty
            $count = (int)$db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
            if ($count === 0) {
                $db->exec("INSERT INTO settings (`key`, `value`) VALUES 
                    ('shipping_flat_rate', '200.00'),
                    ('shipping_free_threshold', '1500.00');");
            }
            
            $stmt = $db->query("SELECT `key`, `value` FROM settings");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($rows) {
                $settingsCache = $rows;
            }
        } catch (Exception $e) {
            error_log("Settings error: " . $e->getMessage());
        }
    }
    return $settingsCache[$key] ?? $default;
}

// Helper to update a database setting dynamically
function setSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Settings set error: " . $e->getMessage());
        return false;
    }
}

