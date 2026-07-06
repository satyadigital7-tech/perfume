<?php
$pageTitle = "Order Confirmed - Elixir & Co.";
$metaDesc = "Thank you for your order! Your purchase from Elixir & Co. has been placed successfully.";
include __DIR__ . '/../includes/header.php';

$db = getDB();
$order = null;
$orderItems = [];

$orderId = trim($_GET['order_id'] ?? '');
$email = trim($_GET['email'] ?? '');
$mobile = trim($_GET['mobile'] ?? '');
$waUrl = $_GET['wa_url'] ?? '';

if ($orderId) {
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND billing_email = ?");
        $stmt->execute([$orderId, $email]);
        $order = $stmt->fetch();
    } elseif (!empty($mobile)) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND billing_mobile = ?");
        $stmt->execute([$orderId, $mobile]);
        $order = $stmt->fetch();
    }
    
    if ($order) {
        // Fetch order items with product details
        $itemsStmt = $db->prepare("SELECT oi.*, p.name, p.brand, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $orderItems = $itemsStmt->fetchAll();
    }
}
?>

<div class="header-container" style="margin-top: 50px; margin-bottom: 80px;">
    <?php if ($order): ?>
        <div style="max-width: 850px; margin: 0 auto; background: var(--color-white); border: 1px solid var(--color-border); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden;">
            
            <!-- Success Header -->
            <div style="text-align: center; padding: 40px 20px; border-bottom: 1px solid var(--color-border); background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);">
                <div style="width: 70px; height: 70px; line-height: 70px; border-radius: 50%; background: rgba(212, 175, 55, 0.1); margin: 0 auto 20px; text-align: center; color: var(--color-gold); font-size: 2.2rem; border: 2px solid var(--color-gold);">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h1 style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--color-black); margin-bottom: 10px;">Order Confirmed</h1>
                <p style="font-size: 1.1rem; color: var(--color-text-muted); margin-bottom: 5px;">Thank you for your purchase, <strong><?= e($order['billing_name']) ?></strong>!</p>
                <p style="font-size: 0.95rem; color: var(--color-text-muted);">Order ID: <strong style="color: var(--color-black);"><?= e($order['order_id']) ?></strong></p>
            </div>

            <!-- WhatsApp confirmation overlay if redirecting -->
            <?php if (!empty($waUrl)): ?>
                <div style="background: rgba(212, 175, 55, 0.05); border-bottom: 1px solid var(--color-border); padding: 30px; text-align: center;">
                    <i class="fa-brands fa-whatsapp" style="font-size: 3rem; color: var(--color-success); margin-bottom: 15px;"></i>
                    <h2 style="font-family: var(--font-heading); color: var(--color-gold); font-size: 1.5rem; margin-bottom: 10px;">Confirm on WhatsApp</h2>
                    <p style="font-size: 0.95rem; margin-bottom: 15px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        Please click the button below to send your order summary to our WhatsApp team to complete manual confirmation.
                    </p>
                    <a href="<?= e($waUrl) ?>" target="_blank" class="btn btn-gold" style="padding: 10px 25px; font-size: 0.95rem; font-weight: 600;">
                        <i class="fa-brands fa-whatsapp"></i> Confirm on WhatsApp
                    </a>
                    
                    <script>
                        setTimeout(() => {
                            window.open('<?= addslashes($waUrl) ?>', '_blank');
                        }, 1800);
                    </script>
                </div>
            <?php endif; ?>

            <!-- Order Specs & Shipping Split -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); border-bottom: 1px solid var(--color-border);">
                <!-- Delivery Coordinates -->
                <div style="padding: 30px; border-right: 1px solid var(--color-border);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; border-bottom: 2px solid var(--color-gold); padding-bottom: 8px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Coordinates</h3>
                    <div style="line-height: 1.8; font-size: 0.9rem;">
                        <div><strong>Name:</strong> <?= e($order['billing_name']) ?></div>
                        <div><strong>Mobile:</strong> <?= e($order['billing_mobile']) ?></div>
                        <div><strong>Email:</strong> <?= e($order['billing_email']) ?></div>
                        <div style="margin-top: 10px; line-height: 1.5;">
                            <strong>Shipping Address:</strong><br>
                            <?= e($order['address']) ?>, <?= e($order['city']) ?>,<br>
                            <?= e($order['state']) ?> - <?= e($order['pincode']) ?>
                        </div>
                    </div>
                </div>

                <!-- Payment & Shipping Stats -->
                <div style="padding: 30px;">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; border-bottom: 2px solid var(--color-gold); padding-bottom: 8px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Payment Details</h3>
                    <div style="line-height: 1.8; font-size: 0.9rem; margin-bottom: 20px;">
                        <div><strong>Payment Method:</strong> <?= e($order['payment_method']) ?></div>
                        <div><strong>Payment Status:</strong> 
                            <span style="padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; <?= strtolower($order['payment_status']) === 'paid' ? 'background: #d4edda; color: #155724;' : 'background: #fff3cd; color: #856404;' ?>">
                                <?= strtoupper(e($order['payment_status'])) ?>
                            </span>
                        </div>
                        <div><strong>Order Status:</strong> 
                            <span style="padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; background: #e2e3e5; color: #383d41;">
                                <?= strtoupper(e($order['order_status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div style="padding: 30px; border-bottom: 1px solid var(--color-border);">
                <h3 style="font-family: var(--font-heading); font-size: 1.15rem; border-bottom: 2px solid var(--color-gold); padding-bottom: 8px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px;">Fragrances In Your Order</h3>
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($orderItems as $item): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--color-border); padding: 12px; border-radius: 6px; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <img src="<?= BASE_URL ?>/assets/images/<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" style="width: 55px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid var(--color-border);">
                                <div>
                                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-gold); font-weight: 600; letter-spacing: 0.5px;"><?= e($item['brand']) ?></div>
                                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--color-black);"><?= e($item['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px;">Qty: <?= $item['quantity'] ?></div>
                                </div>
                            </div>
                            <div style="font-weight: 600; font-size: 0.95rem;">
                                ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pricing Calculation -->
            <div style="background: #fafafa; padding: 30px; display: flex; justify-content: flex-end;">
                <div style="width: 100%; max-width: 350px; font-size: 0.95rem; line-height: 2;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eaeaea; padding-bottom: 4px;">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eaeaea; padding: 4px 0; color: var(--color-error);">
                            <span>Discount (<?= e($order['coupon_code']) ?>)</span>
                            <span>-₹<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eaeaea; padding: 4px 0;">
                        <span>Shipping & Handling</span>
                        <span><?= $order['shipping'] == 0 ? '<span style="color:var(--color-success); font-weight:600;">FREE</span>' : '₹' . number_format($order['shipping'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eaeaea; padding: 4px 0;">
                        <span>GST / Sales Tax (10%)</span>
                        <span>₹<?= number_format($order['tax'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 8px; font-size: 1.25rem; font-weight: 700; color: var(--color-black);">
                        <span>Total Paid</span>
                        <span style="color: var(--color-gold);">₹<?= number_format($order['total'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="padding: 30px; text-align: center; border-top: 1px solid var(--color-border); display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/order-tracking?order_id=<?= e($order['order_id']) ?>&mobile=<?= e($order['billing_mobile']) ?>" class="btn btn-gold" style="padding: 12px 25px; font-size: 0.9rem; font-weight: 600;">
                    <i class="fa-solid fa-truck"></i> Track Order Status
                </a>
                <a href="<?= BASE_URL ?>/" class="btn" style="padding: 12px 25px; font-size: 0.9rem; font-weight: 600; border: 1px solid var(--color-border); background: transparent; color: var(--color-black);">
                    Continue Shopping
                </a>
            </div>

        </div>
    <?php else: ?>
        <div style="max-width: 600px; margin: 0 auto; text-align: center; padding: 60px 20px; background: var(--color-white); border: 1px solid var(--color-border); border-radius: 8px;">
            <div style="font-size: 3.5rem; color: var(--color-error); margin-bottom: 20px;">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h2 style="font-family: var(--font-heading); font-size: 1.8rem; margin-bottom: 10px;">Order Details Unavailable</h2>
            <p style="color: var(--color-text-muted); margin-bottom: 30px; font-size: 0.95rem;">
                We could not retrieve order parameters. Please check your tracking link, or contact support if you believe this is an error.
            </p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="<?= BASE_URL ?>/order-tracking" class="btn btn-gold" style="padding: 10px 20px;">
                    Track Shipments
                </a>
                <a href="<?= BASE_URL ?>/" class="btn" style="padding: 10px 20px; border: 1px solid var(--color-border); background: transparent; color: var(--color-black);">
                    Return Home
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
