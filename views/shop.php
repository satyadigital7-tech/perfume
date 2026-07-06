<?php
$pageTitle = "Luxury Fragrances";
if ($route === 'men') {
    $pageTitle = "Men's Luxury Perfumes";
} elseif ($route === 'women') {
    $pageTitle = "Women's Luxury Perfumes";
} elseif ($route === 'search') {
    $pageTitle = "Search Results";
}
$metaDesc = "Browse our exclusive luxury perfume collections. Filter by brand, gender, notes, and price range.";
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Initialize filter parameters from GET
$genderFilter = $_GET['gender'] ?? '';
$brandFilters = $_GET['brand'] ?? [];
$typeFilters = $_GET['fragrance_type'] ?? [];
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$searchQuery = $_GET['q'] ?? '';
$isNew = $_GET['new'] ?? '';
$isBestseller = $_GET['bestseller'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

// Convert single brand or type to array if they are passed as string
if (is_string($brandFilters)) {
    $brandFilters = [$brandFilters];
}
if (is_string($typeFilters)) {
    $typeFilters = [$typeFilters];
}

// Build SQL query dynamically
$queryStr = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($genderFilter) {
    $queryStr .= " AND gender = ?";
    $params[] = $genderFilter;
}

if (!empty($brandFilters)) {
    $inClause = implode(',', array_fill(0, count($brandFilters), '?'));
    $queryStr .= " AND brand IN ($inClause)";
    foreach ($brandFilters as $brand) {
        $params[] = $brand;
    }
}

if (!empty($typeFilters)) {
    $inClause = implode(',', array_fill(0, count($typeFilters), '?'));
    $queryStr .= " AND fragrance_type IN ($inClause)";
    foreach ($typeFilters as $type) {
        $params[] = $type;
    }
}

if ($minPrice !== null) {
    $queryStr .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $queryStr .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) <= ?";
    $params[] = $maxPrice;
}

if ($searchQuery) {
    $queryStr .= " AND (name LIKE ? OR brand LIKE ? OR fragrance_type LIKE ? OR description LIKE ?)";
    $likeParam = '%' . $searchQuery . '%';
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
}

if ($isNew === '1') {
    $queryStr .= " AND is_new_arrival = 1";
}

if ($isBestseller === '1') {
    $queryStr .= " AND is_best_seller = 1";
}

// Sorting logic
switch ($sortBy) {
    case 'price_asc':
        $queryStr .= " ORDER BY (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) ASC";
        break;
    case 'price_desc':
        $queryStr .= " ORDER BY (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) DESC";
        break;
    case 'rating':
        $queryStr .= " ORDER BY rating DESC";
        break;
    case 'newest':
    default:
        $queryStr .= " ORDER BY id DESC";
        break;
}

$stmt = $db->prepare($queryStr);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Unique brand lists & types for sidebar rendering
$allBrands = ['Dior', 'Chanel', 'Creed', 'Tom Ford', 'Armani', 'Versace', 'YSL'];
$allTypes = ['Woody', 'Floral', 'Fresh', 'Citrus', 'Oriental', 'Musk'];
?>

