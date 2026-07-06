<?php
/**
 * Reset Password Page
 * Route: /reset-password?token=xxxxx
 */
require_once __DIR__ . '/../config/db.php';

$db        = getDB();
$token     = trim($_GET['token'] ?? '');
$pageTitle = "Reset Password";
$tokenValid = false;
$tokenEmail = '';
$errorMsg   = '';
$successMsg = '';

// ── Handle POST: Update Password ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken   = trim($_POST['token'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($postToken) || empty($newPassword) || empty($confirmPass)) {
        $errorMsg = 'All fields are required.';
    } elseif ($newPassword !== $confirmPass) {
        $errorMsg = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $errorMsg = 'Password must be at least 8 characters long.';
    } else {
        // Verify token is still valid
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$postToken]);
        $resetRow = $stmt->fetch();

        if (!$resetRow) {
            $errorMsg = 'This reset link has expired or is invalid. Please request a new one.';
        } else {
            // Hash and update password
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd    = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->execute([$hashed, $resetRow['email']]);

            // Delete used token (single-use)
            $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$resetRow['email']]);

            $successMsg = 'success';
        }
    }
    $token = $postToken; // retain for form
}

// ── Validate Token (GET or after failed POST) ─────────────────────────────
if ($successMsg !== 'success' && !empty($token) && empty($errorMsg)) {
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRow = $stmt->fetch();

    if ($resetRow) {
        $tokenValid = true;
        $tokenEmail = $resetRow['email'];
    } else {
        $errorMsg = 'This reset link has expired or is invalid. Please request a new one.';
    }
} elseif (!empty($token) && empty($errorMsg) && $successMsg !== 'success') {
    $tokenValid = true;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
.reset-page-wrapper {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1209 100%);
}
.reset-card {
    background: #fff;
    width: 100%;
    max-width: 460px;
    padding: 48px 44px;
    position: relative;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.reset-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #c8a96e, #e8d5a3, #c8a96e);
}
.reset-logo {
    text-align: center;
    margin-bottom: 28px;
}
.reset-logo img {
    height: 50px;
    object-fit: contain;
}
.reset-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
    text-align: center;
}
.reset-subtitle {
    font-size: 0.85rem;
    color: #888;
    text-align: center;
    margin-bottom: 30px;
    line-height: 1.6;
}
.reset-form-group {
    margin-bottom: 18px;
}
.reset-form-group label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #555;
    margin-bottom: 7px;
}
.reset-input-wrap {
    position: relative;
}
.reset-form-group input[type="password"],
.reset-form-group input[type="text"] {
    width: 100%;
    border: 1px solid #ddd;
    padding: 12px 44px 12px 14px;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fafafa;
    color: #1a1a1a;
    border-radius: 3px;
}
.reset-form-group input:focus {
    border-color: #c8a96e;
    box-shadow: 0 0 0 3px rgba(200,169,110,0.12);
    background: #fff;
}
.toggle-pw {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #aaa;
    font-size: 0.9rem;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
}
.toggle-pw:hover { color: #c8a96e; }

/* Password Strength */
.strength-bar-wrap {
    margin-top: 8px;
    display: flex;
    gap: 4px;
}
.strength-seg {
    flex: 1;
    height: 4px;
    background: #eee;
    border-radius: 2px;
    transition: background 0.3s;
}
.strength-label {
    font-size: 0.72rem;
    margin-top: 5px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.strength-label.weak   { color: #e53935; }
.strength-label.fair   { color: #fb8c00; }
.strength-label.good   { color: #43a047; }
.strength-label.strong { color: #2e7d32; }

/* Alerts */
.reset-alert {
    padding: 12px 16px;
    border-radius: 3px;
    font-size: 0.85rem;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}
.reset-alert.error   { background: #fff5f5; border: 1px solid #fca5a5; color: #b91c1c; }
.reset-alert.success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }

/* Submit Button */
.reset-btn {
    width: 100%;
    padding: 14px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    border-radius: 3px;
    margin-top: 8px;
}
.reset-btn:hover   { background: #c8a96e; color: #000; }
.reset-btn:active  { transform: scale(0.99); }
.reset-btn.gold    { background: #c8a96e; color: #000; }
.reset-btn.gold:hover { background: #b8996e; }

.reset-back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    font-size: 0.82rem;
    color: #888;
    text-decoration: none;
    transition: color 0.2s;
}
.reset-back-link:hover { color: #c8a96e; }

/* Expired / Success States */
.reset-state-icon {
    font-size: 3rem;
    text-align: center;
    margin-bottom: 16px;
}
.reset-expired { text-align: center; }
.reset-success-screen { text-align: center; }
.reset-success-screen .big-check {
    width: 70px; height: 70px;
    background: #d1fae5;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: #166534;
}
</style>

<div class="reset-page-wrapper">
    <div class="reset-card">

        <!-- Logo -->
        <div class="reset-logo">
            <a href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/assets/images/LOGO.png" alt="Elixir & Co.">
            </a>
        </div>

        <?php if ($successMsg === 'success'): ?>
        <!-- ── Success State ── -->
        <div class="reset-success-screen">
            <div class="big-check"><i class="fa-solid fa-check"></i></div>
            <h2 class="reset-title">Password Updated!</h2>
            <p class="reset-subtitle">
                Your password has been reset successfully.<br>
                You can now log in with your new password.
            </p>
            <a href="<?= BASE_URL ?>/" class="reset-btn gold" style="display:block; text-decoration:none; text-align:center; padding:14px;">
                <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right:8px;"></i>Go to Login
            </a>
        </div>

        <?php elseif (!empty($errorMsg) && empty($token)): ?>
        <!-- ── No Token / Expired ── -->
        <div class="reset-expired">
            <div class="reset-state-icon">⏰</div>
            <h2 class="reset-title">Link Expired</h2>
            <p class="reset-subtitle"><?= htmlspecialchars($errorMsg) ?></p>
            <a href="<?= BASE_URL ?>/" class="reset-btn" style="display:block; text-decoration:none; text-align:center; padding:14px;">
                Request New Reset Link
            </a>
        </div>

        <?php else: ?>
        <!-- ── Reset Password Form ── -->
        <h2 class="reset-title">Reset Password</h2>
        <p class="reset-subtitle">Create a new secure password for your account.</p>

        <?php if (!empty($errorMsg)): ?>
            <div class="reset-alert error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/reset-password" id="reset-pw-form" novalidate>
            <?php if (!empty($token) && empty($errorMsg)): ?>
                <!-- Pre-filled Token from URL -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <?php else: ?>
                <!-- OTP Input Field -->
                <div class="reset-form-group">
                    <label for="otp_code">OTP Code</label>
                    <div class="reset-input-wrap">
                        <input type="text" id="otp_code" name="token" maxlength="6" required
                               placeholder="Enter 6-digit OTP" value="<?= htmlspecialchars($token) ?>"
                               style="letter-spacing: 4px; text-align: center; font-size: 1.2rem; font-weight: bold;">
                    </div>
                </div>
            <?php endif; ?>

            <!-- New Password -->
            <div class="reset-form-group">
                <label for="new_password">New Password</label>
                <div class="reset-input-wrap">
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Minimum 8 characters" required autocomplete="new-password"
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="toggleVisibility('new_password', this)" tabindex="-1">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <!-- Strength Bar -->
                <div class="strength-bar-wrap" id="strength-bar">
                    <div class="strength-seg" id="seg1"></div>
                    <div class="strength-seg" id="seg2"></div>
                    <div class="strength-seg" id="seg3"></div>
                    <div class="strength-seg" id="seg4"></div>
                </div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <!-- Confirm Password -->
            <div class="reset-form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="reset-input-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter your new password" required autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="toggleVisibility('confirm_password', this)" tabindex="-1">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div id="match-msg" style="font-size:0.75rem; margin-top:5px;"></div>
            </div>

            <button type="submit" class="reset-btn gold" id="reset-submit-btn">
                <i class="fa-solid fa-lock" style="margin-right:8px;"></i>Reset Password
            </button>
        </form>

        <a href="<?= BASE_URL ?>/" class="reset-back-link">
            <i class="fa-solid fa-arrow-left" style="margin-right:5px;"></i>Back to Login
        </a>
        <?php endif; ?>

    </div>
</div>

<script>
// Toggle password visibility
function toggleVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

// Password strength checker
function checkStrength(pw) {
    var segs   = [document.getElementById('seg1'), document.getElementById('seg2'),
                  document.getElementById('seg3'), document.getElementById('seg4')];
    var label  = document.getElementById('strength-label');

    var score = 0;
    if (pw.length >= 8)  score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    var colors = ['#e53935', '#fb8c00', '#43a047', '#2e7d32'];
    var labels = ['Weak', 'Fair', 'Good', 'Strong'];
    var classes= ['weak', 'fair', 'good', 'strong'];

    segs.forEach(function(s, i) {
        s.style.background = i < score ? colors[score - 1] : '#eee';
    });

    if (pw.length === 0) {
        label.textContent = '';
        label.className   = 'strength-label';
    } else {
        label.textContent = labels[score - 1] || 'Weak';
        label.className   = 'strength-label ' + (classes[score - 1] || 'weak');
    }

    // Also check match
    checkMatch();
}

// Confirm password match indicator
function checkMatch() {
    var pw   = document.getElementById('new_password').value;
    var cpw  = document.getElementById('confirm_password').value;
    var msg  = document.getElementById('match-msg');

    if (!cpw) { msg.textContent = ''; return; }
    if (pw === cpw) {
        msg.innerHTML = '<span style="color:#2e7d32;"><i class="fa-solid fa-check"></i> Passwords match</span>';
    } else {
        msg.innerHTML = '<span style="color:#e53935;"><i class="fa-solid fa-xmark"></i> Passwords do not match</span>';
    }
}

document.getElementById('confirm_password') && document.getElementById('confirm_password').addEventListener('input', checkMatch);

// Form validation before submit
document.getElementById('reset-pw-form') && document.getElementById('reset-pw-form').addEventListener('submit', function(e) {
    var pw  = document.getElementById('new_password').value;
    var cpw = document.getElementById('confirm_password').value;

    if (pw.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return;
    }
    if (pw !== cpw) {
        e.preventDefault();
        alert('Passwords do not match.');
        return;
    }

    var btn = document.getElementById('reset-submit-btn');
    btn.textContent = 'Updating...';
    btn.disabled = true;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
