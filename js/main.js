// --- 1. محرك البيانات المركزي للسلة ---
function getCartData() {
    return JSON.parse(localStorage.getItem("cart")) || [];
}

function saveCart(updatedCart) {
    localStorage.setItem("cart", JSON.stringify(updatedCart));
    refreshCartUI();
}

// --- 2. دالة التنبيهات الموحدة (SweetAlert2) ---
function showToast(message, icon = 'success') {
    const isDark = document.body.classList.contains("dark-mode");
    Swal.fire({
        text: message,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: isDark ? '#1e2530' : '#ffffff',
        color:      isDark ? '#e6edf3' : '#1a1a2e',
        iconColor: icon === 'success' ? '#198754' : icon === 'error' ? '#dc3545' : '#0dcaf0'
    });
}

// --- 3. نظام التحديث التلقائي ---
function refreshCartUI() {
    updateCounters();
    renderCart();
}

function updateCounters() { 
    const cart = getCartData();
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badge = document.getElementById("cart-count");
    if(badge) badge.innerText = count;
}

function renderCart() {
    const cartContainer = document.getElementById("cart-items-list");
    const cartTotal = document.getElementById("cart-total");
    if (!cartContainer) return;

    let cart = getCartData();
    if (cart.length === 0) {
        cartContainer.innerHTML = `<li class="text-muted text-center py-5">Your cart is empty.</li>`;
        if(cartTotal) cartTotal.innerText = "$0.00";
    } else {
        let total = 0;
        cartContainer.innerHTML = cart.map(item => {
            total += item.price * item.quantity;
            return `
                <li class="mb-3 p-2 border-bottom border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/Task(1)/pages/product-details.php?id=${item.id}" class="text-white text-decoration-none fw-bold">${item.name}</a>
                        <button class="btn btn-sm btn-outline-danger remove-item" data-id="${item.id}" aria-label="Remove ${item.name} from cart">✖</button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small>$${item.price} each</small>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-light minus" data-id="${item.id}" aria-label="Decrease quantity">-</button>
                            <span class="px-2 fw-bold" aria-label="Quantity: ${item.quantity}">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-light plus" data-id="${item.id}" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                </li>`;
        }).join('');
        if(cartTotal) cartTotal.innerText = `$${total.toFixed(2)}`;
    }
}

// --- 4. إدارة التفاعل (Auth Modals & Cart) ---
document.addEventListener("DOMContentLoaded", () => {
    // مراقبة الضغطات في كامل الموقع للسلة
    document.addEventListener("click", (e) => {
        if (e.target.closest("#cart-items-list")) {
            const btn = e.target;
            const id = parseInt(btn.dataset.id);
            if (!id) return;

            let cart = getCartData();
            let item = cart.find(p => p.id === id);

            if (btn.classList.contains("plus")) {
                item.quantity++;
            } else if (btn.classList.contains("minus")) {
                if (item.quantity > 1) item.quantity--;
                else cart = cart.filter(p => p.id !== id);
            } else if (btn.classList.contains("remove-item")) {
                cart = cart.filter(p => p.id !== id);
                showToast('Product removed from cart', 'info');
            }
            saveCart(cart);
        }
    });

    refreshCartUI();
    const offcanvasEl = document.getElementById('cartSidebar');
    if (offcanvasEl) {
        offcanvasEl.addEventListener('show.bs.offcanvas', renderCart);
    }
});

// --- 5. دوال الـ Authentication والـ UI ---

// إظهار/إخفاء باسورد واحد (للوج إن)
window.togglePassword = function(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input && icon) {
        input.type = input.type === "password" ? "text" : "password";
        icon.innerText = input.type === "password" ? "👁️" : "🙈";
    }
};

// إظهار/إخفاء حقلين الباسورد معاً (للساين أب)
window.toggleBothPasswords = function(iconId) {
    const pass1 = document.getElementById('regPass');
    const pass2 = document.getElementById('regConfirmPass');
    const icon = document.getElementById(iconId);
    if (pass1 && pass2 && icon) {
        const isPassword = pass1.type === "password";
        pass1.type = isPassword ? "text" : "password";
        pass2.type = isPassword ? "text" : "password";
        icon.innerText = isPassword ? "🙈" : "👁️";
    }
};

// التحقق من بيانات الساين أب
window.validateSignUp = function(event) {
    event.preventDefault();
    const email = document.getElementById('regEmail').value;
    const pass = document.getElementById('regPass').value;
    const confirmPass = document.getElementById('regConfirmPass').value;
    const errorMsg = document.getElementById('errorMsg');
    
    errorMsg.style.display = 'none';

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const strongPassRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    if (!emailRegex.test(email)) {
        errorMsg.innerText = "Please enter a valid email address.";
        errorMsg.style.display = 'block';
    } else if (!strongPassRegex.test(pass)) {
        errorMsg.innerText = "Password must be at least 8 characters, include uppercase, lowercase, number, and special char.";
        errorMsg.style.display = 'block';
    } else if (pass !== confirmPass) {
        errorMsg.innerText = "Passwords do not match!";
        errorMsg.style.display = 'block';
    } else {
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        if (modal) modal.hide();
        showToast("Account created successfully!", "success");
    }
};

// --- 6. الحل الجذري لمشكلة padding-right (Modal + Offcanvas) ---
const fixBodyPadding = () => {
    document.body.style.paddingRight = '0';
    document.body.style.overflow = '';
};

// نحذف الـ backdrop القديم فقط بعد إغلاق الـ modal مش قبل فتحه
document.addEventListener('hidden.bs.modal', () => {
    document.body.classList.remove('modal-open');
    fixBodyPadding();
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
});

document.addEventListener('hidden.bs.offcanvas', () => {
    fixBodyPadding();
});