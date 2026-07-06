<?php
$pageTitle = "Page Not Found";
$metaDesc = "The premium page you are looking for does not exist on Elixir & Co.";
include __DIR__ . '/../includes/header.php';
?>

<div class="header-container">
    <div class="wishlist-empty" style="margin: 80px 0;">
        <i class="fa-solid fa-compass"></i>
        <h2>Olfactory Track Lost</h2>
        <p>The luxury pathway or fragrance review column you are seeking cannot be found.</p>
        <a href="<?= BASE_URL ?>/" class="btn btn-gold">Return to Salon</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
