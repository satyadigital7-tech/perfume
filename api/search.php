<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['status' => 'success', 'results' => []]);
    exit;
}

try {
    $db = getDB();
    
    // Look for matches in Name, Brand, Notes, or Description
    $stmt = $db->prepare("SELECT id, brand, name, price, discount_price, image_url FROM products WHERE name LIKE ? OR brand LIKE ? OR fragrance_type LIKE ? OR description LIKE ? LIMIT 5");
    
    $likeQuery = '%' . $query . '%';
    $stmt->execute([$likeQuery, $likeQuery, $likeQuery, $likeQuery]);
    $results = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'results' => $results
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Search suggestion database error occurred.'
    ]);
}
