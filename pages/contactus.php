<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/settings_helper.php';

$pdo = getDB();
$ws  = getSiteSettings();

$phone        = $ws['phone_number']  ?? '+20 123 456 789';
$workingHours = $ws['working_hours'] ?? 'Sun - Thu: 9 AM - 6 PM';
$fbUrl        = $ws['facebook_url']  ?? '#';
$igUrl        = $ws['instagram_url'] ?? '#';
$mapsUrl      = $ws['google_maps_url']?? '';
$waNum        = $ws['whatsapp_number']?? '';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    if (checkRateLimit('contact_us', 5, 15)) {
        $err = 'You have exceeded the message limit. Please wait 15 minutes.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email']     ?? '');
        $msgText  = trim($_POST['message']   ?? '');
        $uid      = getCurrentUserId();

        if (strlen($fullName) < 2)
            $err = 'Please enter your name (at least 2 characters).';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $err = 'Please enter a valid email address.';
        elseif (strlen($msgText) < 10)
            $err = 'Message is too short (at least 10 characters).';
        else {
            $pdo->prepare("INSERT INTO contact_messages (user_id,full_name,email,message,is_notified) VALUES (?,?,?,?,0)")
                ->execute([$uid, $fullName, $email, $msgText]);
            logRateLimitAttempt('contact_us');
            $msg = '✅ Your message has been sent! We will get back to you soon.';
        }
    }
}

$csrf         = generateCsrfToken();
$prefillName  = $_SESSION['user_name']  ?? '';
$prefillEmail = $_SESSION['user_email'] ?? '';

$pageTitle       = 'Contact Us';
$pageDescription = 'Get in touch with Cairo Store for support and inquiries.';
require_once __DIR__ . '/../components/header.php';
?>
<?php include '../components/navbar.php'; ?>

<main id="main-content" role="main">
<section class="container py-5">

    <nav class="store-breadcrumb mb-4">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <span class="current">Contact Us</span>
    </nav>

    <div class="text-center mb-5">
        <h1 class="fw-bold">Contact Us</h1>
        <p class="lead">We Would Love To Hear From You</p>
    </div>

    <div class="row g-4">
        <!-- ── Contact Info ─────────────────────────── -->
        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <h2 class="h3 mb-4">📬 Contact Information</h2>
                <p>📍 Cairo, Egypt</p>
                <p>📞 <?= htmlspecialchars($phone) ?></p>
                <p>✉️ info@cairostore.com</p>
                <p>🕒 <?= htmlspecialchars($workingHours) ?></p>
                <?php if ($waNum): ?>
                <p>💬 <a href="https://wa.me/<?= preg_replace('/\D/','',$waNum) ?>" target="_blank">WhatsApp: <?= htmlspecialchars($waNum) ?></a></p>
                <?php endif; ?>
                <hr>
                <h3 class="h5 mb-2">Follow Us</h3>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($fbUrl && $fbUrl !== '#'): ?>
                    <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Facebook</a>
                    <?php endif; ?>
                    <?php if ($igUrl && $igUrl !== '#'): ?>
                    <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" class="btn btn-sm btn-outline-danger">Instagram</a>
                    <?php endif; ?>
                    <?php if ($mapsUrl): ?>
                    <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" class="btn btn-sm btn-outline-success">📍 Map</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Contact Form ──────────────────────────── -->
        <div class="col-lg-7">
            <div class="card p-4">
                <h2 class="h3 mb-4">💌 Send Message</h2>

                <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="send_message" value="1">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf) ?>">

                    <div class="float-group">
                        <input type="text" id="contactName" name="full_name" placeholder=" " required
                               value="<?= htmlspecialchars($prefillName) ?>"
                               <?= $prefillName ? 'readonly style="opacity:.6;cursor:not-allowed;"' : '' ?>
                               autocomplete="name">
                        <label>Full Name</label>
                    </div>
                    <div class="float-group">
                        <input type="email" id="contactEmail" name="email" placeholder=" " required
                               value="<?= htmlspecialchars($prefillEmail) ?>"
                               <?= $prefillEmail ? 'readonly style="opacity:.6;cursor:not-allowed;"' : '' ?>
                               autocomplete="email">
                        <label>Email Address</label>
                    </div>
                    <div class="float-group">
                        <textarea id="contactMessage" name="message" rows="5" placeholder=" " required></textarea>
                        <label>Your Message</label>
                    </div>
                    <button id="contactSendBtn" type="submit" class="btn btn-success w-100 btn-disabled-faded"
                            disabled aria-disabled="true">Send Message</button>
                </form>
            </div>
        </div>
    </div>

</section>
</main>

<?php include '../components/footer.php'; ?>
<script>
(function () {
    const msgArea = document.getElementById('contactMessage');
    const sendBtn = document.getElementById('contactSendBtn');
    if (!msgArea || !sendBtn) return;
    function checkContact() {
        const ok = msgArea.value.trim().length >= 10;
        updateButtonState(sendBtn, ok);
    }
    msgArea.addEventListener('input', checkContact);
    checkContact();
})();
</script>
</body>
</html>
