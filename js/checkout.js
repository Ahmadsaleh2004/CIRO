function initCheckout() {
    if (window.location.pathname.includes("checkout.php")) {
        renderCheckoutSummary();
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCheckout);
} else {
    initCheckout();
}

function renderCheckoutSummary() {
    const summary = document.getElementById("order-summary"); // أو cart-items-list حسب ID صفحتك
    if (!summary) return;

    const cart = getCartData();
    if (cart.length === 0) {
        summary.innerHTML = `<p class="text-muted text-center">No Products Found</p>`;
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
            // استخدام التنبيه الموحد الأنيق
            showToast("Order Placed Successfully!", "success");
            
            localStorage.removeItem("cart");
            setTimeout(() => {
                window.location.href = "/Task(1)/index.php";
            }, 2000);
        });
    }
}