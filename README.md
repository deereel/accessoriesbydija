# Dija Accessories - Premium Jewelry E-commerce Website

A modern, responsive e-commerce website for Dija Accessories, specializing in premium jewelry for men and women.

## Features

### Frontend
- **Responsive Design**: Mobile-first approach with modern CSS Grid and Flexbox
- **SEO Optimized**: Clean URLs, meta tags, structured data, XML sitemap
- **Product Categories**: Rings, necklaces, earrings, bracelets, bangles, anklets, sets
- **Custom Jewelry**: Consultation form for personalized jewelry design
- **Search & Filtering**: Advanced product filtering by price, material, stone type
- **Shopping Cart**: Local storage-based cart functionality
- **Customer Testimonials**: Social proof integration

### Backend
- **PHP Architecture**: Clean, maintainable PHP code structure
- **Database**: MySQL with comprehensive schema for products, orders, customers
- **Admin Panel**: Easy content management for products, categories, banners
- **Security**: Input validation, SQL injection prevention, secure headers

### Performance & SEO
- **Page Speed**: Optimized images, CSS/JS compression, browser caching
- **SEO-Friendly URLs**: `/category/women/rings` instead of `?cat=women&sub=rings`
- **Meta Tags**: Dynamic title/description generation
- **Structured Data**: JSON-LD for products and organization
- **Sitemap**: XML sitemap for search engines

## Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/yourusername/accessoriesbydija.git
   cd accessoriesbydija
   ```

2. **Database Setup**
   - Create MySQL database: `dija_accessories`
   - Import schema: `mysql -u root -p dija_accessories < database.sql`
   - Update database credentials in `config/database.php`

3. **Web Server Configuration**
   - Ensure Apache mod_rewrite is enabled
   - Point document root to project folder
   - The `.htaccess` file handles URL rewriting

4. **File Permissions**
   ```bash
   chmod 755 assets/images/
   chmod 644 *.php
   ```

## Directory Structure

```
accessoriesbydija/
├── admin/                 # Admin panel
│   ├── index.php         # Dashboard
│   ├── products.php      # Product management
│   └── ...
├── assets/               # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── config/               # Configuration files
│   └── database.php
├── includes/             # Shared components
│   ├── header.php
│   └── footer.php
├── index.php            # Homepage
├── category.php         # Category/product listing
├── custom-jewelry.php   # Custom design page
├── database.sql         # Database schema
├── sitemap.xml         # SEO sitemap
└── .htaccess           # URL rewriting & security
```

## Key Pages

### Homepage (`index.php`)
- Hero section with call-to-action
- Featured product categories
- Customer testimonials
- Newsletter signup

### Category Pages (`category.php`)
- Dynamic category/subcategory handling
- Product filtering (price, material, stone type)
- Responsive product grid
- Breadcrumb navigation

### Custom Jewelry (`custom-jewelry.php`)
- Design consultation form
- Process explanation
- Gallery of custom pieces
- Budget and timeline selection

### Admin Panel (`admin/`)
- Dashboard with key metrics
- Product/category management
- Order tracking
- Customer testimonials

## SEO Features

### URL Structure
- Clean, hierarchical URLs
- Category-based navigation
- Breadcrumb implementation

### Meta Tags
- Dynamic title/description generation
- Open Graph tags for social sharing
- Canonical URLs to prevent duplicate content

### Performance
- Image optimization and lazy loading
- CSS/JS minification
- Browser caching headers
- GZIP compression

## Customization

### Adding New Categories
1. Update navigation in `includes/header.php`
2. Add category to database via admin panel
3. Update sitemap.xml

### Styling
- Main styles in `assets/css/style.css`
- CSS variables for easy color scheme changes
- Mobile-first responsive design

### Product Management
- Admin panel for easy product addition
- Image upload functionality
- Inventory tracking
- SEO fields for each product

## Security Features

- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- Secure headers via .htaccess
- Admin authentication

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and customization requests, please contact the development team.