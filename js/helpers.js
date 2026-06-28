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

// 4. الدالة الموحدة لحساب الخصم
function calculateDiscount(price) {
    const oldPrice = Math.round(price * 1.25);
    const discount = Math.round(((oldPrice - price) / oldPrice) * 100);
    return { oldPrice, discount };
}

// 5. Back to Top زرار
function initBackToTop() {
    // إنشاء الزرار
    const btn = document.createElement("button");
    btn.id = "back-to-top";
    btn.title = "Back to top";
    btn.innerHTML = "↑";
    document.body.appendChild(btn);

    window.addEventListener("scroll", () => {
        btn.classList.toggle("visible", window.scrollY > 350);
    });

    btn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
}

// 6. Scroll Reveal — عناصر تظهر بـ animation لما تتسكرول إليها
function initScrollReveal() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    // نراقب فقط العناصر اللي عندها reveal وlم تتفعل بعد
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

// 8. Page Transitions — overlay fade عند الانتقال بين الصفحات
function initPageTransitions() {
    // إنشاء الـ overlay
    const overlay = document.createElement("div");
    overlay.id = "page-overlay";
    document.body.appendChild(overlay);

    // fade out الـ overlay عند تحميل الصفحة (entering)
    requestAnimationFrame(() => {
        overlay.classList.remove("active");
    });

    // fade in الـ overlay عند الضغط على أي رابط داخلي
    document.addEventListener("click", (e) => {
        const link = e.target.closest("a[href]");
        if (!link) return;

        const href = link.getAttribute("href");

        // نتجاهل الروابط الخارجية، anchors، modal triggers، external links
        if (!href ||
            href.startsWith("#") ||
            href.startsWith("http") ||
            href.startsWith("mailto") ||
            href.startsWith("tel") ||
            link.hasAttribute("data-bs-toggle") ||
            link.hasAttribute("data-bs-target") ||
            link.target === "_blank") return;

        e.preventDefault();

        // أظهر الـ overlay
        overlay.classList.add("active");

        // انتقل للصفحة بعد انتهاء الـ animation
        setTimeout(() => {
            window.location.href = href;
        }, 240);
    });

    // لو المستخدم ضغط Back — اعمل fade in
    window.addEventListener("pageshow", () => {
        overlay.classList.remove("active");
    });
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
