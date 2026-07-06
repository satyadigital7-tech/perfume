<?php
$pageTitle = "Discover Haute Fragrances";
$metaDesc = "Shop exclusive, authentic luxury fragrances for men and women. Curated best sellers and new arrivals.";
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Fetch Best Sellers
$bestSellersStmt = $db->prepare("SELECT * FROM products WHERE is_best_seller = 1 LIMIT 4");
$bestSellersStmt->execute();
$bestSellers = $bestSellersStmt->fetchAll();

// Fetch New Arrivals
$newArrivalsStmt = $db->prepare("SELECT * FROM products WHERE is_new_arrival = 1 LIMIT 4");
$newArrivalsStmt->execute();
$newArrivals = $newArrivalsStmt->fetchAll();
?>

<!-- Hero Slideshow Section -->
<section class="hero-slideshow" id="hero-slideshow">

    <!-- Slide 1 -->
    <div class="hero-slide active" style="background-image: url('<?= BASE_URL ?>/assets/images/hero/1 (1).jpeg');">
        <div class="hero-slide-overlay"></div>
        <div class="header-container">
            <div class="hero-container">
                <span class="hero-label">Exquisite Collections</span>
                <h1 class="hero-title">Discover the Art of <span>Luxury Fragrance</span></h1>
                <p class="hero-subtitle">Indulge your senses with our curated portfolio of rare, hand-crafted fragrances designed for timeless sophistication.</p>
                <div class="hero-ctas">
                    <a href="<?= BASE_URL ?>/men" class="btn btn-gold">Shop Men</a>
                    <a href="<?= BASE_URL ?>/women" class="btn btn-outline-gold">Shop Women</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 2 -->
    <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/assets/images/hero/1 (2).jpeg');">
        <div class="hero-slide-overlay"></div>
        <div class="header-container">
            <div class="hero-container">
                <span class="hero-label">For Him</span>
                <h1 class="hero-title">Bold. Powerful. <span>Unforgettable.</span></h1>
                <p class="hero-subtitle">Explore our men's collection — woody, fresh, and oriental scents that command attention and leave a lasting impression.</p>
                <div class="hero-ctas">
                    <a href="<?= BASE_URL ?>/men" class="btn btn-gold">Shop Men's Collection</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 3 -->
    <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/assets/images/hero/1 (3).jpeg');">
        <div class="hero-slide-overlay"></div>
        <div class="header-container">
            <div class="hero-container">
                <span class="hero-label">For Her</span>
                <h1 class="hero-title">Elegance in <span>Every Drop.</span></h1>
                <p class="hero-subtitle">From floral to oriental — discover women's fragrances that celebrate beauty, grace, and timeless femininity.</p>
                <div class="hero-ctas">
                    <a href="<?= BASE_URL ?>/women" class="btn btn-gold">Shop Women's Collection</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 4 -->
    <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/assets/images/hero/1 (4).jpeg');">
        <div class="hero-slide-overlay"></div>
        <div class="header-container">
            <div class="hero-container">
                <span class="hero-label">Exclusive Gift Sets</span>
                <h1 class="hero-title">The Art of <span>Gifting.</span></h1>
                <p class="hero-subtitle">Express your love and appreciation with our premium curated perfume gift sets, beautifully wrapped for that special moment.</p>
                <div class="hero-ctas">
                    <a href="<?= BASE_URL ?>/gift-sets" class="btn btn-gold">Explore Gift Sets</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Prev / Next Arrows -->
    <button class="hero-arrow hero-arrow-prev" onclick="heroSlide(-1)" aria-label="Previous slide">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button class="hero-arrow hero-arrow-next" onclick="heroSlide(1)" aria-label="Next slide">
        <i class="fa-solid fa-chevron-right"></i>
    </button>

    <!-- Dot Indicators -->
    <div class="hero-dots">
        <button class="hero-dot active" onclick="heroGoTo(0)" aria-label="Slide 1"></button>
        <button class="hero-dot" onclick="heroGoTo(1)" aria-label="Slide 2"></button>
        <button class="hero-dot" onclick="heroGoTo(2)" aria-label="Slide 3"></button>
        <button class="hero-dot" onclick="heroGoTo(3)" aria-label="Slide 4"></button>
    </div>

</section>

