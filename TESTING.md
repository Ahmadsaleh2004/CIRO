# TESTING.md — CIRO Store — Test Cases كاملة

## ترتيب التثبيت قبل الاختبار
1. إنشاء قاعدة البيانات: `mysql -u root -p < config/schema.sql`
2. ضبط كلمة سر الأدمن: `php setup/seed_admin_password.php`
3. ترحيل المنتجات: `php setup/migrate.php`
4. تأكد إعدادات `config/db.php` صحيحة (DB_USER, DB_PASS)

---

## 1. اختبارات العرض العام (Visitor)

| # | الصفحة | المتوقع |
|---|--------|---------|
| V1 | `/Task(1)/index.php` | تُحمَّل المنتجات من DB في الـ Slider والـ Carousels |
| V2 | `/Task(1)/pages/products.php` | تظهر كل المنتجات بمؤشر المخزون والسعر بعد الخصم |
| V3 | Search في products | Autocomplete يظهر أول 5 نتائج |
| V4 | Price Range Slider | يُفلتر المنتجات لحظياً |
| V5 | Sort A-Z / by price | الترتيب يعمل بشكل صحيح |
| V6 | `/Task(1)/pages/product-details.php?id=1` | يعرض تفاصيل المنتج والتقييمات |
| V7 | Zoom hover على صورة المنتج | تكبير سلس |
| V8 | زر Cart للزائر | **يجب أن يكون مخفياً تماماً** |
| V9 | زر Wishlist | يعمل ويُخزّن في localStorage |
| V10 | Login Modal | يفتح بشكل صحيح |
| V11 | `/Task(1)/pages/checkout.php` (زائر) | يُعيد توجيه لـ login=required |
| V12 | `/Task(1)/pages/aboutus.php` | يعرض بيانات من website_settings |
| V13 | `/Task(1)/pages/contactus.php` | تُحفظ الرسالة في contact_messages |
| V14 | Dark/Light mode toggle | يعمل ويُحفظ في localStorage |
| V15 | Navbar blur بعد 50px scroll | يعمل بسلاسة |

---

## 2. اختبارات المستخدم المسجّل (User)

| # | العملية | المتوقع |
|---|---------|---------|
| U1 | Register بإيميل جديد | ينجح التسجيل + redirect للـ Login Modal |
| U2 | Register بإيميل مكرر | رسالة خطأ واضحة |
| U3 | Login صحيح | redirect لـ index.php + ظهور اسم المستخدم |
| U4 | Login خاطئ (5+ مرات) | Rate Limiting يمنع المحاولات لـ 10 دقائق |
| U5 | زر Cart | يظهر بعد تسجيل الدخول |
| U6 | Add to Cart من products | يُضاف للـ localStorage + bounce animation |
| U7 | Checkout خطوة 1 | يعرض العناوين المحفوظة |
| U8 | Checkout خطوة 3 → Place Order | ينشئ طلب في orders + يُفرّغ الـ cart |
| U9 | `/Task(1)/pages/order-confirmation.php?id=X` | يعرض ملخص الطلب |
| U10 | My Info → Personal Info | تعديل البيانات يتطلب كلمة السر |
| U11 | My Info → My Orders | تظهر الطلبات بعد U8 |
| U12 | My Info → Saved Addresses | إضافة/حذف/تعيين افتراضي |
| U13 | Notify Me (منتج مخزون=0) | يُسجّل في stock_notifications |
| U14 | إضافة تقييم لمنتج | يُسجّل في product_reviews |
| U15 | تعديل تقييم سابق | يُحدّث السجل الموجود (لا يُضيف جديد) |
| U16 | Session timeout بعد 30 دق خمول | تسجيل خروج تلقائي |
| U17 | Wishlist | يبقى في localStorage (غير مرتبط بـ DB) |

---

## 3. اختبارات الأدمن Role A (Super Admin)

