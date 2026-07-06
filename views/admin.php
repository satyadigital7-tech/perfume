<?php
$pageTitle = "Genie Admin Portal";
require_once __DIR__ . '/../config/db.php';

$db = getDB();

// ==========================================
// POST Handlers (Admin Actions)
// ==========================================
$loginError = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Separate Admin Login
    if (isset($_POST['submit_admin_login'])) {
        $email = trim($_POST['admin_email']);
        $password = trim($_POST['admin_password']);

        // Load credentials from env.php
        $envFile = __DIR__ . '/../config/env.php';
        if (file_exists($envFile)) {
            $env = require $envFile;
            $adminUser = $env['admin_user'] ?? 'admin@mrgenieperfumes.in';
            $adminPass = $env['admin_pass'] ?? 'Genie0558';

            if ($email === $adminUser && $password === $adminPass) {
                $_SESSION['is_admin'] = true;
                header("Location: " . BASE_URL . "/admin");
                exit;
            } else {
                $loginError = "Invalid administrator credentials.";
            }
        } else {
            $loginError = "env.php configuration file missing.";
        }
    }

    // CSRF verification for DB actions
    if (isset($_POST['admin_action'])) {
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die("Security validation failed.");
        }
        
        $action = $_POST['admin_action'];
        $activeTab = $_GET['tab'] ?? 'overview';

        // Update Order
        if ($action === 'update_order') {
            $orderId = (int)$_POST['order_id'];
            $status = trim($_POST['order_status']);
            $tracking = trim($_POST['tracking_number']);
            $paymentStatus = trim($_POST['payment_status']);
            try {
                $stmt = $db->prepare("UPDATE orders SET order_status = ?, tracking_number = ?, payment_status = ? WHERE id = ?");
                $stmt->execute([$status, $tracking, $paymentStatus, $orderId]);
                setFlashMessage('success', 'Order updated successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=orders");
            exit;
        }

        // Add Product
        if ($action === 'add_product') {
            $brand = trim($_POST['brand']);
            $name = trim($_POST['name']);
            $gender = trim($_POST['gender']);
            $fragrance_type = trim($_POST['fragrance_type']);
            $price = (float)$_POST['price'];
            $discount_price = (float)$_POST['discount_price'];
            $description = trim($_POST['description']);
            $top_notes = trim($_POST['top_notes']);
            $heart_notes = trim($_POST['heart_notes']);
            $base_notes = trim($_POST['base_notes']);
            $stock = (int)$_POST['stock'];
            $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
            $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;

            if (!function_exists('handleProductImageUpload')) {
                function handleProductImageUpload($inputName) {
                    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES[$inputName]['tmp_name'];
                        $fileName = $_FILES[$inputName]['name'];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = time() . '_' . $inputName . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($fileName));
                            $uploadFileDir = __DIR__ . '/../assets/images/';
                            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                                return $newFileName;
                            }
                        }
                    }
                    return null;
                }
            }

            $image_url = handleProductImageUpload('product_image') ?? 'sauvage.jpg';
            $image_url_2 = handleProductImageUpload('product_image_2');
            $image_url_3 = handleProductImageUpload('product_image_3');
            $image_url_4 = handleProductImageUpload('product_image_4');
            $rating = isset($_POST['rating']) ? (float)$_POST['rating'] : 5.0;

            try {
                $stmt = $db->prepare("INSERT INTO products (brand, name, gender, fragrance_type, price, discount_price, description, top_notes, heart_notes, base_notes, image_url, image_url_2, image_url_3, image_url_4, stock, rating, is_best_seller, is_new_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$brand, $name, $gender, $fragrance_type, $price, $discount_price, $description, $top_notes, $heart_notes, $base_notes, $image_url, $image_url_2, $image_url_3, $image_url_4, $stock, $rating, $is_best_seller, $is_new_arrival]);
                setFlashMessage('success', 'Product published successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=products");
            exit;
        }

        // Edit Product
        if ($action === 'edit_product') {
            $productId = (int)$_POST['product_id'];
            $brand = trim($_POST['brand']);
            $name = trim($_POST['name']);
            $gender = trim($_POST['gender']);
            $fragrance_type = trim($_POST['fragrance_type']);
            $price = (float)$_POST['price'];
            $discount_price = (float)$_POST['discount_price'];
            $description = trim($_POST['description']);
            $top_notes = trim($_POST['top_notes']);
            $heart_notes = trim($_POST['heart_notes']);
            $base_notes = trim($_POST['base_notes']);
            $stock = (int)$_POST['stock'];
            $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
            $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;

            if (!function_exists('handleProductImageUpload')) {
                function handleProductImageUpload($inputName) {
                    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES[$inputName]['tmp_name'];
                        $fileName = $_FILES[$inputName]['name'];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = time() . '_' . $inputName . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($fileName));
                            $uploadFileDir = __DIR__ . '/../assets/images/';
                            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                                return $newFileName;
                            }
                        }
                    }
                    return null;
                }
            }

            $rating = isset($_POST['rating']) ? (float)$_POST['rating'] : 5.0;

            $image_sql = "";
            $extra_params = [];
            
            $new_img_1 = handleProductImageUpload('product_image');
            $image_url_2 = handleProductImageUpload('product_image_2');
            $image_url_3 = handleProductImageUpload('product_image_3');
            $image_url_4 = handleProductImageUpload('product_image_4');

            if ($new_img_1) {
                $image_sql .= ", image_url = ?";
                $extra_params[] = $new_img_1;
            }
            if ($image_url_2) {
                $image_sql .= ", image_url_2 = ?";
                $extra_params[] = $image_url_2;
            }
            if ($image_url_3) {
                $image_sql .= ", image_url_3 = ?";
                $extra_params[] = $image_url_3;
            }
            if ($image_url_4) {
                $image_sql .= ", image_url_4 = ?";
                $extra_params[] = $image_url_4;
            }

            $params = [$brand, $name, $gender, $fragrance_type, $price, $discount_price, $description, $top_notes, $heart_notes, $base_notes, $stock, $rating, $is_best_seller, $is_new_arrival];
            $params = array_merge($params, $extra_params);
            $params[] = $productId;

            try {
                $sql = "UPDATE products SET brand = ?, name = ?, gender = ?, fragrance_type = ?, price = ?, discount_price = ?, description = ?, top_notes = ?, heart_notes = ?, base_notes = ?, stock = ?, rating = ?, is_best_seller = ?, is_new_arrival = ? $image_sql WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                setFlashMessage('success', 'Product updated successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=products");
            exit;
        }

        // Delete Product
        if ($action === 'delete_product') {
            $productId = (int)$_POST['product_id'];
            try {
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                setFlashMessage('success', 'Product deleted successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=products");
            exit;
        }

        // Create Coupon
        if ($action === 'create_coupon') {
            $code = strtoupper(trim($_POST['code']));
            $type = trim($_POST['discount_type']);
            $value = (float)$_POST['value'];
            $active = isset($_POST['active']) ? 1 : 0;
            try {
                $stmt = $db->prepare("INSERT INTO coupons (code, discount_type, value, active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code, $type, $value, $active]);
                setFlashMessage('success', 'Coupon code created.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=coupons");
            exit;
        }

        // Toggle Coupon
        if ($action === 'toggle_coupon') {
            $couponId = (int)$_POST['coupon_id'];
            $active = (int)$_POST['active'];
            $newStatus = $active === 1 ? 0 : 1;
            try {
                $stmt = $db->prepare("UPDATE coupons SET active = ? WHERE id = ?");
                $stmt->execute([$newStatus, $couponId]);
                setFlashMessage('success', 'Coupon status updated.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=coupons");
            exit;
        }

        // Change Admin Password
        if ($action === 'change_admin_password') {
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $envFile = __DIR__ . '/../config/env.php';
            if (!file_exists($envFile)) {
                setFlashMessage('error', 'env.php configuration file is missing.');
                header("Location: " . BASE_URL . "/admin?tab=password");
                exit;
            }
            
            $env = require $envFile;
            $currentAdminPass = $env['admin_pass'] ?? 'Genie0558';
            
            if ($oldPassword !== $currentAdminPass) {
                setFlashMessage('error', 'The current password you entered is incorrect.');
            } elseif (strlen($newPassword) < 8) {
                setFlashMessage('error', 'New password must be at least 8 characters long.');
            } elseif ($newPassword !== $confirmPassword) {
                setFlashMessage('error', 'New password and confirmation do not match.');
            } else {
                try {
                    $env['admin_pass'] = $newPassword;
                    
                    // Format php array syntax nicely
                    $content = "<?php\n// ============================================================\n// ENVIRONMENT CONFIGURATION — DO NOT COMMIT THIS FILE TO GIT\n// ============================================================\n\nreturn [\n";
                    foreach ($env as $key => $val) {
                        $content .= "    '" . addslashes($key) . "' => '" . addslashes($val) . "',\n";
                    }
                    $content .= "];\n";
                    
                    if (file_put_contents($envFile, $content) !== false) {
                        setFlashMessage('success', 'Administrator password updated successfully.');
                    } else {
                        setFlashMessage('error', 'Failed to write changes to env.php. Please check file permissions.');
                    }
                } catch (Exception $e) {
                    setFlashMessage('error', $e->getMessage());
                }
            }
            header("Location: " . BASE_URL . "/admin?tab=password");
            exit;
        }

        // Update Shipping Settings
        if ($action === 'update_shipping_settings') {
            $flatRate = trim($_POST['shipping_flat_rate'] ?? '200.00');
            $freeThreshold = trim($_POST['shipping_free_threshold'] ?? '1500.00');
            
            $success = setSetting('shipping_flat_rate', $flatRate) && setSetting('shipping_free_threshold', $freeThreshold);
            if ($success) {
                setFlashMessage('success', 'Shipping and delivery settings updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update shipping settings.');
            }
            header("Location: " . BASE_URL . "/admin?tab=settings");
            exit;
        }

        // Update Hero Images
        if ($action === 'update_hero_images') {
            $uploadFileDir = __DIR__ . '/../assets/images/hero/';
            $updatedCount = 0;
            $errors = [];

            // Ensure directory exists
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            for ($i = 1; $i <= 4; $i++) {
                $inputName = "hero_image_$i";
                if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES[$inputName]['tmp_name'];
                    $fileName = $_FILES[$inputName]['name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($fileExtension, $allowedExtensions)) {
                        $targetPath = $uploadFileDir . "1 ($i).jpeg";
                        if (move_uploaded_file($fileTmpPath, $targetPath)) {
                            $updatedCount++;
                        } else {
                            $errors[] = "Failed to save Hero Image $i.";
                        }
                    } else {
                        $errors[] = "Hero Image $i has an invalid file type. Allowed: jpg, jpeg, png, webp.";
                    }
                } elseif (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error uploading Hero Image $i (Code: " . $_FILES[$inputName]['error'] . ").";
                }
            }

            if ($updatedCount > 0) {
                if (empty($errors)) {
                    setFlashMessage('success', "Successfully updated $updatedCount hero slideshow image(s).");
                } else {
                    setFlashMessage('success', "Updated $updatedCount hero slideshow image(s), but encountered some errors: " . implode(" ", $errors));
                }
            } elseif (!empty($errors)) {
                setFlashMessage('error', implode(" ", $errors));
            } else {
                setFlashMessage('error', 'No images were selected for upload.');
            }

            header("Location: " . BASE_URL . "/admin?tab=settings");
            exit;
        }

        // Update Haute Collection Images
        if ($action === 'update_haute_images') {
            $uploadFileDir = __DIR__ . '/../assets/images/Haute Collection/';
            $updatedCount = 0;
            $errors = [];

            // Ensure directory exists
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            // Process Him Banner
            if (isset($_FILES['haute_men_image']) && $_FILES['haute_men_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['haute_men_image']['tmp_name'];
                $fileName = $_FILES['haute_men_image']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (in_array($fileExtension, $allowedExtensions)) {
                    $targetPath = $uploadFileDir . "men.jpeg";
                    if (move_uploaded_file($fileTmpPath, $targetPath)) {
                        $updatedCount++;
                    } else {
                        $errors[] = "Failed to save For Him banner image.";
                    }
                } else {
                    $errors[] = "For Him banner image has an invalid file type. Allowed: jpg, jpeg, png, webp.";
                }
            } elseif (isset($_FILES['haute_men_image']) && $_FILES['haute_men_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading For Him banner image (Code: " . $_FILES['haute_men_image']['error'] . ").";
            }

            // Process Her Banner
            if (isset($_FILES['haute_women_image']) && $_FILES['haute_women_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['haute_women_image']['tmp_name'];
                $fileName = $_FILES['haute_women_image']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (in_array($fileExtension, $allowedExtensions)) {
                    $targetPath = $uploadFileDir . "women.jpeg";
                    if (move_uploaded_file($fileTmpPath, $targetPath)) {
                        $updatedCount++;
                    } else {
                        $errors[] = "Failed to save For Her banner image.";
                    }
                } else {
                    $errors[] = "For Her banner image has an invalid file type. Allowed: jpg, jpeg, png, webp.";
                }
            } elseif (isset($_FILES['haute_women_image']) && $_FILES['haute_women_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading For Her banner image (Code: " . $_FILES['haute_women_image']['error'] . ").";
            }

            if ($updatedCount > 0) {
                if (empty($errors)) {
                    setFlashMessage('success', "Successfully updated $updatedCount Haute Collection image(s).");
                } else {
                    setFlashMessage('success', "Updated $updatedCount Haute Collection image(s), but encountered some errors: " . implode(" ", $errors));
                }
            } elseif (!empty($errors)) {
                setFlashMessage('error', implode(" ", $errors));
            } else {
                setFlashMessage('error', 'No images were selected for upload.');
            }

            header("Location: " . BASE_URL . "/admin?tab=settings");
            exit;
        }

        // Delete Message
        if ($action === 'delete_message') {
            $msgId = (int)$_POST['message_id'];
            try {
                $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                $stmt->execute([$msgId]);
                setFlashMessage('success', 'Message deleted.');
            } catch (Exception $e) {
                setFlashMessage('error', $e->getMessage());
            }
            header("Location: " . BASE_URL . "/admin?tab=messages");
            exit;
        }
    }
}

// ==========================================
// RENDER STANDALONE LOGIN PAGE
// ==========================================
if (!isAdmin()):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Sign-In | Genie backoffice</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-bg: #0d0d0d;
            --color-card: #151515;
            --color-gold: #D4AF37;
            --color-border: #2c2c2c;
            --color-text: #e0e0e0;
            --color-muted: #888888;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--color-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--color-text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-card {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            border-top: 4px solid var(--color-gold);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            text-align: center;
        }
        .logo-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: #ffffff;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 0.8rem;
            color: var(--color-muted);
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .error-box {
            background-color: rgba(198, 40, 40, 0.15);
            border: 1px solid #c62828;
            color: #ff8a80;
            padding: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-muted);
        }
        .form-group input {
            width: 100%;
            height: 45px;
            background-color: #202020;
            border: 1px solid var(--color-border);
            padding: 0 15px;
            color: #ffffff;
            outline: none;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: var(--color-gold);
        }
        .btn-auth {
            width: 100%;
            height: 48px;
            background-color: var(--color-gold);
            border: none;
            color: #000000;
            font-family: inherit;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-auth:hover {
            background-color: #b5932c;
        }
        .footer-link {
            display: block;
            margin-top: 25px;
            font-size: 0.8rem;
            color: var(--color-gold);
            text-decoration: none;
        }
        .footer-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-card">
    <h1 class="logo-title">MR. GENIE</h1>
    <div class="subtitle">Backoffice Portal</div>

    <?php if (!empty($loginError)): ?>
        <div class="error-box"><?= e($loginError) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin" method="POST">
        <input type="hidden" name="submit_admin_login" value="1">
        
        <div class="form-group">
            <label>Master Username</label>
            <input type="email" name="admin_email" required placeholder="admin@mrgenieperfumes.in" autocomplete="off">
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="password-toggle-wrapper" style="position: relative; display: block;">
                <input type="password" name="admin_password" required placeholder="••••••••" style="padding-right: 40px;">
                <i class="fa-regular fa-eye toggle-password-visibility-icon" onclick="togglePasswordVisibility(this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; z-index: 5;"></i>
            </div>
        </div>

        <button type="submit" class="btn-auth">Authorize Session</button>
    </form>
    
    <a href="<?= BASE_URL ?>/" class="footer-link">← Return to Storefront</a>
</div>

<script>
function togglePasswordVisibility(icon) {
    const input = icon.previousElementSibling;
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>
<?php
exit;
endif;

// ==========================================
// RENDER STANDALONE BACKOFFICE DASHBOARD
// ==========================================
$activeTab = $_GET['tab'] ?? 'overview';

// Load statistics
$stats = [];
if ($activeTab === 'overview') {
    $stats['revenue'] = (float)$db->query("SELECT SUM(total) FROM orders WHERE payment_status = 'Completed'")->fetchColumn();
    $stats['orders'] = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending'] = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status != 'Delivered'")->fetchColumn();
    $stats['patrons'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $recentOrders = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
}
if ($activeTab === 'orders') {
    $allOrders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
}
if ($activeTab === 'products') {
    $allProducts = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
}
if ($activeTab === 'coupons') {
    $allCoupons = $db->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
}
if ($activeTab === 'messages') {
    $allMessages = $db->query("SELECT * FROM contacts ORDER BY id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Control Console | Mr.genieperfumes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-sidebar: #111111;
            --color-gold: #D4AF37;
            --color-bg: #f8f9fa;
            --color-white: #ffffff;
            --color-border: #eaeaea;
            --color-text-dark: #222222;
            --color-text-muted: #666666;
            --color-success: #2e7d32;
            --color-error: #c62828;
            --color-info: #0288d1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--color-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--color-text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background-color: var(--color-sidebar);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #222222;
        }
        .sidebar-brand {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #222222;
        }
        .sidebar-brand h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: #ffffff;
            letter-spacing: 1px;
        }
        .sidebar-brand span {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--color-gold);
            display: block;
            margin-top: 5px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex-grow: 1;
        }
        .menu-item a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 25px;
            color: #cccccc;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .menu-item a:hover, .menu-item.active a {
            color: #ffffff;
            background-color: #202020;
            border-left-color: var(--color-gold);
        }
        .menu-item.logout-btn a {
            color: #ff8a80;
        }
        .menu-item.logout-btn a:hover {
            background-color: rgba(198, 40, 40, 0.1);
            border-left-color: var(--color-error);
        }

        /* Main Backoffice Panel Layout */
        .main-panel {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            height: 100vh;
        }
        .topbar {
            background-color: var(--color-white);
            height: 70px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        .topbar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .topbar-meta {
            font-size: 0.85rem;
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .content-workspace {
            padding: 40px;
            max-width: 1300px;
            width: 100%;
            margin: 0 auto;
        }

        /* Toast / Notifications inside standalone admin page */
        .admin-toast {
            padding: 15px 25px;
            border-radius: 4px;
            margin-bottom: 30px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .admin-toast.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .admin-toast.error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background-color: var(--color-white);
            border: 1px solid var(--color-border);
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card-info h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-text-muted);
            margin-bottom: 5px;
        }
        .stat-card-info div {
            font-size: 1.8rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--color-text-dark);
        }
        .stat-card-icon {
            font-size: 2.2rem;
            color: var(--color-gold);
            opacity: 0.85;
        }

        /* Content block base wrappers */
        .workspace-block {
            background-color: var(--color-white);
            border: 1px solid var(--color-border);
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            margin-bottom: 40px;
        }
        .block-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 25px;
            font-weight: 700;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 15px;
        }

        /* Form elements styles */
        .admin-form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .admin-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .admin-form-group label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: var(--color-text-muted);
        }
        .admin-form-group input, .admin-form-group select, .admin-form-group textarea {
            height: 40px;
            border: 1px solid var(--color-border);
            background-color: var(--color-bg);
            padding: 0 12px;
            outline: none;
            font-family: inherit;
            font-size: 0.85rem;
            transition: border-color 0.3s;
        }
        .admin-form-group textarea {
            height: 100px;
            padding: 10px;
            resize: vertical;
        }
        .admin-form-group input:focus, .admin-form-group select:focus, .admin-form-group textarea:focus {
            border-color: var(--color-gold);
        }

        /* Generic table styles */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid var(--color-border);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-text-muted);
        }
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid var(--color-bg);
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .admin-table tr:hover {
            background-color: #fafafa;
        }

        /* Badges & Status */
        .status-badge {
            padding: 4px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge.success { background-color: #e8f5e9; color: #2e7d32; }
        .status-badge.pending { background-color: #fff3e0; color: #ef6c00; }
        .status-badge.error { background-color: #ffebee; color: #c62828; }

        /* Action Buttons */
        .btn-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--color-text-dark);
            color: #ffffff;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-admin:hover {
            background-color: var(--color-gold);
            color: #000000;
        }
        .btn-admin-gold {
            background-color: var(--color-gold);
            color: #000000;
            font-weight: 600;
        }
        .btn-admin-gold:hover {
            background-color: #b5932c;
        }
        .btn-admin-outline-red {
            background: transparent;
            border: 1px solid var(--color-error);
            color: var(--color-error);
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        .btn-admin-outline-red:hover {
            background-color: var(--color-error);
            color: #ffffff;
        }

        /* Orders Manager Custom CSS */
        .order-card {
            border: 1px solid var(--color-border);
            margin-bottom: 25px;
            border-radius: 4px;
            overflow: hidden;
        }
        .order-card-header {
            background-color: #fafafa;
            padding: 15px 25px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .order-card-body {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; }
            .sidebar-menu { padding: 15px 0; flex-direction: row; overflow-x: auto; }
            .menu-item a { padding: 10px 20px; border-left: none; border-bottom: 3px solid transparent; }
            .menu-item a:hover, .menu-item.active a { border-left-color: transparent; border-bottom-color: var(--color-gold); }
            .main-panel { height: auto; }
        }
        @media (max-width: 768px) {
            .content-workspace { padding: 20px; }
            .topbar { padding: 0 20px; }
        }

        /* Edit Product Modal Styles */
        .admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
        }
        .admin-modal-content {
            background-color: var(--color-white);
            border: 1px solid var(--color-border);
            border-top: 4px solid var(--color-gold);
            border-radius: 4px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            animation: modalFadeIn 0.3s ease;
        }
        .admin-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--color-border);
        }
        .admin-modal-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .admin-modal-close {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: var(--color-text-muted);
            transition: color 0.3s;
        }
        .admin-modal-close:hover {
            color: var(--color-error);
        }
        .admin-modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .admin-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 20px 30px;
            border-top: 1px solid var(--color-border);
            background-color: #fafafa;
        }
        .btn-admin-outline-gold {
            background: transparent;
            border: 1px solid var(--color-gold);
            color: var(--color-gold);
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        .btn-admin-outline-gold:hover {
            background-color: var(--color-gold);
            color: #000000;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Side Panel Menu -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>MR. GENIE</h2>
        <span>Backoffice Console</span>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-item <?= $activeTab === 'overview' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=overview"><i class="fa-solid fa-chart-pie"></i> Overview</a>
        </li>
        <li class="menu-item <?= $activeTab === 'orders' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=orders"><i class="fa-solid fa-receipt"></i> Orders</a>
        </li>
        <li class="menu-item <?= $activeTab === 'products' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=products"><i class="fa-solid fa-bottle-droplet"></i> Products</a>
        </li>
        <li class="menu-item <?= $activeTab === 'coupons' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=coupons"><i class="fa-solid fa-ticket"></i> Coupons</a>
        </li>
        <li class="menu-item <?= $activeTab === 'password' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=password"><i class="fa-solid fa-key"></i> Change Password</a>
        </li>
        <li class="menu-item <?= $activeTab === 'messages' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=messages"><i class="fa-solid fa-envelope"></i> Messages</a>
        </li>
        <li class="menu-item <?= $activeTab === 'settings' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin?tab=settings"><i class="fa-solid fa-gears"></i> Settings</a>
        </li>
        
        <!-- Divider / External links -->
        <li style="margin: 20px 0; border-top: 1px solid #222222;"></li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>/" target="_blank"><i class="fa-solid fa-store"></i> View Storefront</a>
        </li>
        <li class="menu-item logout-btn">
            <a href="<?= BASE_URL ?>/account?logout=1"><i class="fa-solid fa-power-off"></i> Logout</a>
        </li>
    </ul>
</aside>

<!-- Right Side Panel Workspace -->
<main class="main-panel">
    <header class="topbar">
        <div class="topbar-title">Master Control Panel</div>
        <div class="topbar-meta">
            <span><i class="fa-solid fa-user-shield" style="color: var(--color-gold);"></i> Administrator Session</span>
            <span>|</span>
            <span><?= date('d M Y') ?></span>
        </div>
    </header>

    <div class="content-workspace">
        
        <!-- Render Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="admin-toast <?= e($_SESSION['flash_message']['type']) ?>">
                <?php if ($_SESSION['flash_message']['type'] === 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php endif; ?>
                <div><?= e($_SESSION['flash_message']['text']) ?></div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <!-- TAB CONTENT: OVERVIEW -->
        <?php if ($activeTab === 'overview'): ?>
            <div class="stats-grid">
                <!-- Sales -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h4>Completed Sales</h4>
                        <div>₹<?= number_format($stats['revenue'], 2) ?></div>
                    </div>
                    <i class="fa-solid fa-wallet stat-card-icon"></i>
                </div>
                <!-- Orders -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h4>Total Orders</h4>
                        <div><?= $stats['orders'] ?></div>
                    </div>
                    <i class="fa-solid fa-box-open stat-card-icon"></i>
                </div>
                <!-- Pending -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h4>Pending Cargo</h4>
                        <div><?= $stats['pending'] ?></div>
                    </div>
                    <i class="fa-solid fa-truck-ramp-box stat-card-icon" style="color: var(--color-error);"></i>
                </div>
                <!-- Users -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h4>Patrons</h4>
                        <div><?= $stats['patrons'] ?></div>
                    </div>
                    <i class="fa-solid fa-users stat-card-icon"></i>
                </div>
            </div>

            <div class="workspace-block">
                <div class="block-title">Recent Order Registry</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Client Name</th>
                                <th>Payment</th>
                                <th>Fulfillment</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--color-text-muted);">No orders in database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td><strong><?= e($o['order_id']) ?></strong></td>
                                        <td><?= e($o['billing_name']) ?></td>
                                        <td>
                                            <span class="status-badge <?= $o['payment_status'] === 'Completed' ? 'success' : ($o['payment_status'] === 'Pending' ? 'pending' : 'error') ?>">
                                                <?= e($o['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e($o['order_status']) ?></td>
                                        <td style="font-weight: 600;">₹<?= number_format($o['total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: ORDERS -->
        <?php if ($activeTab === 'orders'): ?>
            <div class="workspace-block">
                <div class="block-title">Manage Customer Orders</div>
                
                <?php if (empty($allOrders)): ?>
                    <p style="text-align: center; padding: 40px 0; color: var(--color-text-muted);">No order transactions found.</p>
                <?php else: ?>
                    <?php foreach ($allOrders as $o): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Order ID</span>
                                    <h4 style="font-size: 1.1rem; font-weight: 600;"><?= e($o['order_id']) ?></h4>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Transaction Total</span>
                                    <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--color-gold);">₹<?= number_format($o['total'], 2) ?></h4>
                                </div>
                            </div>
                            
                            <div class="order-card-body">
                                <!-- Col 1: Delivery Details -->
                                <div>
                                    <h5 style="text-transform: uppercase; font-size: 0.7rem; color: var(--color-text-muted); margin-bottom: 8px; letter-spacing: 0.5px;">Shipping Coordinates</h5>
                                    <p style="font-size: 0.85rem; line-height: 1.6;">
                                        <strong><?= e($o['billing_name']) ?></strong><br>
                                        Mobile: <?= e($o['billing_mobile']) ?><br>
                                        Email: <?= e($o['billing_email']) ?><br>
                                        Address: <?= e($o['address']) ?>, <?= e($o['city']) ?>, <?= e($o['state']) ?> - <?= e($o['pincode']) ?>
                                    </p>
                                </div>

                                <!-- Col 2: Payment Details -->
                                <div>
                                    <h5 style="text-transform: uppercase; font-size: 0.7rem; color: var(--color-text-muted); margin-bottom: 8px; letter-spacing: 0.5px;">Payment Details</h5>
                                    <p style="font-size: 0.85rem; line-height: 1.6; margin-bottom: 12px;">
                                        Method: <?= e($o['payment_method']) ?><br>
                                        Promo Code: <?= $o['coupon_code'] ? e($o['coupon_code']) : 'None' ?><br>
                                        Discount Applied: ₹<?= number_format($o['discount_amount'], 2) ?>
                                    </p>
                                    <?php
                                    $cleanMobile = preg_replace('/[^0-9]/', '', $o['billing_mobile']);
                                    if (strlen($cleanMobile) === 10) {
                                        $cleanMobile = '91' . $cleanMobile;
                                    }
                                    $waMsg = "Hello " . $o['billing_name'] . ", this is Mr.genieperfumes regarding your order " . $o['order_id'] . ". We would like to confirm your order details and delivery timeline.";
                                    $waLink = "https://wa.me/" . $cleanMobile . "?text=" . urlencode($waMsg);
                                    ?>
                                    <a href="<?= $waLink ?>" target="_blank" class="btn-admin" style="font-size: 0.7rem; padding: 6px 12px; border: 1px solid #25D366; background: transparent; color: #25D366; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-brands fa-whatsapp" style="font-size: 0.95rem;"></i> Confirm via WhatsApp</a>
                                    <a href="<?= BASE_URL ?>/invoice?order_id=<?= $o['id'] ?>" target="_blank" class="btn-admin" style="font-size: 0.7rem; padding: 6px 12px; border: 1px solid #c8a96e; background: transparent; color: #c8a96e; display: inline-flex; align-items: center; gap: 6px; margin-top: 8px;"><i class="fa-solid fa-file-invoice" style="font-size: 0.9rem;"></i> Download Invoice</a>

                                </div>

                                <!-- Col 3: Update Order Panel -->
                                <div>
                                    <h5 style="text-transform: uppercase; font-size: 0.7rem; color: var(--color-text-muted); margin-bottom: 8px; letter-spacing: 0.5px;">Update Status</h5>
                                    <form action="<?= BASE_URL ?>/admin?tab=orders" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="admin_action" value="update_order">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">

                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex: 1;">
                                                <select name="order_status" style="width: 100%; height: 35px; border: 1px solid var(--color-border); font-size: 0.8rem; padding: 0 5px;">
                                                    <option value="Order Received" <?= $o['order_status'] === 'Order Received' ? 'selected' : '' ?>>Order Received</option>
                                                    <option value="Shipped" <?= $o['order_status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="Out for Delivery" <?= $o['order_status'] === 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                                    <option value="Delivered" <?= $o['order_status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                </select>
                                            </div>
                                            <div style="flex: 1;">
                                                <select name="payment_status" style="width: 100%; height: 35px; border: 1px solid var(--color-border); font-size: 0.8rem; padding: 0 5px;">
                                                    <option value="Pending" <?= $o['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Completed" <?= $o['payment_status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="Failed" <?= $o['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div>
                                            <input type="text" name="tracking_number" value="<?= e($o['tracking_number'] ?? '') ?>" placeholder="Carrier Tracking ID" style="width: 100%; height: 35px; border: 1px solid var(--color-border); padding: 0 10px; font-size: 0.8rem;">
                                        </div>

                                        <button type="submit" class="btn-admin btn-admin-gold" style="width: 100%; padding: 8px;">Apply Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: PRODUCTS -->
        <?php if ($activeTab === 'products'): ?>
            <!-- Add Product Form -->
            <div class="workspace-block">
                <div class="block-title">Publish New Fragrance</div>
                <form action="<?= BASE_URL ?>/admin?tab=products" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="admin_action" value="add_product">

                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Brand Name</label>
                            <input type="text" name="brand" required placeholder="e.g. Chanel">
                        </div>
                        <div class="admin-form-group">
                            <label>Perfume Name</label>
                            <input type="text" name="name" required placeholder="e.g. Bleu de Chanel">
                        </div>
                        <div class="admin-form-group">
                            <label>Gender classification</label>
                            <select name="gender" required>
                                <option value="Men">Men</option>
                                <option value="Women">Women</option>
                                <option value="Unisex">Unisex</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label>Fragrance Scents Type</label>
                            <input type="text" name="fragrance_type" required placeholder="e.g. Citrus, Woody">
                        </div>
                    </div>

                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Price (₹)</label>
                            <input type="number" step="0.01" name="price" required placeholder="9500">
                        </div>
                        <div class="admin-form-group">
                            <label>Discount Price (₹)</label>
                            <input type="number" step="0.01" name="discount_price" placeholder="0">
                        </div>
                        <div class="admin-form-group">
                            <label>Stock Count</label>
                            <input type="number" name="stock" required value="10">
                        </div>
                        <div class="admin-form-group">
                            <label>Primary Scent Image</label>
                            <input type="file" name="product_image" required accept="image/*" style="padding: 8px 5px; height: auto;">
                        </div>
                    </div>

                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Scent Image 2 (Optional)</label>
                            <input type="file" name="product_image_2" accept="image/*" style="padding: 8px 5px; height: auto;">
                        </div>
                        <div class="admin-form-group">
                            <label>Scent Image 3 (Optional)</label>
                            <input type="file" name="product_image_3" accept="image/*" style="padding: 8px 5px; height: auto;">
                        </div>
                        <div class="admin-form-group">
                            <label>Scent Image 4 (Optional)</label>
                            <input type="file" name="product_image_4" accept="image/*" style="padding: 8px 5px; height: auto;">
                        </div>
                        <div class="admin-form-group">
                            <label>Boutique Rating (1.0 to 5.0)</label>
                            <input type="number" step="0.1" name="rating" min="1.0" max="5.0" value="5.0" required>
                        </div>
                    </div>

                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Top Notes</label>
                            <input type="text" name="top_notes" required placeholder="Grapefruit, Lemon">
                        </div>
                        <div class="admin-form-group">
                            <label>Heart Notes</label>
                            <input type="text" name="heart_notes" required placeholder="Ginger, Mint">
                        </div>
                        <div class="admin-form-group">
                            <label>Base Notes</label>
                            <input type="text" name="base_notes" required placeholder="Cedar, Sandalwood">
                        </div>
                    </div>

                    <div class="admin-form-group" style="margin-bottom: 20px;">
                        <label>Scent Description</label>
                        <textarea name="description" required placeholder="Describe the luxury perfume notes and characteristics..."></textarea>
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                            <input type="checkbox" name="is_best_seller" value="1"> Best Seller
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                            <input type="checkbox" name="is_new_arrival" value="1"> New Arrival
                        </label>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-gold">Publish Scent Product</button>
                </form>
            </div>

            <!-- Products List -->
            <div class="workspace-block">
                <div class="block-title">Fragrances Registry</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Scent Notes</th>
                                <th>Inventory</th>
                                <th>Pricing</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allProducts as $p): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <img src="<?= BASE_URL ?>/assets/images/<?= e($p['image_url']) ?>" style="width: 45px; height: 45px; object-fit: cover; border: 1px solid var(--color-border);">
                                            <div>
                                                <strong><?= e($p['name']) ?></strong><br>
                                                <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?= e($p['brand']) ?> | <?= e($p['gender']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.75rem; color: var(--color-text-muted); line-height: 1.4;">
                                        T: <?= e($p['top_notes']) ?><br>
                                        H: <?= e($p['heart_notes']) ?><br>
                                        B: <?= e($p['base_notes']) ?>
                                    </td>
                                    <td style="font-weight: 500; color: <?= $p['stock'] < 3 ? 'var(--color-error)' : 'inherit' ?>;"><?= $p['stock'] ?> units</td>
                                    <td>
                                        <?php if ($p['discount_price'] > 0): ?>
                                            <span style="color: var(--color-gold); font-weight: 600;">₹<?= number_format($p['discount_price'], 2) ?></span><br>
                                            <span style="text-decoration: line-through; font-size: 0.75rem; color: var(--color-text-muted);">₹<?= number_format($p['price'], 2) ?></span>
                                        <?php else: ?>
                                            <strong>₹<?= number_format($p['price'], 2) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-admin btn-admin-outline-gold" style="padding: 6px 10px; margin-right: 5px;" onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <form action="<?= BASE_URL ?>/admin?tab=products" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="admin_action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-admin btn-admin-outline-red" style="padding: 6px 10px;"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Edit Product Modal -->
            <div id="editProductModal" class="admin-modal">
                <div class="admin-modal-content">
                    <div class="admin-modal-header">
                        <h3>Modify Fragrance Details</h3>
                        <button type="button" class="admin-modal-close" onclick="closeEditModal()">&times;</button>
                    </div>
                    <form action="<?= BASE_URL ?>/admin?tab=products" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="admin_action" value="edit_product">
                        <input type="hidden" id="edit_product_id" name="product_id" value="">

                        <div class="admin-modal-body">
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Brand Name</label>
                                    <input type="text" id="edit_brand" name="brand" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Perfume Name</label>
                                    <input type="text" id="edit_name" name="name" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Gender classification</label>
                                    <select id="edit_gender" name="gender" required>
                                        <option value="Men">Men</option>
                                        <option value="Women">Women</option>
                                        <option value="Unisex">Unisex</option>
                                    </select>
                                </div>
                                <div class="admin-form-group">
                                    <label>Fragrance Scents Type</label>
                                    <input type="text" id="edit_fragrance_type" name="fragrance_type" required>
                                </div>
                            </div>

                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Price (₹)</label>
                                    <input type="number" step="0.01" id="edit_price" name="price" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Discount Price (₹)</label>
                                    <input type="number" step="0.01" id="edit_discount_price" name="discount_price">
                                </div>
                                <div class="admin-form-group">
                                    <label>Stock Count</label>
                                    <input type="number" id="edit_stock" name="stock" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Primary Image <span style="font-size: 0.7rem; color: var(--color-text-muted); text-transform: none;">(leave blank to keep current)</span></label>
                                    <input type="file" name="product_image" accept="image/*" style="padding: 8px 5px; height: auto;">
                                </div>
                            </div>

                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Image 2 <span style="font-size: 0.7rem; color: var(--color-text-muted); text-transform: none;">(leave blank to keep current)</span></label>
                                    <input type="file" name="product_image_2" accept="image/*" style="padding: 8px 5px; height: auto;">
                                </div>
                                <div class="admin-form-group">
                                    <label>Image 3 <span style="font-size: 0.7rem; color: var(--color-text-muted); text-transform: none;">(leave blank to keep current)</span></label>
                                    <input type="file" name="product_image_3" accept="image/*" style="padding: 8px 5px; height: auto;">
                                </div>
                                <div class="admin-form-group">
                                    <label>Image 4 <span style="font-size: 0.7rem; color: var(--color-text-muted); text-transform: none;">(leave blank to keep current)</span></label>
                                    <input type="file" name="product_image_4" accept="image/*" style="padding: 8px 5px; height: auto;">
                                </div>
                                <div class="admin-form-group">
                                    <label>Rating (1.0 to 5.0)</label>
                                    <input type="number" step="0.1" id="edit_rating" name="rating" min="1.0" max="5.0" required>
                                </div>
                            </div>

                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Top Notes</label>
                                    <input type="text" id="edit_top_notes" name="top_notes" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Heart Notes</label>
                                    <input type="text" id="edit_heart_notes" name="heart_notes" required>
                                </div>
                                <div class="admin-form-group">
                                    <label>Base Notes</label>
                                    <input type="text" id="edit_base_notes" name="base_notes" required>
                                </div>
                            </div>

                            <div class="admin-form-group" style="margin-bottom: 20px;">
                                <label>Scent Description</label>
                                <textarea id="edit_description" name="description" required></textarea>
                            </div>

                            <div style="display: flex; gap: 20px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" id="edit_is_best_seller" name="is_best_seller" value="1"> Best Seller
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" id="edit_is_new_arrival" name="is_new_arrival" value="1"> New Arrival
                                </label>
                            </div>
                        </div>

                        <div class="admin-modal-footer">
                            <button type="button" class="btn-admin" style="background-color: #ccc; color: #333;" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="btn-admin btn-admin-gold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: COUPONS -->
        <?php if ($activeTab === 'coupons'): ?>
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start;">
                <!-- Add Coupon Form -->
                <div class="workspace-block">
                    <div class="block-title">Create Promo Code</div>
                    <form action="<?= BASE_URL ?>/admin?tab=coupons" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="admin_action" value="create_coupon">

                        <div class="admin-form-group" style="margin-bottom: 15px;">
                            <label>Coupon Code</label>
                            <input type="text" name="code" required placeholder="e.g. LUXURY20">
                        </div>

                        <div class="admin-form-group" style="margin-bottom: 15px;">
                            <label>Discount Type</label>
                            <select name="discount_type" required>
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Price (₹)</option>
                            </select>
                        </div>

                        <div class="admin-form-group" style="margin-bottom: 15px;">
                            <label>Discount Value</label>
                            <input type="number" step="0.01" name="value" required placeholder="20">
                        </div>

                        <div class="admin-form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                <input type="checkbox" name="active" value="1" checked> Enable Coupon Code
                            </label>
                        </div>

                        <button type="submit" class="btn-admin btn-admin-gold" style="width: 100%;">Create Coupon</button>
                    </form>
                </div>

                <!-- Coupons List -->
                <div class="workspace-block">
                    <div class="block-title">Promo Code Registry</div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Promo Code</th>
                                    <th>Discount</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Toggle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allCoupons as $c): ?>
                                    <tr>
                                        <td><strong><?= e($c['code']) ?></strong></td>
                                        <td><?= $c['discount_type'] === 'percent' ? (float)$c['value'] . '%' : '₹' . number_format($c['value'], 2) ?></td>
                                        <td>
                                            <span class="status-badge <?= $c['active'] ? 'success' : 'error' ?>">
                                                <?= $c['active'] ? 'Active' : 'Disabled' ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <form action="<?= BASE_URL ?>/admin?tab=coupons" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="admin_action" value="toggle_coupon">
                                                <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                                <input type="hidden" name="active" value="<?= $c['active'] ?>">
                                                <button type="submit" class="btn-admin" style="padding: 6px 12px; font-size: 0.75rem;">
                                                    <?= $c['active'] ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: CHANGE PASSWORD -->
        <?php if ($activeTab === 'password'): ?>
            <div class="workspace-block" style="max-width: 600px;">
                <div class="block-title">Change Administrator Password</div>
                <form action="<?= BASE_URL ?>/admin?tab=password" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="admin_action" value="change_admin_password">

                    <div class="admin-form-group" style="margin-bottom: 20px;">
                        <label>Current Password</label>
                        <div class="password-toggle-wrapper" style="position: relative; display: block;">
                            <input type="password" name="old_password" required placeholder="Enter current password" style="padding-right: 40px;">
                            <i class="fa-regular fa-eye toggle-password-visibility-icon" onclick="togglePasswordVisibility(this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; z-index: 5;"></i>
                        </div>
                    </div>

                    <div class="admin-form-group" style="margin-bottom: 20px;">
                        <label>New Password</label>
                        <div class="password-toggle-wrapper" style="position: relative; display: block;">
                            <input type="password" name="new_password" required placeholder="Minimum 8 characters" style="padding-right: 40px;">
                            <i class="fa-regular fa-eye toggle-password-visibility-icon" onclick="togglePasswordVisibility(this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; z-index: 5;"></i>
                        </div>
                    </div>

                    <div class="admin-form-group" style="margin-bottom: 25px;">
                        <label>Confirm New Password</label>
                        <div class="password-toggle-wrapper" style="position: relative; display: block;">
                            <input type="password" name="confirm_password" required placeholder="Re-enter new password" style="padding-right: 40px;">
                            <i class="fa-regular fa-eye toggle-password-visibility-icon" onclick="togglePasswordVisibility(this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; z-index: 5;"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-admin">
                        <i class="fa-solid fa-lock" style="margin-right: 8px;"></i>Update Password
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: CLIENT MESSAGES -->
        <?php if ($activeTab === 'messages'): ?>
            <div class="workspace-block">
                <div class="block-title">Client Inquiry Messages</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sender Name</th>
                                <th>Contact Details</th>
                                <th>Message</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($allMessages) > 0): ?>
                                <?php foreach ($allMessages as $msg): ?>
                                    <tr>
                                        <td style="white-space: nowrap; font-size: 0.8rem;"><?= date('d M Y H:i', strtotime($msg['created_at'])) ?></td>
                                        <td><strong><?= e($msg['name']) ?></strong></td>
                                        <td style="font-size: 0.8rem;">
                                            <div><i class="fa-solid fa-envelope" style="color:var(--color-gold); width:16px;"></i><?= e($msg['email']) ?></div>
                                            <div style="margin-top:4px;"><i class="fa-solid fa-phone" style="color:var(--color-gold); width:16px;"></i><?= e($msg['phone']) ?></div>
                                        </td>
                                        <td style="font-size: 0.85rem; max-width: 400px; word-wrap: break-word; white-space: normal; line-height: 1.5;"><?= nl2br(e($msg['message'])) ?></td>
                                        <td style="text-align: center;">
                                            <form action="<?= BASE_URL ?>/admin?tab=messages" method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="admin_action" value="delete_message">
                                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                <button type="submit" class="btn-admin" style="padding: 6px 12px; font-size: 0.75rem; background-color: #c0392b; color: white; border-color: #c0392b;">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--color-text-muted);">No messages found in database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB CONTENT: SETTINGS -->
        <?php if ($activeTab === 'settings'): ?>
            <div class="workspace-block">
                <div class="block-title">Store Shipping & Delivery Settings</div>
                <form action="<?= BASE_URL ?>/admin?tab=settings" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="admin_action" value="update_shipping_settings">

                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Flat Delivery Charge (₹)</label>
                            <input type="number" step="0.01" name="shipping_flat_rate" required value="<?= e(getSetting('shipping_flat_rate', '200.00')) ?>">
                            <small style="color: var(--color-text-muted); font-size: 0.75rem;">This charge applies to orders under the free shipping threshold.</small>
                        </div>
                        <div class="admin-form-group">
                            <label>Free Shipping Threshold (₹)</label>
                            <input type="number" step="0.01" name="shipping_free_threshold" required value="<?= e(getSetting('shipping_free_threshold', '1500.00')) ?>">
                            <small style="color: var(--color-text-muted); font-size: 0.75rem;">Orders with a subtotal equal to or greater than this amount qualify for FREE shipping.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-gold" style="margin-top: 15px;">Save Settings</button>
                </form>
            </div>

            <div class="workspace-block" style="margin-top: 30px;">
                <div class="block-title">Hero Slideshow Images</div>
                <form action="<?= BASE_URL ?>/admin?tab=settings" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="admin_action" value="update_hero_images">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px;">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <?php 
                            $imagePath = BASE_URL . "/assets/images/hero/1 ($i).jpeg";
                            $imageSrc = $imagePath . "?v=" . time();
                            ?>
                            <div class="admin-form-group" style="border: 1px solid var(--color-border); padding: 15px; border-radius: 4px; background: #fafafa;">
                                <label style="margin-bottom: 10px; display: block; font-weight: 600;">Hero Image <?= $i ?></label>
                                <div style="margin-bottom: 15px; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #eee; border: 1px solid #ddd; border-radius: 2px;">
                                    <img src="<?= $imageSrc ?>" alt="Hero <?= $i ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                </div>
                                <input type="file" name="hero_image_<?= $i ?>" accept="image/*" style="padding: 5px 0; height: auto; font-size: 0.8rem; width: 100%;">
                                <small style="color: var(--color-text-muted); font-size: 0.75rem; display: block; margin-top: 5px;">Recommended size: 1920x800px</small>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-gold">Upload Hero Images</button>
                </form>
            </div>

            <div class="workspace-block" style="margin-top: 30px;">
                <div class="block-title">Haute Collections Banner Images</div>
                <form action="<?= BASE_URL ?>/admin?tab=settings" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="admin_action" value="update_haute_images">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px;">
                        <!-- Men's Collection -->
                        <?php 
                        $menPath = "assets/images/Haute Collection/men.jpeg";
                        $menSrc = BASE_URL . "/" . $menPath . "?v=" . (file_exists(__DIR__ . '/../' . $menPath) ? filemtime(__DIR__ . '/../' . $menPath) : time());
                        ?>
                        <div class="admin-form-group" style="border: 1px solid var(--color-border); padding: 15px; border-radius: 4px; background: #fafafa;">
                            <label style="margin-bottom: 10px; display: block; font-weight: 600;">For Him (Men's Collection)</label>
                            <div style="margin-bottom: 15px; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #eee; border: 1px solid #ddd; border-radius: 2px;">
                                <img src="<?= $menSrc ?>" alt="Men's Banner" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                            </div>
                            <input type="file" name="haute_men_image" accept="image/*" style="padding: 5px 0; height: auto; font-size: 0.8rem; width: 100%;">
                            <small style="color: var(--color-text-muted); font-size: 0.75rem; display: block; margin-top: 5px;">Allowed: JPG, JPEG, PNG, WEBP</small>
                        </div>

                        <!-- Women's Collection -->
                        <?php 
                        $womenPath = "assets/images/Haute Collection/women.jpeg";
                        $womenSrc = BASE_URL . "/" . $womenPath . "?v=" . (file_exists(__DIR__ . '/../' . $womenPath) ? filemtime(__DIR__ . '/../' . $womenPath) : time());
                        ?>
                        <div class="admin-form-group" style="border: 1px solid var(--color-border); padding: 15px; border-radius: 4px; background: #fafafa;">
                            <label style="margin-bottom: 10px; display: block; font-weight: 600;">For Her (Women's Collection)</label>
                            <div style="margin-bottom: 15px; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #eee; border: 1px solid #ddd; border-radius: 2px;">
                                <img src="<?= $womenSrc ?>" alt="Women's Banner" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                            </div>
                            <input type="file" name="haute_women_image" accept="image/*" style="padding: 5px 0; height: auto; font-size: 0.8rem; width: 100%;">
                            <small style="color: var(--color-text-muted); font-size: 0.75rem; display: block; margin-top: 5px;">Allowed: JPG, JPEG, PNG, WEBP</small>
                        </div>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-gold">Upload Haute Collection Images</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
function togglePasswordVisibility(icon) {
    const input = icon.previousElementSibling;
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

function openEditModal(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_brand').value = product.brand;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_gender').value = product.gender;
    document.getElementById('edit_fragrance_type').value = product.fragrance_type;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_discount_price').value = product.discount_price;
    document.getElementById('edit_stock').value = product.stock;
    document.getElementById('edit_top_notes').value = product.top_notes;
    document.getElementById('edit_heart_notes').value = product.heart_notes;
    document.getElementById('edit_base_notes').value = product.base_notes;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_rating').value = product.rating;
    
    document.getElementById('edit_is_best_seller').checked = (parseInt(product.is_best_seller) === 1);
    document.getElementById('edit_is_new_arrival').checked = (parseInt(product.is_new_arrival) === 1);
    
    document.getElementById('editProductModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editProductModal').style.display = 'none';
}

// Close modal when clicking outside content area
window.onclick = function(event) {
    const modal = document.getElementById('editProductModal');
    if (modal && event.target === modal) {
        closeEditModal();
    }
}
</script>

</body>
</html>
