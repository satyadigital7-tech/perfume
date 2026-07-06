<?php
require_once __DIR__ . '/config/db.php';

// Calculate BASE_URL dynamically
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim(str_replace('\\', '/', $scriptDir), '/');
define('BASE_URL', $baseUrl);

// Get route parameter
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Router matching logic
if ($route === '' || $route === 'index.php') {
    $view = 'home.php';
} elseif (preg_match('/^product\/([0-9]+)$/', $route, $matches)) {
    $productId = (int)$matches[1];
    $view = 'product-details.php';
} else {
    $allowed_routes = [
        'men', 'women', 'search', 'gift-sets',
        'cart', 'checkout', 'account', 'wishlist', 'order-tracking', 'order-confirmation',
        'contact', 'about', 'privacy', 'terms', 'refund', 'admin', 'invoice', 'reset-password'
    ];

    if (in_array($route, $allowed_routes)) {
        // Special parameters based on specific routes
        if ($route === 'men') {
            if (!isset($_GET['gender'])) {
                $_GET['gender'] = 'Men';
            }
            $view = 'shop.php';
        } elseif ($route === 'women') {
            if (!isset($_GET['gender'])) {
                $_GET['gender'] = 'Women';
            }
            $view = 'shop.php';
        } elseif ($route === 'search') {
            $view = 'shop.php';
        } elseif ($route === 'gift-sets') {
            if (!isset($_GET['fragrance_type'])) {
                $_GET['fragrance_type'] = 'Gift';
            }
            $view = 'shop.php';
        } else {
            $view = $route . '.php';
        }
    } else {
        $view = '404.php';
    }
}

// Serve the view
$viewPath = __DIR__ . '/views/' . $view;
if (file_exists($viewPath)) {
    include $viewPath;
} else {
    http_response_code(404);
    include __DIR__ . '/views/404.php';
}
