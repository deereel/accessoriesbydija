-- Dija Accessories Database Schema

CREATE DATABASE IF NOT EXISTS dija_accessories;
USE dija_accessories;

-- Categories table
CREATE TABLE categories (
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
CREATE TABLE products (
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
    dimensions VARCHAR(100),
    material VARCHAR(100),
    stone_type VARCHAR(100),
    gender ENUM('men', 'women', 'unisex') DEFAULT 'unisex',
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product categories relationship
CREATE TABLE product_categories (
    product_id INT,
    category_id INT,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Product images
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Customers table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customer addresses
CREATE TABLE customer_addresses (
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
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_method VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Order items
CREATE TABLE order_items (
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
CREATE TABLE custom_requests (
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
CREATE TABLE testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    content TEXT NOT NULL,
    product_id INT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Banners/Promotions
CREATE TABLE banners (
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

-- Newsletter subscribers
CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Insert sample categories
INSERT INTO categories (name, slug, description) VALUES
('Women', 'women', 'Jewelry collection for women'),
('Men', 'men', 'Jewelry collection for men'),
('Rings', 'rings', 'Beautiful rings for all occasions'),
('Necklaces', 'necklaces', 'Elegant necklaces and pendants'),
('Earrings', 'earrings', 'Stunning earrings collection'),
('Bracelets', 'bracelets', 'Stylish bracelets and bangles'),
('Bangles', 'bangles', 'Traditional and modern bangles'),
('Anklets', 'anklets', 'Delicate anklets collection'),
('Sets', 'sets', 'Complete jewelry sets');

-- Insert sample products
INSERT INTO products (name, slug, description, short_description, sku, price, material, stone_type, gender, is_featured) VALUES
('Diamond Solitaire Ring', 'diamond-solitaire-ring', 'Elegant diamond solitaire ring crafted in 14k gold', 'Classic solitaire ring with brilliant cut diamond', 'DSR001', 299.00, 'Gold', 'Diamond', 'women', TRUE),
('Pearl Drop Earrings', 'pearl-drop-earrings', 'Beautiful pearl drop earrings in sterling silver', 'Elegant pearl earrings perfect for any occasion', 'PDE001', 89.00, 'Silver', 'Pearl', 'women', TRUE),
('Gold Chain Necklace', 'gold-chain-necklace', 'Classic gold chain necklace, perfect for layering', '18-inch gold chain necklace', 'GCN001', 159.00, 'Gold', NULL, 'unisex', TRUE),
('Silver Charm Bracelet', 'silver-charm-bracelet', 'Sterling silver charm bracelet with heart charm', 'Adjustable silver bracelet with charm', 'SCB001', 79.00, 'Silver', NULL, 'women', TRUE);

-- Link products to categories
INSERT INTO product_categories (product_id, category_id) VALUES
(1, 1), (1, 3), -- Diamond ring -> Women, Rings
(2, 1), (2, 5), -- Pearl earrings -> Women, Earrings  
(3, 4), -- Gold necklace -> Necklaces
(4, 1), (4, 6); -- Silver bracelet -> Women, Bracelets

-- Insert sample testimonials
INSERT INTO testimonials (customer_name, rating, title, content, is_featured, is_approved) VALUES
('Sarah M.', 5, 'Beautiful quality jewelry', 'Beautiful quality jewelry and excellent customer service. My custom engagement ring exceeded all expectations!', TRUE, TRUE),
('Michael R.', 5, 'Fast shipping', 'Fast shipping and gorgeous pieces. The necklace I ordered looks even better in person.', TRUE, TRUE),
('Emma L.', 5, 'Amazing craftsmanship', 'Amazing craftsmanship and attention to detail. Will definitely be ordering again!', TRUE, TRUE);