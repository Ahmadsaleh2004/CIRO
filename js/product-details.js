// تحميل البيانات وتهيئة الصفحة
document.addEventListener("DOMContentLoaded", async () => {
    let products = [];
    const container = document.getElementById("product-details");

    // Skeleton أثناء التحميل
    if (container) {
        container.innerHTML = `
        <div class="row g-5">
            <div class="col-lg-6">
                <div class="skeleton" style="height:380px; border-radius:14px;"></div>
            </div>
            <div class="col-lg-6 d-flex flex-column gap-3 pt-3">
                <div class="skeleton skeleton-line" style="height:32px; width:70%;"></div>
                <div class="skeleton skeleton-line" style="height:24px; width:40%;"></div>
                <div class="skeleton skeleton-line" style="height:16px;"></div>
                <div class="skeleton skeleton-line" style="height:16px; width:85%;"></div>
                <div class="skeleton skeleton-line" style="height:90px; border-radius:10px;"></div>
                <div class="skeleton skeleton-line" style="height:48px; width:55%;"></div>
            </div>
        </div>`;
    }

    try {
        const response = await fetch('/Task(1)/data/products.json');
        if (!response.ok) throw new Error("Failed to load products");
        products = await response.json();
    } catch (error) {
        console.error("Error:", error);
        if (container) {
            container.innerHTML = `
            <div class="text-center py-5 fade-in-up">
                <div style="font-size:4rem;">⚠️</div>
                <h4 class="mt-3" style="color:var(--text-color);">Failed to load product</h4>
                <p style="color:var(--placeholder-color);">Please check your connection and try again.</p>
                <button class="btn btn-success mt-2" onclick="location.reload()">🔄 Retry</button>
            </div>`;
        }
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const productId = parseInt(params.get("id"));
    const product = products.find(p => p.id === productId);

    if (!product) {
        if (container) {
            container.innerHTML = `
            <div class="text-center py-5 fade-in-up">
                <div style="font-size:4rem;">🔍</div>
                <h4 class="mt-3" style="color:var(--text-color);">Product not found</h4>
                <a href="/Task(1)/pages/products.php" class="btn btn-success mt-2">Browse Products</a>
            </div>`;
        }
        return;
    }

    if (product && container) {
        const { oldPrice, discount } = calculateDiscount(product.price);
        const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
        const isFavorite = wishlist.some(item => item.id === product.id);
        const imgSrc = window.fixImagePath ? window.fixImagePath(product.image) : product.image;

        container.innerHTML = `
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="position-relative">
                    <span class="discount-badge" aria-label="${discount}% discount">-${discount}%</span>
                    <button id="addWishlistBtn" class="favorite-btn" aria-label="${isFavorite ? 'Remove from wishlist' : 'Add to wishlist'}">${isFavorite ? "❤️" : "🤍"}</button>
                    <img src="${imgSrc}" class="img-fluid rounded shadow product-image" alt="${product.name}">
                </div>
            </div>
            <div class="col-lg-6">
                <h1 class="fw-bold mb-3">${product.name}</h1>
                <div class="price-box mb-3">
                    <span class="new-price" aria-label="Current price: $${product.price}">$${product.price}</span>
                    <span class="old-price" aria-label="Original price: $${oldPrice}">$${oldPrice}</span>
                </div>
                <p class="mb-4 product-description">${product.description}</p>
                
                <div class="product-specs mb-4 p-3 rounded">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <span class="spec-label">🏷️ Brand:</span>
                            <span class="spec-value">${product.brand || 'N/A'}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="spec-label">🌍 Origin:</span>
                            <span class="spec-value">${product.madeIn || 'N/A'}</span>
                        </div>
                        <div class="col-sm-12 mt-2">
                            <span class="spec-label">📅 Release Date:</span>
                            <span class="spec-value">${product.releaseDate || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="quantity-box mb-4" role="group" aria-label="Quantity selector">
                    <button id="minusBtn" class="btn btn-outline-secondary" aria-label="Decrease quantity">−</button>
                    <input type="number" value="1" min="1" id="productQty" class="form-control quantity-input" aria-label="Product quantity">
                    <button id="plusBtn" class="btn btn-outline-secondary" aria-label="Increase quantity">+</button>
                </div>
                <button id="addCartBtn" class="btn btn-success btn-lg px-5" aria-label="Add ${product.name} to cart">🛒 Add To Cart</button>
            </div>
        </div>`;

        activateDetailsButtons(product);

        // breadcrumb name
        const bcName = document.getElementById("breadcrumb-name");
        if (bcName) bcName.textContent = product.name;

        // Dynamic page title + OG meta tags
        document.title = `${product.name} | Cairo Store`;
        const setMeta = (prop, val, attr = "name") => {
            let el = document.querySelector(`meta[${attr}="${prop}"]`);
            if (!el) { el = document.createElement("meta"); el.setAttribute(attr, prop); document.head.appendChild(el); }
            el.setAttribute("content", val);
        };
        setMeta("description", product.description);
        setMeta("og:title",       `${product.name} | Cairo Store`, "property");
        setMeta("og:description", product.description,              "property");
        setMeta("og:image",       `https://cairostore.com${imgSrc}`, "property");
        setMeta("og:type",        "product",                         "property");

        renderRelatedProducts(product.id, product.brand, products);
    }
});

function activateDetailsButtons(product) {
    const qtyInput = document.getElementById("productQty");
    const wishlistBtn = document.getElementById("addWishlistBtn");
    const addCartBtn = document.getElementById("addCartBtn");

    document.getElementById("plusBtn").onclick = () => qtyInput.value = parseInt(qtyInput.value) + 1;
    document.getElementById("minusBtn").onclick = () => {
        if (parseInt(qtyInput.value) > 1) qtyInput.value = parseInt(qtyInput.value) - 1;
    };

        // إضافة للسلة باستخدام المحرك المركزي
    addCartBtn.onclick = () => {
        const quantity = parseInt(qtyInput.value);
        let cart = getCartData();
        const existing = cart.find(item => item.id === product.id);

        if (existing) {
            existing.quantity += quantity;
        } else {
            cart.push({ ...product, quantity });
        }

        saveCart(cart);
        qtyInput.value = 1;
        window.showToast("Product added to cart successfully!", "success");
    };

    wishlistBtn.onclick = () => {
        let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
        const exists = wishlist.find(item => item.id === product.id);

        if (exists) {
            wishlist = wishlist.filter(item => item.id !== product.id);
            wishlistBtn.innerHTML = "🤍";
        } else {
            wishlist.push(product);
            wishlistBtn.innerHTML = "❤️";
        }
        localStorage.setItem("wishlist", JSON.stringify(wishlist));
        
        if (typeof updateCounters === "function") updateCounters();
    };
}
function renderRelatedProducts(currentId, brand, products) {
    const container = document.getElementById("related-products");
    if (!container) return;

    // أولوية للـ brand نفسها، لو مش كافية نكمل من باقي المنتجات
    const sameBrand = products.filter(p => p.id !== currentId && p.brand === brand);
    const others    = products.filter(p => p.id !== currentId && p.brand !== brand);
    const related   = [...sameBrand, ...others].slice(0, 4);

    container.innerHTML = related.map(p => {
        const imgSrc = window.fixImagePath(p.image);
        return `
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="/Task(1)/pages/product-details.php?id=${p.id}" class="image-only-product d-block reveal">
                <img src="${imgSrc}" class="img-fill" alt="${p.name}" loading="lazy">
                <div class="price-overlay">$${p.price}</div>
            </a>
        </div>`;
    }).join('');

    requestAnimationFrame(() => {
        if (typeof window.initScrollReveal === 'function') window.initScrollReveal();
    });
}