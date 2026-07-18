// ══════════════════════════════════════════════════════════════
// helpers.js — الدوال المساعدة العامة للتطبيق
// ══════════════════════════════════════════════════════════════

// ── CSRF: تحديث كل حقول التوكن بالصفحة دفعة واحدة ─────────────
/**
 * updateCsrfToken(newToken)
 * تُحدّث كل input[name="csrf_token"] بالصفحة بالتوكن الجديد.
 * استدعِها بعد كل AJAX POST ناجح يُرجع csrf_token.
 */
function updateCsrfToken(newToken) {
    if (!newToken) return;
    document.querySelectorAll('input[name="csrf_token"]').forEach(el => {
        el.value = newToken;
    });
    // خزّن التوكن الحالي للاستخدام الداخلي
    window._csrfToken = newToken;
}

/**
 * fetchWithCsrfRetry(url, options)
 * Wrapper لـ fetch() يعيد المحاولة تلقائياً مرة واحدة
 * إذا فشل الطلب بسبب "Invalid CSRF token".
 *
 * الاستخدام:
 *   const data = await fetchWithCsrfRetry('/handlers/foo.php', {
 *       method: 'POST',
 *       body: formData
 *   });
 */
async function fetchWithCsrfRetry(url, options = {}, _retried = false) {
    const response = await fetch(url, options);
    const data     = await response.json();

    // نحدّث التوكن في كل حالة إذا وُجد بالـ response
    if (data.csrf_token) {
        updateCsrfToken(data.csrf_token);
    }

    // إذا فشل بسبب CSRF وهذه المحاولة الأولى → نجلب توكن جديد ونعيد
    if (!data.success && data.message === 'Invalid CSRF token.' && !_retried) {
        try {
            const csrfRes = await fetch('/Task(1)/handlers/get_csrf.php');
            const csrfData = await csrfRes.json();
            const newToken = csrfData.token;
            if (!newToken) throw new Error('No token received');

            updateCsrfToken(newToken);

            // أعد بناء options.body بالتوكن الجديد
            const newOptions = { ...options };
            if (options.body instanceof FormData) {
                const newBody = new FormData();
                for (const [key, val] of options.body.entries()) {
                    newBody.append(key, key === 'csrf_token' ? newToken : val);
                }
                newOptions.body = newBody;
            } else if (typeof options.body === 'string') {
                const params = new URLSearchParams(options.body);
                params.set('csrf_token', newToken);
                newOptions.body = params.toString();
            }

            return fetchWithCsrfRetry(url, newOptions, true);
        } catch (e) {
            console.error('CSRF Retry failed:', e);
        }
    }

    return data;
}

// نُصدّر الدوال للاستخدام العالمي
window.updateCsrfToken     = updateCsrfToken;
window.fetchWithCsrfRetry  = fetchWithCsrfRetry;

// ── Disabled Button System (المرحلة 5) ─────────────────────────
/**
 * updateButtonState(buttonEl, isValid)
 * تُضيف/تُزيل .btn-disabled-faded + disabled attribute حسب isValid.
 */
function updateButtonState(buttonEl, isValid) {
    if (!buttonEl) return;
    if (isValid) {
        buttonEl.classList.remove('btn-disabled-faded');
        buttonEl.removeAttribute('disabled');
    } else {
        buttonEl.classList.add('btn-disabled-faded');
        buttonEl.setAttribute('disabled', 'true');
    }
}
window.updateButtonState = updateButtonState;

// 1. عند تحميل الصفحة، نشغل المهام الأساسية فوراً
document.addEventListener("DOMContentLoaded", () => {
    applySavedTheme();
    initializeTheme();
    updateCounters();
    initBackToTop();
    highlightNavIcons();
    initPageTransitions();
    initImageFallbacks();
    // initScrollReveal يتشتغل بعد ما products.js يبني الكاردات
});