<div class="header-container">
    <div class="shop-layout">
        
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar">
            <button type="button" class="filter-mobile-btn" id="filter-mobile-btn">
                <i class="fa-solid fa-sliders"></i> Filter & Sort
            </button>
            <form action="" method="GET" id="filter-form">
                
                <!-- If search is ongoing, keep it -->
                <?php if ($searchQuery): ?>
                    <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <?php endif; ?>

                <!-- Gender Filter -->
                <div class="filter-group">
                    <h3>Gender</h3>
                    <ul class="filter-options">
                        <li>
                            <label>
                                <input type="radio" name="gender" value="" <?= $genderFilter === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                                All Categories
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="gender" value="Men" <?= $genderFilter === 'Men' ? 'checked' : '' ?> onchange="this.form.submit()">
                                Men
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="gender" value="Women" <?= $genderFilter === 'Women' ? 'checked' : '' ?> onchange="this.form.submit()">
                                Women
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="gender" value="Unisex" <?= $genderFilter === 'Unisex' ? 'checked' : '' ?> onchange="this.form.submit()">
                                Unisex
                            </label>
                        </li>
                    </ul>
                </div>

                <!-- Brand Filter -->
                <div class="filter-group">
                    <h3>Brand</h3>
                    <ul class="filter-options">
                        <?php foreach ($allBrands as $brand): ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="brand[]" value="<?= e($brand) ?>" 
                                        <?= in_array($brand, $brandFilters) ? 'checked' : '' ?> 
                                        onchange="this.form.submit()">
                                    <?= e($brand) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Fragrance Notes/Types -->
                <div class="filter-group">
                    <h3>Fragrance Notes</h3>
                    <ul class="filter-options">
                        <?php foreach ($allTypes as $type): ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="fragrance_type[]" value="<?= e($type) ?>" 
                                        <?= in_array($type, $typeFilters) ? 'checked' : '' ?> 
                                        onchange="this.form.submit()">
                                    <?= e($type) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price Range Filter -->
                <div class="filter-group">
                    <h3>Price Range</h3>
                    <div class="price-range-inputs">
                        <input type="number" name="min_price" placeholder="Min" value="<?= $minPrice !== null ? e($minPrice) : '' ?>">
                        <span>to</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?= $maxPrice !== null ? e($maxPrice) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-gold" style="width: 100%; margin-top: 15px; padding: 8px; font-size: 0.75rem;">Apply Price</button>
                </div>

                <!-- Clear Filters Button -->
                <a href="<?= BASE_URL ?>/<?= $route ?>" class="btn btn-outline-gold" style="width: 100%; display: block; font-size: 0.75rem; padding: 8px;">Reset Filters</a>
            </form>
        </aside>

        <!-- Main Product Area -->
        <section class="shop-main-content">
            <div class="shop-results-header">
                <div class="results-count">
                    Found <strong><?= count($products) ?></strong> luxurious fragrances
                </div>
                
                <div class="sort-select">
                    <form action="" method="GET" id="sort-form">
                        <!-- Forward existing filters -->
                        <?php if ($genderFilter): ?>
                            <input type="hidden" name="gender" value="<?= e($genderFilter) ?>">
                        <?php endif; ?>
                        <?php foreach ($brandFilters as $brand): ?>
                            <input type="hidden" name="brand[]" value="<?= e($brand) ?>">
                        <?php endforeach; ?>
                        <?php foreach ($typeFilters as $type): ?>
                            <input type="hidden" name="fragrance_type[]" value="<?= e($type) ?>">
                        <?php endforeach; ?>
                        <?php if ($minPrice !== null): ?>
                            <input type="hidden" name="min_price" value="<?= e($minPrice) ?>">
                        <?php endif; ?>
                        <?php if ($maxPrice !== null): ?>
                            <input type="hidden" name="max_price" value="<?= e($maxPrice) ?>">
                        <?php endif; ?>
                        <?php if ($searchQuery): ?>
                            <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                        <?php endif; ?>

                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest arrivals</option>
                            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Patron Rating</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (count($products) > 0): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php 
                        $isInWishlist = false;
                        if (isLoggedIn()) {
                            $wishCheck = $db->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
                            $wishCheck->execute([$_SESSION['user_id'], $product['id']]);
                            $isInWishlist = (bool)$wishCheck->fetch();
                        }
                        ?>
                        <div class="product-card">
                            <?php if ($product['is_best_seller']): ?>
                                <span class="product-badge">Best Seller</span>
                            <?php elseif ($product['is_new_arrival']): ?>
                                <span class="product-badge" style="background-color: var(--color-black);">New</span>
                            <?php endif; ?>
                            
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
            <?php else: ?>
                <div class="wishlist-empty">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h2>No Fragrances Found</h2>
                    <p>Try refining your filters or searching for another scent family.</p>
                    <a href="<?= BASE_URL ?>/<?= $route ?>" class="btn btn-gold">View All Products</a>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
