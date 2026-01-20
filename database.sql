-- Dija Accessories Database Schema

CREATE DATABASE IF NOT EXISTS dija_accessories;
USE dija_accessories;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    parent_id INT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    sku VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    stock_quantity INT DEFAULT 0,
    weight DECIMAL(8,2),
    size VARCHAR(255),
    material VARCHAR(100),
    stone_type VARCHAR(100),
    category VARCHAR(100),
    gender ENUM('men', 'women', 'unisex') DEFAULT 'unisex',
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product categories relationship
CREATE TABLE IF NOT EXISTS product_categories (
    product_id INT,
    category_id INT,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Product images
CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    variant_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Materials table
CREATE TABLE IF NOT EXISTS materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Colors table
CREATE TABLE IF NOT EXISTS colors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    hex_value VARCHAR(7)
);

-- Adornments table
CREATE TABLE IF NOT EXISTS adornments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Sizes table
CREATE TABLE IF NOT EXISTS sizes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Product materials relationship
CREATE TABLE IF NOT EXISTS product_materials (
    product_id INT,
    material_id INT,
    PRIMARY KEY (product_id, material_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);

-- Variant materials relationship
CREATE TABLE IF NOT EXISTS variant_materials (
    variant_id INT,
    material_id INT,
    PRIMARY KEY (variant_id, material_id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);

-- Product colors relationship
CREATE TABLE IF NOT EXISTS product_colors (
    product_id INT,
    color_id INT,
    PRIMARY KEY (product_id, color_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
);

-- Product adornments relationship
CREATE TABLE IF NOT EXISTS product_adornments (
    product_id INT,
    adornment_id INT,
    PRIMARY KEY (product_id, adornment_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (adornment_id) REFERENCES adornments(id) ON DELETE CASCADE
);

-- Product variants
CREATE TABLE IF NOT EXISTS product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    sku VARCHAR(255) NOT NULL UNIQUE,
    price_override DECIMAL(10,2),
    size_override VARCHAR(255),
    main_variant BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Variant tags
CREATE TABLE IF NOT EXISTS variant_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_id INT NOT NULL,
    tag VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);

-- Variant stock
CREATE TABLE IF NOT EXISTS variant_stock (
    variant_id INT PRIMARY KEY,
    stock_quantity INT NOT NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);

-- Add size_override column to product_variants if it doesn't exist
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS size_override VARCHAR(255) NULL;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    is_active BOOLEAN DEFAULT TRUE,
    force_password_reset TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customer addresses
CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type ENUM('billing', 'shipping') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(100),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_method VARCHAR(100),
    email VARCHAR(255),
    contact_name VARCHAR(255),
    contact_phone VARCHAR(20),
    address_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (address_id) REFERENCES customer_addresses(id) ON DELETE SET NULL
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Custom jewelry requests
CREATE TABLE IF NOT EXISTS custom_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    jewelry_type VARCHAR(100) NOT NULL,
    occasion VARCHAR(100),
    budget_range VARCHAR(50) NOT NULL,
    metals TEXT,
    stones TEXT,
    description TEXT NOT NULL,
    timeline VARCHAR(50),
    status ENUM('new', 'in_progress', 'completed', 'cancelled') DEFAULT 'new',
    estimated_price DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    content TEXT NOT NULL,
    product_id INT,
    client_image MEDIUMTEXT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Banners/Promotions
CREATE TABLE IF NOT EXISTS banners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    image_url VARCHAR(255),
    link_url VARCHAR(255),
    button_text VARCHAR(100),
    position VARCHAR(50) DEFAULT 'hero',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Support Tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(100),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    response_text TEXT,
    response_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (customer_id, status),
    INDEX (status, created_at),
    INDEX (priority)
);

-- Inventory transactions
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return') DEFAULT 'sale',
    quantity_change INT NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    notes TEXT,
    previous_stock INT,
    new_stock INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (product_id, created_at),
    INDEX (reference_type, reference_id)
);

-- Inventory logs
CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    quantity_change INT,
    old_quantity INT,
    new_quantity INT,
    user_id INT,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (product_id, created_at)
);

