<?php
require_once __DIR__ . '/../config/db.php';

echo "====================================\n";
echo "MR.GENIEPERFUMES - DATABASE TEST VALIDATION\n";
echo "====================================\n";

try {
    $db = getDB();
    echo "[SUCCESS] Connected to MySQL database successfully.\n";

    // 1. Verify Tables Exist
    $tables = ['users', 'products', 'orders', 'order_items', 'wishlists', 'reviews', 'newsletter', 'coupons', 'blogs', 'contacts'];
    $missing = [];

    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "[SUCCESS] Table '{$table}' exists.\n";
        } else {
            echo "[ERROR] Table '{$table}' is MISSING.\n";
            $missing[] = $table;
        }
    }

    // 2. Verify Product Seeding
    $prodCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "[INFO] Product catalog count: {$prodCount} (Expected: 9)\n";
    if ($prodCount == 9) {
        echo "[SUCCESS] Product seeding verified.\n";
    } else {
        echo "[WARNING] Product seeding mismatch.\n";
    }

    // 3. Verify Coupon Seeding
    $couponCount = $db->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    echo "[INFO] Active coupon code count: {$couponCount} (Expected: 3)\n";
    if ($couponCount == 3) {
        echo "[SUCCESS] Coupon seeding verified.\n";
    } else {
        echo "[WARNING] Coupon seeding mismatch.\n";
    }

    // 4. Verify Blog Seeding
    $blogCount = $db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
    echo "[INFO] Editorial blogs count: {$blogCount} (Expected: 3)\n";
    if ($blogCount == 3) {
        echo "[SUCCESS] Blog seeding verified.\n";
    } else {
        echo "[WARNING] Blog seeding mismatch.\n";
    }

    echo "====================================\n";
    if (empty($missing)) {
        echo "[SUMMARY] ALL TESTS PASSED SUCCESSFULLY.\n";
    } else {
        echo "[SUMMARY] TESTS FAILED. SOME TABLES ARE MISSING.\n";
    }
    echo "====================================\n";

} catch (Exception $e) {
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
}
