// يتم استدعاء هذا الملف فقط من checkout.php — لا حاجة للتحقق من الـ pathname
document.addEventListener("DOMContentLoaded", renderCheckoutSummary);

function renderCheckoutSummary() {
    const summary = document.getElementById("order-summary");
    if (!summary) return;

    const cart = getCartData();

    // لو السلة فاضية — redirect لصفحة المنتجات
    if (cart.length === 0) {
        showToast("Your cart is empty! Add products first.", "error");
        setTimeout(() => {
            window.location.href = "/Task(1)/pages/products.php";
        }, 2000);
        return;
    }

    summary.innerHTML = `
        ${cart.map(item => `
            <div class="d-flex justify-content-between mb-2">
                <span>${item.name} x ${item.quantity}</span>
                <strong>$${(item.price * item.quantity).toFixed(2)}</strong>
            </div>
        `).join('')}
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <h4>Total:</h4>
            <h4 class="text-success">$${cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2)}</h4>
        </div>
    `;

    // معالجة نموذج الطلب (بما أننا في المحرك المركزي)
    const checkoutForm = document.getElementById("checkoutForm");
    if (checkoutForm) {
        checkoutForm.addEventListener("submit", (e) => {
            e.preventDefault();

            // Validation
            const name    = document.getElementById("name")?.value.trim();
            const email   = document.getElementById("email")?.value.trim();
            const phone   = document.getElementById("phone")?.value.trim();
            const address = document.getElementById("address")?.value.trim();

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^[0-9+\s\-]{7,15}$/;

            if (!name || name.length < 2) {
                showToast("Please enter your full name.", "error"); return;
            }
            if (!emailRegex.test(email)) {
                showToast("Please enter a valid email address.", "error"); return;
            }
            if (!phoneRegex.test(phone)) {
                showToast("Please enter a valid phone number.", "error"); return;
            }
            if (!address || address.length < 10) {
                showToast("Please enter a complete delivery address.", "error"); return;
            }

            showToast("Order Placed Successfully! 🎉", "success");
            localStorage.removeItem("cart");
            setTimeout(() => {
                window.location.href = "/Task(1)/index.php";
            }, 2000);
        });
    }
}