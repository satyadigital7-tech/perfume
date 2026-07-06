<?php
/**
 * Invoice Generator — Admin Only
 * URL: /invoice?order_id=123
 */
require_once __DIR__ . '/../config/db.php';

$db   = getDB();
$id   = (int)($_GET['order_id'] ?? 0);

if ($id <= 0) {
    die('Invalid order ID.');
}

// Fetch order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}

// Authorization Guard: Admin or Order Owner
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$isOwner = isset($_SESSION['user_id']) && ((int)$_SESSION['user_id'] === (int)$order['user_id']);

if (!$isAdmin && !$isOwner) {
    http_response_code(403);
    die('Access denied.');
}

// Fetch order items
$items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$orderItems = $items->fetchAll();

$logoUrl = BASE_URL . '/assets/images/LOGO.svg';
$invoiceDate = date('d M Y', strtotime($order['created_at']));
$invoiceNum  = 'INV-' . strtoupper($order['order_id'] ?? str_pad($order['id'], 6, '0', STR_PAD_LEFT));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoiceNum) ?> — Elixir & Co.</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f0f0;
            color: #1a1a1a;
            font-size: 13px;
            line-height: 1.6;
        }

        .page-wrapper {
            max-width: 860px;
            margin: 30px auto;
            background: #ffffff;
            box-shadow: 0 4px 30px rgba(0,0,0,0.12);
        }

        /* ── Print Actions Bar (hidden in print) ── */
        .print-bar {
            background: #1a1a1a;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .print-bar span { color: #aaa; font-size: 12px; }
        .print-bar .btn-row { display: flex; gap: 10px; }
        .btn-invoice {
            padding: 8px 20px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.2s;
        }
        .btn-invoice:hover { opacity: 0.85; }
        .btn-print { background: #c8a96e; color: #000; }
        .btn-back  { background: transparent; color: #aaa; border: 1px solid #444; }

        /* ── Invoice Container ── */
        .invoice {
            padding: 48px 52px;
        }

        /* ── Header ── */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 28px;
            border-bottom: 2px solid #f0f0f0;
        }
        .inv-logo img {
            height: 55px;
            object-fit: contain;
        }
        .inv-logo .brand-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #1a1a1a;
            margin-top: 6px;
        }
        .inv-logo .brand-tagline {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .inv-meta { text-align: right; }
        .inv-meta .inv-title {
            font-size: 28px;
            font-weight: 700;
            color: #c8a96e;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .inv-meta table { margin-top: 8px; margin-left: auto; }
        .inv-meta td { padding: 2px 6px; font-size: 12px; color: #555; }
        .inv-meta td:first-child { color: #999; text-align: right; padding-right: 12px; }
        .inv-meta td:last-child { font-weight: 600; color: #1a1a1a; }

        /* ── Gold Accent Bar ── */
        .gold-bar {
            height: 4px;
            background: linear-gradient(90deg, #c8a96e, #e8d5a3, #c8a96e);
            margin-bottom: 32px;
            border-radius: 2px;
        }

        /* ── Address Section ── */
        .inv-addresses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 36px;
        }
        .inv-address-block h6 {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c8a96e;
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 6px;
        }
        .inv-address-block p {
            font-size: 13px;
            color: #333;
            line-height: 1.8;
        }
        .inv-address-block strong { color: #1a1a1a; }

        /* ── Order Summary Box ── */
        .inv-summary-row {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
        }
        .inv-summary-chip {
            flex: 1;
            background: #f8f8f8;
            border: 1px solid #eee;
            border-top: 3px solid #c8a96e;
            padding: 14px 16px;
        }
        .inv-summary-chip .chip-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #999;
            margin-bottom: 4px;
        }
        .inv-summary-chip .chip-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .inv-summary-chip.gold .chip-value { color: #c8a96e; font-size: 16px; }

        /* ── Items Table ── */
        .inv-items-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c8a96e;
            margin-bottom: 10px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .inv-table thead tr {
            background: #1a1a1a;
            color: #fff;
        }
        .inv-table thead th {
            padding: 10px 14px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .inv-table thead th:last-child { text-align: right; }
        .inv-table thead th:nth-child(2),
        .inv-table thead th:nth-child(3) { text-align: center; }
        .inv-table tbody tr:nth-child(even) { background: #fafafa; }
        .inv-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .inv-table tbody td:nth-child(2),
        .inv-table tbody td:nth-child(3) { text-align: center; }
        .inv-table tbody td:last-child { text-align: right; font-weight: 600; }
        .inv-table tfoot tr { background: #f8f8f8; }
        .inv-table tfoot td {
            padding: 8px 14px;
            font-size: 12px;
            color: #555;
        }
        .inv-table tfoot td:last-child { text-align: right; font-weight: 600; color: #1a1a1a; }
        .inv-table tfoot tr.total-row td {
            background: #1a1a1a;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 14px;
        }
        .inv-table tfoot tr.total-row td:last-child { color: #c8a96e; }

        /* ── Footer ── */
        .inv-footer {
            border-top: 1px solid #f0f0f0;
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 16px;
        }
        .inv-footer .thank-you {
            font-size: 18px;
            font-weight: 300;
            color: #c8a96e;
            letter-spacing: 1px;
        }
        .inv-footer .company-info {
            text-align: right;
            font-size: 11px;
            color: #999;
            line-height: 1.8;
        }
        .inv-footer .company-info strong { color: #555; }

        /* ── Watermark-style status ── */
        .status-stamp {
            display: inline-block;
            padding: 6px 18px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: 2px;
            margin-top: 16px;
        }
        .status-stamp.paid    { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .status-stamp.pending { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
        .status-stamp.failed  { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        /* ── Print Styles ── */
        @media print {
            body { background: white; }
            .print-bar { display: none !important; }
            .page-wrapper { box-shadow: none; margin: 0; }
            .invoice { padding: 32px; }
            @page { margin: 10mm; }
        }

        @media (max-width: 600px) {
            .invoice { padding: 24px 20px; }
            .inv-header { flex-direction: column; gap: 20px; }
            .inv-meta { text-align: left; }
            .inv-meta table { margin-left: 0; }
            .inv-addresses { grid-template-columns: 1fr; }
            .inv-summary-row { flex-wrap: wrap; }
            .print-bar { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

<!-- Action Bar -->
<div class="print-bar">
    <span>Invoice Preview — <?= htmlspecialchars($invoiceNum) ?></span>
    <div class="btn-row">
        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/admin?tab=orders" class="btn-invoice btn-back">← Back to Admin</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/account?tab=orders" class="btn-invoice btn-back">← Back to Account</a>
        <?php endif; ?>
        <button class="btn-invoice btn-print" onclick="window.print()">⬇ Download / Print PDF</button>
    </div>
</div>

<!-- Invoice Page -->
<div class="page-wrapper">
    <div class="invoice">

        <!-- Header -->
        <div class="inv-header">
            <div class="inv-logo">
                <img src="<?= $logoUrl ?>" alt="Elixir & Co. Logo">
                <div class="brand-name">ELIXIR & CO.</div>
                <div class="brand-tagline">Luxury Fragrances</div>
            </div>
            <div class="inv-meta">
                <div class="inv-title">Invoice</div>
                <table>
                    <tr>
                        <td>Invoice No.</td>
                        <td><?= htmlspecialchars($invoiceNum) ?></td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td><?= $invoiceDate ?></td>
                    </tr>
                    <tr>
                        <td>Payment</td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    </tr>
                </table>
                $payStatus = strtolower($order['payment_status']);
                $stampClass = ($payStatus === 'completed' || $payStatus === 'paid') ? 'paid' : ($payStatus === 'failed' ? 'failed' : 'pending');
                ?>
                <div>
                    <span class="status-stamp <?= $stampClass ?>">
                        <?= htmlspecialchars($order['payment_status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Gold Bar -->
        <div class="gold-bar"></div>

        <!-- From / To Addresses -->
        <div class="inv-addresses">
            <div class="inv-address-block">
                <h6>From — Seller</h6>
                <p>
                    <strong>Elixir & Co.</strong><br>
                    Luxury Fragrances & Perfumes<br>
                    India<br>
                    itsmyshopshahid838@gmail.com<br>
                    elixircoperfumes.in
                </p>
            </div>
            <div class="inv-address-block">
                <h6>Bill To — Customer</h6>
                <p>
                    <strong><?= htmlspecialchars($order['billing_name']) ?></strong><br>
                    <?= htmlspecialchars($order['billing_email']) ?><br>
                    <?= htmlspecialchars($order['billing_mobile']) ?><br>
                    <?= htmlspecialchars($order['address']) ?>,<br>
                    <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> — <?= htmlspecialchars($order['pincode']) ?>
                </p>
            </div>
        </div>

        <!-- Summary Chips -->
        <div class="inv-summary-row">
            <div class="inv-summary-chip">
                <div class="chip-label">Order ID</div>
                <div class="chip-value"><?= htmlspecialchars($order['order_id'] ?? '#' . $order['id']) ?></div>
            </div>
            <div class="inv-summary-chip">
                <div class="chip-label">Order Status</div>
                <div class="chip-value"><?= htmlspecialchars($order['order_status']) ?></div>
            </div>
            <?php if ($order['tracking_number']): ?>
            <div class="inv-summary-chip">
                <div class="chip-label">Tracking Number</div>
                <div class="chip-value"><?= htmlspecialchars($order['tracking_number']) ?></div>
            </div>
            <?php endif; ?>
            <div class="inv-summary-chip gold">
                <div class="chip-label">Total Amount</div>
                <div class="chip-value">₹<?= number_format($order['total'], 2) ?></div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="inv-items-title">Order Items</div>
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="text-align:left;">#</th>
                    <th style="text-align:left;">Product Name</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orderItems)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#999; padding: 20px;">No items recorded for this order.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orderItems as $i => $item): ?>
                    <tr>
                        <td style="color:#999;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td>₹<?= number_format($item['price'], 2) ?></td>
                        <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Subtotal</td>
                    <td>₹<?= number_format($order['subtotal'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="4">Shipping</td>
                    <td>₹<?= number_format($order['shipping'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="4">Tax (GST)</td>
                    <td>₹<?= number_format($order['tax'], 2) ?></td>
                </tr>
                <?php if ($order['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="4">Discount <?= $order['coupon_code'] ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></td>
                    <td style="color: #2e7d32;">−₹<?= number_format($order['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="4" style="color:#c8a96e; text-align:left;">Grand Total</td>
                    <td>₹<?= number_format($order['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="inv-footer">
            <div>
                <div class="thank-you">Thank you for your order!</div>
                <div style="font-size: 11px; color: #999; margin-top: 6px;">
                    For queries, contact us at itsmyshopshahid838@gmail.com
                </div>
            </div>
            <div class="company-info">
                <strong>Elixir & Co.</strong><br>
                elixircoperfumes.in<br>
                India
            </div>
        </div>

    </div>
</div>

</body>
</html>
