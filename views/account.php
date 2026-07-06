<?php
$pageTitle = "My Account";
$metaDesc = "Register, login, or manage your personal details, order history, and addresses in the Elixir & Co. client lounge.";
require_once __DIR__ . '/../config/db.php';

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['is_admin']);
    setFlashMessage('success', 'Logged out successfully. Have a beautiful day.');
    header("Location: " . BASE_URL . "/");
    exit;
}

// If administrator is browsing, redirect to the admin panel
if (isAdmin()) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$redirectToVal = $_GET['redirect_to'] ?? $_POST['redirect_to'] ?? '';
if (!empty($redirectToVal) && !(str_starts_with($redirectToVal, '/') || str_starts_with($redirectToVal, BASE_URL))) {
    $redirectToVal = '';
}

$db = getDB();

// Handle Reorder Trigger
if (isset($_GET['reorder_id']) && isLoggedIn()) {
    $reorderId = (int)$_GET['reorder_id'];
    $chkStmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $chkStmt->execute([$reorderId, $_SESSION['user_id']]);
    if ($chkStmt->fetch()) {
        $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$reorderId]);
        $items = $itemsStmt->fetchAll();
        
        foreach ($items as $item) {
            $pId = $item['product_id'];
            $qty = $item['quantity'];
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][$pId] = ($_SESSION['cart'][$pId] ?? 0) + $qty;
        }
        setFlashMessage('success', 'Items from Order added to your cart.');
        header("Location: " . BASE_URL . "/cart");
        exit;
    }
}

// Handle Form Submissions
$isAjax = false;
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $isAjax = isset($_POST['is_ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $action = $_POST['form_action'];

    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errorMsg = "Invalid security token. Please try again.";
    } else {
        if ($action === 'register') {
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $mobile = trim($_POST['mobile']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validations
            if (empty($fullName) || empty($email) || empty($mobile) || empty($password) || empty($confirmPassword)) {
                $errorMsg = "All fields are required for registration.";
            } elseif ($password !== $confirmPassword) {
                $errorMsg = "Passwords do not match.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = "Please enter a valid email address.";
            } else {
                // Check if email already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $errorMsg = "An account with this email address already exists.";
                } else {
                    // Create User
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $db->prepare("INSERT INTO users (full_name, email, mobile, password) VALUES (?, ?, ?, ?)");
                    $ins->execute([$fullName, $email, $mobile, $hashedPassword]);
                    
                    $_SESSION['user_id'] = $db->lastInsertId();
                    $successMsg = 'Welcome to Elixir & Co.! Your account was created.';
                    setFlashMessage('success', $successMsg);
                    $redirectTo = $_POST['redirect_to'] ?? '';
                    if (empty($redirectTo) || !(str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, BASE_URL))) {
                        $redirectTo = BASE_URL . "/account";
                    }
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'success', 'redirect' => $redirectTo, 'message' => $successMsg]);
                        exit;
                    }
                    header("Location: " . $redirectTo);
                    exit;
                }
            }
        } elseif ($action === 'login') {
            $loginInput = trim($_POST['login_input']); // can be email or mobile
            $password = $_POST['password'];

            if (empty($loginInput) || empty($password)) {
                $errorMsg = "Please enter your credentials.";
            } else {
                // Find User
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR mobile = ?");
                $stmt->execute([$loginInput, $loginInput]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $successMsg = 'Welcome back, ' . $user['full_name'] . '.';
                    setFlashMessage('success', $successMsg);
                    $redirectTo = $_POST['redirect_to'] ?? '';
                    if (empty($redirectTo) || !(str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, BASE_URL))) {
                        $redirectTo = BASE_URL . "/account";
                    }
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'success', 'redirect' => $redirectTo, 'message' => $successMsg]);
                        exit;
                    }
                    header("Location: " . $redirectTo);
                    exit;
                } else {
                    $errorMsg = "Invalid email/mobile or password combination.";
                }
            }
        } elseif ($action === 'send_reset_link') {
            // ── Secure Token-Based Password Reset ───────────────────────────────
            $email = trim($_POST['email'] ?? '');

            // Always show the same success message regardless of whether email exists
            // This prevents email enumeration attacks
            $genericSuccess = "If an account exists with this email, we've sent a password reset link. Please check your inbox (and spam folder).";

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Delete any existing reset tokens for this email
                    $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

                    // Generate 6-digit numeric OTP
                    $token     = sprintf("%06d", mt_rand(100000, 999999));
                    $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

                    // Store token in DB
                    $ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $ins->execute([$email, $token, $expiresAt]);

                    // Build reset URL
                    $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                                . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/reset-password?token=' . $token;

                    $name    = $user['full_name'];
                    $subject = "Your Password Reset OTP — Elixir & Co.";
                    $message = "Hello {$name},\n\n"
                             . "We received a request to reset your Elixir & Co. account password.\n\n"
                             . "Your 6-digit password reset OTP is: {$token}\n\n"
                             . "You can enter this OTP on the password reset page, or simply click the link below to reset it automatically:\n\n"
                             . "{$resetUrl}\n\n"
                             . "This OTP and link will expire in 30 minutes.\n\n"
                             . "If you didn't request this, you can safely ignore this email.\n\n"
                             . "Thank you,\nElixir & Co. Team\nhttps://elixircoperfumes.in";

                    sendMail($email, $subject, $message);
                    // Note: We don't check mail result — always show generic success
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => $genericSuccess]);
                exit;
            }
            setFlashMessage('success', $genericSuccess);

        } elseif ($action === 'update_profile') {
            if (isLoggedIn()) {
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $mobile = trim($_POST['mobile']);

                if (empty($fullName) || empty($email) || empty($mobile)) {
                    $errorMsg = "All profile fields are required.";
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, mobile = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $mobile, $_SESSION['user_id']]);
                    setFlashMessage('success', 'Personal information updated successfully.');
                    header("Location: " . BASE_URL . "/account?tab=profile");
                    exit;
                }
            }
        } elseif ($action === 'update_address') {
            if (isLoggedIn()) {
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $state = trim($_POST['state']);
                $pincode = trim($_POST['pincode']);

                if (empty($address) || empty($city) || empty($state) || empty($pincode)) {
                    $errorMsg = "All address fields are required.";
                } else {
                    $stmt = $db->prepare("UPDATE users SET address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
                    $stmt->execute([$address, $city, $state, $pincode, $_SESSION['user_id']]);
                    setFlashMessage('success', 'Saved address updated successfully.');
                    header("Location: " . BASE_URL . "/account?tab=address");
                    exit;
                }
            }
        } elseif ($action === 'change_password') {
            if (isLoggedIn()) {
                $oldPassword = $_POST['old_password'];
                $newPassword = $_POST['new_password'];

                if (empty($oldPassword) || empty($newPassword)) {
                    $errorMsg = "Please fill in all password fields.";
                } else {
                    // Check old password
                    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $currentHash = $stmt->fetchColumn();

                    if (password_verify($oldPassword, $currentHash)) {
                        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upd->execute([$newHash, $_SESSION['user_id']]);
                        setFlashMessage('success', 'Password updated successfully.');
                        header("Location: " . BASE_URL . "/account?tab=password");
                        exit;
                    } else {
                        $errorMsg = "Current password check failed. Please try again.";
                    }
                }
            }
        }
    }
}

