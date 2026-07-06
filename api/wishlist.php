<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error', 
        'code' => 'unauthorized', 
        'message' => 'Please log in to manage your wishlist.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$productId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product reference.']);
    exit;
}

$db = getDB();

// Helper to count wishlist items
function getWishlistCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// Helper to count cart items
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
    case 'toggle':
        // Check if already in wishlist
        $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $wish = $stmt->fetch();

        if ($wish) {
            // Remove
            $del = $db->prepare("DELETE FROM wishlists WHERE id = ?");
            $del->execute([$wish['id']]);
            echo json_encode([
                'status' => 'success',
                'state' => 'removed',
                'message' => 'Removed scent from wishlist.',
                'wishlist_count' => getWishlistCount($userId)
            ]);
        } else {
            // Add
            // Verify product exists
            $pCheck = $db->prepare("SELECT name FROM products WHERE id = ?");
            $pCheck->execute([$productId]);
            $pName = $pCheck->fetchColumn();
            
            if (!$pName) {
                echo json_encode(['status' => 'error', 'message' => 'Product does not exist.']);
                exit;
            }

            $ins = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
            $ins->execute([$userId, $productId]);
            echo json_encode([
                'status' => 'success',
                'state' => 'added',
                'message' => "Added " . $pName . " to wishlist.",
                'wishlist_count' => getWishlistCount($userId)
            ]);
        }
        break;

    case 'move_to_cart':
        // Verify product exists and in stock
        $stmt = $db->prepare("SELECT name, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product does not exist.']);
            exit;
        }

        if ($product['stock'] <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Scent is currently out of stock.']);
            exit;
        }

        // Add to cart
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 0;
        }
        
        if ($_SESSION['cart'][$productId] + 1 > $product['stock']) {
            echo json_encode(['status' => 'error', 'message' => 'Only ' . $product['stock'] . ' bottles left. Cannot add more.']);
            exit;
        }

        $_SESSION['cart'][$productId] += 1;

        // Remove from wishlist
        $del = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $del->execute([$userId, $productId]);

        echo json_encode([
            'status' => 'success',
            'message' => "Moved " . $product['name'] . " to your shopping cart.",
            'cart_count' => getCartCount(),
            'wishlist_count' => getWishlistCount($userId)
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Undefined wishlist action.']);
        break;
}
