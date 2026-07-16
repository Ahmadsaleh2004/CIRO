/**
 * js/auth.js
 * معالجة نماذج Login / Register / Forgot عبر AJAX
 * يعمل مع handlers/auth_handler.php
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── دالة حساب السن بدقة ──────────────────────────────────────────
    function calculateAge(birthDateString) {
        if (!birthDateString) return 0;
        const today = new Date();
        const birthDate = new Date(birthDateString);
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // ── أطوال أرقام الهواتف المحلية المسموحة حسب الدولة ───────────────
    const countryPhoneLengths = {
        '+962': [9],      // الأردن
        '+20':  [10],     // مصر
        '+966': [9],      // السعودية
        '+971': [9],      // الإمارات
        '+1':   [10],     // أمريكا
        '+44':  [10],     // بريطانيا
        '+90':  [10],     // تركيا
        '+49':  [10, 11]  // ألمانيا
    };

    // ── Login Validation ───────────────────────────────────────
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        const loginEmail = document.getElementById('loginEmail');
        const loginPass  = document.getElementById('loginPass');
        const loginBtn   = document.getElementById('loginBtn');

        function checkLoginFormValidity() {
            const isEmailOk = loginEmail && loginEmail.value.trim() !== '';
            const isPassOk  = loginPass && loginPass.value !== '';
            updateButtonState(loginBtn, isEmailOk && isPassOk);
        }

        [loginEmail, loginPass].forEach(el => {
            if (el) {
                el.addEventListener('input', checkLoginFormValidity);
                el.addEventListener('change', checkLoginFormValidity);
            }
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn  = document.getElementById('loginBtn');
            const errEl = document.getElementById('loginError');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Signing in...';
            errEl.style.display = 'none';

            try {
                const data = await fetchWithCsrfRetry(
                    '/Task(1)/handlers/auth_handler.php',
                    { method: 'POST', body: new FormData(loginForm) }
                );

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.href = data.redirect || '/Task(1)/index.php'; }, 700);
                } else {
                    errEl.textContent  = data.message;
                    errEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Sign In';
                    checkLoginFormValidity();
                }
            } catch {
                errEl.textContent  = 'Connection error. Please try again.';
                errEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Sign In';
                checkLoginFormValidity();
            }
        });

        // تشغيل أولي
        checkLoginFormValidity();
    }

    // ── Register Validation ────────────────────────────────────
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        const regName          = document.getElementById('regName');
        const regEmail         = document.getElementById('regEmail');
        const regPass          = document.getElementById('regPass');
        const regConfirmPass   = document.getElementById('regConfirmPass');
        const phoneCountryCode = document.getElementById('phoneCountryCode');
        const regPhoneLocal    = document.getElementById('regPhoneLocal');
        const regGender        = document.getElementById('regGender');
        const regBirthDate     = document.getElementById('regBirthDate');
        const regCountry       = document.getElementById('regCountry');
        const regCity          = document.getElementById('regCity');
        const privacyCheck     = document.getElementById('privacyCheck');
        const regBtn           = document.getElementById('regBtn');

        function checkSignupFormValidity() {
            const isNameOk      = regName && regName.value.trim().length >= 2;
            // الإيميل ينتهي بـ @gmail.com حصراً
            const isEmailOk     = regEmail && /^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(regEmail.value.trim());
            const isPassOk      = regPass && regPass.value.length >= 8;
            const isConfirmOk   = regConfirmPass && regConfirmPass.value === regPass.value;
            
            // التحقق من رقم الهاتف حسب الدولة
            const code          = phoneCountryCode ? phoneCountryCode.value : '';
            const localPhone    = regPhoneLocal ? regPhoneLocal.value.trim() : '';
            const allowedLens   = countryPhoneLengths[code] || [7, 8, 9, 10, 11, 12];
            const isPhoneOk     = localPhone.length > 0 && allowedLens.includes(localPhone.length) && /^\d+$/.test(localPhone);
            
            const isGenderOk    = regGender && regGender.value !== '';
            // السن يجب أن يكون 13 سنة على الأقل
            const isBirthOk     = regBirthDate && regBirthDate.value !== '' && calculateAge(regBirthDate.value) >= 13;
            const isCountryOk   = regCountry && regCountry.value.trim() !== '';
            const isCityOk      = regCity && regCity.value.trim() !== '';
            const isPrivacyOk   = privacyCheck && privacyCheck.checked;

            const isValid = isNameOk && isEmailOk && isPassOk && isConfirmOk && isPhoneOk && isGenderOk && isBirthOk && isCountryOk && isCityOk && isPrivacyOk;
            updateButtonState(regBtn, isValid);
        }

        // تسجيل دالة الصلاحيات على النطاق العالمي لتمكين استدعائها من مودال سياسة الخصوصية
        window.checkSignupFormValidity = checkSignupFormValidity;

        [regName, regEmail, regPass, regConfirmPass, phoneCountryCode, regPhoneLocal, regGender, regBirthDate, regCountry, regCity, privacyCheck].forEach(el => {
            if (el) {
                el.addEventListener('input', checkSignupFormValidity);
                el.addEventListener('change', checkSignupFormValidity);
            }
        });

        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn   = document.getElementById('regBtn');
            const errEl = document.getElementById('regError');
            errEl.style.display = 'none';

            // دمج كود الدولة مع رقم الهاتف المكتوب قبل الإرسال
            const code  = phoneCountryCode?.value || '';
            const local = regPhoneLocal?.value    || '';
            const phoneInput = signupForm.querySelector('input[name="phone"]');
            if (phoneInput && local) phoneInput.value = code + local;

            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';

            try {
                const data = await fetchWithCsrfRetry(
                    '/Task(1)/handlers/auth_handler.php',
                    { method: 'POST', body: new FormData(signupForm) }
                );

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                    showToast(data.message, 'success');
                    signupForm.reset();
                    setTimeout(() => new bootstrap.Modal(document.getElementById('loginModal')).show(), 700);
                } else {
                    errEl.textContent  = data.message;
                    errEl.style.display = 'block';
                    btn.disabled  = false;
                    btn.innerHTML = 'Create Account';
                    checkSignupFormValidity();
                }
            } catch {
                errEl.textContent  = 'Connection error.';
                errEl.style.display = 'block';
                btn.disabled  = false;
                btn.innerHTML = 'Create Account';
                checkSignupFormValidity();
            }
        });

        // تشغيل أولي
        checkSignupFormValidity();
    }

    // ── Forgot Validation ──────────────────────────────────────
    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        const forgotEmail = document.getElementById('forgotEmail');
        const forgotBtn   = document.getElementById('forgotBtn');

        function checkForgotFormValidity() {
            const isEmailOk = forgotEmail && forgotEmail.value.trim() !== '';
            updateButtonState(forgotBtn, isEmailOk);
        }

        if (forgotEmail) {
            forgotEmail.addEventListener('input', checkForgotFormValidity);
            forgotEmail.addEventListener('change', checkForgotFormValidity);
        }

        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('forgotMsg');
            forgotBtn.disabled = true;

            try {
                const data = await fetchWithCsrfRetry(
                    '/Task(1)/handlers/auth_handler.php',
                    { method: 'POST', body: new FormData(forgotForm) }
                );
                msgEl.textContent = data.message;
                msgEl.className   = `alert py-2 small mb-3 alert-${data.success ? 'success' : 'danger'}`;
                msgEl.style.display = 'block';
            } catch {
                msgEl.textContent   = 'Connection error.';
                msgEl.className     = 'alert py-2 small mb-3 alert-danger';
                msgEl.style.display = 'block';
            } finally {
                forgotBtn.disabled = false;
                checkForgotFormValidity();
            }
        });

        // تشغيل أولي
        checkForgotFormValidity();
    }

});

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
