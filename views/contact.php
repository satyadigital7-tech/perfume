<?php
$pageTitle = "Contact Elixir & Co.";
$metaDesc = "Reach out to Elixir & Co. for order inquiries, fragrance selections, or private consultation requests.";
require_once __DIR__ . '/../config/db.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Security verification failed. Please refresh and try again.');
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $message = trim($_POST['message']);

        if (empty($name) || empty($email) || empty($phone) || empty($message)) {
            setFlashMessage('error', 'All fields are required to submit a query.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Please enter a valid email address.');
        } else {
            // Save contact message
            $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $message]);

            setFlashMessage('success', 'Thank you for reaching out. A fragrance concierge will contact you within 24 hours.');
            header("Location: " . BASE_URL . "/contact");
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="header-container">
    <div class="contact-layout">
        
        <!-- Contact Information Panel -->
        <div class="contact-info-panel">
            <div>
                <h3>Connect With Us</h3>
                <p style="font-size: 0.85rem; color: #CCCCCC; margin-bottom: 30px;">For product inquiries, custom ordering requests, or feedback, our client relationship team is at your disposal.</p>
                
                <ul class="contact-details-list">
                    <li>
                        <i class="fa-solid fa-hotel"></i>
                        <div>
                            <h4>Boutique Location</h4>
                            <p>Shakthi garden kalyan nagar, Nagarbhavi Main Road</p>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-headset"></i>
                        <div>
                            <h4>Client Support</h4>
                            <p>+91 9071233343</p>
                            <p>Mon - Sat: 9:00 AM - 7:00 PM IST</p>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-signature"></i>
                        <div>
                            <h4>Electronic Inquiries</h4>
                            <p>itsmyshopshahid838@gmail.com</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Google Map Embed -->
            <div class="contact-map" style="margin-top: 40px; height: 250px; border: 1px solid var(--color-medium-gray); border-radius: 4px; overflow: hidden;">
                <iframe 
                    src="https://maps.google.com/maps?q=12.9645,77.5097&z=15&output=embed" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>

        <!-- Contact Submission Form -->
        <div class="contact-form-panel">
            <h3>Send a Message</h3>
            <p>Fill out the credentials below. A brand manager will address your request.</p>
            
            <form action="<?= BASE_URL ?>/contact" method="POST" style="margin-top: 25px;">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label for="contact-name">Your Full Name <span style="color:var(--color-error)">*</span></label>
                    <input type="text" id="contact-name" name="name" required placeholder="Johnathan Doe">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact-email">Email Address <span style="color:var(--color-error)">*</span></label>
                        <input type="email" id="contact-email" name="email" required placeholder="name@domain.com">
                    </div>
                    <div class="form-group">
                        <label for="contact-phone">Phone Number <span style="color:var(--color-error)">*</span></label>
                        <input type="text" id="contact-phone" name="phone" required placeholder="+91 90712 33343">
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact-msg">Message Content <span style="color:var(--color-error)">*</span></label>
                    <textarea id="contact-msg" name="message" rows="5" required placeholder="Outline your inquiry in detail..."></textarea>
                </div>

                <button type="submit" name="contact_submit" class="btn btn-gold" style="width: 100%;">Transmit Message</button>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
