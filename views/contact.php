<?php
$pageTitle = "Contact Us";
$metaDesc = "We're here to help you with anything you need. Get in touch with us and we'll get back to you as soon as possible.";
require_once __DIR__ . '/../config/db.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Security verification failed. Please refresh and try again.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($phone) || empty($message)) {
            setFlashMessage('error', 'All fields are required to submit a query.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Please enter a valid email address.');
        } else {
            // Concatenate subject into the message to fit database schema
            $fullMessage = "Subject: " . $subject . "\n\n" . $message;

            // Save contact message
            $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $fullMessage]);

            setFlashMessage('success', 'Thank you for reaching out! A brand manager will address your request within 24 hours.');
            header("Location: " . BASE_URL . "/contact");
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="elixir-contact-page">
    <!-- Header/Banner Section -->
    <section class="elixir-contact-hero">
        <div class="header-container hero-grid">
            <div class="elixir-hero-content">
                <h1 class="elixir-hero-brand" style="font-size: 3.5rem; margin-bottom: 20px;">Contact Us</h1>
                <div class="elixir-hero-separator">
                    <i class="fa-solid fa-star-of-life"></i>
                </div>
                <p class="elixir-hero-desc" style="max-width: 500px; color: #b0b0b0;">We're here to help you with anything you need. Get in touch with us and we'll get back to you as soon as possible.</p>
            </div>
            <div class="elixir-hero-image-wrapper" style="max-height: 280px; overflow: hidden;">
                <img src="<?= BASE_URL ?>/assets/images/black_orchid.jpg" alt="Contact Us Feature" style="height: 280px; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="elixir-contact-body-section">
        <div class="header-container elixir-contact-grid">
            <!-- Left Column: Get In Touch -->
            <div class="elixir-contact-info-col">
                <h2 class="elixir-contact-sec-title">Get In Touch</h2>
                <div class="elixir-sec-separator"></div>

                <ul class="elixir-contact-list">
                    <li>
                        <div class="icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="details">
                            <span class="label">Phone</span>
                            <span class="value">+91 98765 43210</span>
                        </div>
                    </li>
                    <li>
                        <div class="icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="details">
                            <span class="label">Email</span>
                            <span class="value">hello@elixircandco.com</span>
                        </div>
                    </li>
                    <li>
                        <div class="icon"><i class="fa-brands fa-whatsapp"></i></div>
                        <div class="details">
                            <span class="label">WhatsApp</span>
                            <span class="value">+91 98765 43210</span>
                        </div>
                    </li>
                    <li>
                        <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="details">
                            <span class="label">Address</span>
                            <span class="value">
                                Elixir & Co.<br>
                                Khasra No. 1234, Sector 37,<br>
                                Gurugram, Haryana 122001,<br>
                                India
                            </span>
                        </div>
                    </li>
                </ul>

                <div class="elixir-business-hours">
                    <h3 class="hours-title">Business Hours</h3>
                    <p class="hours-detail">Monday – Saturday: 10:00 AM – 8:00 PM</p>
                    <p class="hours-detail">Sunday: 11:00 AM – 6:00 PM</p>
                </div>
            </div>

            <!-- Right Column: Send Us A Message -->
            <div class="elixir-contact-form-col">
                <h2 class="elixir-contact-sec-title">Send Us A Message</h2>
                <div class="elixir-sec-separator"></div>

                <form action="<?= BASE_URL ?>/contact" method="POST" class="elixir-contact-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="contact_submit" value="1">

                    <div class="form-group">
                        <input type="text" name="name" required placeholder="Your Name">
                    </div>

                    <div class="form-group">
                        <input type="email" name="email" required placeholder="Email Address">
                    </div>

                    <div class="form-group">
                        <input type="text" name="phone" required placeholder="Phone Number">
                    </div>

                    <div class="form-group select-wrapper">
                        <select name="subject" required>
                            <option value="" disabled selected>Subject</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Order Support">Order Support</option>
                            <option value="Product Feedback">Product Feedback</option>
                            <option value="Business Partnership">Business Partnership</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <textarea name="message" rows="6" required placeholder="Your Message"></textarea>
                    </div>

                    <button type="submit" class="elixir-btn-submit">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Support Icons Section -->
    <section class="elixir-support-bar">
        <div class="header-container elixir-support-flex">
            <div class="elixir-support-item">
                <div class="icon"><i class="fa-regular fa-clock"></i></div>
                <div class="text">
                    <h4>Fast Response</h4>
                    <p>We aim to reply within 24 hours.</p>
                </div>
            </div>
            <div class="elixir-support-item">
                <div class="icon"><i class="fa-regular fa-face-smile"></i></div>
                <div class="text">
                    <h4>Customer First</h4>
                    <p>Your satisfaction is our priority.</p>
                </div>
            </div>
            <div class="elixir-support-item">
                <div class="icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
                <div class="text">
                    <h4>Easy Returns</h4>
                    <p>Hassle-free returns within 7 days.</p>
                </div>
            </div>
            <div class="elixir-support-item">
                <div class="icon"><i class="fa-solid fa-lock"></i></div>
                <div class="text">
                    <h4>Secure Support</h4>
                    <p>Your information is always safe with us.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
