<?php
$pageTitle = "Shop Luxury Fragrances";
if ($route === 'men') {
    $pageTitle = "Men's Luxury Perfumes";
} elseif ($route === 'women') {
    $pageTitle = "Women's Luxury Perfumes";
} elseif ($route === 'search') {
    $pageTitle = "Search Results";
}
$metaDesc = "Browse Elixir & Co. exclusive luxury perfume collections. Filter by categories, price range, and size.";
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Initialize filter parameters from GET
$genderFilter = $_GET['gender'] ?? '';
$categoryFilters = $_GET['category'] ?? [];
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 499;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : 4999;
$sizeFilters = $_GET['size'] ?? [];
$concentrationFilters = $_GET['concentration'] ?? [];
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

// Convert single category to array if passed as string
if (is_string($categoryFilters)) {
    $categoryFilters = [$categoryFilters];
}
if (is_string($sizeFilters)) {
    $sizeFilters = [$sizeFilters];
}
if (is_string($concentrationFilters)) {
    $concentrationFilters = [$concentrationFilters];
}

// Build SQL query dynamically
$queryStr = "SELECT * FROM products WHERE 1=1";
$params = [];

// Apply gender/category filters
if ($genderFilter) {
    $queryStr .= " AND gender = ?";
    $params[] = $genderFilter;
}

if (!empty($categoryFilters)) {
    // Categories maps to gender/type filters
    $catClauses = [];
    foreach ($categoryFilters as $cat) {
        if ($cat === 'For Him') {
            $catClauses[] = "gender = 'Men'";
        } elseif ($cat === 'For Her') {
            $catClauses[] = "gender = 'Women'";
        } elseif ($cat === 'Unisex') {
            $catClauses[] = "gender = 'Unisex'";
        } elseif ($cat === 'Gift Sets') {
            $catClauses[] = "fragrance_type = 'Gift'";
        }
    }
    if (!empty($catClauses)) {
        $queryStr .= " AND (" . implode(" OR ", $catClauses) . ")";
    }
}

// Apply Price Filters
$queryStr .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) >= ?";
$params[] = $minPrice;
$queryStr .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) <= ?";
$params[] = $maxPrice;

// Apply Search Queries
if ($searchQuery) {
    $queryStr .= " AND (name LIKE ? OR brand LIKE ? OR fragrance_type LIKE ? OR description LIKE ?)";
    $likeParam = '%' . $searchQuery . '%';
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
}

// Sorting logic
$sortOrderStr = " ORDER BY id DESC";
switch ($sortBy) {
    case 'price_asc':
        $sortOrderStr = " ORDER BY (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) ASC";
        break;
    case 'price_desc':
        $sortOrderStr = " ORDER BY (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) DESC";
        break;
    case 'rating':
        $sortOrderStr = " ORDER BY rating DESC";
        break;
    case 'newest':
    default:
        $sortOrderStr = " ORDER BY id DESC";
        break;
}

// Pagination logic
$limit = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch total count for pagination
$countQueryStr = str_replace("SELECT *", "SELECT COUNT(*)", $queryStr);
$countStmt = $db->prepare($countQueryStr);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch products for current page
$finalQueryStr = $queryStr . $sortOrderStr . " LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($finalQueryStr);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Mockup metadata for exact price ranges and notes matching the design
$productMockDetails = [
    1 => ['price_range' => '₹1,499 – ₹2,199', 'notes' => 'Rose, Jasmine, White Musk'],
    2 => ['price_range' => '₹1,599 – ₹2,299', 'notes' => 'Amber, Vanilla, Sandalwood'],
    3 => ['price_range' => '₹1,299 – ₹1,999', 'notes' => 'Bergamot, Lemon, Musk'],
    4 => ['price_range' => '₹1,499 – ₹2,199', 'notes' => 'Tuberose, Ylang-Ylang, Musk'],
    5 => ['price_range' => '₹1,799 – ₹2,499', 'notes' => 'Oud, Patchouli, Saffron'],
    6 => ['price_range' => '₹1,399 – ₹2,099', 'notes' => 'Pine, Cedarwood, Vetiver'],
    7 => ['price_range' => '₹1,499 – ₹2,199', 'notes' => 'Musk, Tonka Bean, Amber'],
    8 => ['price_range' => '₹1,299 – ₹1,999', 'notes' => 'Vanilla, Praline, Musk']
];
?>

