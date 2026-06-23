// 1. عند تحميل الصفحة، نشغل المهام الأساسية فوراً
document.addEventListener("DOMContentLoaded", () => {
    applySavedTheme();
    initializeTheme();
    updateCounters();
});

// 2. إدارة الثيم (الوضع المظلم/الفاتح)
function applySavedTheme() {
    const themeStyle = document.getElementById("theme-style");
    const themeToggle = document.getElementById("theme-toggle");
    if (!themeStyle) return;

    const savedTheme = localStorage.getItem("theme");
    
    // التحقق من الثيم المحفوظ وتطبيقه
    if (savedTheme === "dark") {
        themeStyle.removeAttribute("disabled"); // تشغيل الدارك مود
        document.body.classList.add("dark-mode");
        if (themeToggle) themeToggle.innerHTML = "☀️";
    } else {
        themeStyle.setAttribute("disabled", "true"); // إيقاف الدارك مود (الوضع الفاتح)
        document.body.classList.remove("dark-mode");
        if (themeToggle) themeToggle.innerHTML = "🌙";
    }
}

function initializeTheme() {
    const themeToggle = document.getElementById("theme-toggle");
    const themeStyle = document.getElementById("theme-style");

    if (!themeToggle || !themeStyle) return;

    // تعيين الأيقونة الابتدائية بناءً على حالة الملف الحالية
    themeToggle.innerHTML = themeStyle.hasAttribute("disabled") ? "🌙" : "☀️";

    themeToggle.onclick = () => {
        // إذا كان الدارك مود معطلاً (disabled موجود)، نقوم بتفعيله
        if (themeStyle.hasAttribute("disabled")) {
            themeStyle.removeAttribute("disabled");
            document.body.classList.add("dark-mode");
            localStorage.setItem("theme", "dark");
            themeToggle.innerHTML = "☀️";
        } else {
            // إذا كان الدارك مود يعمل، نقوم بتعطيله والعودة للوضع الفاتح
            themeStyle.setAttribute("disabled", "true");
            document.body.classList.remove("dark-mode");
            localStorage.setItem("theme", "light");
            themeToggle.innerHTML = "🌙";
        }
    };
}

// 3. إدارة العدادات (السلة والمفضلة)
// ملحوظة: هذه الدالة عامة (Global)، يمكنك مناداتها من أي ملف JS آخر مثل products.js
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
}

// 4. الدالة الموحدة لحساب الخصم (لتقليل التكرار)
function calculateDiscount(price) {
    const oldPrice = Math.round(price * 1.25);
    const discount = Math.round(((oldPrice - price) / oldPrice) * 100);
    return { oldPrice, discount };
}