-- Newsletter subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'manager', 'superadmin') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    can_force_password_reset TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Collection banners
CREATE TABLE IF NOT EXISTS collection_banners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    image_url VARCHAR(255) NOT NULL,
    link_url VARCHAR(255),
    button_text VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Currency settings
CREATE TABLE IF NOT EXISTS currency_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(3) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shipping rates table
CREATE TABLE IF NOT EXISTS shipping_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country VARCHAR(100) NOT NULL,
    weight_min DECIMAL(8,2) DEFAULT 0,
    weight_max DECIMAL(8,2) DEFAULT 999999.99,
    fee DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'GBP',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (country, weight_min, weight_max),
    UNIQUE KEY unique_country_weight (country, weight_min, weight_max)
);

-- Site settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory transactions (for tracking stock changes)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return') DEFAULT 'sale',
    quantity_change INT NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    notes TEXT,
    previous_stock INT,
    new_stock INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (product_id, created_at),
    INDEX (reference_type, reference_id)
);

-- Inventory logs (for admin dashboard)
CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    quantity_change INT,
    old_quantity INT,
    new_quantity INT,
    user_id INT,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (product_id, created_at)
);

-- Refund logs
CREATE TABLE IF NOT EXISTS refund_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    payment_method VARCHAR(50) NOT NULL,
    reference VARCHAR(255),
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (order_id, created_at)
);

-- Insert sample categories
INSERT IGNORE INTO categories (name, slug, description) VALUES
('Women', 'women', 'Jewelry collection for women'),
('Men', 'men', 'Jewelry collection for men'),
('Rings', 'rings', 'Beautiful rings for all occasions'),
('Necklaces', 'necklaces', 'Elegant necklaces and pendants'),
('Earrings', 'earrings', 'Stunning earrings collection'),
('Bracelets', 'bracelets', 'Stylish bracelets and bangles'),
('Bangles', 'bangles', 'Traditional and modern bangles'),
('Anklets', 'anklets', 'Delicate anklets collection'),
('Sets', 'sets', 'Complete jewelry sets');

-- Add FULLTEXT index for search
ALTER TABLE products ADD FULLTEXT(name, description, short_description);

-- Insert sample products
INSERT IGNORE INTO products (name, slug, description, short_description, sku, price, material, stone_type, gender, is_featured) VALUES
('Diamond Solitaire Ring', 'diamond-solitaire-ring', 'Elegant diamond solitaire ring crafted in 14k gold', 'Classic solitaire ring with brilliant cut diamond', 'DSR001', 299.00, 'Gold', 'Diamond', 'women', TRUE),
('Pearl Drop Earrings', 'pearl-drop-earrings', 'Beautiful pearl drop earrings in sterling silver', 'Elegant pearl earrings perfect for any occasion', 'PDE001', 89.00, 'Silver', 'Pearl', 'women', TRUE),
('Gold Chain Necklace', 'gold-chain-necklace', 'Classic gold chain necklace, perfect for layering', '18-inch gold chain necklace', 'GCN001', 159.00, 'Gold', NULL, 'unisex', TRUE),
('Silver Charm Bracelet', 'silver-charm-bracelet', 'Sterling silver charm bracelet with heart charm', 'Adjustable silver bracelet with charm', 'SCB001', 79.00, 'Silver', NULL, 'women', TRUE),
('Emerald Tennis Bracelet', 'emerald-tennis-bracelet', 'Stunning emerald tennis bracelet in white gold', 'Luxury emerald bracelet with premium stones', 'ETB001', 450.00, 'Gold', 'Emerald', 'women', TRUE),
('Mens Signet Ring', 'mens-signet-ring', 'Classic mens signet ring in sterling silver', 'Traditional signet ring for men', 'MSR001', 125.00, 'Silver', NULL, 'men', TRUE),
('Rose Gold Pendant', 'rose-gold-pendant', 'Delicate rose gold pendant with diamond accent', 'Beautiful rose gold heart pendant', 'RGP001', 189.00, 'Rose Gold', 'Diamond', 'women', TRUE),
('Platinum Wedding Band', 'platinum-wedding-band', 'Classic platinum wedding band for men', 'Timeless platinum band', 'PWB001', 350.00, 'Platinum', NULL, 'men', TRUE);

