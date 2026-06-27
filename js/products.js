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
   Add to Cart (Central) — uses saveCart() engine
   ================================================ */
window.addToCart = (id, qty = 1) => {
    let product = products.find(p => p.id === id);
    let cart = getCartData();
    let existing = cart.find(item => item.id === id);

    if (existing) existing.quantity += qty;
    else cart.push({ ...product, quantity: qty });

    saveCart(cart);
    window.showToast("Added to cart!", "success");
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
    const input = document.getElementById(`qty-${id}`);
    const qty = parseInt(input.value);
    window.addToCart(parseInt(id), qty);
    input.value = 1;
};

/* ================================================
   Fix Image Path (remove leading ../)
   Shared utility used across products, wishlist, and home sections
   ================================================ */
function fixImagePath(imgPath) {
    return '/Task(1)' + imgPath.replace(/^\.\./, '');
}
window.fixImagePath = fixImagePath;

/* ================================================
   Skeleton Loading — Products Page
   ================================================ */
function showSkeletons(containerId, count = 6) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = Array(count).fill(`
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="skeleton-card">
                <div class="skeleton skeleton-img"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
            </div>
        </div>`).join('');
}

/* ================================================
   Render — Products Page (with Add to Cart)
   ================================================ */
function renderProducts(data) {
    const container = document.getElementById("products-container");
    const countEl   = document.getElementById("results-count");
    if (!container) return;

    if (countEl) {
        countEl.textContent = data.length === 0
            ? "No products found"
            : `Showing ${data.length} of ${products.length} products`;
    }

    if (data.length === 0) {
        container.innerHTML = `
        <div class="col-12 text-center py-5 fade-in-up">
            <div style="font-size:4rem;">📦</div>
            <h4 class="mt-3" style="color:var(--text-color);">No products found</h4>
            <p style="color:var(--placeholder-color);">Try a different search or reset filters.</p>
        </div>`;
        return;
    }

    const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

    container.innerHTML = data.map(product => {
        const isFavorite = wishlist.some(item => item.id === product.id);
        const imgSrc = fixImagePath(product.image);

        return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100 shadow border-0 reveal">
                
                <button class="favorite-btn" onclick="window.toggleWishlist(${product.id}, this)">
                    ${isFavorite ? "❤️" : "🤍"}
                </button>

                <a href="/Task(1)/pages/product-details.php?id=${product.id}" style="text-decoration:none;">
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
                            <span class="new-price fs-5 fw-bold">$${product.price}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="quantity-box mb-3 d-flex justify-content-center align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeQty('${product.id}', -1)">−</button>
                            <input type="number" value="1" id="qty-${product.id}" class="form-control quantity-input" style="width:60px; text-align:center;">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeQty('${product.id}', 1)">+</button>
                        </div>
                        
                        <div class="d-grid">
                            <button class="btn btn-success add-cart" onclick="handleAddToCart('${product.id}')">
                                🛒 Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    // تشغيل scroll reveal بعد بناء الكاردات مباشرة
    requestAnimationFrame(() => {
        if (typeof window.initScrollReveal === 'function') window.initScrollReveal();
    });
}

/* ================================================
   Render — Slider (Home Page)
   يبني الـ slides من الـ best-seller products تلقائياً
   ================================================ */
function renderSlider(allProducts) {
    const sliderInner = document.getElementById("slider-inner");
    if (!sliderInner) return;

    // captions لكل منتج — لو مفيش caption يستخدم الاسم
    const captions = {
        1:  { title: "Wireless Freedom",         sub: "Experience premium sound quality with Apple Airpods." },
        2:  { title: "Active Noise Cancellation", sub: "Immerse yourself in music with Airpods Pro." },
        3:  { title: "Smart Fitness",             sub: "Track your health and goals with Apple Watch." },
        6:  { title: "Powerful Tablet",           sub: "Work and entertainment with the powerful iPad." },
        8:  { title: "Professional Photography",  sub: "Advanced smartphone with excellent camera system." },
        9:  { title: "High Performance",          sub: "Powerful laptop designed for professionals." },
        12: { title: "Gaming Excellence",         sub: "Dive into a new world of gaming with PS4." },
    };

    const sliderProducts = allProducts.filter(p =>
        ["best-seller", "limited"].includes(p.tag)
    ).slice(0, 6);

    sliderInner.innerHTML = sliderProducts.map((p, index) => {
        const imgSrc = fixImagePath(p.image);
        const cap    = captions[p.id] || { title: p.name, sub: p.description };
        return `
        <div class="carousel-item ${index === 0 ? "active" : ""}">
            <a href="/Task(1)/pages/product-details.php?id=${p.id}">
                <img src="${imgSrc}" class="d-block w-100 slider-image" alt="${p.name}">
            </a>
            <div class="carousel-caption d-none d-md-block text-shadow">
                <h2 class="display-4 fw-bold">${cap.title}</h2>
                <p class="lead">${cap.sub}</p>
            </div>
        </div>`;
    }).join('');
}

/* ================================================
   Render — Home Sections (Horizontal Carousel)
   ================================================ */
function renderHomeSections(allProducts) {
    renderSlider(allProducts);

    const tagConfig = {
        "best-seller": { label: "⭐ Best Seller", cls: "hpc-tag-best"    },
        "new":         { label: "🆕 New",          cls: "hpc-tag-new"     },
        "limited":     { label: "🔥 Limited",       cls: "hpc-tag-limited" },
        "regular":     { label: "🏷️ Sale",          cls: "hpc-tag-regular" },
    };

    const buildCarousel = (trackId, list) => {
        const track = document.getElementById(trackId);
        if (!track) return;

        track.innerHTML = list.map(p => {
            const imgSrc = fixImagePath(p.image);
            const tag    = tagConfig[p.tag] || { label: "", cls: "" };
            return `
            <div class="carousel-item-wrap">
                <a href="/Task(1)/pages/product-details.php?id=${p.id}" class="home-product-card">
                    ${tag.label ? `<span class="hpc-tag ${tag.cls}">${tag.label}</span>` : ""}
                    <img src="${imgSrc}" alt="${p.name}" class="hpc-img" loading="lazy">
                    <div class="hpc-body">
                        <div class="hpc-name">${p.name}</div>
                        <div class="hpc-price">$${p.price}</div>
                    </div>
                </a>
            </div>`;
        }).join('');
    };

    buildCarousel("best-sellers-track",  allProducts.filter(p => p.tag === "best-seller"));
    buildCarousel("new-arrivals-track",   allProducts.filter(p => p.tag === "new"));
    buildCarousel("other-products-track", allProducts.filter(p => p.tag === "limited" || p.tag === "regular"));

    // تفعيل أزرار السهمين
    document.querySelectorAll(".section-carousel-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const track = document.getElementById(btn.dataset.target);
            if (!track) return;
            const cardWidth = track.querySelector(".carousel-item-wrap")?.offsetWidth + 18 || 300;
            const dir = btn.classList.contains("prev-btn") ? -1 : 1;
            track.scrollBy({ left: dir * cardWidth * 2, behavior: "smooth" });
        });
    });
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

        // فلترة البحث — اسم أو brand أو description
        if (query) {
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(query) ||
                p.brand?.toLowerCase().includes(query) ||
                p.description?.toLowerCase().includes(query)
            );
        }

        // ترتيب بالاسم
        if (sort === "az")   filtered.sort((a, b) => a.name.localeCompare(b.name));
        if (sort === "za")   filtered.sort((a, b) => b.name.localeCompare(a.name));

        // ترتيب بالسعر
        if (sort === "low")  filtered.sort((a, b) => a.price - b.price);
        if (sort === "high") filtered.sort((a, b) => b.price - a.price);

        // فلترة بالـ tag
        if (sort.startsWith("tag-")) {
            const tag = sort.replace("tag-", "");
            filtered = filtered.filter(p => p.tag === tag);
        }

        // فلترة بالـ brand
        if (sort.startsWith("brand-")) {
            const brand = sort.replace("brand-", "");
            filtered = filtered.filter(p => p.brand === brand);
        }

        // فلترة بالسعر
        if (sort === "price-u100") filtered = filtered.filter(p => p.price < 100);
        if (sort === "price-u300") filtered = filtered.filter(p => p.price < 300);
        if (sort === "price-u500") filtered = filtered.filter(p => p.price < 500);
        if (sort === "price-o500") filtered = filtered.filter(p => p.price >= 500);

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
    // skeleton أثناء التحميل
    if (document.getElementById("products-container")) {
        showSkeletons("products-container", 6);
    }

    const res = await fetch('/Task(1)/data/products.json');
    products = await res.json();

    if (document.getElementById("products-container")) {
        renderProducts(products);
        initProductPageControls();
    } else {
        renderHomeSections(products);
    }
});