<script>
(function() {
    let current = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('.hero-dot');
    let timer    = setInterval(() => heroSlide(1), 5000);

    function show(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    window.heroSlide = function(dir) {
        clearInterval(timer);
        show(current + dir);
        timer = setInterval(() => heroSlide(1), 5000);
    };

    window.heroGoTo = function(n) {
        clearInterval(timer);
        show(n);
        timer = setInterval(() => heroSlide(1), 5000);
    };
})();
</script>




<!-- Featured Collections -->
<section class="collections-section">
    <div class="header-container">
        <div class="sections-title">
            <h2>The Haute Collections</h2>
            <p>Explore distinct olfactory worlds crafted for the modern connoisseur.</p>
        </div>
        <div class="collections-grid">
            <!-- Men -->
            <div class="collection-banner">
                <?php 
                $menPath = 'assets/images/Haute Collection/men.jpeg';
                $menVersion = file_exists(__DIR__ . '/../' . $menPath) ? filemtime(__DIR__ . '/../' . $menPath) : time();
                ?>
                <img src="<?= BASE_URL ?>/<?= $menPath ?>?v=<?= $menVersion ?>" alt="Men's Collection">
                <div class="collection-content">
                    <h3>For Him</h3>
                    <p>Luxury fragrances crafted for confidence, strength, and sophistication.</p>
                    <a href="<?= BASE_URL ?>/men" class="btn btn-outline-gold">Explore Men</a>
                </div>
            </div>
            <!-- Women -->
            <div class="collection-banner">
                <?php 
                $womenPath = 'assets/images/Haute Collection/women.jpeg';
                $womenVersion = file_exists(__DIR__ . '/../' . $womenPath) ? filemtime(__DIR__ . '/../' . $womenPath) : time();
                ?>
                <img src="<?= BASE_URL ?>/<?= $womenPath ?>?v=<?= $womenVersion ?>" alt="Women's Collection">
                <div class="collection-content">
                    <h3>For Her</h3>
                    <p>Elegant, sensual scents designed for timeless beauty and grace.</p>
                    <a href="<?= BASE_URL ?>/women" class="btn btn-outline-gold">Explore Women</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Best Sellers Section -->
<section class="products-section">
    <div class="header-container">
        <div class="sections-title">
            <h2>The Best Sellers</h2>
            <p>Our most coveted and iconic signature scents, loved worldwide.</p>
        </div>
        <div class="product-grid">
            <?php foreach ($bestSellers as $product): ?>
                <?php 
                $isInWishlist = false;
                if (isLoggedIn()) {
                    $wishCheck = $db->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
                    $wishCheck->execute([$_SESSION['user_id'], $product['id']]);
                    $isInWishlist = (bool)$wishCheck->fetch();
                }
                ?>
                <div class="product-card">
                    <span class="product-badge">Best Seller</span>
                    <button class="wishlist-toggle <?= $isInWishlist ? 'active' : '' ?>" 
                            onclick="toggleWishlist(<?= $product['id'] ?>, this)" 
                            aria-label="Toggle wishlist">
                        <i class="<?= $isInWishlist ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                    <div class="product-card-image">
                        <a href="<?= BASE_URL ?>/product/<?= $product['id'] ?>">
                            <img src="<?= BASE_URL ?>/assets/images/<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="product-card-info">
                        <div class="product-brand"><?= e($product['brand']) ?></div>
                        <h3 class="product-name"><a href="<?= BASE_URL ?>/product/<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
                        <div class="product-rating">
                            <?php 
                            $rating = (float)$product['rating'];
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="<?= $i <= $rating ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            <?php endfor; ?>
                            <span>(<?= $rating ?>)</span>
                        </div>
                        <div class="product-price">
                            <?php if ($product['discount_price'] > 0): ?>
                                <span class="discounted-price">₹<?= number_format($product['discount_price'], 2) ?></span>
                                <span class="original-price">₹<?= number_format($product['price'], 2) ?></span>
                            <?php else: ?>
                                <span>₹<?= number_format($product['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-card-action">
                        <button class="btn btn-black" onclick="addToCart(<?= $product['id'] ?>, 1)">Add To Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- New Arrivals Section -->
<section class="products-section" style="background-color: var(--color-white); padding-bottom: 80px; padding-top: 40px; margin-top: 80px;">
    <div class="header-container">
        <div class="sections-title">
            <h2>The New Arrivals</h2>
            <p>Discover the latest boundary-pushing fragrances freshly added to our boutique.</p>
        </div>
        <div class="product-grid">
            <?php foreach ($newArrivals as $product): ?>
                <?php 
                $isInWishlist = false;
                if (isLoggedIn()) {
                    $wishCheck = $db->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
                    $wishCheck->execute([$_SESSION['user_id'], $product['id']]);
                    $isInWishlist = (bool)$wishCheck->fetch();
                }
                ?>
                <div class="product-card">
                    <span class="product-badge" style="background-color: var(--color-black);">New</span>
                    <button class="wishlist-toggle <?= $isInWishlist ? 'active' : '' ?>" 
                            onclick="toggleWishlist(<?= $product['id'] ?>, this)" 
                            aria-label="Toggle wishlist">
                        <i class="<?= $isInWishlist ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                    <div class="product-card-image">
                        <a href="<?= BASE_URL ?>/product/<?= $product['id'] ?>">
                            <img src="<?= BASE_URL ?>/assets/images/<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="product-card-info">
                        <div class="product-brand"><?= e($product['brand']) ?></div>
                        <h3 class="product-name"><a href="<?= BASE_URL ?>/product/<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
                        <div class="product-rating">
                            <?php 
                            $rating = (float)$product['rating'];
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="<?= $i <= $rating ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            <?php endfor; ?>
                            <span>(<?= $rating ?>)</span>
                        </div>
                        <div class="product-price">
                            <?php if ($product['discount_price'] > 0): ?>
                                <span class="discounted-price">₹<?= number_format($product['discount_price'], 2) ?></span>
                                <span class="original-price">₹<?= number_format($product['price'], 2) ?></span>
                            <?php else: ?>
                                <span>₹<?= number_format($product['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-card-action">
                        <button class="btn btn-black" onclick="addToCart(<?= $product['id'] ?>, 1)">Add To Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
