let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

document.addEventListener("DOMContentLoaded", () => {
    renderWishlist();
});

/* ================================================
   Fix Image Path Helper
   ================================================ */
function fixWishlistImagePath(imgPath) {
    return '/Task(1)' + imgPath.replace(/^\.\./, '');
}

/* ================================================
   Render Wishlist
   ================================================ */
function renderWishlist() {
    const container = document.getElementById("wishlist-container");
    if (!container) return;

    if (wishlist.length === 0) {
        container.innerHTML = `
        <div class="col-12 text-center py-5">
            <h2 style="color: var(--text-color);">❤️ Wishlist Is Empty</h2>
            <p class="mt-3" style="color: var(--placeholder-color);">Add Some Products To Wishlist</p>
            <a href="/Task(1)/pages/products.php" class="btn btn-dark mt-2">Browse Products</a>
        </div>`;
        return;
    }

    container.innerHTML = "";

    wishlist.forEach(product => {
        const { oldPrice, discount } = calculateDiscount(product.price);
        const imgSrc = fixWishlistImagePath(product.image);

        container.innerHTML += `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="product-card">
                <!-- Discount Badge -->
                <span class="discount-badge">-${discount}%</span>

                <!-- Remove from Wishlist -->
                <button class="favorite-btn remove-favorite" data-id="${product.id}">❤️</button>

                <!-- Image -->
                <a href="/Task(1)/pages/product-details.php?id=${product.id}" style="text-decoration:none; flex:1; display:flex; flex-direction:column;">
                    <div class="card-img-wrapper">
                        <img src="${imgSrc}" alt="${product.name}" loading="lazy">
                    </div>
                    <div class="card-body">
                        <div>
                            <h5 class="fw-bold">${product.name}</h5>
                            <div class="price-box mb-2">
                                <span class="new-price">$${product.price}</span>
                                <span class="old-price ms-2">$${oldPrice}</span>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Add to Cart -->
                <div style="padding: 0 14px 14px;">
                    <button class="btn btn-success add-cart w-100" data-id="${product.id}">
                        🛒 Add To Cart
                    </button>
                </div>
            </div>
        </div>`;
    });

    activateWishlistButtons();
}

/* ================================================
   Activate Wishlist Buttons
   ================================================ */
function activateWishlistButtons() {
    // Remove from Wishlist
    document.querySelectorAll(".remove-favorite").forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            const id = parseInt(btn.dataset.id);
            wishlist = wishlist.filter(item => item.id !== id);
            localStorage.setItem("wishlist", JSON.stringify(wishlist));
            if (typeof updateCounters === "function") updateCounters();
            renderWishlist();
        };
    });

    // Add to Cart
    document.querySelectorAll(".add-cart").forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            const id = parseInt(btn.dataset.id);
            const product = wishlist.find(item => item.id === id);

            let cart = (typeof getCartData === 'function')
                ? getCartData()
                : (JSON.parse(localStorage.getItem("cart")) || []);

            const existing = cart.find(item => item.id === id);
            if (existing) existing.quantity += 1;
            else cart.push({ ...product, quantity: 1 });

            if (typeof saveCart === 'function') saveCart(cart);
            else localStorage.setItem("cart", JSON.stringify(cart));

            if (typeof window.showToast === 'function')
                window.showToast("Added to cart!", "success");
        };
    });
}