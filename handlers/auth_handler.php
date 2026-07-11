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

        if (!$email || !$pass) respond(false, 'يرجى إدخال الإيميل وكلمة السر.');

        if (isRateLimited($email)) {
            respond(false, 'تجاوزت عدد المحاولات. حاول بعد ' . getRateLimitMinutes($email) . ' دقيقة.');
        }

        $pdo = getDB();

        // تحقق من admins أولاً
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            logLoginAttempt($email, true);
            session_regenerate_id(true);
            $_SESSION['admin_id']    = (int)$admin['id'];
            $_SESSION['admin_name']  = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role']  = $admin['role'];
            $_SESSION['last_active'] = time();
            loadAdminPermissions((int)$admin['id']);
            respond(true, 'مرحباً ' . $admin['full_name'], [
                'redirect' => '/Task(1)/admin/dashboard.php',
                'type'     => 'admin',
            ]);
        }

        // تحقق من users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            logLoginAttempt($email, true);
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_active'] = time();
            $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$user['id']]);
            respond(true, 'مرحباً ' . $user['full_name'], [
                'redirect' => '/Task(1)/index.php',
                'type'     => 'user',
            ]);
        }

        logLoginAttempt($email, false);
        error_log("Login failed for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        respond(false, 'الإيميل أو كلمة السر غير صحيحة.');

    // ════════════════════════════════════
    case 'register':
    // ════════════════════════════════════
        verifyCsrfToken($_POST['csrf_token'] ?? '');

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
            respond(false, 'الاسم الكامل يجب أن يكون حرفين على الأقل.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            respond(false, 'يرجى إدخال إيميل صحيح.');
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass))
            respond(false, 'كلمة السر: 8 أحرف على الأقل، حرف كبير، صغير، رقم، ورمز.');
        if ($pass !== $confirmPass)
            respond(false, 'كلمة السر وتأكيدها غير متطابقتين.');
        if (!in_array($gender, ['male', 'female']))
            respond(false, 'يرجى تحديد الجنس.');
        if (!$ppAccepted)
            respond(false, 'يجب الموافقة على سياسة الخصوصية.');

        // Server-side phone validation (max 9 digits local number)
        if ($phone !== '') {
            $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
            if (str_starts_with($cleanPhone, '+')) {
                $cleanPhone = substr($cleanPhone, 1);
            } elseif (str_starts_with($cleanPhone, '00')) {
                $cleanPhone = substr($cleanPhone, 2);
            }
            $prefixes = ['962', '966', '971', '965', '974', '973', '968', '20'];
            foreach ($prefixes as $prefix) {
                if (str_starts_with($cleanPhone, $prefix)) {
                    $cleanPhone = substr($cleanPhone, strlen($prefix));
                    break;
                }
            }
            if (!ctype_digit($cleanPhone) || strlen($cleanPhone) > 9 || strlen($cleanPhone) < 7) {
                respond(false, 'رقم الهاتف غير صحيح (الحد الأقصى للجزء المحلي 9 أرقام).');
            }
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) respond(false, 'هذا الإيميل مسجّل مسبقاً. سجّل دخولك.');

        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("
            INSERT INTO users (full_name,email,password,phone_number,country,city,
                               gender,birth_date,privacy_policy_accepted,
                               privacy_policy_accepted_at,last_activity,created_at)
            VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW(),NOW())
        ")->execute([$fullName,$email,$hash,$phone?:null,$country?:null,$city?:null,$gender,$birthDate?:null]);

        respond(true, 'تم إنشاء حسابك! يمكنك تسجيل الدخول الآن.');

    // ════════════════════════════════════
    case 'logout':
    // ════════════════════════════════════
        logout();

    // ════════════════════════════════════
    case 'forgot':
    // ════════════════════════════════════
        // سيُربط بـ PHPMailer لاحقاً — نُظهر نجاح دائماً (لمنع تعداد الإيميلات)
        respond(true, 'إن كان الإيميل مسجلاً، ستصلك رسالة لإعادة تعيين كلمة السر.');

    default:
        http_response_code(400);
        respond(false, 'طلب غير صحيح.');
}
