/**
 * js/products.js
 * ─────────────────────────────────────────────────────────────────
 * هذا الملف يُصدّر دوال مشتركة فقط — لا يُنفّذ كوداً تلقائياً عند التحميل.
 *
 * من يستدعيها:
 *  • index.php           → window.dbProducts → renderHomeSections()
 *  • pages/products.php  → كود PHP Server-Side + Inline JS للفلترة/البحث
 *  • pages/wishlist.php  → addToCart / toggleWishlist
 *
 * ملاحظة: renderProducts() وinitProductPageControls() حُذفتا
 * لأنهما مكررتان مع الكود الـ Inline داخل pages/products.php
 * ─────────────────────────────────────────────────────────────────
 */

/* ================================================
   Fix Image Path — shared utility
   ================================================ */
function fixImagePath(imgPath) {
    if (!imgPath) return '';
    if (imgPath.startsWith('/')) return imgPath;
    return '/Task(1)' + imgPath.replace(/^\.\./, '');
}
window.fixImagePath = fixImagePath;

/* ================================================
   Toggle Wishlist (Central)
   ================================================ */
window.toggleWishlist = (id, btnElement, productData = null) => {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const index  = wishlist.findIndex(item => item.id === id);

    if (index > -1) {
        wishlist.splice(index, 1);
        if (btnElement) btnElement.innerHTML = '🤍';
    } else {
        if (productData) {
            wishlist.push(productData);
        }
        if (btnElement) btnElement.innerHTML = '❤️';
    }
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    if (typeof updateCounters === 'function') updateCounters();
};

/* ================================================
   Add to Cart (Central)
   ================================================ */
