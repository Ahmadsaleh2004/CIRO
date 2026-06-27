// تحميل البيانات وتهيئة الصفحة
document.addEventListener("DOMContentLoaded", async () => {
    let products = [];
    
    try {
        const response = await fetch('/Task(1)/data/products.json');
        if (!response.ok) throw new Error("فشل في تحميل المنتجات");
        products = await response.json();
    } catch (error) {
        console.error("خطأ:", error);
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const productId = parseInt(params.get("id"));
    const product = products.find(p => p.id === productId);
    const container = document.getElementById("product-details");

    if (product && container) {
        const { oldPrice, discount } = calculateDiscount(product.price);
        const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
        const isFavorite = wishlist.some(item => item.id === product.id);

        container.innerHTML = `
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="position-relative">
                    <span class="discount-badge">-${discount}%</span>
                    <button id="addWishlistBtn" class="favorite-btn">${isFavorite ? "❤️" : "🤍"}</button>
                    <img src="${product.image}" class="img-fluid rounded shadow product-image" alt="${product.name}">
                </div>
            </div>
            <div class="col-lg-6">
                <h1 class="fw-bold mb-3">${product.name}</h1>
                <div class="price-box mb-3">
                    <span class="new-price">$${product.price}</span>
                    <span class="old-price">$${oldPrice}</span>
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

                <div class="quantity-box mb-4">
                    <button id="minusBtn" class="btn btn-outline-secondary">-</button>
                    <input type="number" value="1" min="1" id="productQty" class="form-control quantity-input">
                    <button id="plusBtn" class="btn btn-outline-secondary">+</button>
                </div>
                <button id="addCartBtn" class="btn btn-success btn-lg px-5">🛒 Add To Cart</button>
            </div>
        </div>`;

        activateDetailsButtons(product);

        // breadcrumb name
        const bcName = document.getElementById("breadcrumb-name");
        if (bcName) bcName.textContent = product.name;

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