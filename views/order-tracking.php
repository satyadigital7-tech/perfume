<?php
$pageTitle = "Track Scent Order";
$metaDesc = "Track the real-time fulfillment status of your luxury fragrance shipment from Mr.genieperfumes.";
include __DIR__ . '/../includes/header.php';

$db = getDB();
$order = null;
$orderItems = [];
$searched = false;

$orderIdInput = $_GET['order_id'] ?? '';
$mobileInput = $_GET['mobile'] ?? '';

if ($orderIdInput && $mobileInput) {
    $searched = true;
    
    // Clean inputs
    $orderId = trim($orderIdInput);
    $mobile = trim($mobileInput);

    // Fetch order matching ID and billing mobile
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND billing_mobile = ?");
    $stmt->execute([$orderId, $mobile]);
    $order = $stmt->fetch();

    if ($order) {
        // Fetch order items with product details
        $itemsStmt = $db->prepare("SELECT oi.*, p.name, p.brand, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $orderItems = $itemsStmt->fetchAll();
    }
}
?>

<div class="header-container">
    <div class="tracking-wrapper">
        
        <!-- Placed WhatsApp redirect overlay -->
        <?php if (isset($_GET['placed']) && $_GET['placed'] == '1' && isset($_GET['wa_url'])): ?>
            <div style="background: rgba(212, 175, 55, 0.1); border: 2px solid var(--color-gold); padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 40px; margin-top: 30px;">
                <i class="fa-brands fa-whatsapp" style="font-size: 3.5rem; color: var(--color-success); margin-bottom: 15px;"></i>
                <h2 style="font-family: var(--font-heading); color: var(--color-gold); font-size: 1.8rem; margin-bottom: 10px;">Order Placed Successfully!</h2>
                <p style="font-size: 0.95rem; margin-bottom: 15px;">Your Order ID is <strong><?= e($_GET['order_id']) ?></strong>. We have registered your details in our system.</p>
                <p style="font-size: 0.9rem; margin-bottom: 25px; color: var(--color-text-muted);">Please click the button below to send your order summary to our WhatsApp to manually confirm and complete your order.</p>
                <a href="<?= e($_GET['wa_url']) ?>" target="_blank" class="btn btn-gold" style="padding: 12px 30px; font-size: 1rem; font-weight: 600;">
                    <i class="fa-brands fa-whatsapp"></i> Confirm Order on WhatsApp
                </a>
                
                <script>
                    setTimeout(() => {
                        window.open('<?= addslashes($_GET['wa_url']) ?>', '_blank');
                    }, 1800);
                </script>
            </div>
        <?php endif; ?>

        <!-- Track Order Input Card -->
        <div class="tracking-card">
            <h3>Track Your Shipment</h3>
            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 25px;">Enter your Order ID (e.g. LS10025) and your billing mobile number below to review current processing status.</p>
            
            <form action="<?= BASE_URL ?>/order-tracking" method="GET" class="order-tracking-form">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="track-id">Order ID</label>
                    <input type="text" id="track-id" name="order_id" required placeholder="e.g. LS10025" value="<?= e($orderIdInput) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="track-mobile">Mobile Number</label>
                    <input type="text" id="track-mobile" name="mobile" required placeholder="e.g. 9876543210" value="<?= e($mobileInput) ?>">
                </div>
                <button type="submit" class="btn btn-gold" style="padding: 12px 25px;">Track Order</button>
            </form>
        </div>

        <?php if ($searched): ?>
            <?php if ($order): ?>
                
                <!-- Status Stepper Card -->
                <div class="tracking-card">
                    <h3>Fulfillment Stepper</h3>
                    
                    <?php
                    // Map order statuses to step indexes (0 to 5)
                    $statusSteps = [
                        'order received' => 0,
                        'confirmed' => 1,
                        'processing' => 2,
                        'shipped' => 3,
                        'out for delivery' => 4,
                        'delivered' => 5
                    ];
                    $currentStatus = strtolower($order['order_status']);
                    $currentStepIdx = $statusSteps[$currentStatus] ?? 0;
                    
                    // Display status description text based on PRD requirements
                    $statusMessage = '';
                    switch ($currentStatus) {
                        case 'order received':
                            $statusMessage = "Your order has been received successfully.";
                            break;
                        case 'confirmed':
                            $statusMessage = "Your order has been confirmed by our team.";
                            break;
                        case 'processing':
                            $statusMessage = "Your perfume is being packed.";
                            break;
                        case 'shipped':
                            $trackingNumText = !empty($order['tracking_number']) ? $order['tracking_number'] : 'AWB123456789';
                            $statusMessage = "Your order has been shipped. Tracking Number: <strong>" . e($trackingNumText) . "</strong>";
                            break;
                        case 'out for delivery':
                            $statusMessage = "Your order is out for delivery.";
                            break;
                        case 'delivered':
                            $statusMessage = "Order delivered successfully.";
                            break;
                        default:
                            $statusMessage = "Your order status is: " . e($order['order_status']);
                            break;
                    }
                    
                    // Stepper percentage width
                    $progressWidth = ($currentStepIdx / 5) * 100;
                    ?>
                    
                    <p style="font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5; color: var(--color-gold); font-weight: 500;"><?= $statusMessage ?></p>
                    
                    <div class="stepper">
                        <div class="stepper-progress" style="width: <?= $progressWidth ?>%;"></div>
                        
                        <!-- Step 1: Order Received -->
                        <div class="step <?= $currentStepIdx >= 0 ? 'completed' : '' ?> <?= $currentStepIdx === 0 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-file-invoice"></i></div>
                            <span class="step-label">Order Received</span>
                        </div>
                        
                        <!-- Step 2: Confirmed -->
                        <div class="step <?= $currentStepIdx >= 1 ? 'completed' : '' ?> <?= $currentStepIdx === 1 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <span class="step-label">Confirmed</span>
                        </div>
                        
                        <!-- Step 3: Processing -->
                        <div class="step <?= $currentStepIdx >= 2 ? 'completed' : '' ?> <?= $currentStepIdx === 2 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-box-open"></i></div>
                            <span class="step-label">Processing</span>
                        </div>
                        
                        <!-- Step 4: Shipped -->
                        <div class="step <?= $currentStepIdx >= 3 ? 'completed' : '' ?> <?= $currentStepIdx === 3 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-truck"></i></div>
                            <span class="step-label">Shipped</span>
                        </div>
                        
                        <!-- Step 5: Out for Delivery -->
                        <div class="step <?= $currentStepIdx >= 4 ? 'completed' : '' ?> <?= $currentStepIdx === 4 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                            <span class="step-label">Out for Delivery</span>
                        </div>
                        
                        <!-- Step 6: Delivered -->
                        <div class="step <?= $currentStepIdx >= 5 ? 'completed' : '' ?> <?= $currentStepIdx === 5 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-house-chimney-user"></i></div>
                            <span class="step-label">Delivered</span>
                        </div>
                    </div>
                </div>

                <!-- Receipt details card -->
                <div class="tracking-card">
                    <h3>Invoice Summary & Delivery Coordinates</h3>
                    
                    <div class="order-tracking-info-grid" style="margin-bottom: 30px; font-size: 0.85rem; border-bottom: 1px solid var(--color-medium-gray); padding-bottom: 20px;">
                        <div>
                            <h4 style="font-family: var(--font-heading); font-size: 1rem; color: var(--color-gold); margin-bottom: 10px;">Recipient Info</h4>
                            <p><strong>Name:</strong> <?= e($order['billing_name']) ?></p>
                            <p><strong>Mobile:</strong> <?= e($order['billing_mobile']) ?></p>
                            <p><strong>Email:</strong> <?= e($order['billing_email']) ?: 'N/A' ?></p>
                        </div>
                        <div>
                            <h4 style="font-family: var(--font-heading); font-size: 1rem; color: var(--color-gold); margin-bottom: 10px;">Coordinates</h4>
                            <p><?= e($order['address']) ?></p>
                            <p><?= e($order['city']) ?>, <?= e($order['state']) ?> - <?= e($order['pincode']) ?></p>
                            <p style="margin-top: 5px;"><strong>Payment Mode:</strong> <?= e($order['payment_method']) ?> (<?= e($order['payment_status']) ?>)</p>
                        </div>
                    </div>

                    <h4 style="font-family: var(--font-heading); font-size: 1.1rem; margin-bottom: 15px;">Olfactory Inventory</h4>
                    <div class="table-responsive" style="margin-bottom: 25px;">
                        <table class="orders-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>Fragrance</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td><?= e($item['brand']) ?> - <?= e($item['name']) ?></td>
                                        <td>x <?= $item['quantity'] ?></td>
                                        <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="max-width: 300px; margin-left: auto; font-size: 0.9rem;">
                        <div class="summary-row">
                            <span>Scent Subtotal:</span>
                            <span>₹<?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="summary-row discount">
                                <span>Discount (<?= e($order['coupon_code']) ?>):</span>
                                <span>-₹<?= number_format($order['discount_amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Express Shipping:</span>
                            <span>₹<?= number_format($order['shipping'], 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Luxury Tax (10%):</span>
                            <span>₹<?= number_format($order['tax'], 2) ?></span>
                        </div>
                        <div class="summary-row total" style="border-top: 1px solid var(--color-medium-gray); padding-top: 10px; font-weight: 600;">
                            <span>Grand Total:</span>
                            <span>₹<?= number_format($order['total'], 2) ?></span>
                        </div>
                    </div>

                    <!-- Need Help WhatsApp Section -->
                    <div style="border-top: 1px solid var(--color-medium-gray); margin-top: 40px; padding-top: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <h4 style="font-family: var(--font-heading); font-size: 1.25rem; color: var(--color-gold); margin-bottom: 5px;">Need Help?</h4>
                            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">Contact our customer lounge team directly on WhatsApp for live status updates.</p>
                        </div>
                        <?php
                        $helpMessage = "Hello,\n\nI would like an update on my order.\n\nOrder ID: " . $order['order_id'] . "\n\nPlease share the current status.";
                        $waHelpUrl = "https://wa.me/919071233343?text=" . urlencode($helpMessage);
                        ?>
                        <a href="<?= $waHelpUrl ?>" target="_blank" class="btn btn-outline-gold" style="padding: 10px 25px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-brands fa-whatsapp"></i> Track on WhatsApp
                        </a>
                    </div>

                </div>

            <?php else: ?>
                <div class="wishlist-empty" style="margin-top: 30px;">
                    <i class="fa-solid fa-ban"></i>
                    <h2>Order Not Found</h2>
                    <p>No matches for Order ID: <strong><?= e($orderIdInput) ?></strong> under Mobile number: <strong><?= e($mobileInput) ?></strong>.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