// 2. إدارة الثيم (الوضع المظلم/الفاتح)
function applySavedTheme() {
    const themeStyle = document.getElementById("theme-style");
    const themeToggle = document.getElementById("theme-toggle");
    if (!themeStyle) return;

    const savedTheme = localStorage.getItem("theme");
    
    if (savedTheme === "dark") {
        themeStyle.removeAttribute("disabled");
        document.body.classList.add("dark-mode");
        if (themeToggle) themeToggle.innerHTML = "☀️";
    } else {
        themeStyle.setAttribute("disabled", "true");
        document.body.classList.remove("dark-mode");
        if (themeToggle) themeToggle.innerHTML = "🌙";
    }
}

function initializeTheme() {
    const themeToggle = document.getElementById("theme-toggle");
    const themeStyle = document.getElementById("theme-style");

    if (!themeToggle || !themeStyle) return;

    themeToggle.innerHTML = themeStyle.hasAttribute("disabled") ? "🌙" : "☀️";

    themeToggle.onclick = () => {
        if (themeStyle.hasAttribute("disabled")) {
            themeStyle.removeAttribute("disabled");
            document.body.classList.add("dark-mode");
            localStorage.setItem("theme", "dark");
            themeToggle.innerHTML = "☀️";
        } else {
            themeStyle.setAttribute("disabled", "true");
            document.body.classList.remove("dark-mode");
            localStorage.setItem("theme", "light");
            themeToggle.innerHTML = "🌙";
        }
    };
}

// 3. إدارة العدادات (السلة والمفضلة)
function updateCounters() {
    const wishlistCount = document.getElementById("wishlist-count");
    const cartCount = document.getElementById("cart-count");

    const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
    const cart = JSON.parse(localStorage.getItem("cart")) || [];

    if (wishlistCount) {
        wishlistCount.textContent = wishlist.length;
    }

    if (cartCount) {
        const total = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        cartCount.textContent = total;
    }

    highlightNavIcons();
}


