let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

document.addEventListener("DOMContentLoaded", () => {
    // skeleton أثناء أي تأخير
    const container = document.getElementById("wishlist-container");
    if (container && wishlist.length > 0) {
        container.innerHTML = Array(Math.min(wishlist.length, 3)).fill(`
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="skeleton-card">
                <div class="skeleton skeleton-img"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
            </div>
        </div>`).join('');
    }
    renderWishlist();
});

/* ================================================
   Quantity Helper (Wishlist Page)
   ================================================ */
window.changeWishlistQty = (id, val) => {
    const input = document.getElementById(`qty-${id}`);
    const newVal = parseInt(input.value) + val;
    if (newVal >= 1) input.value = newVal;
};

/* ================================================
   Render Wishlist
   ================================================ */
function renderWishlist() {
    const container = document.getElementById("wishlist-container");
    if (!container) return;

    if (wishlist.length === 0) {
        container.innerHTML = `
        <div class="col-12 text-center py-5 fade-in-up">
            <div style="font-size:5rem;">💔</div>
            <h3 class="mt-3 fw-bold" style="color:var(--text-color);">Your Wishlist is Empty</h3>
            <p class="mt-2" style="color:var(--placeholder-color);">Save your favorite products and come back to them anytime.</p>
            <a href="/Task(1)/pages/products.php" class="btn btn-success mt-3 px-4 py-2">🛍️ Browse Products</a>
        </div>`;
        return;
    }

    container.innerHTML = wishlist.map(product => {
        const imgSrc = window.fixImagePath(product.image);
        return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100 shadow border-0">

                <button class="favorite-btn remove-favorite" data-id="${product.id}" aria-label="Remove ${product.name} from wishlist">❤️</button>

                <a href="/Task(1)/pages/product-details.php?id=${product.id}" style="text-decoration:none;" aria-label="View ${product.name}">
                    <img
                        src="${imgSrc}"
                        class="card-img-top product-image"
                        alt="${product.name}"
                        loading="lazy"
                    >
                </a>

                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="mb-3">
                        <h5 class="fw-bold">${product.name}</h5>
                        <div class="price-box">
                            <span class="new-price fs-5 fw-bold" aria-label="Price: $${product.price}">$${product.price}</span>
                        </div>
                    </div>

                    <div>
                        <div class="quantity-box mb-3 d-flex justify-content-center align-items-center gap-2" role="group" aria-label="Quantity">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeWishlistQty('${product.id}', -1)" aria-label="Decrease quantity">−</button>
                            <input type="number" value="1" id="qty-${product.id}" class="form-control quantity-input" style="width:60px; text-align:center;" aria-label="Quantity">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeWishlistQty('${product.id}', 1)" aria-label="Increase quantity">+</button>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-success add-cart" data-id="${product.id}" aria-label="Add ${product.name} to cart">
                                🛒 Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

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

    // Add to Cart — بيقرأ الكمية من الـ input ويرجعها لـ 1 بعد الإضافة
    document.querySelectorAll(".add-cart").forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            const id = parseInt(btn.dataset.id);
            const product = wishlist.find(item => item.id === id);
            const input = document.getElementById(`qty-${id}`);
            const qty = parseInt(input.value) || 1;

            let cart = getCartData();
            const existing = cart.find(item => item.id === id);
            if (existing) existing.quantity += qty;
            else cart.push({ ...product, quantity: qty });

            saveCart(cart);
            input.value = 1;
            window.showToast("Added to cart!", "success");
        };
    });
}