| # | العملية | المتوقع |
|---|---------|---------|
| A1 | Login بـ ahmadsaleh9688@gmail.com | redirect لـ dashboard.php |
| A2 | Dashboard | تظهر الإحصائيات والرسوم البيانية |
| A3 | Navbar (admin panel) | تظهر كل الروابط (Admins/Dashboard/Products/Users/Support/Orders/Site Configuration) |
| A4 | Manage Admin | إضافة أدمن جديد + يظهر في القائمة |
| A5 | حذف أدمن | يتطلب re-auth + يرفض حذف آخر أدمن |
| A6 | تعديل صلاحيات أدمن | يُحفظ في admin_permissions |
| A7 | Products → بطاقة + | تفتح add-product.php |
| A8 | Add Product | يظهر فوراً في صفحة المنتجات + سطر في admin_audit_log |
| A9 | Edit Product | يُحدّث البيانات + stock_quantity + سطر في admin_audit_log |
| A10 | Delete Product | يُحذف مع Confirm Dialog + سطر في admin_audit_log |
| A11 | Site Configuration | التغييرات تنعكس على الـ Footer وAbout وContact |
| A12 | Manage Users → Export CSV | يُنزّل ملف CSV |
| A13 | Support | تظهر رسائل Contact Us |
| A14 | Manage Orders → Change Status | يُحدّث الـ status + سطر في admin_audit_log |
| A15 | Manage Orders → Export CSV | يُنزّل ملف CSV |
| A16 | Backup DB | يُنشئ ملف .sql في backups/ |
| A17 | كل العمليات الحساسة | تُسجَّل في admin_audit_log (تحقق من error.log إن لم تُسجَّل) |

---

## 4. اختبارات الأدمن Role B/C/D (صلاحيات محدودة)

> Role B: can_view_dashboard + can_manage_products فقط

| # | العملية | المتوقع |
|---|---------|---------|
| B1 | `/Task(1)/admin/dashboard.php` | ✅ مسموح |
| B2 | `/Task(1)/admin/manage-admins.php` | ❌ 403 |
| B3 | `/Task(1)/admin/manage-users.php` | ❌ 403 |
| B4 | `/Task(1)/admin/support.php` | ❌ 403 |
| B5 | `/Task(1)/admin/manage-orders.php` | ❌ 403 |
| B6 | `/Task(1)/admin/site-settings.php` | ❌ 403 |
| B7 | Products → زر + وزر حذف | ✅ يظهر (can_manage_products) |
| B8 | Navbar | لا يظهر رابط Admins أو Users |

> Role C: can_manage_support + can_manage_orders فقط

| # | العملية | المتوقع |
|---|---------|---------|
| C1 | `/Task(1)/admin/support.php` | ✅ مسموح |
| C2 | `/Task(1)/admin/manage-orders.php` | ✅ مسموح |
| C3 | `/Task(1)/admin/dashboard.php` | ❌ 403 |
| C4 | `/Task(1)/admin/manage-admins.php` | ❌ 403 |

---

## 5. اختبارات الأمان

| # | الاختبار | المتوقع |
|---|---------|---------|
| S1 | CSRF: إرسال form بدون csrf_token | 403 |
| S2 | وصول مباشر لـ `/Task(1)/handlers/auth_handler.php` بدون POST | 400 |
| S3 | وصول مباشر لـ `/Task(1)/config/db.php` | 403 (htaccess) |
| S4 | وصول مباشر لـ `/Task(1)/admin/dashboard.php` كزائر | redirect لـ index.php |
| S5 | 5 محاولات login خاطئة خلال 10 دقائق | Rate Limit مع رسالة الوقت المتبقي |
| S6 | XSS: إدخال `<script>alert(1)</script>` في name | يُعرض كنص (htmlspecialchars) |
| S7 | SQL Injection في search | Prepared Statements تمنعها |
| S8 | وصول `/Task(1)/backups/` من المتصفح | 403 |

---

## 6. اختبارات admin_audit_log

| العملية | action المتوقع |
|---------|----------------|
| إضافة أدمن | `add_admin` |
| حذف أدمن | `delete_admin` |
| تعديل صلاحيات | `update_permissions` |
| إضافة منتج | `add_product` |
| تعديل منتج | `update_product` |
| حذف منتج | `delete_product` |
| حذف مستخدم | `delete_user` |
| تغيير حالة طلب | `change_order_status` |
| تغيير رتبة A | `change_role` |

---

## 7. التحقق من اكتمال التثبيت (Checklist)

```
[ ] schema.sql نُفّذ بنجاح (14 جدول)
[ ] setup/seed_admin_password.php شغّل مرة واحدة
[ ] setup/migrate.php شغّل وظهرت المنتجات في DB
[ ] config/db.php يحتوي بيانات الاتصال الصحيحة
[ ] مجلد images/ قابل للكتابة (chmod 755)
[ ] مجلد backups/ موجود أو سيُنشأ تلقائياً
[ ] php.ini: upload_max_filesize >= 5M
[ ] Apache mod_rewrite مُفعَّل
```

---

## 8. اختبارات إصلاحات الأخطاء الحرجة (بعد التعديلات)

### 8.1 إصلاح صفحة بيضاء بعد Edit/Add Product

