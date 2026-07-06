<?php
require_once __DIR__ . '/../config/db.php';
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/account?redirect_to=" . urlencode(BASE_URL . "/checkout"));
    exit;
}

$pageTitle = "Secure Checkout";
$metaDesc = "Enter billing details and choose payment methods to place your luxury fragrance order.";
include __DIR__ . '/../includes/header.php';

// Redirect if cart is empty
if (empty($_SESSION['cart']) || !isset($_SESSION['order_summary'])) {
    echo "<script>window.location.href = '" . BASE_URL . "/cart';</script>";
    exit;
}

$summary = $_SESSION['order_summary'];
$db = getDB();

// Pull logged-in user data for autofill
$currentUser = getLoggedInUser();

// Fetch cart items for rendering order summary
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $db->prepare("SELECT id, name, brand, price, discount_price FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products = $stmt->fetchAll();

$cartItemsSummary = [];
foreach ($products as $p) {
    $qty = $_SESSION['cart'][$p['id']];
    $price = (float)($p['discount_price'] > 0 ? $p['discount_price'] : $p['price']);
    $cartItemsSummary[] = [
        'name' => $p['name'],
        'brand' => $p['brand'],
        'qty' => $qty,
        'subtotal' => $price * $qty
    ];
}
?>

<div class="header-container">
    <div class="checkout-layout">
        
        <!-- Billing Details Form -->
        <div class="checkout-billing-form">
            <h1 class="checkout-title">Billing & Shipping Details</h1>
            
            <form id="checkout-form-element" onsubmit="event.preventDefault(); triggerCheckoutSubmit();">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="billing-name">Full Name <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="billing-name" name="full_name" required value="<?= e($currentUser['full_name'] ?? '') ?>" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label for="billing-email">Email Address <span style="color:var(--color-error)">*</span></label>
                        <input type="email" id="billing-email" name="email" required value="<?= e($currentUser['email'] ?? '') ?>" placeholder="john@example.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="billing-mobile">Mobile Number <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="billing-mobile" name="mobile" required value="<?= e($currentUser['mobile'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="form-group">
                        <label for="billing-pincode">Pincode / Zipcode <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="billing-pincode" name="pincode" required value="<?= e($currentUser['pincode'] ?? '') ?>" placeholder="10011">
                    </div>
                </div>

                <div class="form-group">
                    <label for="billing-address">Delivery Address <span style="color:var(--color-error)">*</span></label>
                    <textarea id="billing-address" name="address" rows="3" required placeholder="Apartment, Street Name, Block"><?= e($currentUser['address'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="billing-city">City <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="billing-city" name="city" required value="<?= e($currentUser['city'] ?? '') ?>" placeholder="New York">
                    </div>
                    <div class="form-group">
                        <label for="billing-state">State / Province <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="billing-state" name="state" required value="<?= e($currentUser['state'] ?? '') ?>" placeholder="NY">
                    </div>
                </div>

                <!-- Payment Methods Choice -->
                <div class="payment-methods-selector">
                    <h3 style="margin-bottom: 20px; font-family: var(--font-heading); font-size: 1.4rem;">Select Payment Method</h3>
                    
                    <!-- WhatsApp -->
                    <div class="payment-option active" onclick="selectPaymentOption(this, 'WhatsApp')" style="display: flex; align-items: flex-start; gap: 15px; border: 1px solid var(--color-gold); padding: 15px 20px; margin-bottom: 12px; cursor: pointer; transition: var(--transition-smooth); background-color: var(--color-light-gray);">
                        <input type="radio" name="payment_choice" id="pay-whatsapp" value="WhatsApp" checked style="margin-top: 4px; accent-color: var(--color-gold);">
                        <div class="payment-option-info">
                            <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 3px; display: flex; align-items: center; gap: 8px;"><i class="fa-brands fa-whatsapp" style="color: #25D366; font-size: 1.1rem;"></i> Confirm via WhatsApp</h4>
                            <p style="font-size: 0.75rem; color: var(--color-text-muted); line-height: 1.4;">Your order is instantly recorded. You will be redirected to WhatsApp to confirm and complete manually with our representative.</p>
                        </div>
                    </div>

                    <!-- Razorpay -->
                    <div class="payment-option" onclick="selectPaymentOption(this, 'Razorpay')" style="display: flex; align-items: flex-start; gap: 15px; border: 1px solid var(--color-medium-gray); padding: 15px 20px; margin-bottom: 12px; cursor: pointer; transition: var(--transition-smooth);">
                        <input type="radio" name="payment_choice" id="pay-razorpay" value="Razorpay" style="margin-top: 4px; accent-color: var(--color-gold);">
                        <div class="payment-option-info">
                            <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 3px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-credit-card" style="color: var(--color-gold); font-size: 1.05rem;"></i> Razorpay (UPI, Card, Netbanking)</h4>
                            <p style="font-size: 0.75rem; color: var(--color-text-muted); line-height: 1.4;">Pay securely online using UPI, Credit/Debit cards, Netbanking, or Wallets.</p>
                        </div>
                    </div>

                    <!-- COD -->
                    <div class="payment-option" onclick="selectPaymentOption(this, 'COD')" style="display: flex; align-items: flex-start; gap: 15px; border: 1px solid var(--color-medium-gray); padding: 15px 20px; margin-bottom: 12px; cursor: pointer; transition: var(--transition-smooth);">
                        <input type="radio" name="payment_choice" id="pay-cod" value="COD" style="margin-top: 4px; accent-color: var(--color-gold);">
                        <div class="payment-option-info">
                            <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 3px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-hand-holding-dollar" style="color: var(--color-gold); font-size: 1.05rem;"></i> Cash on Delivery (COD)</h4>
                            <p style="font-size: 0.75rem; color: var(--color-text-muted); line-height: 1.4;">Pay in cash upon delivery of your luxury fragrance package. No redirection required.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" id="checkout-submit-btn" class="btn btn-gold" style="width: 100%; margin-top: 30px;">Place Order &amp; Redirect to WhatsApp</button>
            </form>
        </div>

        <!-- Order Summary Side Card -->
        <div class="cart-summary">
            <h3>Scent Order Summary</h3>
            
            <div style="border-bottom: 1px solid var(--color-medium-gray); padding-bottom: 15px; margin-bottom: 20px;">
                <?php foreach ($cartItemsSummary as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 10px;">
                        <span><?= e($item['name']) ?> <strong style="color: var(--color-gold);">x <?= $item['qty'] ?></strong></span>
                        <span>₹<?= number_format($item['subtotal'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span>₹<?= number_format($summary['subtotal'], 2) ?></span>
            </div>

            <?php if ($summary['discount'] > 0): ?>
                <div class="summary-row discount">
                    <span>Discount (<?= e($summary['coupon_code']) ?>)</span>
                    <span>-₹<?= number_format($summary['discount'], 2) ?></span>
                </div>
            <?php endif; ?>

            <div class="summary-row">
                <span>Express Shipping</span>
                <span><?= $summary['shipping'] == 0 ? '<span style="color: var(--color-success); font-weight:600;">FREE</span>' : '₹' . number_format($summary['shipping'], 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Luxury Tax (10%)</span>
                <span>₹<?= number_format($summary['tax'], 2) ?></span>
            </div>
            <div class="summary-row total">
                <span>Grand Total</span>
                <span id="grand-total-amount">₹<?= number_format($summary['total'], 2) ?></span>
            </div>
        </div>

    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function selectPaymentOption(element, method) {
    const options = document.querySelectorAll('.payment-option');
    options.forEach(o => {
        o.classList.remove('active');
        o.style.borderColor = 'var(--color-medium-gray)';
        o.style.backgroundColor = 'transparent';
    });
    element.classList.add('active');
    element.style.borderColor = 'var(--color-gold)';
    element.style.backgroundColor = 'var(--color-light-gray)';
    
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    const submitBtn = document.getElementById('checkout-submit-btn');
    if (method === 'WhatsApp') {
        submitBtn.innerText = "Place Order & Redirect to WhatsApp";
    } else if (method === 'Razorpay') {
        submitBtn.innerText = "Pay Now via Razorpay";
    } else {
        submitBtn.innerText = "Place Order (Cash on Delivery)";
    }
}

function triggerCheckoutSubmit() {
    const billingForm = document.getElementById('checkout-form-element');
    const selectedRadio = billingForm.querySelector('input[name="payment_choice"]:checked');
    const method = selectedRadio ? selectedRadio.value : 'WhatsApp';

    const formData = new FormData(billingForm);
    formData.append('action', 'place_order');
    formData.append('payment_method', method);

    const submitBtn = document.getElementById('checkout-submit-btn');
    const originalText = submitBtn.innerText;
    submitBtn.disabled = true;
    submitBtn.innerText = "Placing Order...";

    fetch(`${BASE_URL}/api/checkout.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('success', 'Order Placed Successfully!');
            setTimeout(() => {
                if (method === 'WhatsApp') {
                    window.location.href = `${BASE_URL}/order-confirmation?order_id=${data.order_id}&mobile=${encodeURIComponent(data.mobile)}&wa_url=${encodeURIComponent(data.whatsapp_url)}`;
                } else {
                    window.location.href = `${BASE_URL}/order-confirmation?order_id=${data.order_id}&email=${encodeURIComponent(data.email)}`;
                }
            }, 1000);
        } else if (data.status === 'razorpay_checkout') {
            // Load Razorpay checkout modal
            const options = {
                "key": data.key,
                "amount": data.amount,
                "currency": data.currency,
                "name": data.name,
                "description": data.description,
                "image": data.image,
                "order_id": data.razorpay_order_id,
                "handler": function (response) {
                    verifyRazorpayPayment(response, data.db_order_id, data.order_id, data.email);
                },
                "prefill": {
                    "name": data.prefill.name,
                    "email": data.prefill.email,
                    "contact": data.prefill.contact
                },
                "theme": {
                    "color": "#D4AF37"
                },
                "modal": {
                    "ondismiss": function () {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                        showToast('info', 'Payment cancelled.');
                    }
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        } else {
            showToast('error', data.message);
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    })
    .catch(() => {
        showToast('error', 'Checkout failed. Please check form parameters.');
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    });
}

function verifyRazorpayPayment(response, dbOrderId, orderIdStr, email) {
    const submitBtn = document.getElementById('checkout-submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerText = "Verifying Payment...";
    
    const formData = new FormData();
    formData.append('action', 'verify_payment');
    formData.append('db_order_id', dbOrderId);
    formData.append('order_id', orderIdStr);
    formData.append('razorpay_payment_id', response.razorpay_payment_id);
    formData.append('razorpay_order_id', response.razorpay_order_id);
    formData.append('razorpay_signature', response.razorpay_signature);
    
    fetch(`${BASE_URL}/api/checkout.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(resData => {
        if (resData.status === 'success') {
            showToast('success', 'Payment verified successfully!');
            setTimeout(() => {
                window.location.href = `${BASE_URL}/order-confirmation?order_id=${orderIdStr}&email=${encodeURIComponent(email)}`;
            }, 1000);
        } else {
            showToast('error', resData.message);
            submitBtn.disabled = false;
            submitBtn.innerText = "Pay Now via Razorpay";
        }
    })
    .catch(() => {
        showToast('error', 'Payment verification failed. Please contact support.');
        submitBtn.disabled = false;
        submitBtn.innerText = "Pay Now via Razorpay";
    });
}

// Auto-fill City & State from Pincode using postal API
document.addEventListener('DOMContentLoaded', () => {
    const pincodeInput = document.getElementById('billing-pincode');
    const cityInput = document.getElementById('billing-city');
    const stateInput = document.getElementById('billing-state');

    if (pincodeInput && cityInput && stateInput) {
        pincodeInput.addEventListener('input', () => {
            const pincode = pincodeInput.value.trim();
            if (/^\d{6}$/.test(pincode)) {
                cityInput.placeholder = "Fetching city...";
                stateInput.placeholder = "Fetching state...";
                
                fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data[0] && data[0].Status === 'Success') {
                            const postOffices = data[0].PostOffice;
                            if (postOffices && postOffices.length > 0) {
                                cityInput.value = postOffices[0].District;
                                stateInput.value = postOffices[0].State;
                                showToast('success', 'Location auto-filled successfully.');
                            }
                        }
                    })
                    .catch(() => {
                        cityInput.placeholder = "City";
                        stateInput.placeholder = "State";
                    });
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
