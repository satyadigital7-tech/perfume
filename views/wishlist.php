<?php
$pageTitle = "My Wishlist";
$metaDesc = "View and manage the luxury fragrances you have handpicked to purchase later.";
include __DIR__ . '/../includes/header.php';

$db = getDB();
$wishlistItems = [];

if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT p.*, w.id as wishlist_id FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistItems = $stmt->fetchAll();
}
?>

<div class="header-container">
    
    <?php if (!isLoggedIn()): ?>
        <div class="wishlist-empty">
            <i class="fa-regular fa-heart"></i>
            <h2>Curate Your Scent Wardrobe</h2>
            <p>Please log in or create an account to save your favorite luxury fragrances for later.</p>
            <a href="<?= BASE_URL ?>/account" class="btn btn-gold">Login / Register</a>
        </div>
    <?php else: ?>
        <div style="margin-top: 50px; margin-bottom: 80px;">
            <div class="sections-title">
                <h2>Your Favorited Fragrances</h2>
                <p>A private sanctuary for the premium scents you wish to experience.</p>
            </div>

            <?php if (count($wishlistItems) > 0): ?>
                <div class="product-grid">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="product-card">
                            <button class="wishlist-toggle active" 
                                    onclick="toggleWishlist(<?= $item['id'] ?>, this); setTimeout(() => window.location.reload(), 800);" 
                                    aria-label="Remove from wishlist">
                                <i class="fa-solid fa-heart"></i>
                            </button>

                            <div class="product-card-image">
                                <a href="<?= BASE_URL ?>/product/<?= $item['id'] ?>">
                                    <img src="<?= BASE_URL ?>/assets/images/<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
                                </a>
                            </div>

                            <div class="product-card-info">
                                <div class="product-brand"><?= e($item['brand']) ?></div>
                                <h3 class="product-name"><a href="<?= BASE_URL ?>/product/<?= $item['id'] ?>"><?= e($item['name']) ?></a></h3>
                                <div class="product-price">
                                    <?php if ($item['discount_price'] > 0): ?>
                                        <span class="discounted-price">₹<?= number_format($item['discount_price'], 2) ?></span>
                                        <span class="original-price">₹<?= number_format($item['price'], 2) ?></span>
                                    <?php else: ?>
                                        <span>₹<?= number_format($item['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-card-action" style="display: flex; gap: 10px;">
                                <button class="btn btn-black" style="flex-grow: 1; font-size: 0.75rem; padding: 10px;" onclick="moveWishlistToCart(<?= $item['id'] ?>)">
                                    Move to Cart
                                </button>
                                <button class="btn btn-outline-gold" style="font-size: 0.75rem; padding: 10px;" onclick="toggleWishlist(<?= $item['id'] ?>, null); setTimeout(() => window.location.reload(), 500);">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wishlist-empty">
                    <i class="fa-regular fa-heart"></i>
                    <h2>Your Wishlist is Empty</h2>
                    <p>When browsing our shop, click the heart icon on any perfume bottle card to save it here.</p>
                    <a href="<?= BASE_URL ?>/" class="btn btn-gold">Explore Scent Boutique</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