| # | الخطوة | المتوقع |
|---|--------|---------|
| F1 | Admin → Products → Edit على أي منتج → تعديل اسم → Save | يظهر رسالة نجاح ✅ + لا تظهر صفحة بيضاء |
| F2 | Admin → Products → Add Product → ملء الحقول → Save | يُضاف المنتج + يُعاد توجيه لـ products.php |
| F3 | Admin → Manage Users → حذف مستخدم | يُحذف + يظهر رسالة نجاح |
| F4 | Admin → Manage Orders → تغيير حالة طلب | يُغيّر الحالة + يظهر رسالة نجاح |
| F5 | تحقق من `error.log` | لا توجد أخطاء TypeError بـ `$adminId` |

### 8.2 إصلاح تسجيل الدخول بعد التسجيل

| # | الخطوة | المتوقع |
|---|--------|---------|
| L1 | تسجيل حساب جديد (إيميل + باسورد قوي) | ينجح التسجيل + رسالة نجاح |
| L2 | فتح Login Modal → إدخال نفس البيانات → Sign In | **يجب أن ينجح** + redirect لـ index.php |
| L3 | تسجيل خروج → تسجيل دخول مرة أخرى | ينجح |
| L4 | تسجيل دخول بكلمة سر خاطئة | رسالة "الإيميل أو كلمة السر غير صحيحة" |
| L5 | 5 محاولات خاطئة | Rate Limiting يظهر رسالة الوقت المتبقي |
| L6 | تسجيل دخول أدمن → تصفح صفحات عامة | زر "🛠️ Admin Panel" يظهر بجانب Theme Toggle |
| L7 | الضغط على "Admin Panel" | يُوجّه لـ dashboard.php |

### 8.3 إصلاح شريط التنقّل (Navbar) للأدمن

| # | الخطوة | المتوقع |
|---|--------|---------|
| N1 | أدمن مسجّل دخول → index.php | Navbar يظهر: Home, Products, About Us, Contact Us فقط |
| N2 | لا تظهر روابط: Dashboard, Users, Support, Orders, Settings | ✅ مخفية تماماً |
| N3 | زر "🛠️ Admin Panel" يظهر بجانب زر Theme Toggle | ✅ |
| N4 | الضغط على "Admin Panel" | يُوجّه لـ admin/dashboard.php |
| N5 | Dropdown "👑 اسم الأدمن" | يظهر: My Info, Contact, Backup DB (لو A), Log Out |
| N6 | الأدمن في admin/layout.php | تظهر كل الروابط (Dashboard/Users/Support/Orders/Site Configuration) كما كانت |

### 8.4 إصلاح الوضع الليلي (Dark Mode)

| # | الصفحة | التحقق |
|---|--------|--------|
| D1 | My Info → Personal Info | حقول الإدخال تظهر بوضوح بالوضعين (بما فيها autofill) |
| D2 | My Info → My Orders | بطاقات الطلبات تتوافق مع الوضعين |
| D3 | My Info → Saved Addresses | بطاقات العناوين + أزرار Set Default/Delete تظهر بوضوح |
| D4 | My Info → Admin Settings (Role A) | حقول القائمة المنسدلة + الأزرار تظهر بوضوح |
| D5 | Site Configuration | كل حقول الإدخال + النصوص + الأزرار تظهر بوضوح |
| D6 | Login Modal | حقول الإدخال تتوافق مع الوضع الليلي |
| D7 | Register Modal | حقول الإدخال + القوائم المنسدلة تتوافق |
| D8 | Cart Sidebar | المحتوى يظهر بوضوح بالوضعين |
| D9 | Tab switching في My Info | الانتقال سلس بين التبويبات |
| D10 | Toggle الثيم بسرعة متعددة | لا تظهر تأثيرات جانبية |

### 8.5 إعادة تسمية Settings → Site Configuration

| # | الموقع | التحقق |
|---|--------|--------|
| R1 | admin/layout.php navbar link | يظهر "⚙️ Site Configuration" |
| R2 | admin/site-settings.php title | يظهر "⚙️ Site Configuration" |
| R3 | علامة المتصفح (tab title) | "Site Configuration | Cairo Store Admin" |

### 8.6 تحسينات الأنيميشن

| # | العنصر | التحقق |
|---|--------|--------|
| AN1 | Product cards في products.php | تظهر بحركة fade-in عند تحميل الصفحة |
| AN2 | التبديل بين Light/Dark | انتقال سلس للونات |
| AN3 | فتح/إغلاق Modals | حركة slide-in ناعمة (~300ms) |
| AN4 | Dropdown menus | fade + scale سلس |
| AN5 | Tab switching في My Info | slide سلس |
| AN6 | prefers-reduced-motion | عطّل التأثيرات الحركية (اختبر من DevTools) |
| AN7 | لا توجد حركات بطيئة (>400ms) | كل الحركات سريعة ومتجاوبة |