// 5. Back to Top زرار
function initBackToTop() {
    // إنشاء الزرار
    const btn = document.createElement("button");
    btn.id = "back-to-top";
    btn.title = "Back to top";
    btn.innerHTML = "↑";
    document.body.appendChild(btn);

    // ── Scroll Throttle (المرحلة 3) — requestAnimationFrame ──
    let scrollTicking = false;
    window.addEventListener("scroll", () => {
        if (!scrollTicking) {
            requestAnimationFrame(() => {
                btn.classList.toggle("visible", window.scrollY > 350);
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    });

    btn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
}

// 6. Scroll Reveal — عناصر تظهر بـ animation مع Stagger متتالي
function initScrollReveal() {
    let delay = 0;
    let lastTime = 0;
    const observer = new IntersectionObserver((entries) => {
        const sortedEntries = [...entries].sort((a, b) => {
            const rectA = a.target.getBoundingClientRect();
            const rectB = b.target.getBoundingClientRect();
            return (rectA.top - rectB.top) || (rectA.left - rectB.left);
        });

        sortedEntries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const now = performance.now();
                if (now - lastTime > 150) {
                    delay = 0;
                }
                lastTime = now;

                el.style.transitionDelay = `${delay}ms`;
                el.classList.add("visible");
                delay += 60; // 60ms stagger
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll(".reveal:not(.visible)").forEach(el => {
        observer.observe(el);
    });
}

// نصدّر الدالة عشان products.js يقدر يستدعيها بعد بناء الكاردات
window.initScrollReveal = initScrollReveal;

// 7. Navbar — highlight الـ wishlist/cart icon لو في صفحتهم
function highlightNavIcons() {
    const path = window.location.pathname;
    const wishlistBtn = document.querySelector('a[href*="wishlist.php"]');
    const cartBtn     = document.querySelector('[data-bs-target="#cartSidebar"]');

    const cart     = JSON.parse(localStorage.getItem("cart"))     || [];
    const wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

    if (wishlistBtn) {
        wishlistBtn.classList.toggle("navbar-icon-active", path.includes("wishlist") || wishlist.length > 0);
    }
    if (cartBtn) {
        cartBtn.classList.toggle("navbar-icon-active", cart.length > 0);
    }
}

// 8. Page Transitions — Professional slide + blur + loading bar
function initPageTransitions() {
    // إنشاء الـ overlay
    const overlay = document.createElement("div");
    overlay.id = "page-overlay";
    document.body.appendChild(overlay);

    // إنشاء loading bar أعلى الصفحة
    const bar = document.createElement("div");
    bar.id = "page-progress-bar";
    document.body.appendChild(bar);

    // fade in عند تحميل الصفحة
    requestAnimationFrame(() => {
        overlay.classList.remove("active");
        bar.classList.remove("loading");
    });

    document.addEventListener("click", (e) => {
        const link = e.target.closest("a[href]");
        if (!link) return;

        const href = link.getAttribute("href");

        if (!href) return;
        if (href.startsWith("#")) return;
        if (href.startsWith("http")) return;
        if (href.startsWith("mailto")) return;
        if (href.startsWith("tel")) return;
        if (href.startsWith("javascript")) return;
        if (link.hasAttribute("data-bs-toggle")) return;
        if (link.hasAttribute("data-bs-target")) return;
        if (link.hasAttribute("data-bs-dismiss")) return;
        if (link.target === "_blank") return;
        if (document.querySelector(".modal.show")) return;
        if (document.querySelector(".offcanvas.show")) return;

        e.preventDefault();

        // أظهر الـ loading bar وبعدين الـ overlay
        bar.classList.add("loading");
        setTimeout(() => overlay.classList.add("active"), 80);

        setTimeout(() => {
            window.location.href = href;
        }, 280);
    });

    window.addEventListener("pageshow", () => {
        overlay.classList.remove("active");
        bar.classList.remove("loading");
    });

    document.addEventListener("show.bs.modal",    () => overlay.classList.remove("active"));
    document.addEventListener("show.bs.offcanvas", () => overlay.classList.remove("active"));
}

// 9. Image Fallback — لو صورة مكسورة تظهر placeholder
function initImageFallbacks() {
    const fallbackSrc = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' font-size='14' fill='%239ca3af' text-anchor='middle' dy='.3em'%3EImage not found%3C/text%3E%3C/svg%3E";

    // نراقب كل الصور في الصفحة
    document.querySelectorAll("img").forEach(img => addFallback(img));

    // نراقب الصور اللي بتتضاف لاحقاً (dynamic content)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    if (node.tagName === "IMG") addFallback(node);
                    node.querySelectorAll?.("img").forEach(img => addFallback(img));
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    function addFallback(img) {
        if (img.dataset.fallbackSet) return;
        img.dataset.fallbackSet = "1";
        img.addEventListener("error", () => {
            img.src = fallbackSrc;
            img.classList.add("img-error");
            img.alt = img.alt || "Image not available";
        });
    }
}

// ── Confirm dialog system (Phase 25) ─────────────────────────
function confirmAction(title, text, type = 'warning', callback) {
    Swal.fire({
        title: title,
        text: text,
        icon: type,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, confirm',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}
window.confirmAction = confirmAction;

// ── filterStatus — shared across admin pages ───────────────────
/**
 * filterStatus(value)
 * Sets ?status=value in URL and reloads, removing page param.
 * Used by manage-orders.php and manage-users.php
 */
function filterStatus(value) {
    var p = new URLSearchParams(window.location.search);
    if (value) p.set('status', value);
    else       p.delete('status');
    p.delete('page');
    window.location.href = '?' + p.toString();
}
window.filterStatus = filterStatus;

// ── Global Loading Spinner (Phase 25) ─────────────────────────
function showLoading() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100vw';
        overlay.style.height = '100vh';
        overlay.style.background = 'rgba(0, 0, 0, 0.4)';
        overlay.style.backdropFilter = 'blur(3px)';
        overlay.style.zIndex = '9999';
        overlay.style.display = 'flex';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        overlay.innerHTML = '<div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Loading...</span></div>';
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}
window.showLoading = showLoading;
window.hideLoading = hideLoading;

// تشغيل الـ loading تلقائياً عند تقديم أي فورمة غير مستثناة
document.addEventListener('submit', (e) => {
    if (!e.defaultPrevented && !e.target.classList.contains('search-form') && !e.target.classList.contains('no-spinner')) {
        showLoading();
    }
});