// Redirect error message if any
if ($errorMsg) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
        exit;
    }
    setFlashMessage('error', $errorMsg);
}

// If user is logged in, pull full user record and order history
$user = null;
$orders = [];
if (isLoggedIn()) {
    $user = getLoggedInUser();
    
    // Fetch orders
    $ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $ordersStmt->execute([$_SESSION['user_id']]);
    $orders = $ordersStmt->fetchAll();
}

$activeTab = $_GET['tab'] ?? 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<div class="header-container">
    <?php if (!isLoggedIn()): ?>
        
        <!-- Authentication View (Login & Register) -->
        <div class="account-layout">
            
            <!-- Login -->
            <div class="auth-card">
                <h2>Patron Login</h2>
                <p>Welcome back. Access your saved shipping details and purchase history.</p>
                <form action="<?= BASE_URL ?>/account" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="login">
                    <input type="hidden" name="redirect_to" value="<?= e($redirectToVal) ?>">
                    
                    <div class="form-group">
                        <label for="login-input">Email or Mobile Number</label>
                        <input type="text" id="login-input" name="login_input" required placeholder="name@domain.com">
                    </div>

                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>



                    <button type="submit" class="btn btn-gold" style="width: 100%;">Sign In</button>
                </form>
            </div>

            <!-- Registration -->
            <div class="auth-card">
                <h2>Join the Lounge</h2>
                <p>Create an account to gain loyalty points, track orders, and curate your wishlist.</p>
                <form action="<?= BASE_URL ?>/account" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="register">
                    <input type="hidden" name="redirect_to" value="<?= e($redirectToVal) ?>">

                    <div class="form-group">
                        <label for="reg-name">Full Name</label>
                        <input type="text" id="reg-name" name="full_name" required placeholder="Alexander Mercer">
                    </div>

                    <div class="form-group">
                        <label for="reg-email">Email Address</label>
                        <input type="email" id="reg-email" name="email" required placeholder="mercer@domain.com">
                    </div>

                    <div class="form-group">
                        <label for="reg-mobile">Mobile Number</label>
                        <input type="text" id="reg-mobile" name="mobile" required placeholder="+1 (555) 000-0000">
                    </div>

                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <input type="password" id="reg-password" name="password" required placeholder="Choose a secure password">
                    </div>

                    <div class="form-group">
                        <label for="reg-confirm-password">Confirm Password</label>
                        <input type="password" id="reg-confirm-password" name="confirm_password" required placeholder="Re-enter your password">
                    </div>

                    <button type="submit" class="btn btn-black" style="width: 100%;">Create Account</button>
                </form>
            </div>

        </div>

    <?php else: ?>
        
        <!-- Logged-in Dashboard Layout -->
        <div class="dashboard-container">
            
            <!-- Dashboard Navigation Sidebar -->
            <aside class="dashboard-nav">
                <div style="text-align: center; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--color-medium-gray);">
                    <div style="font-size: 2.5rem; color: var(--color-gold);"><i class="fa-solid fa-circle-user"></i></div>
                    <h4 style="font-family: var(--font-heading); font-size: 1.15rem; margin-top: 5px;"><?= e($user['full_name']) ?></h4>
                    <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?= e($user['email']) ?></span>
                </div>
                <a href="?tab=dashboard" class="dashboard-nav-item <?= $activeTab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin" class="dashboard-nav-item" style="background-color: var(--color-gold-light); color: var(--color-black); border-left: 4px solid var(--color-gold); font-weight: 600;"><i class="fa-solid fa-user-gear"></i> Admin Panel</a>
                <?php endif; ?>
                <a href="?tab=orders" class="dashboard-nav-item <?= $activeTab === 'orders' ? 'active' : '' ?>">Order History</a>
                <a href="?tab=profile" class="dashboard-nav-item <?= $activeTab === 'profile' ? 'active' : '' ?>">Personal Info</a>
                <a href="?tab=address" class="dashboard-nav-item <?= $activeTab === 'address' ? 'active' : '' ?>">Saved Addresses</a>
                <a href="<?= BASE_URL ?>/wishlist" class="dashboard-nav-item">My Wishlist</a>
                <a href="?tab=password" class="dashboard-nav-item <?= $activeTab === 'password' ? 'active' : '' ?>">Change Password</a>
                <a href="?logout=1" class="dashboard-nav-item" style="color: var(--color-error);"><i class="fa-solid fa-power-off"></i> Logout</a>
            </aside>

            <!-- Dashboard Content Panels -->
            <section class="dashboard-content">
                
                <!-- Panel 1: General Dashboard -->
                <div class="dashboard-panel <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                    <h3>Patron Dashboard</h3>
                    <p style="margin-bottom: 20px;">Welcome back, <strong><?= e($user['full_name']) ?></strong>.</p>
                    
                    <?php if (isAdmin()): ?>
                        <div style="background: var(--color-gold-light); border: 1px solid var(--color-gold); padding: 20px; border-radius: 0; margin-bottom: 25px; text-align: left;">
                            <h4 style="color: var(--color-black); font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;"><i class="fa-solid fa-user-shield"></i> Administrator Session</h4>
                            <p style="font-size: 0.85rem; margin-bottom: 15px; color: #555; line-height: 1.5;">You are logged in as an administrator. Access the control panel to manage products, adjust inventory, view details of user transactions, and issue promotional coupons.</p>
                            <a href="<?= BASE_URL ?>/admin" class="btn btn-gold" style="font-size: 0.8rem; padding: 8px 20px;">Go to Admin Panel</a>
                        </div>
                    <?php endif; ?>

                    <p style="margin-bottom: 30px; font-size: 0.95rem; color: var(--color-text-muted);">From your personal account dashboard, you can view your recent orders, manage your shipping and billing addresses, and edit your password and profile details.</p>
                    
                    <div class="dashboard-cards-grid">
                        <div style="border: 1px solid var(--color-medium-gray); padding: 25px; text-align: center;">
                            <i class="fa-solid fa-basket-shopping" style="font-size: 2rem; color: var(--color-gold); margin-bottom: 15px;"></i>
                            <h4>Curate Scents</h4>
                            <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 10px 0;">Find new woody, citrus, and floral releases.</p>
                            <a href="<?= BASE_URL ?>/shop" class="btn btn-black" style="font-size: 0.75rem; padding: 6px 15px;">Go Shop</a>
                        </div>
                        <div style="border: 1px solid var(--color-medium-gray); padding: 25px; text-align: center;">
                            <i class="fa-solid fa-location-arrow" style="font-size: 2rem; color: var(--color-gold); margin-bottom: 15px;"></i>
                            <h4>Track Cargo</h4>
                            <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 10px 0;">Keep an eye on shipped luxury parcels.</p>
                            <a href="<?= BASE_URL ?>/order-tracking" class="btn btn-outline-gold" style="font-size: 0.75rem; padding: 6px 15px;">Track Orders</a>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: Orders list -->
                <div class="dashboard-panel <?= $activeTab === 'orders' ? 'active' : '' ?>">
                    <h3>Purchase History</h3>
                    <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date Placed</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td data-label="Order ID"><strong><?= e($order['order_id']) ?></strong></td>
                                            <td data-label="Date Placed"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td data-label="Total Amount">₹<?= number_format($order['total'], 2) ?></td>
                                            <td data-label="Status">
                                                <?php 
                                                $st = strtolower($order['order_status']);
                                                $stClass = 'pending';
                                                if ($st === 'order received') $stClass = 'pending';
                                                if ($st === 'confirmed') $stClass = 'confirmed';
                                                if ($st === 'processing') $stClass = 'processing';
                                                if ($st === 'shipped') $stClass = 'shipped';
                                                if ($st === 'delivered') $stClass = 'delivered';
                                                ?>
                                                <span class="status-badge <?= $stClass ?>"><?= e($order['order_status']) ?></span>
                                            </td>
                                            <td data-label="Action">
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <a href="<?= BASE_URL ?>/order-tracking?order_id=<?= urlencode($order['order_id']) ?>&mobile=<?= urlencode($order['billing_mobile']) ?>" class="btn btn-black" style="font-size: 0.65rem; padding: 4px 8px;">View / Track</a>
                                                    <a href="<?= BASE_URL ?>/invoice?order_id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-gold" style="font-size: 0.65rem; padding: 4px 8px;"><i class="fa-solid fa-file-invoice"></i> Invoice</a>
                                                    <a href="?tab=orders&reorder_id=<?= $order['id'] ?>" class="btn btn-gold" style="font-size: 0.65rem; padding: 4px 8px;">Reorder</a>
                                                    <?php
                                                    $waQuery = "Hello,\n\nI would like an update on my order.\n\nOrder ID: " . $order['order_id'] . "\n\nPlease share the current status.";
                                                    $waContactUrl = "https://wa.me/919071233343?text=" . urlencode($waQuery);
                                                    ?>
                                                    <a href="<?= $waContactUrl ?>" target="_blank" class="btn btn-outline-gold" style="font-size: 0.65rem; padding: 4px 8px;"><i class="fa-brands fa-whatsapp"></i> Chat</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--color-text-muted); font-style: italic;">You have not placed any orders yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Panel 3: Profile info -->
                <div class="dashboard-panel <?= $activeTab === 'profile' ? 'active' : '' ?>">
                    <h3>Edit Profile Details</h3>
                    <form action="<?= BASE_URL ?>/account?tab=profile" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="p-name">Full Name</label>
                            <input type="text" id="p-name" name="full_name" required value="<?= e($user['full_name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="p-email">Email Address</label>
                            <input type="email" id="p-email" name="email" required value="<?= e($user['email']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="p-mobile">Mobile Number</label>
                            <input type="text" id="p-mobile" name="mobile" required value="<?= e($user['mobile']) ?>">
                        </div>

                        <button type="submit" class="btn btn-gold">Save Profile Changes</button>
                    </form>
                </div>

                <!-- Panel 4: Address info -->
                <div class="dashboard-panel <?= $activeTab === 'address' ? 'active' : '' ?>">
                    <h3>Saved Delivery Address</h3>
                    <form action="<?= BASE_URL ?>/account?tab=address" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="update_address">

                        <div class="form-group">
                            <label for="a-addr">Address</label>
                            <textarea id="a-addr" name="address" rows="3" required placeholder="Apartment, Street Address"><?= e($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="a-city">City</label>
                                <input type="text" id="a-city" name="city" required value="<?= e($user['city'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="a-state">State / Province</label>
                                <input type="text" id="a-state" name="state" required value="<?= e($user['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="a-pin">Pincode / Zipcode</label>
                            <input type="text" id="a-pin" name="pincode" required value="<?= e($user['pincode'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-gold">Update Saved Address</button>
                    </form>
                </div>

                <!-- Panel 5: Change Password -->
                <div class="dashboard-panel <?= $activeTab === 'password' ? 'active' : '' ?>">
                    <h3>Change Password</h3>
                    <form action="<?= BASE_URL ?>/account?tab=password" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="change_password">

                        <div class="form-group">
                            <label for="pwd-old">Current Password</label>
                            <input type="password" id="pwd-old" name="old_password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        </div>

                        <div class="form-group">
                            <label for="pwd-new">New Password</label>
                            <input type="password" id="pwd-new" name="new_password" required placeholder="Enter new secure password">
                        </div>

                        <button type="submit" class="btn btn-gold">Update Password</button>
                    </form>
                </div>

            </section>

        </div>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
