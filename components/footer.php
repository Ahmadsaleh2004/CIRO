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
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Log In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <input type="email" class="form-control mb-3" placeholder="Email">

                    <div class="input-group mb-2">
                        <input type="password" class="form-control" id="loginPass" placeholder="Password">
                        <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('loginPass','eyeLogin')" id="eyeLogin">👁️</span>
                    </div>

                    <div class="d-flex justify-content-between mb-4">
                        <a href="#" class="small" data-bs-toggle="modal" data-bs-target="#forgotModal" data-bs-dismiss="modal">
                            Forgot Password?
                        </a>
                    </div>

                    <button class="btn btn-dark w-100 mb-3">Log In</button>
                    <p class="text-center small mb-0" style="color: var(--modal-text);">
                        Don't have an account?
                        <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Sign Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form id="signupForm" onsubmit="validateSignUp(event)">
                        <input type="text" id="regName" class="form-control mb-3" placeholder="Full Name" required>
                        <input type="email" id="regEmail" class="form-control mb-3" placeholder="Email (e.g., user@example.com)" required>

                        <div class="input-group mb-3">
                            <input type="password" id="regPass" class="form-control" placeholder="Password" required>
                            <span class="input-group-text" style="cursor:pointer;" onclick="toggleBothPasswords('eyeReg')" id="eyeReg">👁️</span>
                        </div>

                        <div class="mb-3">
                            <input type="password" id="regConfirmPass" class="form-control" placeholder="Confirm Password" required>
                        </div>

                        <div id="errorMsg" class="text-danger small mb-3" style="display:none;"></div>

                        <button type="submit" class="btn btn-success w-100 mb-3">Create Account</button>
                        <p class="text-center small mb-0" style="color: var(--modal-text);">
                            Already have an account?
                            <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Log In</a>
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
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <p class="small mb-3" style="color: var(--placeholder-color);">Enter your email address to receive a password reset link.</p>
                    <input type="email" class="form-control mb-4" placeholder="Enter your email">
                    <button class="btn btn-warning w-100 mb-3">Send Reset Link</button>
                    <p class="text-center small mb-0">
                        <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Back to Log In</a>
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
    <script src="/Task(1)/js/product-details.js"></script>
    <script src="/Task(1)/js/wishlist.js"></script>
    <script src="/Task(1)/js/checkout.js"></script>
</footer>