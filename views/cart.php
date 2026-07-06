<?php
$pageTitle = "Shopping Cart";
$metaDesc = "Review the luxurious fragrances in your cart before moving to secure checkout.";
include __DIR__ . '/../includes/header.php';

$db = getDB();
$cartItems = [];
$subtotal = 0.00;

if (!empty($_SESSION['cart'])) {
    // Collect product details
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $price = (float)($p['discount_price'] > 0 ? $p['discount_price'] : $p['price']);
        $itemSubtotal = $price * $qty;
        
        $subtotal += $itemSubtotal;
        $cartItems[] = [
            'product' => $p,
            'quantity' => $qty,
            'price' => $price,
            'subtotal' => $itemSubtotal
        ];
    }
}

// Discount Calculation from applied coupon
$discountAmount = 0.00;
$couponCode = '';
if (isset($_SESSION['applied_coupon'])) {
    $couponCode = $_SESSION['applied_coupon']['code'];
    $val = (float)$_SESSION['applied_coupon']['value'];
    if ($_SESSION['applied_coupon']['discount_type'] === 'percent') {
        $discountAmount = ($subtotal * $val) / 100;
    } else {
        $discountAmount = min($val, $subtotal); // fixed coupon cannot exceed subtotal
    }
}

$discountedSubtotal = max(0, $subtotal - $discountAmount);

// Shipping policy: Free above threshold, else flat rate
$shipping = 0.00;
if ($discountedSubtotal > 0) {
    $freeThreshold = (float)getSetting('shipping_free_threshold', '1500.00');
    $flatRate = (float)getSetting('shipping_flat_rate', '200.00');
    $shipping = $discountedSubtotal >= $freeThreshold ? 0.00 : $flatRate;
}

// Tax policy: 10% tax rate
$tax = $discountedSubtotal * 0.10;
$total = $discountedSubtotal + $shipping + $tax;

// Store calculations in session for checkout reference
$_SESSION['order_summary'] = [
    'subtotal' => $subtotal,
    'discount' => $discountAmount,
    'coupon_code' => $couponCode,
    'shipping' => $shipping,
    'tax' => $tax,
    'total' => $total
];
?>

<div class="header-container">
    <?php if (count($cartItems) > 0): ?>
        <div class="cart-layout">
            
            <!-- Cart Items List Table -->
            <div class="cart-table-wrapper">
                <h1 class="cart-title">Your Olfactory Selections</h1>
                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-item-detail">
                                            <img src="<?= BASE_URL ?>/assets/images/<?= e($item['product']['image_url']) ?>" class="cart-item-image" alt="<?= e($item['product']['name']) ?>">
                                            <div class="cart-item-title">
                                                <h4><a href="<?= BASE_URL ?>/product/<?= $item['product']['id'] ?>"><?= e($item['product']['name']) ?></a></h4>
                                                <p><?= e($item['product']['brand']) ?> | <?= e($item['product']['gender']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart-item-price">₹<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <div class="qty-selector" style="width: 100px;">
                                            <button type="button" onclick="updateCartQty(<?= $item['product']['id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                            <input type="text" value="<?= $item['quantity'] ?>" readonly style="width: 30px; font-size: 0.85rem;">
                                            <button type="button" onclick="updateCartQty(<?= $item['product']['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-item-subtotal">₹<?= number_format($item['subtotal'], 2) ?></td>
                                    <td>
                                        <button class="btn-remove-item" onclick="removeFromCart(<?= $item['product']['id'] ?>)" aria-label="Remove item">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cart Invoice Summary Panel -->
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Scent Subtotal</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <?php if ($discountAmount > 0): ?>
                    <div class="summary-row discount">
                        <span>Coupon Discount (<?= e($couponCode) ?>) <a href="#" onclick="removeCoupon(); return false;" style="color: var(--color-error); font-size: 0.75rem; margin-left: 5px;">[Remove]</a></span>
                        <span>-₹<?= number_format($discountAmount, 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Express Shipping</span>
                    <span><?= $shipping == 0 ? '<span style="color: var(--color-success); font-weight:600;">FREE</span>' : '₹' . number_format($shipping, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Luxury Tax (10%)</span>
                    <span>₹<?= number_format($tax, 2) ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span>₹<?= number_format($total, 2) ?></span>
                </div>

                <!-- Coupon System input -->
                <?php if (empty($couponCode)): ?>
                    <div class="coupon-box">
                        <input type="text" id="coupon-input" placeholder="Promo Code: e.g. LUXURY10" autocomplete="off">
                        <button type="button" onclick="applyCoupon()">Apply</button>
                    </div>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/checkout" class="btn btn-gold btn-checkout">Proceed to Checkout</a>
                <?php else: ?>
                    <button type="button" class="btn btn-gold btn-checkout" onclick="openCheckoutLoginModal()" style="width: 100%; border: none; display: block; text-align: center; text-transform: uppercase; font-family: inherit; font-size: 0.85rem; font-weight: 600; letter-spacing: 1.5px; padding: 15px 30px; cursor: pointer; transition: background-color 0.2s;">Proceed to Checkout</button>
                <?php endif; ?>
            </div>

        </div>
    <?php else: ?>
        <div class="cart-empty">
            <i class="fa-solid fa-bag-shopping"></i>
            <h2>Your Cart is Empty</h2>
            <p>You have not added any luxury scents to your shopping cart yet.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-gold">Browse Collections</a>
        </div>
    <?php endif; ?>
</div>

<script>
function openCheckoutLoginModal() {
    // Dynamically update redirect inputs to send user to checkout page post-login
    const redirectInputs = document.querySelectorAll('#login-modal-overlay input[name="redirect_to"]');
    redirectInputs.forEach(input => {
        input.value = `${BASE_URL}/checkout`;
    });
    // Open the login modal
    if (typeof openLoginModal === 'function') {
        openLoginModal();
    } else {
        window.location.href = `${BASE_URL}/account?redirect_to=${encodeURIComponent(BASE_URL + '/checkout')}`;
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
