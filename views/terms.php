<?php
$pageTitle = "Terms & Conditions";
$metaDesc = "Review terms of use, shipping guarantees, returns policy, and purchasing conditions for Mr.genieperfumes boutique.";
include __DIR__ . '/../includes/header.php';
?>

<div class="header-container">
    <div class="static-page-layout">
        <h1>Terms & Conditions</h1>
        <p>Last updated: June 22, 2026</p>
        
        <h2>Product Authenticity & Integrity</h2>
        <p>Mr.genieperfumes guarantees that all fragrances offered on our boutique catalog are 100% authentic original products from brand manufacturers. Due to the high value and chemical sensitivity of pure extracts, returned items must be unopened and returned inside their original sealed cellophanes.</p>
        
        <h3 style="font-family: var(--font-heading); font-size: 1.3rem; margin-top: 30px; color: var(--color-gold);">1. Shipping Policy</h3>
        <p>We process all orders within 1-2 business days. Express shipping is free for all orders totaling ₹<?= number_format((float)getSetting('shipping_free_threshold', '1500.00')) ?> or more; otherwise, a flat delivery charge of ₹<?= number_format((float)getSetting('shipping_flat_rate', '200.00')) ?> applies. Estimated delivery times are between 3 to 7 business days, depending on location. Tracking coordinates are accessible inside our Order Tracking panel.</p>
        
        <h2>Limitation of Liability</h2>
        <p>Mr.genieperfumes is not liable for direct, indirect, or incidental damages arising from scent allergies or incorrect application of fragrances. We strongly recommend testing new scent signatures on a tiny patch of skin first.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
