/**
 * js/wishlist.js — Wishlist يبقى في localStorage
 */

let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('wishlist-container');
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

// ── تصحيح مسار الصورة ────────────────────────────────────────
function fixWishlistImg(img) {
    if (!img) return '';
    if (img.startsWith('/Task(1)/')) return img;
    if (img.startsWith('../images/')) return img.replace('../images/', '/Task(1)/images/');
    if (img.startsWith('images/'))   return '/Task(1)/' + img;
    return img;
}

window.changeWishlistQty = (id, val) => {
    const input = document.getElementById('qty-' + id);
    if (!input) return;
    const v = parseInt(input.value) + val;
    if (v >= 1) input.value = v;
};

function renderWishlist() {
    const container = document.getElementById('wishlist-container');
    if (!container) return;

    wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

    if (!wishlist.length) {
        container.innerHTML = `
        <div class="col-12 text-center py-5 fade-in-up">
            <div style="font-size:5rem;">💔</div>
            <h3 class="mt-3 fw-bold" style="color:var(--text-color);">Your Wishlist is Empty</h3>
            <p class="mt-2" style="color:var(--placeholder-color);">Save your favorite products and come back anytime.</p>
            <a href="/Task(1)/pages/products.php" class="btn btn-success mt-3 px-4 py-2">🛍️ Browse Products</a>
        </div>`;
        return;
    }

    container.innerHTML = wishlist.map(p => {
        const imgSrc = fixWishlistImg(p.image_path || p.image || '');
        const price  = Number(p.price || 0);
        return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100 shadow border-0 position-relative">
                <button class="favorite-btn remove-fav" data-id="${p.id}" aria-label="Remove from wishlist">❤️</button>
                <a href="/Task(1)/pages/product-details.php?id=${p.id}" style="text-decoration:none;">
                    <img src="${imgSrc}" class="card-img-top product-image"
                         alt="${p.name}" loading="lazy">
                </a>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="mb-3">
                        <h5 class="fw-bold">${p.name}</h5>
                        <div class="price-box">
                            <span class="new-price fs-5 fw-bold">$${price.toFixed(2)}</span>
                        </div>
                    </div>
                    <div>
                        <div class="quantity-box mb-3 d-flex justify-content-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeWishlistQty('${p.id}',-1)">−</button>
                            <input type="number" value="1" id="qty-${p.id}"
                                   class="form-control quantity-input" style="width:60px;">
                            <button class="btn btn-outline-secondary btn-sm" onclick="changeWishlistQty('${p.id}',1)">+</button>
                        </div>
                        <button class="btn btn-success w-100 add-to-cart-wl" data-id="${p.id}">
                            🛒 Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    // Remove
    document.querySelectorAll('.remove-fav').forEach(btn => {
        btn.addEventListener('click', () => {
            wishlist = wishlist.filter(i => i.id != btn.dataset.id);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            if (typeof updateCounters === 'function') updateCounters();
            renderWishlist();
        });
    });

    // Add to Cart
    document.querySelectorAll('.add-to-cart-wl').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = parseInt(btn.dataset.id);
            const product = wishlist.find(i => i.id == id);
            if (!product) return;
            const input = document.getElementById('qty-' + id);
            const qty   = parseInt(input?.value || 1);
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const ex = cart.find(i => i.id == id);
            if (ex) ex.quantity += qty;
            else cart.push({ ...product, quantity: qty });
            localStorage.setItem('cart', JSON.stringify(cart));
            if (typeof refreshCartUI === 'function') refreshCartUI();
            if (typeof showToast    === 'function') showToast('Added to cart!', 'success');
            if (input) input.value = 1;
            // bounce
            const cb = document.querySelector('[data-bs-target="#cartSidebar"]');
            if (cb) { cb.classList.add('cart-bounce'); setTimeout(() => cb.classList.remove('cart-bounce'), 500); }
        });
    });

    if (typeof window.initScrollReveal === 'function') requestAnimationFrame(window.initScrollReveal);
}
