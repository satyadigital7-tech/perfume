<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Verify authentication
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to submit a review.']);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$POST['rating'] : 5;
$reviewText = trim($_POST['review_text'] ?? '');
$userId = $_SESSION['user_id'];

if (!$productId || empty($reviewText)) {
    echo json_encode(['status' => 'error', 'message' => 'Product reference and review content are required.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating score. Please select between 1 and 5 stars.']);
    exit;
}

try {
    $db = getDB();

    // Verify product exists
    $pCheck = $db->prepare("SELECT id FROM products WHERE id = ?");
    $pCheck->execute([$productId]);
    if (!$pCheck->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }

    // Check if user has already reviewed this product to avoid duplicates
    $rCheck = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $rCheck->execute([$userId, $productId]);
    if ($rCheck->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted a review for this fragrance.']);
        exit;
    }

    // Insert Review
    $ins = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
    $ins->execute([$productId, $userId, $rating, $reviewText]);

    // Recalculate average rating for product
    $avgStmt = $db->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ?");
    $avgStmt->execute([$productId]);
    $newAvg = (float)$avgStmt->fetchColumn();

    // Limit decimal precision and update product record
    $newAvg = round($newAvg, 1);
    $updProduct = $db->prepare("UPDATE products SET rating = ? WHERE id = ?");
    $updProduct->execute([$newAvg, $productId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Your sensory assessment has been registered. Thank you.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while submitting review.'
    ]);
}
