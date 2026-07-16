<?php
/**
 * handlers/auth_handler.php
 * معالج طلبات المصادقة (Login / Register / Logout / Forgot)
 * كل الطلبات POST عبر fetch() من auth.js
 */

require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

// GET logout مباشر (رابط زر الخروج)
if (($_GET['action'] ?? '') === 'logout_get') {
    logout();
}

$action = $_POST['action'] ?? '';

function respond(bool $ok, string $msg, array $extra = []): void {
    // أرجع التوكن الجديد دائماً لمزامنة الـ DOM
    $extra['csrf_token'] = generateCsrfToken();
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

switch ($action) {

    // ════════════════════════════════════
    case 'login':
    // ════════════════════════════════════
        verifyCsrfToken($_POST['csrf_token'] ?? '');

        $email = trim(strtolower($_POST['email']    ?? ''));
        $pass  = $_POST['password'] ?? '';

        if (!$email || !$pass) respond(false, 'Please enter your email and password.');

        if (isRateLimited($email)) {
            respond(false, 'Too many attempts. Try again in ' . getRateLimitMinutes($email) . ' minute(s).');
        }

        $pdo = getDB();

        // تحقق من admins أولاً
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            logLoginAttempt($email, true);
            session_regenerate_id(true);
            // عزل جلسة الأدمن عن المستخدم العادي
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            
            $_SESSION['admin_id']    = (int)$admin['id'];
            $_SESSION['admin_name']  = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role']  = $admin['role'];
            $_SESSION['last_active'] = time();
            loadAdminPermissions((int)$admin['id']);
            respond(true, 'Welcome, ' . $admin['full_name'], [
                'redirect' => '/Task(1)/admin/support.php',
                'type'     => 'admin',
            ]);
        }

        // تحقق من users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            logLoginAttempt($email, true);

            // ── فحص حظر المستخدم (3 strikes) ──────────────────
            $strikeStmt = $pdo->prepare("SELECT COUNT(*) FROM user_strikes WHERE user_id = ?");
            $strikeStmt->execute([$user['id']]);
            if ((int)$strikeStmt->fetchColumn() >= 3) {
                respond(false, 'Your account has been suspended due to multiple violations. Please contact support.');
            }

            session_regenerate_id(true);
            // عزل جلسة المستخدم العادي عن الأدمن
            unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role'], $_SESSION['admin_permissions']);

            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_active'] = time();
            $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$user['id']]);
            respond(true, 'Welcome, ' . $user['full_name'], [
                'redirect' => '/Task(1)/index.php',
                'type'     => 'user',
            ]);
        }

        logLoginAttempt($email, false);
        error_log("Login failed for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        respond(false, 'Email or password is incorrect.');

    // ════════════════════════════════════
    case 'register':
    // ════════════════════════════════════
        verifyCsrfToken($_POST['csrf_token'] ?? '');

        // فحص Rate Limiting لإنشاء الحسابات (3 حسابات / ساعة)
        if (checkRateLimit('signup', 3, 60)) {
            respond(false, 'You have exceeded the account creation limit. Please try again in an hour.');
        }

        $fullName   = trim($_POST['full_name']        ?? '');
        $email      = trim(strtolower($_POST['email'] ?? ''));
        $pass       = $_POST['password']               ?? '';
        $confirmPass= $_POST['confirm_password']       ?? '';
        $phone      = trim($_POST['phone']             ?? '');
        $country    = trim($_POST['country']           ?? '');
        $city       = trim($_POST['city']              ?? '');
        $gender     = $_POST['gender']                 ?? '';
        $birthDate  = $_POST['birth_date']             ?? '';
        $ppAccepted = !empty($_POST['privacy_policy_accepted']);

        // Server-side validation
        if (strlen($fullName) < 2)
            respond(false, 'Full name must be at least 2 characters.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            respond(false, 'Please enter a valid email address.');
        if (!str_ends_with($email, '@gmail.com'))
            respond(false, 'Email must be a @gmail.com address.');
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass))
            respond(false, 'Password must be at least 8 characters with uppercase, lowercase, number, and symbol.');
        if ($pass !== $confirmPass)
            respond(false, 'Passwords do not match.');
        if (!in_array($gender, ['male', 'female']))
            respond(false, 'Please select your gender.');
        if (!$ppAccepted)
            respond(false, 'You must agree to the Privacy Policy.');

        // ── رقم الهاتف — إلزامي + عدد خانات ثابت ──────────────────
        if ($phone === '') {
            respond(false, 'Phone number is required.');
        }

        // تنظيف الرقم: احذف كل شيء ما عدا الأرقام والـ +
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($cleanPhone, '+')) {
            $cleanPhone = substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '00')) {
            $cleanPhone = substr($cleanPhone, 2);
        }

        // احذف كود الدولة المعروف لاستخراج الرقم المحلي
        $countryPhoneLengths = [
            '962' => [9],        // الأردن
            '20'  => [10],       // مصر
            '966' => [9],        // السعودية
            '971' => [9],        // الإمارات
            '965' => [8],        // الكويت
            '974' => [8],        // قطر
            '973' => [8],        // البحرين
            '968' => [8],        // عُمان
            '1'   => [10],       // أمريكا/كندا
            '44'  => [10],       // بريطانيا
            '90'  => [10],       // تركيا
            '49'  => [10, 11],   // ألمانيا
        ];

        $localPart   = $cleanPhone;
        $allowedLens = [7, 8, 9, 10, 11]; // افتراضي
        foreach ($countryPhoneLengths as $prefix => $lens) {
            if (str_starts_with($cleanPhone, (string)$prefix)) {
                $localPart   = substr($cleanPhone, strlen((string)$prefix));
                $allowedLens = $lens;
                break;
            }
        }

        if (!ctype_digit($localPart) || !in_array(strlen($localPart), $allowedLens)) {
            respond(false, 'Invalid phone number length for the selected country code.');
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) respond(false, 'This email is already registered. Please sign in.');

        // ── تحقق من تكرار رقم الهاتف ────────────────────────────
        $stmtPhone = $pdo->prepare("SELECT id FROM users WHERE phone_number = ? LIMIT 1");
        $stmtPhone->execute([$phone]);
        if ($stmtPhone->fetch()) respond(false, 'This phone number is already registered with another account.');

        // ── تحقق العمر 13+ سيرفري ───────────────────────────────
        if ($birthDate) {
            $birth = new DateTime($birthDate);
            $today = new DateTime();
            $age   = $today->diff($birth)->y;
            if ($age < 13) {
                respond(false, 'You must be at least 13 years old to register.');
            }
        } else {
            respond(false, 'Birth date is required.');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("
            INSERT INTO users (full_name,email,password,phone_number,country,city,
                               gender,birth_date,privacy_policy_accepted,
                               privacy_policy_accepted_at,last_activity,created_at)
            VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW(),NOW())
        ")->execute([$fullName,$email,$hash,$phone,$country?:null,$city?:null,$gender,$birthDate]);

        logRateLimitAttempt('signup');

        respond(true, 'Account created successfully! You can now sign in.');

    // ════════════════════════════════════
    case 'logout':
    // ════════════════════════════════════
        logout();

    // ════════════════════════════════════
    case 'forgot':
    // ════════════════════════════════════
        // سيُربط بـ PHPMailer لاحقاً — نُظهر نجاح دائماً (لمنع تعداد الإيميلات)
        respond(true, 'If this email is registered, you will receive a reset link shortly.');

    default:
        http_response_code(400);
        respond(false, 'طلب غير صحيح.');
}
