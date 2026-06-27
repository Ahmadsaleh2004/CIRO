// 1. عند تحميل الصفحة، نشغل المهام الأساسية فوراً
document.addEventListener("DOMContentLoaded", () => {
    applySavedTheme();
    initializeTheme();
    updateCounters();
    initBackToTop();
    highlightNavIcons();
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