-- Link products to categories
INSERT IGNORE INTO product_categories (product_id, category_id) VALUES
(1, 1), (1, 3), -- Diamond ring -> Women, Rings
(2, 1), (2, 5), -- Pearl earrings -> Women, Earrings  
(3, 4), -- Gold necklace -> Necklaces
(4, 1), (4, 6), -- Silver bracelet -> Women, Bracelets
(5, 1), (5, 6), -- Emerald bracelet -> Women, Bracelets
(6, 2), (6, 3), -- Mens ring -> Men, Rings
(7, 1), (7, 4), -- Rose gold pendant -> Women, Necklaces
(8, 2), (8, 3); -- Platinum band -> Men, Rings

-- Support Tickets table (for customer support requests)
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(100),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    response_text TEXT,
    response_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX (customer_id, status),
    INDEX (status, created_at),
    INDEX (priority)
);

-- Insert sample testimonials
INSERT IGNORE INTO testimonials (customer_name, rating, title, content, is_featured, is_approved) VALUES
('Sarah M.', 5, 'Beautiful quality jewelry', 'Beautiful quality jewelry and excellent customer service. My custom engagement ring exceeded all expectations!', TRUE, TRUE),
('Michael R.', 5, 'Fast shipping', 'Fast shipping and gorgeous pieces. The necklace I ordered looks even better in person.', TRUE, TRUE),
('Emma L.', 5, 'Amazing craftsmanship', 'Amazing craftsmanship and attention to detail. Will definitely be ordering again!', TRUE, TRUE);

-- Insert default admin user (username: admin, password: admin123)
INSERT IGNORE INTO admin_users (username, password_hash, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@dijaccessories.com', 'Administrator');

-- Insert initial materials
INSERT IGNORE INTO materials (name) VALUES
('Sterling Silver'), ('Stainless Steel'), ('Titanium Steel'), ('Copper'), ('Pearl'), ('Platinum');

-- Insert initial colors
INSERT IGNORE INTO colors (name) VALUES
('Gold'), ('Silver'), ('Rose Gold'), ('Black'), ('White'), ('Blue'), ('Pink'), ('Custom');

-- Insert initial adornments
INSERT IGNORE INTO adornments (name) VALUES
('Diamond'), ('Ruby'), ('Emerald'), ('Zirconia'), ('Sapphire'), ('Pearl'), ('Moissanite'), ('Blue Gem'), ('Pink Gem'), ('White Gem'), ('Red Gem'), ('White Stone'), ('Black Stone'), ('Red Stone'), ('Pink Stone');

-- Insert sample collection banners
INSERT IGNORE INTO collection_banners (title, subtitle, image_url, link_url, button_text, sort_order) VALUES
('New Arrivals', 'Discover our latest jewelry collection', 'assets/images/banners/new-arrivals.jpg', '/category/new', 'Shop Now', 1),
('Wedding Collection', 'Perfect pieces for your special day', 'assets/images/banners/wedding.jpg', '/category/wedding', 'Explore', 2),
('Gift Sets', 'Beautiful jewelry gift sets', 'assets/images/banners/gift-sets.jpg', '/category/sets', 'Shop Gifts', 3);

-- Insert currency settings
INSERT IGNORE INTO currency_settings (code, name, symbol, exchange_rate, is_default, is_active) VALUES
('GBP', 'British Pound', '£', 1.000000, TRUE, TRUE),
('USD', 'US Dollar', '$', 1.270000, FALSE, TRUE),
('EUR', 'Euro', '€', 1.170000, FALSE, TRUE),
('CNY', 'Chinese Yuan', '¥', 9.100000, FALSE, TRUE),
('NGN', 'Nigerian Naira', '₦', 1050.000000, FALSE, TRUE);

-- Cart table for persistent cart storage
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NULL,
    session_id VARCHAR(255) NULL,
    guest_email VARCHAR(255) NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    material_id INT NULL,
    variation_id INT NULL,
    size_id INT NULL,
    selected_price DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
    FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL,
    FOREIGN KEY (size_id) REFERENCES variation_sizes(id) ON DELETE SET NULL,
    INDEX (customer_id, updated_at),
    INDEX (session_id, updated_at),
    INDEX (guest_email, updated_at)
);

