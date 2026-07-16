-- ============================================================
-- schema.sql — CIRO Store — كل الجداول دفعة واحدة (مراحل 1-13)
-- تشغيل: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ciro_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ciro_db;

-- ── 1. users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id                         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name                  VARCHAR(100)  NOT NULL,
    email                      VARCHAR(150)  NOT NULL UNIQUE,
    password                   VARCHAR(255)  NOT NULL,
    phone_number               VARCHAR(30)   DEFAULT NULL UNIQUE,
    country                    VARCHAR(80)   DEFAULT NULL,
    city                       VARCHAR(80)   DEFAULT NULL,
    gender                     ENUM('male','female') DEFAULT NULL,
    birth_date                 DATE          DEFAULT NULL,
    last_activity              DATETIME      DEFAULT NULL,
    privacy_policy_accepted    TINYINT(1)    NOT NULL DEFAULT 0,
    privacy_policy_accepted_at DATETIME      DEFAULT NULL,
    created_at                 DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email        (email),
    INDEX idx_last_activity(last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. admins ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30)  DEFAULT NULL,
    role         ENUM('A','B','C','D') NOT NULL DEFAULT 'B',
    added_by     INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email(email),
    CONSTRAINT fk_admin_added_by FOREIGN KEY (added_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: الأدمن الرئيسي — كلمة السر تُضبط بـ seed_admin_password.php
INSERT INTO admins (full_name, email, password, phone_number, role, added_by)
VALUES ('Ahmad Saleh','ahmadsaleh9688@gmail.com','PLACEHOLDER','+962799538805','A',NULL);

-- ── 3. admin_permissions ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_permissions (
    id                           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id                     INT UNSIGNED NOT NULL UNIQUE,
    can_manage_admins            TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_products          TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_users             TINYINT(1) NOT NULL DEFAULT 0,
    can_view_dashboard           TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_support           TINYINT(1) NOT NULL DEFAULT 0,
    can_edit_site_content        TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_checkout_settings TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_orders            TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_perm_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_permissions
    (admin_id,can_manage_admins,can_manage_products,can_manage_users,
     can_view_dashboard,can_manage_support,can_edit_site_content,
     can_manage_checkout_settings,can_manage_orders)
VALUES (1,1,1,1,1,1,1,1,1);

-- ── 4. admin_audit_log ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    action      VARCHAR(100) NOT NULL,
    target_type VARCHAR(50)  DEFAULT NULL,
    target_id   INT UNSIGNED DEFAULT NULL,
    details     TEXT         DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin   (admin_id),
    INDEX idx_created (created_at),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. login_attempts ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_email_time(email, attempted_at),
    INDEX idx_ip_time   (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. categories + age_groups ────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name ENUM('accessories','phone','computer','gaming') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name) VALUES ('accessories'),('phone'),('computer'),('gaming');

CREATE TABLE IF NOT EXISTS age_groups (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO age_groups (name) VALUES ('all_ages'),('kids'),('teens'),('adults');

-- ── 7. products ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(200) NOT NULL,
    description          TEXT         DEFAULT NULL,
    country_of_origin    VARCHAR(80)  DEFAULT NULL,
    manufacturer         VARCHAR(100) DEFAULT NULL,
    price                DECIMAL(10,2) NOT NULL,
    discount_percentage  DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    price_after_discount DECIMAL(10,2) GENERATED ALWAYS AS
                             (ROUND(price - (price * discount_percentage / 100), 2)) STORED,
    gender_category      ENUM('male','female','both') NOT NULL DEFAULT 'both',
    image_path           VARCHAR(300) DEFAULT NULL,
    date_added           DATE         DEFAULT NULL,
    sales_count          INT UNSIGNED NOT NULL DEFAULT 0,
    stock_quantity       INT UNSIGNED NOT NULL DEFAULT 0,
    name_ar              VARCHAR(200) DEFAULT NULL,
    description_ar       TEXT         DEFAULT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_visible           TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_sales(sales_count),
    INDEX idx_date (date_added),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. pivot tables ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_category_pivot (
    product_id  INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, category_id),
    CONSTRAINT fk_pcp_product  FOREIGN KEY (product_id)  REFERENCES products(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pcp_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_age_group_pivot (
    product_id   INT UNSIGNED NOT NULL,
    age_group_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, age_group_id),
    CONSTRAINT fk_pagp_product   FOREIGN KEY (product_id)   REFERENCES products(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pagp_age_group FOREIGN KEY (age_group_id) REFERENCES age_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 9. product_reviews ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (product_id, user_id),
    CONSTRAINT fk_rev_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 10. stock_notifications ───────────────────────────────────
CREATE TABLE IF NOT EXISTS stock_notifications (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id   INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    is_notified  TINYINT(1) NOT NULL DEFAULT 0,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sn_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_sn_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 11. user_addresses ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_addresses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    label        VARCHAR(50)  DEFAULT 'Home',
    country      VARCHAR(80)  DEFAULT NULL,
    city         VARCHAR(80)  DEFAULT NULL,
    full_address TEXT         NOT NULL,
    phone_number VARCHAR(30)  DEFAULT NULL,
    is_default   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user(user_id),
    CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. orders + order_items ──────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    order_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    address_id     INT UNSIGNED DEFAULT NULL,
    total_amount   DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50)   NOT NULL DEFAULT 'cash_on_delivery',
    status         ENUM('not_taken','taken','completed','cancelled') NOT NULL DEFAULT 'not_taken',
    is_notified    TINYINT(1) NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_date   (created_at),
    CONSTRAINT fk_ord_user    FOREIGN KEY (user_id)    REFERENCES users(id)          ON DELETE CASCADE,
    CONSTRAINT fk_ord_address FOREIGN KEY (address_id) REFERENCES user_addresses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          INT UNSIGNED NOT NULL,
    product_id        INT UNSIGNED NOT NULL,
    quantity          INT UNSIGNED NOT NULL,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)   REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 13. website_settings ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS website_settings (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    employees_count      INT UNSIGNED DEFAULT 50,
    site_url             VARCHAR(255) DEFAULT 'https://cairostore.com',
    facebook_url         VARCHAR(255) DEFAULT '#',
    instagram_url        VARCHAR(255) DEFAULT '#',
    snapchat_url         VARCHAR(255) DEFAULT '#',
    whatsapp_number      VARCHAR(30)  DEFAULT NULL,
    tiktok_url           VARCHAR(255) DEFAULT NULL,
    twitter_x_url        VARCHAR(255) DEFAULT NULL,
    google_maps_url      VARCHAR(500) DEFAULT NULL,
    copyright_text       VARCHAR(255) DEFAULT '© Cairo Store. All Rights Reserved.',
    phone_number         VARCHAR(30)  DEFAULT '+20 123 456 789',
    working_hours        VARCHAR(100) DEFAULT 'Sun - Thu: 9 AM - 6 PM',
    logo_path            VARCHAR(300) DEFAULT NULL,
    favicon_path         VARCHAR(300) DEFAULT NULL,
    default_currency     VARCHAR(10)  DEFAULT 'USD',
    default_language     VARCHAR(10)  DEFAULT 'en',
    return_policy        TEXT         DEFAULT NULL,
    privacy_policy       TEXT         DEFAULT NULL,
    terms_and_conditions TEXT         DEFAULT NULL,
    footer_text          TEXT         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO website_settings
    (employees_count, site_url, copyright_text, phone_number, working_hours,
     return_policy, privacy_policy, terms_and_conditions, footer_text)
VALUES (
    50,
    'https://cairostore.com',
    '© Cairo Store. All Rights Reserved.',
    '+20 123 456 789',
    'Sun - Thu: 9 AM - 6 PM',
    'You may return any product within 14 days of purchase in its original condition.',
    'We respect your privacy. Your personal data is kept secure and never shared with third parties without your consent.',
    'By using Cairo Store, you agree to our terms. All sales are subject to product availability.',
    'Premium electronics store offering smartphones, laptops, gaming devices and smart accessories.'
);

-- ── 14. contact_messages ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_notified TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_sent_at (sent_at),
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 15. notifications ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    title            VARCHAR(200) NOT NULL,
    message          TEXT         NOT NULL,
    sender_admin_id  INT UNSIGNED DEFAULT NULL,
    is_read          TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created   (created_at),
    CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)         REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_notif_admin FOREIGN KEY (sender_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 16. user_strikes (Warnings/Strikes) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS user_strikes (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL,
    reason            TEXT         NOT NULL,
    issued_by_admin_id INT UNSIGNED DEFAULT NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    CONSTRAINT fk_strike_user  FOREIGN KEY (user_id)            REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_strike_admin FOREIGN KEY (issued_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 17. orders: إضافة عمود taken_at (الـ ENUM محدَّث بالجدول أعلاه) ──────────────
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS taken_at DATETIME DEFAULT NULL;
