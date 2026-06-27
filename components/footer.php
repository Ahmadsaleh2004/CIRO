<footer class="custom-footer mt-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h4 class="fw-bold mb-2">🏪 Cairo Store</h4>
                <p class="small" style="color: var(--footer-text);">
                    Premium electronics store offering smartphones, laptops, gaming devices and smart accessories.
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
                <p class="small mb-3" style="color: var(--footer-text);">Stay updated with our latest news and offers!</p>
                <div class="d-flex gap-3 mt-2">
                    <a href="#" title="Facebook"><img src="https://cdn.simpleicons.org/facebook/white" width="26" height="26" alt="Facebook"></a>
                    <a href="#" title="Instagram"><img src="https://cdn.simpleicons.org/instagram/white" width="26" height="26" alt="Instagram"></a>
                    <a href="#" title="Snapchat"><img src="https://cdn.simpleicons.org/snapchat/white" width="26" height="26" alt="Snapchat"></a>
                </div>
            </div>
        </div>

        <hr>

        <div class="text-center">
            <p class="mb-0 small" style="color: var(--footer-text);">
                © <?php echo date("Y"); ?> Cairo Store. All Rights Reserved.
            </p>
        </div>
    </div>

    <!-- Cart Sidebar (Offcanvas) -->
    <div class="offcanvas offcanvas-end" id="cartSidebar" tabindex="-1" aria-labelledby="cartSidebarLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="cartSidebarLabel">🛒 Your Shopping Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul id="cart-items-list" class="list-unstyled">
                <li class="text-center py-5" style="color: var(--placeholder-color);">Your cart is empty.</li>
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

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-login">
                    <div>
                        <span class="modal-icon">🔐</span>
                        <h5 class="modal-title">Welcome Back</h5>
                        <small style="color:rgba(255,255,255,0.7); font-size:0.8rem;">Sign in to your account</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="float-group">
                        <input type="email" class="form-control" placeholder=" ">
                        <label>Email Address</label>
                    </div>
                    <div class="float-group mb-1">
                        <div class="input-group">
                            <input type="password" id="loginPass" placeholder=" ">
                            <span class="input-group-text" onclick="togglePassword('loginPass','eyeLogin')" id="eyeLogin">👁️</span>
                        </div>
                        <label>Password</label>
                    </div>
                    <div class="d-flex justify-content-end mb-4">
                        <a href="#" class="small" data-bs-toggle="modal" data-bs-target="#forgotModal" data-bs-dismiss="modal">
                            Forgot Password?
                        </a>
                    </div>
                    <button class="btn btn-dark w-100 mb-3 py-2">Sign In</button>
                    <div class="modal-divider">or</div>
                    <p class="text-center small mb-0">
                        Don't have an account?
                        <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Create one</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-register">
                    <div>
                        <span class="modal-icon">✨</span>
                        <h5 class="modal-title">Create Account</h5>
                        <small style="color:rgba(255,255,255,0.7); font-size:0.8rem;">Join Cairo Store today</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="signupForm" onsubmit="validateSignUp(event)">
                        <div class="float-group">
                            <input type="text" id="regName" placeholder=" " required>
                            <label>Full Name</label>
                        </div>
                        <div class="float-group">
                            <input type="email" id="regEmail" placeholder=" " required>
                            <label>Email Address</label>
                        </div>
                        <div class="float-group">
                            <div class="input-group">
                                <input type="password" id="regPass" placeholder=" " required>
                                <span class="input-group-text" onclick="toggleBothPasswords('eyeReg')" id="eyeReg">👁️</span>
                            </div>
                            <label>Password</label>
                        </div>
                        <div class="float-group">
                            <input type="password" id="regConfirmPass" placeholder=" " required>
                            <label>Confirm Password</label>
                        </div>
                        <div id="errorMsg" class="text-danger small mb-3" style="display:none;"></div>
                        <button type="submit" class="btn btn-success w-100 mb-3 py-2">Create Account</button>
                        <div class="modal-divider">or</div>
                        <p class="text-center small mb-0">
                            Already have an account?
                            <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Sign In</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-forgot">
                    <div>
                        <span class="modal-icon">🔑</span>
                        <h5 class="modal-title">Reset Password</h5>
                        <small style="color:rgba(255,255,255,0.7); font-size:0.8rem;">We'll send you a reset link</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="float-group">
                        <input type="email" placeholder=" ">
                        <label>Email Address</label>
                    </div>
                    <button class="btn btn-warning w-100 mb-3 py-2">Send Reset Link</button>
                    <div class="modal-divider">or</div>
                    <p class="text-center small mb-0">
                        <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">← Back to Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="/Task(1)/js/main.js"></script>
    <script src="/Task(1)/js/helpers.js"></script>
    <script src="/Task(1)/js/products.js"></script>
</footer>