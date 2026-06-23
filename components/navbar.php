<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/Task(1)/index.php">
            🏪 Cairo Store
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/pages/products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/pages/aboutus.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/pages/contactus.php">Contact Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Log In
                    </a>
                </li>
            </ul>

            <div class="d-flex gap-2 align-items-center">
                <a href="/Task(1)/pages/wishlist.php" class="btn btn-outline-danger position-relative">
                    ❤️ <span id="wishlist-count" class="counter-badge">0</span>
                </a>

                <button type="button"
                    class="btn btn-outline-warning position-relative"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#cartSidebar"
                    aria-controls="cartSidebar">
                    🛒 <span id="cart-count" class="counter-badge">0</span>
                </button>

                <button id="theme-toggle" class="btn btn-outline-light" title="Toggle Theme">
                    🌙
                </button>
            </div>
        </div>
    </div>
</nav>