<?php
// SEO Configuration and Functions

function generateProductStructuredData($product) {
    return [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => htmlspecialchars($product['name']),
        "description" => htmlspecialchars($product['description']),
        "image" => $product['images'],
        "sku" => $product['sku'],
        "brand" => [
            "@type" => "Brand",
            "name" => "Accessories By Dija"
        ],
        "offers" => [
            "@type" => "Offer",
            "price" => $product['price'],
            "priceCurrency" => "GBP",
            "availability" => $product['stock'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "url" => "https://accessoriesbydija.com/product/" . $product['slug']
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => $product['rating'] ?? 4.5,
            "reviewCount" => $product['review_count'] ?? 10
        ]
    ];
}

function generateBreadcrumbStructuredData($breadcrumbs) {
    $items = [];
    foreach ($breadcrumbs as $index => $crumb) {
        $items[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $crumb['name'],
            "item" => $crumb['url']
        ];
    }
    
    return [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $items
    ];
}

function sanitizeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function generateMetaTags($page_data) {
    $meta = [
        'title' => sanitizeOutput($page_data['title'] ?? 'Accessories By Dija - Premium Jewelry'),
        'description' => sanitizeOutput($page_data['description'] ?? 'Discover premium jewelry collection'),
        'keywords' => sanitizeOutput($page_data['keywords'] ?? 'jewelry, rings, necklaces, earrings'),
        'robots' => $page_data['robots'] ?? 'index, follow',
        'canonical' => $page_data['canonical'] ?? (isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI']
    ];
    
    return $meta;
}
?>