<div class="elixir-shop-page">
    <!-- Header/Banner Section -->
    <section class="elixir-shop-hero">
        <div class="header-container hero-grid">
            <div class="elixir-hero-content">
                <h1 class="elixir-hero-brand" style="font-size: 3.5rem; margin-bottom: 20px;">Shop</h1>
                <p class="elixir-hero-desc" style="max-width: 500px; color: #b0b0b0; margin-bottom: 20px;">Discover our collection of fine fragrances, crafted with the world's finest ingredients.</p>
                <div class="elixir-breadcrumbs">
                    <a href="<?= BASE_URL ?>/">Home</a> &nbsp;&gt;&nbsp; <span class="active">Shop</span>
                </div>
            </div>
            <div class="elixir-hero-image-wrapper" style="max-height: 280px; overflow: hidden;">
                <img src="<?= BASE_URL ?>/assets/images/black_orchid.jpg" alt="Shop Feature" style="height: 280px; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="elixir-shop-body-section">
        <div class="header-container elixir-shop-grid">
            
            <!-- Left Column: Filters Sidebar -->
            <aside class="elixir-shop-sidebar">
                <form action="<?= BASE_URL ?>/search" method="GET" id="shop-filter-form">
                    <?php if ($searchQuery): ?>
                        <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="sort" value="<?= e($sortBy) ?>">

                    <div class="sidebar-header">
                        <h3>Filters</h3>
                        <a href="<?= BASE_URL ?>/search" class="clear-filters-btn">Clear All</a>
                    </div>

                    <!-- Categories Group -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">Categories <i class="fa-solid fa-chevron-down"></i></h4>
                        <div class="filter-group-content">
                            <label class="checkbox-container">
                                <input type="checkbox" name="category[]" value="All Perfumes" <?= empty($categoryFilters) || in_array('All Perfumes', $categoryFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                All Perfumes
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="category[]" value="For Him" <?= in_array('For Him', $categoryFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                For Him
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="category[]" value="For Her" <?= in_array('For Her', $categoryFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                For Her
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="category[]" value="Unisex" <?= in_array('Unisex', $categoryFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                Unisex
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="category[]" value="Gift Sets" <?= in_array('Gift Sets', $categoryFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                Gift Sets
                            </label>
                        </div>
                    </div>

                    <!-- Price Group -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">Price <i class="fa-solid fa-chevron-down"></i></h4>
                        <div class="filter-group-content">
                            <div class="price-slider-label">
                                <span>₹<?= number_format($minPrice) ?></span>
                                <span>₹<?= number_format($maxPrice) ?></span>
                            </div>
                            <div class="price-range-inputs" style="display: flex; gap: 10px; margin-top: 10px;">
                                <input type="number" name="min_price" value="<?= $minPrice ?>" style="width: 50%; padding: 8px;" placeholder="Min" onchange="this.form.submit()">
                                <input type="number" name="max_price" value="<?= $maxPrice ?>" style="width: 50%; padding: 8px;" placeholder="Max" onchange="this.form.submit()">
                            </div>
                        </div>
                    </div>

                    <!-- Size Group -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">Size <i class="fa-solid fa-chevron-down"></i></h4>
                        <div class="filter-group-content">
                            <label class="checkbox-container">
                                <input type="checkbox" name="size[]" value="50 ml" <?= in_array('50 ml', $sizeFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                50 ml
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="size[]" value="100 ml" <?= in_array('100 ml', $sizeFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                100 ml
                            </label>
                        </div>
                    </div>

                    <!-- Concentration Group -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">Concentration <i class="fa-solid fa-chevron-down"></i></h4>
                        <div class="filter-group-content">
                            <label class="checkbox-container">
                                <input type="checkbox" name="concentration[]" value="Eau de Parfum" <?= in_array('Eau de Parfum', $concentrationFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                Eau de Parfum (EDP)
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="concentration[]" value="Extrait de Parfum" <?= in_array('Extrait de Parfum', $concentrationFilters) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="checkmark"></span>
                                Extrait de Parfum
                            </label>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/search" class="elixir-btn-reset-filters">Reset Filters <i class="fa-solid fa-rotate-left"></i></a>
                </form>
            </aside>

            <!-- Right Column: Products List -->
            <div class="elixir-shop-content">
                <div class="elixir-shop-results-header">
                    <div class="results-count">
                        Showing <strong><?= count($products) > 0 ? ($offset + 1) . '-' . ($offset + count($products)) : '0' ?></strong> of <strong><?= $totalProducts ?></strong> products
                    </div>
                    
                    <div class="sort-select">
                        <form action="" method="GET">
                            <!-- Forward existing filters -->
                            <?php if ($genderFilter): ?>
                                <input type="hidden" name="gender" value="<?= e($genderFilter) ?>">
                            <?php endif; ?>
                            <?php foreach ($categoryFilters as $cat): ?>
                                <input type="hidden" name="category[]" value="<?= e($cat) ?>">
                            <?php endforeach; ?>
                            <input type="hidden" name="min_price" value="<?= $minPrice ?>">
                            <input type="hidden" name="max_price" value="<?= $maxPrice ?>">
                            <?php if ($searchQuery): ?>
                                <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                            <?php endif; ?>

                            <select name="sort" onchange="this.form.submit()">
                                <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Latest Arrivals</option>
                                <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Patron Rating</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (count($products) > 0): ?>
                    <div class="elixir-shop-products-grid">
                        <?php foreach ($products as $p): 
                            $meta = $productMockDetails[$p['id']] ?? ['price_range' => '₹' . number_format($p['price']), 'notes' => $p['top_notes']];
                            
                            $isInWishlist = false;
                            if (isLoggedIn()) {
                                $wishCheck = $db->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
                                $wishCheck->execute([$_SESSION['user_id'], $p['id']]);
                                $isInWishlist = (bool)$wishCheck->fetch();
                            }
                        ?>
                            <div class="elixir-grid-card">
                                <div class="elixir-grid-card-img-wrapper">
                                    <?php if ($p['is_best_seller']): ?>
                                        <span class="elixir-grid-badge">Bestseller</span>
                                    <?php elseif ($p['is_new_arrival']): ?>
                                        <span class="elixir-grid-badge" style="background-color: #000000; color: #ffffff;">New</span>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/product/<?= $p['id'] ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>">
                                    </a>
                                </div>
                                <div class="elixir-grid-card-info">
                                    <h3 class="elixir-grid-card-title"><a href="<?= BASE_URL ?>/product/<?= $p['id'] ?>"><?= e($p['name']) ?></a></h3>
                                    <p class="elixir-grid-card-notes"><?= e($meta['notes']) ?></p>
                                    
                                    <div class="elixir-shop-size-options">
                                        <button class="size-btn active">50 ml</button>
                                        <button class="size-btn">100 ml</button>
                                    </div>

                                    <div class="elixir-grid-card-price" style="margin-top: 15px; margin-bottom: 20px; font-weight: 700;">
                                        <?= e($meta['price_range']) ?>
                                    </div>
                                    
                                    <div class="elixir-shop-card-actions">
                                        <button class="elixir-btn-add-cart" onclick="addToCart(<?= $p['id'] ?>, 1)">Add To Cart</button>
                                        <button class="elixir-shop-wishlist-btn <?= $isInWishlist ? 'active' : '' ?>" onclick="toggleWishlist(<?= $p['id'] ?>, this)">
                                            <i class="<?= $isInWishlist ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="elixir-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&sort=<?= $sortBy ?>&gender=<?= $genderFilter ?>&min_price=<?= $minPrice ?>&max_price=<?= $maxPrice ?>&q=<?= urlencode($searchQuery) ?>" class="page-link">&lt;</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>&sort=<?= $sortBy ?>&gender=<?= $genderFilter ?>&min_price=<?= $minPrice ?>&max_price=<?= $maxPrice ?>&q=<?= urlencode($searchQuery) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&sort=<?= $sortBy ?>&gender=<?= $genderFilter ?>&min_price=<?= $minPrice ?>&max_price=<?= $maxPrice ?>&q=<?= urlencode($searchQuery) ?>" class="page-link">&gt;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="wishlist-empty" style="background: #ffffff; padding: 60px 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border-radius: 4px;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 2.5rem; color: var(--color-gold); margin-bottom: 20px;"></i>
                        <h2>No Fragrances Found</h2>
                        <p>Try refining your filters or resetting the search parameters.</p>
                        <a href="<?= BASE_URL ?>/search" class="btn btn-gold" style="margin-top: 20px;">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
