<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if (!$productId && $action !== '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product reference.']);
    exit;
}

$db = getDB();

// Fetch product to verify stock
if ($productId) {
    $stmt = $db->prepare("SELECT name, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }
}

// Helper to count total cart items
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $count += $qty;
        }
    }
    return $count;
}

switch ($action) {
    case 'add':
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 0;
        }
        
        $newQty = $_SESSION['cart'][$productId] + $quantity;
        if ($newQty > $product['stock']) {
            echo json_encode([
                'status' => 'error', 
                'message' => "Cannot add more items. Only {$product['stock']} bottles are in stock.",
                'cart_count' => getCartCount()
            ]);
            exit;
        }

        $_SESSION['cart'][$productId] = $newQty;
        echo json_encode([
            'status' => 'success',
            'message' => "Added " . $quantity . " x " . $product['name'] . " to your cart.",
            'cart_count' => getCartCount()
        ]);
        break;

    case 'update':
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            echo json_encode([
                'status' => 'success',
                'message' => "Removed product from cart.",
                'cart_count' => getCartCount()
            ]);
            exit;
        }

        if ($quantity > $product['stock']) {
            echo json_encode([
                'status' => 'error',
                'message' => "Only {$product['stock']} bottles of this scent are in stock.",
                'cart_count' => getCartCount()
            ]);
            exit;
        }

        $_SESSION['cart'][$productId] = $quantity;
        echo json_encode([
            'status' => 'success',
            'message' => "Cart updated.",
            'cart_count' => getCartCount()
        ]);
        break;

    case 'remove':
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        echo json_encode([
            'status' => 'success',
            'message' => "Removed scent from cart.",
            'cart_count' => getCartCount()
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Undefined cart action.']);
        break;
}
