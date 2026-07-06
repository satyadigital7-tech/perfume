<?php
header('Content-Type: application/json');
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = preg_replace('/\/api$/', '', rtrim(str_replace('\\', '/', $scriptDir), '/'));
    define('BASE_URL', $baseUrl);
}
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'apply_coupon':
        $code = trim($_POST['coupon_code'] ?? '');
        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Please supply a code.']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            $_SESSION['applied_coupon'] = [
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'value' => (float)$coupon['value']
            ];
            echo json_encode(['status' => 'success', 'message' => "Coupon '{$coupon['code']}' applied successfully."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired promo code.']);
        }
        break;

    case 'remove_coupon':
        unset($_SESSION['applied_coupon']);
        echo json_encode(['status' => 'success', 'message' => 'Coupon removed.']);
        break;

    case 'place_order':
        // Verify CSRF
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            echo json_encode(['status' => 'error', 'message' => 'Security token mismatch. Please reload page.']);
            exit;
        }

        // Validate Cart exists
        if (empty($_SESSION['cart']) || !isset($_SESSION['order_summary'])) {
            echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
            exit;
        }

        $summary = $_SESSION['order_summary'];

        // Validate Billing Inputs
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'Cash on Delivery';
        
        $paymentStatus = 'Pending';
        $orderStatus = 'Order Received';
        $amountInPaise = 0;
        $razorpayOrderId = '';

        if ($paymentMethod === 'Razorpay') {
            $paymentStatus = 'Unpaid';
            $orderStatus = 'Pending Payment';
            
            // Create Razorpay Order
            $amountInPaise = (int)round($summary['total'] * 100);
            $receiptId = 'rcpt_' . time() . '_' . mt_rand(1000, 9999);
            
            $keyId = "rzp_live_T87Kc4oZWZyu2R";
            $keySecret = "yUiITxb0lnJBY7z0i2zoRpfb";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            
            $payload = json_encode([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $receiptId
            ]);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $rzpResult = curl_exec($ch);
            $rzpHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($rzpHttpCode !== 200 && $rzpHttpCode !== 201) {
                @file_put_contents(__DIR__ . '/../razorpay_err_log.txt', date('Y-m-d H:i:s') . " | HTTP $rzpHttpCode | Response: $rzpResult | Payload: $payload\n", FILE_APPEND);
                echo json_encode(['status' => 'error', 'message' => 'Failed to initialize online payment with Razorpay. Please try again or select another payment method.']);
                exit;
            }
            
            $rzpData = json_decode($rzpResult, true);
            $razorpayOrderId = $rzpData['id'] ?? null;
            if (!$razorpayOrderId) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid order details returned by Razorpay.']);
                exit;
            }
        }

        if (empty($fullName) || empty($email) || empty($mobile) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
            echo json_encode(['status' => 'error', 'message' => 'All billing and delivery fields are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
            exit;
        }

        try {
            // Begin transaction
            $db->beginTransaction();

            $cartItems = [];
            
            // 1. Verify and deduct stock for each cart item
            foreach ($_SESSION['cart'] as $pId => $qty) {
                // Fetch product info with lock for writing (FOR UPDATE)
                $pStmt = $db->prepare("SELECT id, name, price, discount_price, stock FROM products WHERE id = ? FOR UPDATE");
                $pStmt->execute([$pId]);
                $product = $pStmt->fetch();

                if (!$product) {
                    throw new Exception("Product ID #{$pId} not found in database.");
                }

                if ($product['stock'] < $qty) {
                    throw new Exception("Insufficient stock for '{$product['name']}'. Only {$product['stock']} bottles remaining.");
                }

                // Calculate product unit price
                $unitPrice = (float)($product['discount_price'] > 0 ? $product['discount_price'] : $product['price']);
                
                // Deduct stock
                $updStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $updStock->execute([$qty, $pId]);

                $cartItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $qty,
                    'price' => $unitPrice
                ];
            }

            // 2. Insert Order (with NULL order_id initially)
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            $insOrder = $db->prepare("INSERT INTO orders (user_id, billing_name, billing_email, billing_mobile, address, city, state, pincode, subtotal, shipping, tax, coupon_code, discount_amount, total, payment_method, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insOrder->execute([
                $userId,
                $fullName,
                $email,
                $mobile,
                $address,
                $city,
                $state,
                $pincode,
                $summary['subtotal'],
                $summary['shipping'],
                $summary['tax'],
                $summary['coupon_code'] ?: null,
                $summary['discount'],
                $summary['total'],
                $paymentMethod,
                $paymentStatus,
                $orderStatus
            ]);
            $orderId = $db->lastInsertId();

            // 2.5 Generate unique Order ID string and update orders table
            $orderIdStr = 'LS' . (10000 + $orderId);
            $updOrder = $db->prepare("UPDATE orders SET order_id = ? WHERE id = ?");
            $updOrder->execute([$orderIdStr, $orderId]);

            // 3. Insert Order Items (with product_name)
            $insItem = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $insItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            if ($paymentMethod === 'Razorpay') {
                // Commit Transaction (order is created in Pending Payment / Unpaid state)
                $db->commit();
                echo json_encode([
                    'status' => 'razorpay_checkout',
                    'key' => 'rzp_live_T87Kc4oZWZyu2R',
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'name' => 'Elixir & Co.',
                    'description' => 'Luxury Fragrances Order ' . $orderIdStr,
                    'image' => BASE_URL . '/assets/images/LOGO.svg',
                    'razorpay_order_id' => $razorpayOrderId,
                    'prefill' => [
                        'name' => $fullName,
                        'email' => $email,
                        'contact' => $mobile
                    ],
                    'order_id' => $orderIdStr,
                    'db_order_id' => $orderId,
                    'email' => $email
                ]);
                exit;
            }

            // 4. Construct WhatsApp Pre-Filled Message
            $waMessage = "Hello Elixir & Co.,\n\n";
            $waMessage .= "I would like to place an order.\n\n";
            $waMessage .= "Order ID: " . $orderIdStr . "\n\n";
            $waMessage .= "Customer Name: " . $fullName . "\n";
            $waMessage .= "Mobile: " . $mobile . "\n\n";
            $waMessage .= "Products:\n";
            
            $idx = 1;
            foreach ($cartItems as $item) {
                $waMessage .= $idx . ". " . $item['product_name'] . " - Qty " . $item['quantity'] . " - ₹" . number_format($item['price'], 2) . "\n";
                $idx++;
            }
            
            $waMessage .= "\nTotal Amount: ₹" . number_format($summary['total'], 2) . "\n\n";
            $waMessage .= "Delivery Address:\n";
            $waMessage .= $address . ", " . $city . ", " . $state . " - " . $pincode . "\n\n";
            $waMessage .= "Please confirm my order.";
            
            $whatsappUrl = "https://wa.me/919071233343?text=" . urlencode($waMessage);

            // Commit Transaction
            $db->commit();

            // Construct and send email confirmation (to customer and admin)
            $emailSubject = "Order Confirmed - " . $orderIdStr;
            $emailMsg = "ORDER CONFIRMED - Elixir & Co.\n";
            $emailMsg .= "Receipt for Order: " . $orderIdStr . "\n";
            $emailMsg .= "---------------------------------------------\n";
            $emailMsg .= "Dear " . $fullName . ",\n\n";
            $emailMsg .= "Thank you for your luxury fragrance order with Elixir & Co.!\n";
            $emailMsg .= "Your transaction was processed successfully. Below is your detailed invoice summary:\n\n";
            $emailMsg .= "ITEMS PLACED:\n";
            
            $idx = 1;
            foreach ($cartItems as $item) {
                $emailMsg .= $idx . ". " . $item['product_name'] . " - Qty " . $item['quantity'] . " - ₹" . number_format($item['price'], 2) . "\n";
                $idx++;
            }
            
            $emailMsg .= "\nORDER SUMMARY:\n";
            $emailMsg .= "Subtotal: ₹" . number_format($summary['subtotal'], 2) . "\n";
            if ($summary['discount'] > 0) {
                $emailMsg .= "Discount (" . $summary['coupon_code'] . "): -₹" . number_format($summary['discount'], 2) . "\n";
            }
            $emailMsg .= "Shipping: " . ($summary['shipping'] > 0 ? "₹" . number_format($summary['shipping'], 2) : "FREE") . "\n";
            $emailMsg .= "Tax (GST): ₹" . number_format($summary['tax'], 2) . "\n";
            $emailMsg .= "Total Amount: ₹" . number_format($summary['total'], 2) . "\n\n";
            $emailMsg .= "DELIVERY ADDRESS:\n";
            $emailMsg .= $address . ", " . $city . ", " . $state . " - " . $pincode . "\n";
            $emailMsg .= "Mobile: " . $mobile . "\n\n";
            $emailMsg .= "Thank you for shopping with us!\n";
            $emailMsg .= "- Elixir & Co.";

            // Send to customer
            sendMail($email, $emailSubject, $emailMsg);

            // Send copy to admin
            sendMail('notification@elixircoperfumes.in', "New Order Placed - " . $orderIdStr, $emailMsg);

            // Clear session data relating to cart
            $_SESSION['cart'] = [];
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['order_summary']);

            echo json_encode([
                'status' => 'success',
                'order_id' => $orderIdStr,
                'mobile' => $mobile,
                'whatsapp_url' => $whatsappUrl
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'verify_payment':
        $dbOrderId = $_POST['db_order_id'] ?? '';
        $orderIdStr = $_POST['order_id'] ?? '';
        $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
        $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
        $razorpaySignature = $_POST['razorpay_signature'] ?? '';
        
        if (empty($dbOrderId) || empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($razorpaySignature)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing parameter signature components.']);
            exit;
        }
        
        $keySecret = "yUiITxb0lnJBY7z0i2zoRpfb";
        
        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $keySecret);
        
        if ($expectedSignature === $razorpaySignature) {
            try {
                $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
                $orderStmt->execute([$dbOrderId]);
                $order = $orderStmt->fetch();
                
                if (!$order) {
                    echo json_encode(['status' => 'error', 'message' => 'Order not found for verification.']);
                    exit;
                }
                
                // If already paid, just return success
                if ($order['payment_status'] === 'Paid') {
                    echo json_encode(['status' => 'success', 'message' => 'Payment already verified.']);
                    exit;
                }
                
                // Update order payment status and status
                $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Order Received' WHERE id = ?");
                $upd->execute([$dbOrderId]);
                
                // Fetch order items
                $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$dbOrderId]);
                $items = $itemsStmt->fetchAll();
                
                // Send confirmation emails
                $emailSubject = "Order Confirmed - " . $orderIdStr;
                $emailMsg = "ORDER CONFIRMED - Elixir & Co.\n";
                $emailMsg .= "Receipt for Order: " . $orderIdStr . "\n";
                $emailMsg .= "---------------------------------------------\n";
                $emailMsg .= "Dear " . $order['billing_name'] . ",\n\n";
                $emailMsg .= "Thank you for your luxury fragrance order with Elixir & Co.!\n";
                $emailMsg .= "Your transaction was processed successfully. Below is your detailed invoice summary:\n\n";
                $emailMsg .= "ITEMS PLACED:\n";
                
                $idx = 1;
                foreach ($items as $item) {
                    $emailMsg .= $idx . ". " . $item['product_name'] . " - Qty " . $item['quantity'] . " - ₹" . number_format($item['price'], 2) . "\n";
                    $idx++;
                }
                
                $emailMsg .= "\nORDER SUMMARY:\n";
                $emailMsg .= "Subtotal: ₹" . number_format($order['subtotal'], 2) . "\n";
                if ($order['discount_amount'] > 0) {
                    $emailMsg .= "Discount (" . $order['coupon_code'] . "): -₹" . number_format($order['discount_amount'], 2) . "\n";
                }
                $emailMsg .= "Shipping: " . ($order['shipping'] > 0 ? "₹" . number_format($order['shipping'], 2) : "FREE") . "\n";
                $emailMsg .= "Tax (GST): ₹" . number_format($order['tax'], 2) . "\n";
                $emailMsg .= "Total Amount: ₹" . number_format($order['total'], 2) . "\n\n";
                $emailMsg .= "DELIVERY ADDRESS:\n";
                $emailMsg .= $order['address'] . ", " . $order['city'] . ", " . $order['state'] . " - " . $order['pincode'] . "\n";
                $emailMsg .= "Mobile: " . $order['billing_mobile'] . "\n\n";
                $emailMsg .= "Payment Mode: Razorpay (Paid)\n";
                $emailMsg .= "Transaction ID: " . $razorpayPaymentId . "\n\n";
                $emailMsg .= "Thank you for shopping with us!\n";
                $emailMsg .= "- Elixir & Co.";
                
                sendMail($order['billing_email'], $emailSubject, $emailMsg);
                sendMail('notification@elixircoperfumes.in', "New Paid Order - " . $orderIdStr, $emailMsg);
                
                // Clear session data relating to cart
                $_SESSION['cart'] = [];
                unset($_SESSION['applied_coupon']);
                unset($_SESSION['order_summary']);
                
                echo json_encode(['status' => 'success', 'message' => 'Payment verified.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error post-payment database update: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Razorpay signature mismatch. Integrity check failed.']);
        }
        break;

    case 'newsletter':
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
            exit;
        }

        try {
            $stmt = $db->prepare("SELECT id FROM newsletter WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'You are already registered for newsletter updates.']);
                exit;
            }

            $ins = $db->prepare("INSERT INTO newsletter (email) VALUES (?)");
            $ins->execute([$email]);
            echo json_encode(['status' => 'success', 'message' => 'Thank you for joining our newsletter circle.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Newsletter database insertion error.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Undefined checkout action.']);
        break;
}
