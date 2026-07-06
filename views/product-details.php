<?php
$db = getDB();

// Fetch product details
$productStmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$productStmt->execute([$productId ?? 0]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pageTitle = $product['brand'] . " " . $product['name'];
$metaDesc = e($product['description']);
include __DIR__ . '/../includes/header.php';

// Fetch reviews for this product
$reviewsStmt = $db->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.id DESC");
$reviewsStmt->execute([$product['id']]);
$reviews = $reviewsStmt->fetchAll();

// Fetch related products (same gender, excluding current product)
$relatedStmt = $db->prepare("SELECT * FROM products WHERE gender = ? AND id != ? LIMIT 3");
$relatedStmt->execute([$product['gender'], $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

$isInWishlist = false;
if (isLoggedIn()) {
    $wishCheck = $db->prepare("SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?");
    $wishCheck->execute([$_SESSION['user_id'], $product['id']]);
    $isInWishlist = (bool)$wishCheck->fetch();
}
?>

<div class="header-container">
    
    <div class="product-details-container">
        
        <!-- Product Images Gallery -->
        <div class="details-gallery">
            <div class="details-main-image" id="main-image-container" onmousemove="zoomImage(event)" onmouseleave="resetZoom()">
                <img id="main-product-img" src="<?= BASE_URL ?>/assets/images/<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
            </div>
            
            <?php
            // Collect all available product images
            $images = [ $product['image_url'] ];
            if (!empty($product['image_url_2'])) $images[] = $product['image_url_2'];
            if (!empty($product['image_url_3'])) $images[] = $product['image_url_3'];
            if (!empty($product['image_url_4'])) $images[] = $product['image_url_4'];
            ?>
            
            <?php if (count($images) > 1): ?>
                <div class="details-thumbnails" style="margin-top: 15px;">
                    <?php foreach ($images as $idx => $img): ?>
                        <img class="<?= $idx === 0 ? 'active' : '' ?>" src="<?= BASE_URL ?>/assets/images/<?= e($img) ?>" alt="Thumbnail <?= $idx+1 ?>" onclick="switchImage('<?= BASE_URL ?>/assets/images/<?= e($img) ?>', this)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Purchase Information -->
        <div class="details-info">
            <div class="details-brand"><?= e($product['brand']) ?></div>
            <h1 class="details-title"><?= e($product['name']) ?></h1>
            
            <!-- Scent Rating -->
            <div class="details-rating" style="margin-top: 5px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <div style="color: var(--color-gold); font-size: 0.95rem;">
                    <?php
                    $productRating = (float)$product['rating'];
                    $fullStars = floor($productRating);
                    $halfStar = ($productRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    
                    for ($i = 0; $i < $fullStars; $i++) {
                        echo '<i class="fa-solid fa-star"></i>';
                    }
                    if ($halfStar) {
                        echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    }
                    for ($i = 0; $i < $emptyStars; $i++) {
                        echo '<i class="fa-regular fa-star"></i>';
                    }
                    ?>
                </div>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--color-black);"><?= number_format($productRating, 1) ?> / 5.0</span>
                <span style="font-size: 0.85rem; color: var(--color-text-muted);">| Scent Score</span>
            </div>

            <div class="details-price">
                <?php if ($product['discount_price'] > 0): ?>
                    <span class="discount">₹<?= number_format($product['discount_price'], 2) ?></span>
                    <span class="original">₹<?= number_format($product['price'], 2) ?></span>
                <?php else: ?>
                    <span>₹<?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['top_notes']) || !empty($product['heart_notes']) || !empty($product['base_notes'])): ?>
                <div class="notes-container">
                    <h3>Fragrance Notes</h3>
                    <div class="notes-grid">
                        <?php if (!empty($product['top_notes'])): ?>
                            <div class="note-card">
                                <h4>Top Notes</h4>
                                <p><?= e($product['top_notes']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['heart_notes'])): ?>
                            <div class="note-card">
                                <h4>Heart Notes</h4>
                                <p><?= e($product['heart_notes']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['base_notes'])): ?>
                            <div class="note-card">
                                <h4>Base Notes</h4>
                                <p><?= e($product['base_notes']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 35px;">
                <h3 style="font-family: var(--font-heading); font-size: 1.1rem; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-black);">Scent Description</h3>
                <p class="details-desc" style="margin-bottom: 0;"><?= e($product['description']) ?></p>
            </div>

            <ul class="details-meta">
                <li><strong>Collection Category:</strong> <?= e($product['gender']) ?>'s Perfumes</li>
                <li><strong>Fragrance Family:</strong> <?= e($product['fragrance_type']) ?></li>
                <li><strong>Boutique Availability:</strong> 
                    <?php if ($product['stock'] > 0): ?>
                        <span style="color: var(--color-success); font-weight: 600;">In Stock (<?= $product['stock'] ?> bottles left)</span>
                    <?php else: ?>
                        <span style="color: var(--color-error); font-weight: 600;">Out of Stock</span>
                    <?php endif; ?>
                </li>
            </ul>


            <!-- Product Actions -->
            <?php if ($product['stock'] > 0): ?>
                <div class="details-actions">
                    <div class="qty-selector">
                        <button type="button" onclick="changeQty(-1)">-</button>
                        <input type="number" id="detail-qty" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>
                    <button class="btn btn-black btn-add-cart" onclick="triggerAddToCart(<?= $product['id'] ?>)">Add To Cart</button>
                    <button class="btn btn-gold" onclick="triggerBuyNow(<?= $product['id'] ?>)">Buy Now</button>
                    <button class="btn-wishlist <?= $isInWishlist ? 'active' : '' ?>" onclick="toggleWishlist(<?= $product['id'] ?>, this)" aria-label="Add to wishlist">
                        <i class="<?= $isInWishlist ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ━━━ Pincode Delivery Checker ━━━ -->
            <div class="pincode-checker" id="pincode-checker-box">
                <div class="pincode-checker-label">
                    <i class="fa-solid fa-truck-fast"></i>
                    Check Delivery Availability
                </div>
                <div class="pincode-input-row">
                    <input
                        type="text"
                        id="pincode-input"
                        placeholder="Enter 6-digit Pincode"
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="postal-code"
                    >
                    <button type="button" id="pincode-check-btn" onclick="checkPincodeDelivery()">Check</button>
                </div>
                <div id="pincode-result" class="pincode-result"></div>
            </div>

            <style>
            .pincode-checker {
                margin: 25px 0 15px 0;
                background: #f9f7f3;
                border: 1px solid #e8d9b8;
                border-left: 4px solid var(--color-gold, #c8a96e);
                padding: 16px 18px;
                border-radius: 4px;
            }
            .pincode-checker-label {
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1.5px;
                color: #6b5c3e;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .pincode-checker-label i { color: var(--color-gold, #c8a96e); font-size: 1rem; }
            .pincode-input-row {
                display: flex;
                gap: 0;
                max-width: 320px;
            }
            .pincode-input-row input {
                flex: 1;
                border: 1px solid #d4b97a;
                border-right: none;
                padding: 9px 14px;
                font-size: 0.9rem;
                font-family: inherit;
                outline: none;
                border-radius: 3px 0 0 3px;
                background: #fff;
                letter-spacing: 2px;
                color: #1a1a1a;
                transition: border-color 0.2s;
            }
            .pincode-input-row input:focus { border-color: var(--color-gold, #c8a96e); }
            .pincode-input-row input::placeholder { letter-spacing: 0; color: #bbb; }
            #pincode-check-btn {
                background: var(--color-gold, #c8a96e);
                color: #000;
                border: 1px solid #c8a96e;
                padding: 9px 20px;
                font-family: inherit;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
                cursor: pointer;
                border-radius: 0 3px 3px 0;
                transition: background 0.2s, opacity 0.2s;
            }
            #pincode-check-btn:hover { opacity: 0.85; }
            #pincode-check-btn:disabled { opacity: 0.6; cursor: not-allowed; }
            .pincode-result {
                margin-top: 12px;
                font-size: 0.85rem;
                line-height: 1.6;
                min-height: 20px;
            }
            .pincode-result.loading { color: #888; }
            .pincode-result.success { color: #2e6b2e; }
            .pincode-result.error   { color: #c0392b; }
            .pincode-result .delivery-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .pincode-result .delivery-location {
                font-weight: 600;
                color: #1a1a1a;
                font-size: 0.9rem;
            }
            .pincode-result .delivery-date {
                color: #2e6b2e;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .pincode-result .delivery-date i { color: #4caf50; }
            .pincode-result .delivery-note {
                color: #888;
                font-size: 0.78rem;
                margin-top: 2px;
            }
            .pincode-result .pincode-spinner {
                display: inline-block;
                width: 14px;
                height: 14px;
                border: 2px solid #ddd;
                border-top-color: var(--color-gold, #c8a96e);
                border-radius: 50%;
                animation: spin 0.7s linear infinite;
                vertical-align: middle;
                margin-right: 6px;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            </style>

            <script>
            (function() {
                // Auto-trigger on 6 digits entered
                document.getElementById('pincode-input').addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                    if (this.value.length === 6) {
                        checkPincodeDelivery();
                    } else {
                        document.getElementById('pincode-result').innerHTML = '';
                        document.getElementById('pincode-result').className = 'pincode-result';
                    }
                });

                // Also trigger on Enter key
                document.getElementById('pincode-input').addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && this.value.length === 6) checkPincodeDelivery();
                });
            })();

            function checkPincodeDelivery() {
                var pin = document.getElementById('pincode-input').value.trim();
                var resultEl = document.getElementById('pincode-result');
                var btn = document.getElementById('pincode-check-btn');

                if (!/^[1-9][0-9]{5}$/.test(pin)) {
                    resultEl.className = 'pincode-result error';
                    resultEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Please enter a valid 6-digit pincode.';
                    return;
                }

                // Show loading state
                btn.disabled = true;
                resultEl.className = 'pincode-result loading';
                resultEl.innerHTML = '<span class="pincode-spinner"></span> Checking availability...';

                // Use Indian Postal API (free, no key required)
                fetch('https://api.postalpincode.in/pincode/' + pin)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        btn.disabled = false;
                        if (!data || data[0].Status !== 'Success' || !data[0].PostOffice || data[0].PostOffice.length === 0) {
                            resultEl.className = 'pincode-result error';
                            resultEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Sorry, we could not find this pincode. Please check and try again.';
                            return;
                        }

                        var po      = data[0].PostOffice[0];
                        var city    = po.District || po.Name;
                        var state   = po.State;
                        var country = po.Country;

                        // Delivery day calculation based on pincode zone
                        var days = getDeliveryDays(pin, state);
                        var deliveryDate = getDeliveryDateStr(days);
                        var daysLabel = days === 1 ? 'Tomorrow' : 'in ' + days + ' days';

                        resultEl.className = 'pincode-result success';
                        resultEl.innerHTML =
                            '<div class="delivery-info">' +
                                '<div class="delivery-location">' +
                                    '<i class="fa-solid fa-location-dot" style="color:#c8a96e; margin-right:5px;"></i>' +
                                    city + ', ' + state +
                                '</div>' +
                                '<div class="delivery-date">' +
                                    '<i class="fa-solid fa-circle-check"></i>' +
                                    'Estimated Delivery: <strong>' + deliveryDate + '</strong> (' + daysLabel + ')' +
                                '</div>' +
                                '<div class="delivery-note">' +
                                    '<i class="fa-regular fa-clock" style="margin-right:4px;"></i>' +
                                    'Free delivery on orders above ₹1,500 &bull; Order before 6 PM for same-day dispatch' +
                                '</div>' +
                            '</div>';
                    })
                    .catch(function() {
                        btn.disabled = false;
                        // Fallback: use pincode zone without API
                        var days = getDeliveryDays(pin, '');
                        var deliveryDate = getDeliveryDateStr(days);
                        resultEl.className = 'pincode-result success';
                        resultEl.innerHTML =
                            '<div class="delivery-info">' +
                                '<div class="delivery-date">' +
                                    '<i class="fa-solid fa-circle-check"></i>' +
                                    'Estimated Delivery by <strong>' + deliveryDate + '</strong>' +
                                </div>' +
                                '<div class="delivery-note">Free delivery on orders above ₹1,500</div>' +
                            '</div>';
                    });
            }

            function getDeliveryDays(pin, state) {
                // Metro pincodes (faster delivery: 2 days)
                var metroStates = ['Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'Telangana', 'Gujarat', 'West Bengal'];
                var metroPrefixes = ['110', '400', '500', '600', '700', '380', '560'];

                var prefix3 = pin.substring(0, 3);
                var prefix2 = pin.substring(0, 2);

                // Metro cities prefix check
                if (metroPrefixes.indexOf(prefix3) !== -1) return 2;

                // Metro state check
                if (state && metroStates.indexOf(state) !== -1) return 3;

                // North-east and remote areas
                var remoteStates = ['Arunachal Pradesh', 'Nagaland', 'Manipur', 'Mizoram', 'Tripura', 'Meghalaya', 'Sikkim', 'Andaman and Nicobar Islands', 'Lakshadweep'];
                if (state && remoteStates.indexOf(state) !== -1) return 7;

                // Remote pincode prefixes (J&K, NE states)
                var remotePrefixes = ['19', '79', '79', '74', '83', '73', '79'];
                if (remotePrefixes.indexOf(prefix2) !== -1) return 6;

                // Default: Tier 2/3 cities
                return 4;
            }

            function getDeliveryDateStr(days) {
                var d = new Date();
                // Skip Sundays
                var added = 0;
                while (added < days) {
                    d.setDate(d.getDate() + 1);
                    if (d.getDay() !== 0) added++; // Skip Sunday
                }
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var days2 = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                return days2[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            }
            </script>


            <!-- Share product options -->
            <div style="margin-top: 15px; font-size: 0.85rem; color: var(--color-text-muted);">
                <strong>Share Scents:</strong> 
                <a href="#" style="margin-left: 10px;" title="Pinterest"><i class="fa-brands fa-pinterest"></i></a>
                <a href="#" style="margin-left: 10px;" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" style="margin-left: 10px;" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
            </div>

        </div>

    </div>

    <!-- Related Products -->
    <?php if (count($relatedProducts) > 0): ?>
        <section class="related-products">
            <div class="sections-title">
                <h2>Suggested Pairings</h2>
                <p>Complement your fragrance wardrobe with these highly matching scents.</p>
            </div>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="product-card">
                        <div class="product-card-image">
                            <a href="<?= BASE_URL ?>/product/<?= $related['id'] ?>">
                                <img src="<?= BASE_URL ?>/assets/images/<?= e($related['image_url']) ?>" alt="<?= e($related['name']) ?>">
                            </a>
                        </div>
                        <div class="product-card-info">
                            <div class="product-brand"><?= e($related['brand']) ?></div>
                            <h3 class="product-name"><a href="<?= BASE_URL ?>/product/<?= $related['id'] ?>"><?= e($related['name']) ?></a></h3>
                            <div class="product-price">
                                <span>₹<?= number_format($related['price'], 2) ?></span>
                            </div>
                        </div>
                        <div class="product-card-action">
                            <a href="<?= BASE_URL ?>/product/<?= $related['id'] ?>" class="btn btn-outline-gold" style="display: block;">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Reviews Section Anchor -->
    <span id="reviews-anchor"></span>
    <section class="reviews-section">
        <div class="reviews-grid">
            
            <!-- List Reviews -->
            <div class="reviews-list">
                <h3>Patron Feedback (<?= count($reviews) ?>)</h3>
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="reviewer-name"><?= e($rev['full_name']) ?></span>
                                <span class="review-date"><?= date('F d, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="review-stars" style="margin-bottom: 10px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $rev['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="review-body"><?= e($rev['review_text']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--color-text-muted); font-style: italic;">No reviews yet. Be the first to share your thoughts on this scent.</p>
                <?php endif; ?>
            </div>

            <!-- Submit Review Form -->
            <div class="review-form-container">
                <h3>Write a Review</h3>
                <?php if (isLoggedIn()): ?>
                    <form id="product-review-form" class="review-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        
                        <div class="form-group">
                            <label>Scent Rating</label>
                            <div class="stars-rating-select" id="star-selector">
                                <i class="fa-solid fa-star active" data-rating="1"></i>
                                <i class="fa-solid fa-star active" data-rating="2"></i>
                                <i class="fa-solid fa-star active" data-rating="3"></i>
                                <i class="fa-solid fa-star active" data-rating="4"></i>
                                <i class="fa-solid fa-star active" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="selected-rating" value="5">
                        </div>

                        <div class="form-group">
                            <label for="review_text">Your Scent Assessment</label>
                            <textarea name="review_text" id="review_text" rows="5" placeholder="Share your experience with the top, middle, and base notes projection..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold">Submit Review</button>
                    </form>
                <?php else: ?>
                    <div style="background-color: var(--color-white); border: 1px dashed var(--color-gold); padding: 30px; text-align: center;">
                        <p style="margin-bottom: 15px; font-size: 0.9rem;">You must be logged in to submit a rating.</p>
                        <a href="<?= BASE_URL ?>/account" onclick="event.preventDefault(); if (typeof openLoginModal === 'function') { openLoginModal(); } else { window.location.href = this.href; }" class="btn btn-black" style="font-size: 0.8rem; padding: 10px 20px;">Login / Register</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

</div>

<script>
// Image switching gallery
function switchImage(src, element) {
    document.getElementById('main-product-img').src = src;
    const thumbs = document.querySelectorAll('.details-thumbnails img');
    thumbs.forEach(t => t.classList.remove('active'));
    element.classList.add('active');
}

// Hover zoom logic
function zoomImage(e) {
    if (window.innerWidth <= 768) {
        return; // Disable hover zoom on mobile viewports
    }
    const container = document.getElementById('main-image-container');
    const img = document.getElementById('main-product-img');
    const rect = container.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    img.style.transformOrigin = `${(x / rect.width) * 100}% ${(y / rect.height) * 100}%`;
    img.style.transform = 'scale(1.8)';
}

function resetZoom() {
    if (window.innerWidth <= 768) {
        return;
    }
    const img = document.getElementById('main-product-img');
    img.style.transform = 'scale(1)';
    img.style.transformOrigin = 'center center';
}

// Quantity changing
function changeQty(amt) {
    const input = document.getElementById('detail-qty');
    let val = parseInt(input.value) + amt;
    const maxVal = parseInt(input.getAttribute('max'));
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    input.value = val;
}

// Actions wrappers
function triggerAddToCart(productId) {
    const qty = parseInt(document.getElementById('detail-qty').value);
    addToCart(productId, qty);
}

function triggerBuyNow(productId) {
    const qty = parseInt(document.getElementById('detail-qty').value);
    
    // Programmatically add to cart
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', qty);

    fetch(`${BASE_URL}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(data.cart_count);
            }
            // Go directly to the cart page
            window.location.href = `${BASE_URL}/cart`;
        } else {
            showToast('error', data.message);
        }
    });
}

// Star rating interactive selector
document.addEventListener('DOMContentLoaded', () => {
    const stars = document.querySelectorAll('#star-selector i');
    const ratingInput = document.getElementById('selected-rating');
    
    if (stars.length && ratingInput) {
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                ratingInput.value = rating;
                
                stars.forEach((s, idx) => {
                    if (idx < rating) {
                        s.className = 'fa-solid fa-star active';
                    } else {
                        s.className = 'fa-regular fa-star';
                    }
                });
            });
        });
    }

    // Handle AJAX review submission
    const reviewForm = document.getElementById('product-review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(reviewForm);
            
            fetch(`${BASE_URL}/api/reviews.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('success', data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(() => {
                showToast('error', 'Failed to submit review.');
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