window.addToCart = (id, qty = 1, productData = null) => {
    let cart     = typeof getCartData === 'function' ? getCartData() : (JSON.parse(localStorage.getItem('cart')) || []);
    let existing = cart.find(item => item.id === id);

    if (existing) {
        existing.quantity += qty;
    } else if (productData) {
        cart.push({ ...productData, quantity: qty });
    }

    if (typeof saveCart === 'function') {
        saveCart(cart);
    } else {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    if (typeof showToast === 'function') showToast('Added to cart!', 'success');

    // Cart bounce
    const cb = document.querySelector('[data-bs-target="#cartSidebar"]');
    if (cb) {
        cb.classList.add('cart-bounce');
        setTimeout(() => cb.classList.remove('cart-bounce'), 500);
    }
};

/* ================================================
   Quantity Helpers
   ================================================ */
window.changeQty = (id, val) => {
    const input  = document.getElementById(`qty-${id}`);
    if (!input) return;
    const newVal = parseInt(input.value) + val;
    if (newVal >= 1) input.value = newVal;
};

window.handleAddToCart = (id, productData = null) => {
    const input = document.getElementById(`qty-${id}`);
    const qty   = input ? parseInt(input.value) : 1;
    window.addToCart(parseInt(id), qty, productData);
    if (input) input.value = 1;
};

/* ================================================
   Skeleton Loading — مشتركة
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
window.showSkeletons = showSkeletons;

/* ================================================
   Render — Slider (Home Page)
   يبني الـ slides من best-seller products
   ================================================ */
function renderSlider(allProducts) {
    const sliderInner = document.getElementById('slider-inner');
    if (!sliderInner) return;

    const captions = {
        1:  { title: 'Wireless Freedom',         sub: 'Experience premium sound quality with Apple Airpods.' },
        2:  { title: 'Active Noise Cancellation', sub: 'Immerse yourself in music with Airpods Pro.' },
        3:  { title: 'Smart Fitness',             sub: 'Track your health and goals with Apple Watch.' },
        6:  { title: 'Powerful Tablet',           sub: 'Work and entertainment with the powerful iPad.' },
        8:  { title: 'Professional Photography',  sub: 'Advanced smartphone with excellent camera system.' },
        9:  { title: 'High Performance',          sub: 'Powerful laptop designed for professionals.' },
        12: { title: 'Gaming Excellence',         sub: 'Dive into a new world of gaming with PS4.' },
    };

    const sliderProducts = allProducts
        .filter(p => ['best-seller', 'limited'].includes(p.tag))
        .slice(0, 6);

    sliderInner.innerHTML = sliderProducts.map((p, index) => {
        const imgSrc = fixImagePath(p.image || p.image_path || '');
        const cap    = captions[p.id] || { title: p.name, sub: p.description };
        return `
        <div class="carousel-item ${index === 0 ? 'active' : ''}">
            <a href="/Task(1)/pages/product-details.php?id=${p.id}">
                <img src="${imgSrc}" class="d-block w-100 slider-image" alt="${p.name}" loading="lazy">
            </a>
            <div class="carousel-caption d-none d-md-block text-shadow">
                <h2 class="display-4 fw-bold">${cap.title}</h2>
                <p class="lead">${cap.sub}</p>
            </div>
        </div>`;
    }).join('');
}
window.renderSlider = renderSlider;

/* ================================================
   Render — Home Sections (Horizontal Carousel)
   تُستدعى صراحة من index.php عبر window.dbProducts
   ================================================ */
function renderHomeSections(allProducts) {
    renderSlider(allProducts);

    const tagConfig = {
        'best-seller': { label: '⭐ Best Seller', cls: 'hpc-tag-best'    },
        'new':         { label: '🆕 New',          cls: 'hpc-tag-new'     },
        'limited':     { label: '🔥 Limited',       cls: 'hpc-tag-limited' },
        'regular':     { label: '🏷️ Sale',          cls: 'hpc-tag-regular' },
    };

    const buildCarousel = (trackId, list) => {
        const track = document.getElementById(trackId);
        if (!track) return;

        track.innerHTML = list.map(p => {
            const imgSrc = fixImagePath(p.image || p.image_path || '');
            const tag    = tagConfig[p.tag] || { label: '', cls: '' };
            return `
            <div class="carousel-item-wrap reveal">
                <a href="/Task(1)/pages/product-details.php?id=${p.id}" class="home-product-card">
                    ${tag.label ? `<span class="hpc-tag ${tag.cls}">${tag.label}</span>` : ''}
                    <img src="${imgSrc}" alt="${p.name}" class="hpc-img" loading="lazy">
                    <div class="hpc-body">
                        <div class="hpc-name">${p.name}</div>
                        <div class="hpc-price">$${p.price}</div>
                    </div>
                </a>
            </div>`;
        }).join('');
    };

    // كل قسم يعرض 7 منتجات بالضبط
    // Best Sellers: أعلى 7 بالـ tag
    // New Arrivals: أحدث 7 بالـ tag
    // Explore: أي 7 منتجات (مرتبة عشوائياً من كل المنتجات)
    buildCarousel('best-sellers-track',  allProducts.filter(p => p.tag === 'best-seller').slice(0, 7));
    buildCarousel('new-arrivals-track',   allProducts.filter(p => p.tag === 'new').slice(0, 7));

    // Explore: خذ كل المنتجات، رتبها عشوائياً، وخذ أول 7
    const exploreProducts = [...allProducts]
        .sort(() => Math.random() - 0.5)
        .slice(0, 7);
    buildCarousel('other-products-track', exploreProducts);

    // تفعيل أزرار السهمين
    document.querySelectorAll('.section-carousel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const track = document.getElementById(btn.dataset.target);
            if (!track) return;
            const cardWidth = track.querySelector('.carousel-item-wrap')?.offsetWidth + 18 || 300;
            const dir = btn.classList.contains('prev-btn') ? -1 : 1;
            track.scrollBy({ left: dir * cardWidth * 2, behavior: 'smooth' });
        });
    });

    // تشغيل Scroll Reveal بعد بناء الكاردات
    requestAnimationFrame(() => {
        if (typeof window.initScrollReveal === 'function') window.initScrollReveal();
    });
}
window.renderHomeSections = renderHomeSections;