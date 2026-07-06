<?php
$pageTitle = "Luxury Fragrances";
$metaDesc = "Discover Elixir & Co. premium, long-lasting fragrances. Crafted to leave a lasting impression.";
include __DIR__ . '/../includes/header.php';
?>

<div class="elixir-home">
    <!-- Hero Slideshow Section -->
    <section class="elixir-hero-slider" id="heroSlider">

        <!-- Slide 1 -->
        <div class="hero-slide active" style="background-image: url('<?= BASE_URL ?>/assets/images/black_orchid.jpg');">
            <div class="hero-slide-overlay"></div>
            <div class="hero-slide-content">
                <div class="elixir-hero-brand">Elixir &amp; Co.</div>
                <div class="elixir-hero-separator"><i class="fa-solid fa-star-of-life"></i></div>
                <h1 class="elixir-hero-tagline">Crafted to leave a<br>lasting impression</h1>
                <p class="elixir-hero-desc">Luxury fragrances inspired by elegance, nature, and timeless sophistication.</p>
                <div class="elixir-hero-ctas">
                    <a href="<?= BASE_URL ?>/search" class="elixir-btn-gold">Shop Collection</a>
                    <a href="<?= BASE_URL ?>/about" class="elixir-btn-outline">Our Story</a>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/assets/images/creed_aventus.jpg');">
            <div class="hero-slide-overlay"></div>
            <div class="hero-slide-content">
                <div class="elixir-hero-brand">Elixir &amp; Co.</div>
                <div class="elixir-hero-separator"><i class="fa-solid fa-star-of-life"></i></div>
                <h1 class="elixir-hero-tagline">The art of<br>fine fragrance</h1>
                <p class="elixir-hero-desc">Amber Noir — a bold statement of power, warmth, and undeniable confidence.</p>
                <div class="elixir-hero-ctas">
                    <a href="<?= BASE_URL ?>/product/2" class="elixir-btn-gold">Shop Amber Noir</a>
                    <a href="<?= BASE_URL ?>/bestsellers" class="elixir-btn-outline">Bestsellers</a>
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/assets/images/blog_craftsmanship.jpg');">
            <div class="hero-slide-overlay"></div>
            <div class="hero-slide-content">
                <div class="elixir-hero-brand">Elixir &amp; Co.</div>
                <div class="elixir-hero-separator"><i class="fa-solid fa-star-of-life"></i></div>
                <h1 class="elixir-hero-tagline">Scents that tell<br>your story</h1>
                <p class="elixir-hero-desc">Rose Elixir — soft floral luxury with notes of Rose, Jasmine, and White Musk.</p>
                <div class="elixir-hero-ctas">
                    <a href="<?= BASE_URL ?>/product/1" class="elixir-btn-gold">Shop Rose Elixir</a>
                    <a href="<?= BASE_URL ?>/search" class="elixir-btn-outline">All Fragrances</a>
                </div>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button class="hero-arrow hero-prev" id="heroPrev" aria-label="Previous slide">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="hero-arrow hero-next" id="heroNext" aria-label="Next slide">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <!-- Dots -->
        <div class="hero-dots">
            <button class="hero-dot active" data-slide="0"></button>
            <button class="hero-dot" data-slide="1"></button>
            <button class="hero-dot" data-slide="2"></button>
        </div>
    </section>

    <!-- Slideshow JavaScript -->
    <script>
    (function() {
        var slides = document.querySelectorAll('.hero-slide');
        var dots   = document.querySelectorAll('.hero-dot');
        var prev   = document.getElementById('heroPrev');
        var next   = document.getElementById('heroNext');
        var current = 0;
        var timer;

        function goTo(n) {
            slides[current].classList.remove('active');
            dots[current].classList.remove('active');
            current = (n + slides.length) % slides.length;
            slides[current].classList.add('active');
            dots[current].classList.add('active');
        }

        function autoPlay() {
            timer = setInterval(function() { goTo(current + 1); }, 5000);
        }

        function resetTimer() { clearInterval(timer); autoPlay(); }

        if (prev) prev.addEventListener('click', function() { goTo(current - 1); resetTimer(); });
        if (next) next.addEventListener('click', function() { goTo(current + 1); resetTimer(); });

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                goTo(parseInt(this.dataset.slide));
                resetTimer();
            });
        });

        autoPlay();
    })();
    </script>

    <!-- Our Collection Section -->
    <section class="elixir-collection">
        <div class="header-container">
            <div class="elixir-section-subtitle">Our Collection</div>
            <h2 class="elixir-section-title">Exceptional Scents. Unforgettable You.</h2>
            <div class="elixir-title-decoration">
                <i class="fa-solid fa-star-of-life"></i>
            </div>

            <div class="elixir-collection-grid">
                <!-- Card 1: Rose Elixir -->
                <div class="elixir-collection-card">
                    <div class="elixir-card-img-side">
                        <img src="<?= BASE_URL ?>/assets/images/coco_mademoiselle.jpg" alt="Rose Elixir">
                    </div>
                    <div class="elixir-card-info-side">
                        <div>
                            <h3 class="elixir-card-name">Rose Elixir</h3>
                            <p class="elixir-card-tag">Soft floral luxury</p>
                            <p class="elixir-card-desc">Rose, Jasmine, White Musk</p>
                        </div>
                        <div>
                            <div class="elixir-card-size">50 ml | 100 ml</div>
                            <a href="<?= BASE_URL ?>/product/1" class="elixir-btn-card">Shop Now</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Amber Noir (Dark Theme) -->
                <div class="elixir-collection-card dark-theme-card">
                    <div class="elixir-card-img-side">
                        <img src="<?= BASE_URL ?>/assets/images/creed_aventus.jpg" alt="Amber Noir">
                    </div>
                    <div class="elixir-card-info-side">
                        <div>
                            <h3 class="elixir-card-name">Amber Noir</h3>
                            <p class="elixir-card-tag">Warm & sophisticated</p>
                            <p class="elixir-card-desc">Amber, Vanilla, Sandalwood</p>
                        </div>
                        <div>
                            <div class="elixir-card-size">50 ml | 100 ml</div>
                            <a href="<?= BASE_URL ?>/product/2" class="elixir-btn-card">Shop Now</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Citrus Essence -->
                <div class="elixir-collection-card">
                    <div class="elixir-card-img-side">
                        <img src="<?= BASE_URL ?>/assets/images/acqua_di_gio.jpg" alt="Citrus Essence">
                    </div>
                    <div class="elixir-card-info-side">
                        <div>
                            <h3 class="elixir-card-name">Citrus Essence</h3>
                            <p class="elixir-card-tag">Fresh & vibrant</p>
                            <p class="elixir-card-desc">Bergamot, Lemon, Musk</p>
                        </div>
                        <div>
                            <div class="elixir-card-size">50 ml | 100 ml</div>
                            <a href="<?= BASE_URL ?>/product/3" class="elixir-btn-card">Shop Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="elixir-story-section">
        <div class="header-container elixir-story-grid">
            <div class="elixir-story-img-wrapper">
                <img src="<?= BASE_URL ?>/assets/images/blog_craftsmanship.jpg" alt="The Art of Fine Fragrance">
            </div>
            <div class="elixir-story-content">
                <div class="elixir-story-subtitle">Our Story</div>
                <h2 class="elixir-story-title">The Art of Fine Fragrance</h2>
                <p class="elixir-story-desc">At Elixir & Co., every perfume is a celebration of artistry and passion. We carefully select the finest ingredients from around the world to craft fragrances that evoke emotions, tell stories, and leave a lasting impression.</p>
                <a href="<?= BASE_URL ?>/about" class="elixir-btn-gold">Discover Our Story</a>
            </div>
        </div>
    </section>

    <!-- Features Bar -->
    <section class="elixir-features-bar">
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

    <!-- Testimonials Section -->
    <section class="elixir-testimonials-section">
        <div class="header-container elixir-testimonials-container">
            <h2 class="elixir-section-title" style="color: #ffffff; margin-bottom: 50px;">What Our Customers Say</h2>
            
            <div class="elixir-testimonials-grid">
                <div class="elixir-testimonial-card">
                    <div class="elixir-testimonial-stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="elixir-testimonial-text">"Absolutely in love with Elixir & Co. The fragrance is elegant, long-lasting and truly feels premium."</p>
                    <div class="elixir-testimonial-author">— Aishwarya R.</div>
                </div>

                <div class="elixir-testimonial-card">
                    <div class="elixir-testimonial-stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="elixir-testimonial-text">"From the packaging to the scent, everything is perfect. My new go-to perfume brand!"</p>
                    <div class="elixir-testimonial-author">— Rahul M.</div>
                </div>
            </div>

            <!-- Navigation Arrows -->
            <button class="elixir-carousel-arrow elixir-arrow-prev" aria-label="Previous testimonial">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button class="elixir-carousel-arrow elixir-arrow-next" aria-label="Next testimonial">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