-- Abandoned cart tracking table
CREATE TABLE IF NOT EXISTS abandoned_carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    customer_id INT NULL,
    session_id VARCHAR(255) NULL,
    guest_email VARCHAR(255) NULL,
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    recovered BOOLEAN DEFAULT FALSE,
    recovered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    INDEX (email_sent, created_at),
    INDEX (customer_id),
    INDEX (session_id),
    INDEX (guest_email)
);

-- Insert shipping rates
INSERT IGNORE INTO shipping_rates (country, weight_min, weight_max, fee, currency) VALUES
-- United Kingdom rates
('United Kingdom', 0, 1, 3.50, 'GBP'),
('United Kingdom', 1, 4, 5.20, 'GBP'),
('United Kingdom', 4, 999999.99, 7.00, 'GBP'),
-- Canada rates
('Canada', 0, 1, 19.30, 'GBP'),
('Canada', 1, 2, 23.35, 'GBP'),
('Canada', 2, 999999.99, 28.00, 'GBP'),
-- United States rates
('United States', 0, 1, 16.50, 'GBP'),
('United States', 1, 2, 20.20, 'GBP'),
('United States', 2, 999999.99, 30.90, 'GBP'),
-- Ireland rates
('Ireland', 0, 1, 8.65, 'GBP'),
('Ireland', 1, 2, 10.10, 'GBP'),
('Ireland', 2, 999999.99, 11.10, 'GBP');

-- Insert site settings
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Dija Accessories', 'text', 'Website name'),
('site_tagline', 'Premium Jewelry for Every Occasion', 'text', 'Website tagline'),
('default_currency', 'GBP', 'text', 'Default currency code'),
('exchange_api_key', '', 'text', 'Exchange rate API key'),
('products_per_page', '12', 'number', 'Products per page in listings'),
('enable_reviews', 'true', 'boolean', 'Enable product reviews'),
('maintenance_mode', 'false', 'boolean', 'Site maintenance mode');

-- Migration: Update product_variants table for new variant system
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS tag VARCHAR(255) NULL;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS price_adjustment DECIMAL(10,2) NULL;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS stock_quantity INT DEFAULT 0;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Migration: Update product_colors for variant-level colors
ALTER TABLE product_colors ADD COLUMN IF NOT EXISTS variant_id INT NULL;
ALTER TABLE product_colors DROP FOREIGN KEY IF EXISTS product_colors_ibfk_1;
ALTER TABLE product_colors ADD CONSTRAINT fk_product_colors_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE;

-- Migration: Update product_adornments for variant-level adornments
ALTER TABLE product_adornments ADD COLUMN IF NOT EXISTS variant_id INT NULL;
ALTER TABLE product_adornments DROP FOREIGN KEY IF EXISTS product_adornments_ibfk_1;
ALTER TABLE product_adornments ADD CONSTRAINT fk_product_adornments_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE;

-- Migration: Update product_images variant reference
ALTER TABLE product_images DROP FOREIGN KEY IF EXISTS product_images_ibfk_2;
ALTER TABLE product_images ADD CONSTRAINT fk_product_images_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL;