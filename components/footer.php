<?php
/**
 * components/footer.php
 * Footer + Cart Sidebar + Modals (Login / Register / Forgot / Privacy)
 * يقرأ بيانات website_settings من DB
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

// جلب إعدادات الموقع
$ws = [];
try {
    $ws = getDB()->query("SELECT * FROM website_settings LIMIT 1")->fetch() ?: [];
} catch (Exception $e) {}

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
                                   placeholder=" " required autocomplete="email">
                            <label>Email Address</label>
                        </div>
                        <div class="float-group mb-1">
                            <div class="input-group">
                                <input type="password" id="loginPass" name="password"
                                       placeholder=" " required autocomplete="current-password">
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
                                   placeholder=" " required autocomplete="email">
                            <label>Email Address</label>
                        </div>
                        <div id="forgotMsg" class="alert py-2 small mb-3" style="display:none;"></div>
                        <button type="submit" class="btn btn-warning w-100 mb-3 py-2">Send Reset Link</button>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🔒 Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
                    <p><?= nl2br(htmlspecialchars($privacyPolicy)) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('privacyCheck').checked=true;"
                        data-bs-toggle="modal" data-bs-target="#registerModal"
                        data-bs-dismiss="modal">✅ I Agree</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Scripts ═════════════════════════════════════════════ -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/Task(1)/js/main.js"></script>
    <script src="/Task(1)/js/helpers.js"></script>
    <script src="/Task(1)/js/products.js"></script>
    <script src="/Task(1)/js/auth.js"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</footer>
