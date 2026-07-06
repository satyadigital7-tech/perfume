<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?= isset($pageTitle) ? e($pageTitle) . " | Elixir & Co." : "Elixir & Co. | Luxury Perfumes" ?></title>
    <meta name="description" content="<?= isset($metaDesc) ? e($metaDesc) : "Discover the art of luxury fragrance. Premium perfumes for men and women." ?>">
    
    <!-- Open Graph tags for SEO -->
    <meta property="og:title" content="<?= isset($pageTitle) ? e($pageTitle) . " | Elixir & Co." : "Elixir & Co. | Luxury Perfumes" ?>">
    <meta property="og:description" content="<?= isset($metaDesc) ? e($metaDesc) : "Discover the art of luxury fragrance. Premium perfumes for men and women." ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/images/LOGO.png">

    <!-- Favicon — All Browsers & Devices -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>/assets/images/LOGO.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/images/LOGO.png">
    <meta name="theme-color" content="#c8a96e">


    <!-- Google Fonts: Playfair Display & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<!-- Header / Navigation -->
<header class="main-header">
    <div class="header-top">
        <div class="header-container">
            <div class="header-promo">
                <span>Free Express Shipping on Orders Over ₹<?= number_format((float)getSetting('shipping_free_threshold', '1500.00')) ?> | Premium Packaging</span>
            </div>
            <div class="header-top-links">
                <a href="<?= BASE_URL ?>/order-tracking"><i class="fa-solid fa-truck"></i> Track Order</a>
                <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin" style="color: var(--color-gold); font-weight: 600;"><i class="fa-solid fa-user-gear"></i> Admin Panel</a>
                    <a href="<?= BASE_URL ?>/account?logout=1"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
                <?php elseif (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/account"><i class="fa-solid fa-user"></i> My Account</a>
                    <a href="<?= BASE_URL ?>/account?logout=1"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/account" class="login-trigger"><i class="fa-solid fa-user-lock"></i> Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="header-middle">
        <div class="header-container">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Open Menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>/" class="brand-logo">
                <img src="<?= BASE_URL ?>/assets/images/LOGO.png" alt="Elixir & Co. Logo" class="logo-img">
            </a>

            <!-- Live Search Bar -->
            <div class="search-wrapper">
                <form action="<?= BASE_URL ?>/search" method="GET" class="search-form">
                    <input type="text" name="q" id="search-input" placeholder="Search brands, scents, woody, floral..." autocomplete="off" value="<?= e($_GET['q'] ?? '') ?>">
                    <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <!-- Autocomplete suggestions dropdown -->
                <div id="search-suggestions" class="search-suggestions-dropdown" style="display: none;"></div>
            </div>

            <!-- Action Icons (Wishlist, Cart) -->
            <div class="header-actions">
                <!-- Wishlist -->
                <a href="<?= BASE_URL ?>/wishlist" class="header-action-btn" aria-label="Wishlist">
                    <i class="fa-regular fa-heart"></i>
                    <?php 
                    $wishCount = 0;
                    if (isLoggedIn()) {
                        $db = getDB();
                        $wStmt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
                        $wStmt->execute([$_SESSION['user_id']]);
                        $wishCount = $wStmt->fetchColumn();
                    }
                    ?>
                    <span class="badge" id="wishlist-count" style="<?= $wishCount > 0 ? '' : 'display: none;' ?>"><?= $wishCount ?></span>
                </a>

                <!-- Cart -->
                <a href="<?= BASE_URL ?>/cart" class="header-action-btn" aria-label="Shopping Cart">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php 
                    $cartCount = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $qty) {
                            $cartCount += $qty;
                        }
                    }
                    ?>
                    <span class="badge" id="cart-count" style="<?= $cartCount > 0 ? '' : 'display: none;' ?>"><?= $cartCount ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Menu -->
    <nav class="main-nav">
        <div class="header-container">
            <ul class="nav-links">
                <li><a href="<?= BASE_URL ?>/" class="nav-link-item">Home</a></li>
                <li><a href="<?= BASE_URL ?>/search" class="nav-link-item">Shop</a></li>
                <li><a href="<?= BASE_URL ?>/bestsellers" class="nav-link-item">Bestsellers</a></li>
                <li><a href="<?= BASE_URL ?>/about" class="nav-link-item">Our Story</a></li>
                <li><a href="<?= BASE_URL ?>/contact" class="nav-link-item">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Nav Drawer -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
    <aside class="mobile-nav-drawer" id="mobile-nav-drawer">
        <div class="mobile-nav-header">
            <span class="mobile-nav-logo">Elixir & Co.</span>
            <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Close Menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="mobile-nav-body">
            <ul class="mobile-nav-links">
                <li><a href="<?= BASE_URL ?>/">Home</a></li>
                <li><a href="<?= BASE_URL ?>/search">Shop</a></li>
                <li><a href="<?= BASE_URL ?>/bestsellers">Bestsellers</a></li>
                <li><a href="<?= BASE_URL ?>/about">Our Story</a></li>
                <li><a href="<?= BASE_URL ?>/contact">Contact</a></li>
            </ul>
            <div class="mobile-nav-extra">
                <a href="<?= BASE_URL ?>/order-tracking"><i class="fa-solid fa-truck"></i> Track Order</a>
                <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin" style="color: var(--color-gold); font-weight: 600;"><i class="fa-solid fa-user-gear"></i> Admin Panel</a>
                    <a href="<?= BASE_URL ?>/account?logout=1"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
                <?php elseif (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/account"><i class="fa-solid fa-user"></i> My Account</a>
                    <a href="<?= BASE_URL ?>/account?logout=1"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/account" class="login-trigger"><i class="fa-solid fa-user-lock"></i> Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <!-- Login / Register Modal Dialog -->
    <div class="login-modal-overlay" id="login-modal-overlay">
        <div class="login-modal">
            <button class="login-modal-close" id="login-modal-close" aria-label="Close Modal">&times;</button>
            
            <div class="login-modal-tabs">
                <button class="login-modal-tab active" id="modal-tab-login" onclick="switchLoginTab('login')">Sign In</button>
                <button class="login-modal-tab" id="modal-tab-register" onclick="switchLoginTab('register')">Register</button>
            </div>
            
            <!-- Login Form -->
            <form id="modal-login-form" action="<?= BASE_URL ?>/account" method="POST" class="login-modal-form active">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="form_action" value="login">
                <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                
                <h3 class="modal-form-title">Patron Login</h3>
                <p class="modal-form-subtitle">Access your personal olfactory configurations.</p>
                
                <div class="form-group">
                    <label for="modal-login-input">Email or Mobile</label>
                    <input type="text" id="modal-login-input" name="login_input" required placeholder="patron@domain.com">
                </div>
                
                <div class="form-group">
                    <label for="modal-login-password">Password</label>
                    <input type="password" id="modal-login-password" name="password" required placeholder="••••••••">
                </div>
                
                <a href="#" onclick="switchLoginTab('forgot'); return false;" style="display:block; text-align:right; font-size:0.75rem; color:var(--color-gold); margin:-10px 0 15px 0;">Forgot Password?</a>
                
                <button type="submit" class="btn btn-gold modal-submit-btn">Sign In</button>
            </form>
            
            <!-- Register Form -->
            <form id="modal-register-form" action="<?= BASE_URL ?>/account" method="POST" class="login-modal-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="form_action" value="register">
                <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                
                <h3 class="modal-form-title">Join the Lounge</h3>
                <p class="modal-form-subtitle">Enlist in the registry for private status tracking.</p>
                
                <div class="form-group">
                    <label for="modal-reg-name">Full Name</label>
                    <input type="text" id="modal-reg-name" name="full_name" required placeholder="e.g. John Doe">
                </div>
                
                <div class="form-row" style="margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal-reg-email">Email Address</label>
                        <input type="email" id="modal-reg-email" name="email" required placeholder="name@domain.com">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal-reg-mobile">Mobile Number</label>
                        <input type="text" id="modal-reg-mobile" name="mobile" required placeholder="e.g. 9876543210">
                    </div>
                </div>
                
                <div class="form-row" style="margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal-reg-password">Password</label>
                        <input type="password" id="modal-reg-password" name="password" required placeholder="Choose a secure password">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal-reg-confirm-password">Confirm Password</label>
                        <input type="password" id="modal-reg-confirm-password" name="confirm_password" required placeholder="Re-enter your password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-black modal-submit-btn">Create Account</button>
            </form>

            <!-- Forgot Password — Enter Email Form -->
            <form id="modal-forgot-form" action="<?= BASE_URL ?>/account" method="POST" class="login-modal-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="form_action" value="send_reset_link">

                <h3 class="modal-form-title">Forgot Password</h3>
                <p class="modal-form-subtitle">Enter your registered email and we'll send you a secure reset link.</p>

                <div class="form-group">
                    <label for="modal-forgot-email">Email Address</label>
                    <input type="email" id="modal-forgot-email" name="email" required placeholder="name@domain.com" autocomplete="email">
                </div>

                <button type="submit" class="btn btn-gold modal-submit-btn" id="forgot-submit-btn">Send Reset Link</button>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="#" onclick="switchLoginTab('login'); return false;" style="font-size: 0.8rem; color: var(--color-gold);">← Back to Sign In</a>
                </div>
            </form>

            <!-- Forgot Password — Success State (shown via JS) -->
            <div id="modal-forgot-success" class="login-modal-form" style="display:none; text-align:center;">
                <div style="width:64px;height:64px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem;color:#166534;">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                </div>
                <h3 class="modal-form-title">Check Your Email</h3>
                <p class="modal-form-subtitle" id="forgot-success-msg"
                   style="color:#555; font-size:0.9rem; line-height:1.7;">
                    If an account exists with this email, we've sent a password reset link.<br>
                    <strong>The link expires in 30 minutes.</strong><br>
                    Please also check your spam/junk folder.
                </p>
                <a href="#" onclick="switchLoginTab('login'); return false;"
                   class="btn btn-black modal-submit-btn" style="display:block; text-decoration:none; margin-top:20px;">
                    Back to Sign In
                </a>
            </div>



        </div>
    </div>
</header>

<!-- Global Toast Notification System -->
<?php include __DIR__ . '/notification.php'; ?>

<main class="main-content-wrapper">
