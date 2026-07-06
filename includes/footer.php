</main>

<footer class="main-footer">
    <div class="footer-top-newsletter">
        <div class="footer-container">
            <div class="newsletter-content">
                <h2>Join the Inner Circle</h2>
                <p>Subscribe to receive exclusive access to private sales, new collections, and fragrance styling tips.</p>
            </div>
            <form id="newsletter-form" class="newsletter-form">
                <input type="email" id="newsletter-email" placeholder="Enter your email address" required>
                <button type="submit" class="btn-gold">Subscribe</button>
            </form>
            <div id="newsletter-message" class="newsletter-response" style="display: none;"></div>
        </div>
    </div>

    <div class="footer-middle-links">
        <div class="footer-container footer-grid">
            <!-- Brand Info Column -->
            <div class="footer-col brand-info">
                <a href="<?= BASE_URL ?>/" class="footer-logo">
                    <img src="<?= BASE_URL ?>/assets/images/logo.jpeg" alt="Elixir & Co." style="max-height: 50px !important; width: auto !important; object-fit: contain !important; margin-bottom: 20px !important; display: block !important; visibility: visible !important; opacity: 1 !important;">
                </a>
                <p class="brand-pitch">Curating high-end luxury fragrances for men and women. Bringing you authentic signature scents designed for timeless beauty, elegance, and supreme confidence.</p>
                <div class="footer-socials">
                    <a href="#" target="_blank" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
                    <a href="https://wa.me/919071233343" target="_blank" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Shop Categories Column -->
            <div class="footer-col">
                <h4>Collections</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/men">Men's Fragrances</a></li>
                    <li><a href="<?= BASE_URL ?>/women">Women's Fragrances</a></li>
                </ul>
            </div>

            <!-- Information Column -->
            <div class="footer-col">
                <h4>Services & Info</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/about">Our Story</a></li>
                    <li><a href="<?= BASE_URL ?>/order-tracking">Track Order</a></li>
                    <li><a href="<?= BASE_URL ?>/contact">Contact Us</a></li>
                    <li><a href="<?= BASE_URL ?>/privacy">Privacy Policy</a></li>
                    <li><a href="<?= BASE_URL ?>/terms">Terms & Conditions</a></li>
                    <li><a href="<?= BASE_URL ?>/refund">Refund Policy</a></li>
                </ul>
            </div>

            <!-- Contact/Address Column -->
            <div class="footer-col contact-info">
                <h4>Boutique Store</h4>
                <p><i class="fa-solid fa-location-dot"></i> Shakthi garden kalyan nagar<br>Nagarbhavi Main Road</p>
                <p><i class="fa-solid fa-phone"></i> +91 9071233343</p>
                <p><i class="fa-solid fa-envelope"></i> itsmyshopshahid838@gmail.com</p>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-container">
            <p class="copyright">&copy; <?= date('Y') ?> Elixir & Co. All rights reserved. Designed for premium elegance.</p>
            <p>Designed & Developed by <a href="https://talvyyo.com" target="_blank">Talvyyo</a></p>
        </div>
    </div>
</footer>

<!-- App JS -->
<script>
    // Make BASE_URL available in JS
    const BASE_URL = '<?= BASE_URL ?>';
    const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
