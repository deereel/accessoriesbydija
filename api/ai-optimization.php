<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$base_url = 'https://accessoriesbydija.uk';

try {
    // Get site overview
    $site_data = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => "Accessories By Dija",
        "url" => $base_url,
        "description" => "Premium handcrafted jewelry collection featuring rings, necklaces, earrings, bracelets, and custom pieces. Expert artisans create timeless jewelry with ethically sourced materials.",
        "publisher" => [
            "@type" => "Organization",
            "name" => "Dija Accessories",
            "address" => [
                "@type" => "PostalAddress",
                "addressCountry" => "GB"
            ]
        ],
        "potentialAction" => [
            [
                "@type" => "SearchAction",
                "target" => $base_url . "/search?q={search_term}",
                "query-input" => "required name=search_term"
            ]
        ]
    ];

    // Get featured products
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.slug, p.description, p.price, p.category, p.material, p.gender,
               pi.image_url
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1 AND p.featured = 1
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();

    $products_data = [];
    $gender_map_display = ['U' => 'Unisex', 'M' => 'Male', 'F' => 'Female'];
    foreach ($featured_products as $product) {
        $products_data[] = [
            "@type" => "Product",
            "name" => $product['name'],
            "description" => $product['description'],
            "url" => $base_url . "/product/" . $product['slug'],
            "image" => $product['image_url'] ? $base_url . "/" . $product['image_url'] : null,
            "category" => $product['category'],
            "material" => $product['material'],
            "additionalProperty" => [
                ["@type" => "PropertyValue", "name" => "Gender", "value" => $gender_map_display[$product['gender']] ?? $product['gender']]
            ],
            "offers" => [
                "@type" => "Offer",
                "price" => $product['price'],
                "priceCurrency" => "GBP",
                "availability" => "https://schema.org/InStock"
            ]
        ];
    }

    // Get categories
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $categories_data = [];
    foreach ($categories as $category) {
        $categories_data[] = [
            "@type" => "CollectionPage",
            "name" => $category . " Collection",
            "url" => $base_url . "/category.php?cat=" . strtolower(str_replace(' ', '-', $category)),
            "description" => "Browse our " . $category . " collection of premium jewelry"
        ];
    }

    // FAQ data
    $faq_data = [
        "@type" => "FAQPage",
        "mainEntity" => [
            [
                "@type" => "Question",
                "name" => "What materials do you use for your jewelry?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "We use premium materials including 14K and 18K gold, sterling silver, and ethically sourced gemstones. All our pieces are crafted with attention to quality and durability."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "Do you offer custom jewelry design?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Yes, we offer custom jewelry design services. Our expert artisans work with you to create one-of-a-kind pieces that match your vision and style."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "What is your return policy?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "We offer a 30-day return policy on all jewelry purchases. Items must be in original condition with tags attached. Custom pieces are not eligible for return."
                ]
            ]
        ]
    ];

    // Combine all data
    $ai_data = [
        "website" => $site_data,
        "featured_products" => $products_data,
        "categories" => $categories_data,
        "faq" => $faq_data,
        "last_updated" => date('c'),
        "total_products" => count($featured_products),
        "total_categories" => count($categories)
    ];

    echo json_encode($ai_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Internal server error",
        "message" => $e->getMessage()
    ]);
}
?>