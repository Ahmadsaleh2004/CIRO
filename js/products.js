let products = [];

/* ================================================
   Toggle Wishlist (Central)
   ================================================ */
window.toggleWishlist = (id, btnElement) => {
    let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
    let index = wishlist.findIndex(item => item.id === id);

    if (index > -1) {
        wishlist.splice(index, 1);
        btnElement.innerHTML = "🤍";
    } else {
        let product = products.find(p => p.id === id);
        wishlist.push(product);
        btnElement.innerHTML = "❤️";
    }
    localStorage.setItem("wishlist", JSON.stringify(wishlist));
    if (typeof updateCounters === "function") updateCounters();
};

/* ================================================
   Add to Cart (Central)
   ================================================ */
window.addToCart = (id, qty = 1) => {
    let product = products.find(p => p.id === id);
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    let existing = cart.find(item => item.id === id);

    if (existing) existing.quantity += qty;
    else cart.push({ ...product, quantity: qty });

    localStorage.setItem("cart", JSON.stringify(cart));
    if (typeof window.showToast === 'function') window.showToast("Added to cart!", "success");
    if (typeof updateCounters === "function") updateCounters();
};

/* ================================================
   Quantity Helpers (Products Page)
   ================================================ */
window.changeQty = (id, val) => {
    let input = document.getElementById(`qty-${id}`);
    let newVal = parseInt(input.value) + val;
    if (newVal >= 1) input.value = newVal;
};

window.handleAddToCart = (id) => {
    let qty = parseInt(document.getElementById(`qty-${id}`).value);
    window.addToCart(parseInt(id), qty);
};

/* ================================================
   Fix Image Path (remove leading ../)
   ================================================ */
function fixImagePath(imgPath) {
    return '/Task(1)' + imgPath.replace(/^\.\./, '');
}

/* ================================================
   Render — Products Page (with Add to Cart)
   ================================================ */
function renderProducts(data) {
    const container = document.getElementById("products-container");
    if (!container) return;

    const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

    if (data.length === 0) {
        container.innerHTML = `
        <div class="col-12 text-center py-5">
            <p style="color: var(--placeholder-color); font-size: 1.1rem;">No products found.</p>
        </div>`;
        return;
    }

    container.innerHTML = data.map(product => {
        const isFavorite = wishlist.some(item => item.id === product.id);
        const imgSrc = fixImagePath(product.image);
        return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="product-card">
                <!-- Favorite -->
                <button class="favorite-btn" onclick="window.toggleWishlist(${product.id}, this)">
                    ${isFavorite ? "❤️" : "🤍"}
                </button>

                <!-- Image -->
                <a href="/Task(1)/pages/product-details.php?id=${product.id}" style="text-decoration:none;">
                    <div class="card-img-wrapper">
                        <img src="${imgSrc}" alt="${product.name}" loading="lazy">
                    </div>
                </a>

                <!-- Body -->
                <div class="card-body">
                    <div>
                        <h5>${product.name}</h5>
                        <span class="product-price">$${product.price}</span>
                    </div>
                    <div>
                        <!-- Quantity -->
                        <div class="quantity-box mb-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeQty('${product.id}', -1)">−</button>
                            <input type="number" value="1" id="qty-${product.id}" class="form-control quantity-input" style="width:55px; text-align:center;">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeQty('${product.id}', 1)">+</button>
                        </div>
                        <!-- Add to Cart -->
                        <button class="btn btn-success w-100" onclick="handleAddToCart('${product.id}')">
                            🛒 Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

/* ================================================
   Render — Home Sections
   Best Sellers / New Arrivals: Card WITHOUT Add to Cart
   Limited / Regular: Image + Price Overlay only
   ================================================ */
function renderHomeSections(allProducts) {

    const buildCardSection = (containerId, list) => {

    const container =
    document.getElementById(containerId);

    if(!container) return;

    container.innerHTML = list.map(p => {

        const imgSrc =
        fixImagePath(p.image);

        return `

        <div class="col-lg-4 col-md-6 mb-4">

            <a href="/Task(1)/pages/product-details.php?id=${p.id}"
               class="image-only-product d-block">

                <img
                    src="${imgSrc}"
                    alt="${p.name}"
                    class="img-fill"
                    loading="lazy"
                >

                <div class="price-overlay">

                    $${p.price}

                </div>

            </a>

        </div>

        `;

    }).join('');

};

    /* -- Image + Price Overlay (no card body, no title, no button) -- */
    const buildImageOnlySection = (containerId, list) => {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = list.map(p => {
            const imgSrc = fixImagePath(p.image);
            return `
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="/Task(1)/pages/product-details.php?id=${p.id}" class="image-only-product d-block">
                    <img src="${imgSrc}" alt="${p.name}" class="img-fill" loading="lazy">
                    <div class="price-overlay">$${p.price}</div>
                </a>
            </div>`;
        }).join('');
    };

    /* -- Fill sections -- */
    buildCardSection(
        "best-sellers-container",
        allProducts.filter(p => p.tag === "best-seller").slice(0, 3)
    );

    buildCardSection(
        "new-arrivals-container",
        allProducts.filter(p => p.tag === "new").slice(0, 3)
    );

    buildImageOnlySection(
        "other-products-container",
        allProducts.filter(p => p.tag === "limited" || p.tag === "regular").slice(0, 3)
    );
}

/* ================================================
   Products Page — Search & Sort
   ================================================ */
function initProductPageControls() {
    const searchInput = document.getElementById("search");
    const sortSelect  = document.getElementById("sort");
    const resetBtn    = document.getElementById("reset");

    if (!searchInput && !sortSelect) return;

    function applyFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const sort  = sortSelect  ? sortSelect.value : "";

        let filtered = [...products];

        if (query) {
            filtered = filtered.filter(p => p.name.toLowerCase().includes(query));
        }

        if (sort === "az")   filtered.sort((a, b) => a.name.localeCompare(b.name));
        if (sort === "za")   filtered.sort((a, b) => b.name.localeCompare(a.name));
        if (sort === "low")  filtered.sort((a, b) => a.price - b.price);
        if (sort === "high") filtered.sort((a, b) => b.price - a.price);

        renderProducts(filtered);
    }

    if (searchInput) searchInput.addEventListener("input", applyFilters);
    if (sortSelect)  sortSelect.addEventListener("change", applyFilters);
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            if (searchInput) searchInput.value = "";
            if (sortSelect)  sortSelect.value  = "";
            renderProducts(products);
        });
    }
}

/* ================================================
   Bootstrap — Init on DOM Ready
   ================================================ */
document.addEventListener("DOMContentLoaded", async () => {
    const res = await fetch('/Task(1)/data/products.json');
    products = await res.json();

    if (document.getElementById("products-container")) {
        renderProducts(products);
        initProductPageControls();
    } else {
        renderHomeSections(products);
    }
});