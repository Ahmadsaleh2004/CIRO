<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();
$ws  = [];
try { $ws = $pdo->query("SELECT * FROM website_settings LIMIT 1")->fetch() ?: []; } catch (Exception $e) {}

$phone        = $ws['phone_number']  ?? '+20 123 456 789';
$workingHours = $ws['working_hours'] ?? 'Sun - Thu: 9 AM - 6 PM';
$fbUrl        = $ws['facebook_url']  ?? '#';
$igUrl        = $ws['instagram_url'] ?? '#';
$mapsUrl      = $ws['google_maps_url']?? '';
$waNum        = $ws['whatsapp_number']?? '';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $msgText  = trim($_POST['message']   ?? '');
    $uid      = getCurrentUserId();

    if (strlen($fullName) < 2)
        $err = 'يرجى إدخال الاسم (حرفان على الأقل).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $err = 'يرجى إدخال إيميل صحيح.';
    elseif (strlen($msgText) < 10)
        $err = 'الرسالة قصيرة جداً (10 أحرف على الأقل).';
    else {
        $pdo->prepare("INSERT INTO contact_messages (user_id,full_name,email,message,is_notified) VALUES (?,?,?,?,0)")
            ->execute([$uid, $fullName, $email, $msgText]);
        $msg = '✅ تم إرسال رسالتك! سنرد عليك قريباً.';
    }
}

$csrf         = generateCsrfToken();
$prefillName  = $_SESSION['user_name']  ?? '';
$prefillEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Cairo Store</title>
    <meta name="description" content="Get in touch with Cairo Store for support and inquiries.">
    <meta name="robots" content="index, follow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
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
                        <input type="text" name="full_name" placeholder=" " required
                               value="<?= htmlspecialchars($prefillName) ?>"
                               autocomplete="name">
                        <label>Full Name</label>
                    </div>
                    <div class="float-group">
                        <input type="email" name="email" placeholder=" " required
                               value="<?= htmlspecialchars($prefillEmail) ?>"
                               autocomplete="email">
                        <label>Email Address</label>
                    </div>
                    <div class="float-group">
                        <textarea name="message" rows="5" placeholder=" " required></textarea>
                        <label>Your Message</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>

</section>
</main>

<?php include '../components/footer.php'; ?>
</body>
</html>
