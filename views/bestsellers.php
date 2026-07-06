<?php
$pageTitle = "Our Bestsellers";
$metaDesc = "Explore the most coveted and loved signature fragrances from Elixir & Co.";
include __DIR__ . '/../includes/header.php';

$db = getDB();
// Fetch the 8 Elixir & Co. products
$products = $db->query("SELECT * FROM products WHERE brand = 'Elixir & Co.' ORDER BY id ASC LIMIT 8")->fetchAll();

// Custom data for prices, review counts, and bestseller badges to match the mockup exactly
$bestsellerMetadata = [
    1 => ['price_range' => '₹1,499 – ₹2,199', 'reviews' => 2125, 'badge' => false, 'notes' => 'Rose, Jasmine, White Musk'],
    2 => ['price_range' => '₹1,599 – ₹2,299', 'reviews' => 2458, 'badge' => true, 'notes' => 'Amber, Vanilla, Sandalwood'],
    3 => ['price_range' => '₹1,299 – ₹1,999', 'reviews' => 2876, 'badge' => false, 'notes' => 'Bergamot, Lemon, Musk'],
    4 => ['price_range' => '₹1,499 – ₹2,199', 'reviews' => 1876, 'badge' => false, 'notes' => 'Tuberose, Ylang-Ylang, Musk'],
    5 => ['price_range' => '₹1,799 – ₹2,499', 'reviews' => 1389, 'badge' => true, 'notes' => 'Oud, Patchouli, Saffron'],
    6 => ['price_range' => '₹1,399 – ₹2,099', 'reviews' => 1203, 'badge' => false, 'notes' => 'Pine, Cedarwood, Vetiver'],
    7 => ['price_range' => '₹1,499 – ₹2,199', 'reviews' => 1147, 'badge' => false, 'notes' => 'Musk, Tonka Bean, Amber'],
    8 => ['price_range' => '₹1,299 – ₹1,999', 'reviews' => 1028, 'badge' => false, 'notes' => 'Vanilla, Praline, Musk']
];
?>

<div class="elixir-bestsellers-page">
    <!-- Header/Banner Section -->
    <section class="elixir-bestsellers-hero">
        <div class="header-container hero-grid">
            <div class="elixir-hero-content">
                <div class="elixir-hero-subtitle">Our Most Loved</div>
                <h1 class="elixir-hero-brand" style="font-size: 3.5rem;">Bestsellers</h1>
                <div class="elixir-hero-separator">
                    <i class="fa-solid fa-star-of-life"></i>
                </div>
                <p class="elixir-hero-desc" style="max-width: 500px; color: #b0b0b0;">Timeless fragrances loved by thousands. Discover the scents that everyone is talking about.</p>
            </div>
            <div class="elixir-hero-image-wrapper" style="max-height: 280px; overflow: hidden;">
                <img src="<?= BASE_URL ?>/assets/images/black_orchid.jpg" alt="Bestsellers Feature" style="height: 280px; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- Product Grid Section -->
    <section class="elixir-bestsellers-grid-section">
        <div class="header-container">
            <div class="elixir-grid-4col">
                <?php foreach ($products as $p): 
                    $meta = $bestsellerMetadata[$p['id']] ?? ['price_range' => '₹1,499 – ₹2,199', 'reviews' => 1000, 'badge' => false, 'notes' => $p['top_notes']];
                ?>
                    <div class="elixir-grid-card">
                        <div class="elixir-grid-card-img-wrapper">
                            <?php if ($meta['badge']): ?>
                                <span class="elixir-grid-badge">Bestseller</span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/product/<?= $p['id'] ?>">
                                <img src="<?= BASE_URL ?>/assets/images/<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>">
                            </a>
                        </div>
                        <div class="elixir-grid-card-info">
                            <h3 class="elixir-grid-card-title"><a href="<?= BASE_URL ?>/product/<?= $p['id'] ?>"><?= e($p['name']) ?></a></h3>
                            <p class="elixir-grid-card-notes"><?= e($meta['notes']) ?></p>
                            <div class="elixir-grid-card-sizes">50 ml &nbsp;&nbsp;|&nbsp;&nbsp; 100 ml</div>
                            <div class="elixir-grid-card-price"><?= e($meta['price_range']) ?></div>
                            <div class="elixir-grid-card-rating">
                                <div class="stars">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <span class="count">(<?= number_format($meta['reviews']) ?>)</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Bar -->
    <section class="elixir-features-bar" style="background-color: #f7f4ed;">
        <div class="header-container elixir-features-flex">
            <div class="elixir-feature-item">
                <div class="elixir-feature-icon"><i class="fa-regular fa-clock"></i></div>
                <h4 class="elixir-feature-title">Long-Lasting Fragrances</h4>
                <p class="elixir-feature-desc">Stay captivating all day long.</p>
            </div>
            <div class="elixir-feature-item">
                <div class="elixir-feature-icon"><i class="fa-solid fa-droplet"></i></div>
                <h4 class="elixir-feature-title">Premium Ingredients</h4>
                <p class="elixir-feature-desc">Sourced from the finest origins.</p>
            </div>
            <div class="elixir-feature-item">
                <div class="elixir-feature-icon"><i class="fa-solid fa-gift"></i></div>
                <h4 class="elixir-feature-title">Elegant Packaging</h4>
                <p class="elixir-feature-desc">Designed to reflect luxury within.</p>
            </div>
            <div class="elixir-feature-item">
                <div class="elixir-feature-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <h4 class="elixir-feature-title">Made in India</h4>
                <p class="elixir-feature-desc">Proudly crafted for you, in India.</p>
            </div>
            <div class="elixir-feature-item">
                <div class="elixir-feature-icon"><i class="fa-solid fa-crown"></i></div>
                <h4 class="elixir-feature-title">Luxury Experience</h4>
                <p class="elixir-feature-desc">Because you deserve nothing less.</p>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
