/**
 * js/auth.js
 * معالجة نماذج Login / Register / Forgot عبر AJAX
 * يعمل مع handlers/auth_handler.php
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Login ──────────────────────────────────────────────────
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn  = document.getElementById('loginBtn');
            const errEl = document.getElementById('loginError');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Signing in...';
            errEl.style.display = 'none';

            try {
                const res  = await fetch('/Task(1)/handlers/auth_handler.php', { method: 'POST', body: new FormData(loginForm) });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.href = data.redirect || '/Task(1)/index.php'; }, 700);
                } else {
                    errEl.textContent  = data.message;
                    errEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Sign In';
                    refreshCsrfTokens();
                }
            } catch {
                errEl.textContent  = 'Connection error. Please try again.';
                errEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Sign In';
            }
        });
    }

    // ── Register ───────────────────────────────────────────────
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn   = document.getElementById('regBtn');
            const errEl = document.getElementById('regError');
            errEl.style.display = 'none';

            // Client quick-check
            const pass    = document.getElementById('regPass')?.value;
            const confirm = document.getElementById('regConfirmPass')?.value;
            const privacy = document.getElementById('privacyCheck')?.checked;
            const gender  = document.getElementById('regGender')?.value;

            if (pass !== confirm) { errEl.textContent = 'Passwords do not match!'; errEl.style.display = 'block'; return; }
            if (!gender)          { errEl.textContent = 'Please select your gender.'; errEl.style.display = 'block'; return; }
            if (!privacy)         { errEl.textContent = 'You must agree to the Privacy Policy.'; errEl.style.display = 'block'; return; }

            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';

            // دمج كود الدولة مع رقم الهاتف
            const code  = document.getElementById('phoneCountryCode')?.value || '';
            const local = document.getElementById('regPhoneLocal')?.value    || '';
            const phoneInput = signupForm.querySelector('input[name="phone"]');
            if (phoneInput && local) phoneInput.value = code + local;

            try {
                const res  = await fetch('/Task(1)/handlers/auth_handler.php', { method: 'POST', body: new FormData(signupForm) });
                const data = await res.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                    showToast(data.message, 'success');
                    signupForm.reset();
                    refreshCsrfTokens();
                    setTimeout(() => new bootstrap.Modal(document.getElementById('loginModal')).show(), 700);
                } else {
                    errEl.textContent  = data.message;
                    errEl.style.display = 'block';
                    btn.disabled  = false;
                    btn.innerHTML = 'Create Account';
                    refreshCsrfTokens();
                }
            } catch {
                errEl.textContent  = 'Connection error.';
                errEl.style.display = 'block';
                btn.disabled  = false;
                btn.innerHTML = 'Create Account';
            }
        });
    }

    // ── Forgot ─────────────────────────────────────────────────
    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('forgotMsg');
            try {
                const res  = await fetch('/Task(1)/handlers/auth_handler.php', { method: 'POST', body: new FormData(forgotForm) });
                const data = await res.json();
                msgEl.textContent = data.message;
                msgEl.className   = `alert py-2 small mb-3 alert-${data.success ? 'success' : 'danger'}`;
                msgEl.style.display = 'block';
            } catch {
                msgEl.textContent   = 'Connection error.';
                msgEl.className     = 'alert py-2 small mb-3 alert-danger';
                msgEl.style.display = 'block';
            }
        });
    }

});

// ── تجديد CSRF Token ─────────────────────────────────────────
async function refreshCsrfTokens() {
    try {
        const res  = await fetch('/Task(1)/handlers/get_csrf.php');
        const data = await res.json();
        document.querySelectorAll('input[name="csrf_token"]').forEach(el => el.value = data.token);
    } catch { /* silent */ }
}

// ── Logout ────────────────────────────────────────────────────
window.logoutUser = async function () {
    const fd = new FormData();
    fd.append('action', 'logout');
    await fetch('/Task(1)/handlers/auth_handler.php', { method: 'POST', body: fd });
    window.location.href = '/Task(1)/index.php';
};

// ── Password Toggles ─────────────────────────────────────────
window.togglePassword = function (inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;
    input.type     = input.type === 'password' ? 'text' : 'password';
    icon.innerText = input.type === 'password' ? '👁️' : '🙈';
};

window.toggleBothPasswords = function (iconId) {
    const p1   = document.getElementById('regPass');
    const p2   = document.getElementById('regConfirmPass');
    const icon = document.getElementById(iconId);
    if (!p1 || !p2 || !icon) return;
    const show = p1.type === 'password';
    p1.type = p2.type = show ? 'text' : 'password';
    icon.innerText    = show ? '🙈' : '👁️';
};

// ── validateSignUp (legacy — kept for safety) ─────────────────
window.validateSignUp = function (e) { e.preventDefault(); };
