<?php
/**
 * components/footer.php
 * Footer + Cart Sidebar + Modals (Login / Register / Forgot / Privacy)
 * يقرأ بيانات website_settings من DB
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/settings_helper.php';

// جلب إعدادات الموقع (مكاشف مع كاش static)
$ws = getSiteSettings();

$footerText    = $ws['footer_text']    ?? 'Premium electronics store offering smartphones, laptops, gaming devices and smart accessories.';
$fbUrl         = $ws['facebook_url']   ?? '#';
$igUrl         = $ws['instagram_url']  ?? '#';
$snapUrl       = $ws['snapchat_url']   ?? '#';
$copyrightText = $ws['copyright_text'] ?? ('© ' . date('Y') . ' Cairo Store. All Rights Reserved.');
$privacyPolicy = $ws['privacy_policy'] ?? 'We respect your privacy. Your personal data is kept secure.';

$csrf = generateCsrfToken();

// Pre-fill للمستخدم المسجّل
$prefillName  = $_SESSION['user_name']  ?? '';
$prefillEmail = $_SESSION['user_email'] ?? '';
?>
<footer class="custom-footer mt-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h4 class="fw-bold mb-2">🏪 Cairo Store</h4>
                <p class="small" style="color:var(--footer-text);">
                    <?= htmlspecialchars($footerText) ?>
                </p>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-semibold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-1"><a href="/Task(1)/index.php">Home</a></li>
                    <li class="mb-1"><a href="/Task(1)/pages/products.php">Products</a></li>
                    <li class="mb-1"><a href="/Task(1)/pages/aboutus.php">About Us</a></li>
                    <li class="mb-1"><a href="/Task(1)/pages/contactus.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-semibold mb-3">Stay Connected</h5>
                <p class="small mb-3" style="color:var(--footer-text);">Stay updated with our latest news and offers!</p>
                <div class="d-flex gap-3 mt-2">
                    <a href="<?= htmlspecialchars($fbUrl) ?>" title="Facebook">
                        <img src="https://cdn.simpleicons.org/facebook/white" width="26" height="26" alt="Facebook" loading="lazy"></a>
                    <a href="<?= htmlspecialchars($igUrl) ?>" title="Instagram">
                        <img src="https://cdn.simpleicons.org/instagram/white" width="26" height="26" alt="Instagram" loading="lazy"></a>
                    <a href="<?= htmlspecialchars($snapUrl) ?>" title="Snapchat">
                        <img src="https://cdn.simpleicons.org/snapchat/white" width="26" height="26" alt="Snapchat" loading="lazy"></a>
                </div>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="mb-0 small" style="color:var(--footer-text);">
                <?= htmlspecialchars($copyrightText) ?>
            </p>
        </div>
    </div>

    <!-- ══ Cart Sidebar ════════════════════════════════════════ -->
    <div class="offcanvas offcanvas-end" id="cartSidebar" tabindex="-1" aria-labelledby="cartSidebarLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="cartSidebarLabel">🛒 Your Shopping Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul id="cart-items-list" class="list-unstyled" aria-label="Cart items">
                <li class="text-center py-5" style="color:var(--placeholder-color);">Your cart is empty.</li>
            </ul>
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <span id="cart-total" class="fw-bold">$0.00</span>
                </div>
                <button class="btn btn-warning w-100 fw-bold"
                    onclick="window.location.href='/Task(1)/pages/checkout.php'">
                    Proceed To Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- ══ Login Modal ═════════════════════════════════════════ -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-login">
                    <div>
                        <span class="modal-icon">🔐</span>
                        <h5 class="modal-title">Welcome Back</h5>
                        <small style="color:rgba(255,255,255,.7);font-size:.8rem;">Sign in to your account</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" novalidate>
                        <input type="hidden" name="action"     value="login">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                        <div class="float-group">
                            <input type="email" id="loginEmail" name="email"
                                   class="form-control"
                                   placeholder=" " required autocomplete="email"
                                   style="background-color:var(--input-bg);color:var(--input-text);">
                            <label>Email Address</label>
                        </div>
                        <div class="float-group mb-1">
                            <div class="input-group">
                                <input type="password" id="loginPass" name="password"
                                       class="form-control"
                                       placeholder=" " required autocomplete="current-password"
                                       style="background-color:var(--input-bg);color:var(--input-text);">
                                <span class="input-group-text"
                                      onclick="togglePassword('loginPass','eyeLogin')"
                                      id="eyeLogin" style="cursor:pointer;">👁️</span>
                            </div>
                            <label>Password</label>
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <a href="#" class="small"
                               data-bs-toggle="modal" data-bs-target="#forgotModal"
                               data-bs-dismiss="modal">Forgot Password?</a>
                        </div>
                        <div id="loginError" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                        <button type="submit" class="btn btn-dark w-100 mb-3 py-2" id="loginBtn">Sign In</button>
                        <div class="modal-divider">or</div>
                        <p class="text-center small mb-0">
                            Don't have an account?
                            <a href="#" class="fw-bold"
                               data-bs-toggle="modal" data-bs-target="#registerModal"
                               data-bs-dismiss="modal">Create one</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Register Modal ══════════════════════════════════════ -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-register">
                    <div>
                        <span class="modal-icon">✨</span>
                        <h5 class="modal-title">Create Account</h5>
                        <small style="color:rgba(255,255,255,.7);font-size:.8rem;">Join Cairo Store today</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="signupForm" novalidate>
                        <input type="hidden" name="action"     value="register">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="phone"      value=""><!-- يُملأ بـ auth.js -->

                        <div class="row">
                            <div class="col-md-6">
                                <div class="float-group">
                                    <input type="text" name="full_name" id="regName"
                                           placeholder=" " required autocomplete="name">
                                    <label>Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="float-group">
                                    <input type="email" name="email" id="regEmail"
                                           placeholder=" " required autocomplete="email">
                                    <label>Email Address</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="float-group">
                                    <div class="input-group">
                                        <input type="password" id="regPass" name="password"
                                               placeholder=" " required autocomplete="new-password">
                                        <span class="input-group-text"
                                              onclick="toggleBothPasswords('eyeReg')"
                                              id="eyeReg" style="cursor:pointer;">👁️</span>
                                    </div>
                                    <label>Password</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="float-group">
                                    <input type="password" id="regConfirmPass" name="confirm_password"
                                           placeholder=" " required autocomplete="new-password">
                                    <label>Confirm Password</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="float-group">
                                    <div class="input-group">
                                        <select id="phoneCountryCode" class="form-select" style="max-width:110px;">
                                            <option value="+962">🇯🇴 +962</option>
                                            <option value="+20">🇪🇬 +20</option>
                                            <option value="+966">🇸🇦 +966</option>
                                            <option value="+971">🇦🇪 +971</option>
                                            <option value="+1">🇺🇸 +1</option>
                                            <option value="+44">🇬🇧 +44</option>
                                            <option value="+90">🇹🇷 +90</option>
                                            <option value="+49">🇩🇪 +49</option>
                                        </select>
                                        <input type="tel" id="regPhoneLocal" placeholder=" " maxlength="9">
                                    </div>
                                    <label>Phone Number</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="float-group">
                                    <select name="gender" id="regGender" required>
                                        <option value="">Select</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <label>Gender</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="float-group">
                                    <input type="date" name="birth_date" id="regBirthDate" placeholder=" ">
                                    <label>Birth Date</label>
                                </div>
                                <small class="text-muted d-block mt-n2 mb-2" style="font-size:0.75rem;padding-left:4px;">
                                    Must be 13 years or older
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="float-group">
                                    <input type="text" name="country" id="regCountry"
                                           placeholder=" " autocomplete="country-name">
                                    <label>Country</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="float-group">
                                    <input type="text" name="city" id="regCity"
                                           placeholder=" " autocomplete="address-level2">
                                    <label>City</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox"
                                   id="privacyCheck" name="privacy_policy_accepted" required>
                            <label class="form-check-label small" for="privacyCheck">
                                I agree to the
                                <a href="#" data-bs-toggle="modal"
                                   data-bs-target="#privacyModal"
                                   data-bs-dismiss="modal">Privacy Policy</a>
                            </label>
                        </div>

                        <div id="regError" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                        <button type="submit" id="regBtn" class="btn btn-success w-100 mb-3 py-2">
                            Create Account
                        </button>
                        <div class="modal-divider">or</div>
                        <p class="text-center small mb-0">
                            Already have an account?
                            <a href="#" class="fw-bold"
                               data-bs-toggle="modal" data-bs-target="#loginModal"
                               data-bs-dismiss="modal">Sign In</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Forgot Modal ════════════════════════════════════════ -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-forgot">
                    <div>
                        <span class="modal-icon">🔑</span>
                        <h5 class="modal-title">Reset Password</h5>
                        <small style="color:rgba(255,255,255,.7);font-size:.8rem;">We'll send you a reset link</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="forgotForm">
                        <input type="hidden" name="action" value="forgot">
                        <div class="float-group">
                            <input type="email" name="email" id="forgotEmail"
                                   class="form-control"
                                   placeholder=" " required autocomplete="email">
                            <label>Email Address</label>
                        </div>
                        <div id="forgotMsg" class="alert py-2 small mb-3" style="display:none;"></div>
                        <button type="submit" id="forgotBtn" class="btn btn-warning w-100 mb-3 py-2">Send Reset Link</button>
                        <div class="modal-divider">or</div>
                        <p class="text-center small mb-0">
                            <a href="#" class="fw-bold"
                               data-bs-toggle="modal" data-bs-target="#loginModal"
                               data-bs-dismiss="modal">← Back to Sign In</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Privacy Policy Modal ════════════════════════════════ -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background:var(--card-bg); color:var(--text-color); border:1px solid var(--section-border);">
                <div class="modal-header" style="border-bottom:1px solid var(--section-border);">
                    <h5 class="modal-title">🔒 Privacy Policy / سياسة الخصوصية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
                    <h3>🔒 Privacy Policy / سياسة الخصوصية</h3>
                    <hr style="border-color:var(--section-border);">
                    <h5>1. Information We Collect / البيانات التي نجمعها</h5>
                    <p>We collect personal information that you provide to us directly, such as your full name, email address, password, phone number, birth date, gender, country, and city when registering an account or communicating with us.</p>
                    <p>نقوم بجمع المعلومات الشخصية التي تقدمها لنا مباشرة، مثل الاسم الكامل، عنوان البريد الإلكتروني، كلمة المرور، رقم الهاتف، تاريخ الميلاد، الجنس، الدولة، والمدينة عند إنشاء حساب أو التواصل معنا.</p>

                    <h5>2. How We Use Your Information / كيف نستخدم بياناتك</h5>
                    <p>We use your information to facilitate your purchases, process and deliver your orders, communicate order updates, improve our services, prevent fraudulent activities, and comply with legal obligations.</p>
                    <p>نستخدم بياناتك لتسهيل عمليات الشراء، ومعالجة طلباتك وتوصيلها، وإعلامك بآخر التحديثات حول طلباتك، وتحسين خدماتنا، ومنع الأنشطة الاحتيالية، والالتزام بالقوانين المعمول بها.</p>

                    <h5>3. Data Storage & Protection / تخزين وحماية البيانات</h5>
                    <p>All passwords are encrypted using high-security hashing algorithms (BCRYPT). We implement strict security measures to protect your personal data from unauthorized access, alteration, or disclosure.</p>
                    <p>تُشفر جميع كلمات المرور باستخدام خوارزميات تشفير عالية الأمان (BCRYPT). نتخذ تدابير أمنية صارمة لحماية بياناتك الشخصية من الوصول غير المصرح به، أو التعديل، أو الإفصاح.</p>

                    <h5>4. Cookies / ملفات تعريف الارتباط</h5>
                    <p>We use cookies to maintain your login session and store your preferences. You can configure your browser to reject cookies, but some features of the website may not function correctly.</p>
                    <p>نستخدم ملفات تعريف الارتباط للحفاظ على جلسة تسجيل الدخول وتخزين تفضيلاتك. يمكنك ضبط متصفحك لرفض ملفات تعريف الارتباط، ولكن قد لا تعمل بعض ميزات الموقع بشكل صحيح.</p>

                    <h5>5. Your Rights / حقوقك كـ مستخدم</h5>
                    <p>You have the right to access, update, or delete your personal information at any time via your 'My Info' page, or by contacting our support team.</p>
                    <p>لديك الحق في الوصول إلى معلوماتك الشخصية، أو تحديثها، أو حذفها في أي وقت من خلال صفحة 'بياناتي' أو بالتواصل مع فريق الدعم لدينا.</p>

                    <h5>6. Contact Us / اتصل بنا</h5>
                    <p>If you have any questions or concerns regarding this Privacy Policy, you can reach out via the 'Contact Us' page.</p>
                    <p>إذا كان لديك أي أسئلة أو مخاوف بشأن سياسة الخصوصية هذه، يمكنك التواصل معنا عبر صفحة 'اتصل بنا'.</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--section-border);">
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('privacyCheck').checked=true; if(typeof checkSignupFormValidity === 'function') checkSignupFormValidity();"
                        data-bs-toggle="modal" data-bs-target="#registerModal"
                        data-bs-dismiss="modal">✅ I Agree / موافق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Notification Sidebar ════════════════════════════════ -->
    <div id="notifSidebar" role="region" aria-label="Notifications panel">
        <div class="notif-header">
            <span>🔔 Notifications</span>
            <div class="d-flex gap-2">
                <button id="notifMarkAll" class="btn btn-sm btn-outline-light">Mark All Read</button>
                <button id="notifClose" class="btn btn-sm btn-outline-light" aria-label="Close">✕</button>
            </div>
        </div>
        <ul id="notifList" class="notif-list"><li class="notif-empty">Loading...</li></ul>
        <div class="p-2">
            <button id="notifDeleteAll" class="btn btn-sm btn-outline-danger w-100">🗑️ Delete All</button>
        </div>
    </div>

    <!-- ══ Scripts ═════════════════════════════════════════════ -->
    <script>
    // ── إصلاح لون الـ inputs في الـ dark mode عند الـ focus ──
    (function fixInputFocus() {
        function applyInputColors() {
            const isDark = document.body.classList.contains('dark-mode');
            const bg     = isDark ? '#21262d' : '#ffffff';
            const fg     = isDark ? '#e6edf3' : '#1a1a2e';
            document.querySelectorAll(
                '#loginModal input, #forgotModal input, #registerModal input, #registerModal select, #registerModal textarea'
            ).forEach(el => {
                el.style.setProperty('background-color', bg, 'important');
                el.style.setProperty('color', fg, 'important');
            });
        }

        // شغّل عند فتح أي Modal
        document.addEventListener('shown.bs.modal', applyInputColors);
        // شغّل عند تغيير الثيم
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) themeToggle.addEventListener('click', () => setTimeout(applyInputColors, 50));
        // شغّل عند الـ focus مباشرة
        document.addEventListener('focusin', function(e) {
            const modal = e.target.closest('#loginModal, #forgotModal, #registerModal');
            if (!modal) return;
            const isDark = document.body.classList.contains('dark-mode');
            const bg     = isDark ? '#21262d' : '#ffffff';
            const fg     = isDark ? '#e6edf3' : '#1a1a2e';
            e.target.style.setProperty('background-color', bg, 'important');
            e.target.style.setProperty('color', fg, 'important');
        });
    })();
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="/Task(1)/js/main.js?v=<?= filemtime(__DIR__.'/../js/main.js') ?>" defer></script>
    <script src="/Task(1)/js/helpers.js?v=<?= filemtime(__DIR__.'/../js/helpers.js') ?>" defer></script>
    <script src="/Task(1)/js/products.js?v=<?= filemtime(__DIR__.'/../js/products.js') ?>" defer></script>
    <script src="/Task(1)/js/auth.js?v=<?= filemtime(__DIR__.'/../js/auth.js') ?>" defer></script>
    <?php if (isset($loggedInUser) && $loggedInUser): ?>
    <script src="/Task(1)/js/notifications.js" defer></script>
    <?php endif; ?>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</footer>
