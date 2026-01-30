<?php
// SEO Configuration and Functions

function generateProductStructuredData($product) {
    global $BASE_URL;
    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => htmlspecialchars($product['name']),
        "description" => htmlspecialchars($product['description']),
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
            "seller" => [
                "@type" => "Organization",
                "name" => "Accessories By Dija"
            ]
        ]
    ];

    if (!empty($product['images'])) {
        $structured_data["image"] = array_map(function($img) {
            return "https://accessoriesbydija.uk/" . $img;
        }, $product['images']);
    }

    if (!empty($product['rating']) && !empty($product['review_count'])) {
        $structured_data["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => $product['rating'],
            "reviewCount" => $product['review_count']
        ];
    }

    return $structured_data;
}

function generateBreadcrumbStructuredData($breadcrumbs) {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];

    $items = [];
    foreach ($breadcrumbs as $index => $crumb) {
        $items[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $crumb['name'],
            "item" => $base_url . $crumb['url']
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

function generateBusinessStructuredData() {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];

    return [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "@id" => $base_url . "#organization",
        "name" => "Accessories By Dija",
        "alternateName" => "Dija Accessories",
        "url" => $base_url,
        "logo" => $base_url . "/assets/images/logo.webp",
        "description" => "Premium handcrafted jewelry collection featuring rings, necklaces, earrings, bracelets, and custom pieces. Expert artisans create timeless jewelry with ethically sourced materials.",
        "foundingDate" => "2020",
        "slogan" => "Timeless Elegance, Handcrafted Perfection",
        "address" => [
            "@type" => "PostalAddress",
            "addressCountry" => "GB"
        ],
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => "+44-XXXXXXXXXX",
            "contactType" => "customer service",
            "availableLanguage" => ["English"],
            "hoursAvailable" => [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                "opens" => "09:00",
                "closes" => "18:00"
            ]
        ],
        "sameAs" => [
            "https://www.instagram.com/accessoriesbydija",
            "https://www.facebook.com/accessoriesbydija",
            "https://www.twitter.com/accessoriesbydija"
        ],
        "hasOfferCatalog" => [
            "@type" => "OfferCatalog",
            "name" => "Jewelry Collection",
            "itemListElement" => [
                [
                    "@type" => "Offer",
                    "itemOffered" => [
                        "@type" => "Product",
                        "name" => "Rings",
                        "category" => "Fashion Accessories"
                    ]
                ],
                [
                    "@type" => "Offer",
                    "itemOffered" => [
                        "@type" => "Product",
                        "name" => "Necklaces",
                        "category" => "Fashion Accessories"
                    ]
                ],
                [
                    "@type" => "Offer",
                    "itemOffered" => [
                        "@type" => "Product",
                        "name" => "Earrings",
                        "category" => "Fashion Accessories"
                    ]
                ],
                [
                    "@type" => "Offer",
                    "itemOffered" => [
                        "@type" => "Product",
                        "name" => "Bracelets",
                        "category" => "Fashion Accessories"
                    ]
                ]
            ]
        ],
        "knowsAbout" => [
            "Handcrafted Jewelry",
            "Gold Jewelry",
            "Silver Jewelry",
            "Diamond Jewelry",
            "Custom Jewelry Design",
            "Fashion Accessories",
            "Wedding Rings",
            "Engagement Rings"
        ],
        "hasCredential" => [
            "@type" => "EducationalOccupationalCredential",
            "name" => "Certified Jewelry Artisan",
            "credentialCategory" => "Professional Certification"
        ]
    ];
}

function generateFAQStructuredData() {
    return [
        "@context" => "https://schema.org",
        "@type" => "FAQPage",
        "mainEntity" => [
            [
                "@type" => "Question",
                "name" => "Do you offer custom jewelry design?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Yes, we specialize in custom jewelry design. Our expert artisans work with you to create one-of-a-kind pieces tailored to your vision. From engagement rings to personalized gifts, we bring your ideas to life using premium materials."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "What materials do you use?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "We use only the finest materials including 14K and 18K gold, sterling silver, platinum, and ethically sourced diamonds and gemstones. All our materials are carefully selected for quality and durability."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "Do you offer international shipping?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Yes, we ship worldwide. Standard shipping is free on orders over £100, and we offer express delivery options for urgent orders. All packages are fully insured and tracked."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "What is your return policy?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "We offer a 30-day return policy on all jewelry items. Items must be in original condition with tags attached. Custom pieces may have different return terms - please contact us for details."
                ]
            ],
            [
                "@type" => "Question",
                "name" => "How long does custom jewelry take to make?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Custom jewelry typically takes 2-4 weeks to complete, depending on complexity. Rush orders may be available for an additional fee. We'll provide regular updates throughout the creation process."
                ]
            ]
        ]
    ];
}

function generateProductKnowledgePanel($product) {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];

    $knowledge_data = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "@id" => $base_url . "/product/" . $product['slug'] . "#product",
        "name" => $product['name'],
        "description" => $product['description'],
        "sku" => $product['sku'] ?? '',
        "brand" => [
            "@type" => "Brand",
            "@id" => $base_url . "#brand",
            "name" => "Accessories By Dija",
            "slogan" => "Timeless Elegance, Handcrafted Perfection"
        ],
        "manufacturer" => [
            "@type" => "Organization",
            "@id" => $base_url . "#organization",
            "name" => "Accessories By Dija"
        ],
        "category" => $product['category'] ?? 'Jewelry',
        "material" => $product['material'] ?? 'Precious Metals',
        "offers" => [
            "@type" => "Offer",
            "price" => $product['price'],
            "priceCurrency" => "GBP",
            "availability" => $product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "seller" => [
                "@type" => "Organization",
                "@id" => $base_url . "#organization",
                "name" => "Accessories By Dija"
            ],
            "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
            "shippingDetails" => [
                "@type" => "OfferShippingDetails",
                "shippingRate" => [
                    "@type" => "MonetaryAmount",
                    "value" => "0",
                    "currency" => "GBP"
                ],
                "shippingDestination" => [
                    "@type" => "DefinedRegion",
                    "addressCountry" => "GB"
                ],
                "deliveryTime" => [
                    "@type" => "ShippingDeliveryTime",
                    "handlingTime" => [
                        "@type" => "QuantitativeValue",
                        "minValue" => 1,
                        "maxValue" => 2,
                        "unitText" => "Day"
                    ],
                    "transitTime" => [
                        "@type" => "QuantitativeValue",
                        "minValue" => 2,
                        "maxValue" => 5,
                        "unitText" => "Day"
                    ]
                ]
            ]
        ],
        "additionalProperty" => [
            [
                "@type" => "PropertyValue",
                "name" => "Material",
                "value" => $product['material'] ?? 'Premium Metals'
            ],
            [
                "@type" => "PropertyValue",
                "name" => "Gender",
                "value" => ['U' => 'Unisex', 'M' => 'Male', 'F' => 'Female'][$product['gender']] ?? 'Unisex'
            ],
            [
                "@type" => "PropertyValue",
                "name" => "Warranty",
                "value" => "Lifetime Warranty"
            ]
        ]
    ];

    // Add images if available
    if (!empty($product['images'])) {
        $knowledge_data["image"] = array_map(function($img) use ($base_url) {
            return $base_url . "/" . $img;
        }, $product['images']);
    }

    // Add reviews if available
    if (!empty($product['reviews'])) {
        $knowledge_data["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => array_sum(array_column($product['reviews'], 'rating')) / count($product['reviews']),
            "reviewCount" => count($product['reviews']),
            "bestRating" => 5,
            "worstRating" => 1
        ];

        $knowledge_data["review"] = array_map(function($review) {
            return [
                "@type" => "Review",
                "author" => [
                    "@type" => "Person",
                    "name" => $review['customer_name']
                ],
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => $review['rating'],
                    "bestRating" => 5,
                    "worstRating" => 1
                ],
                "reviewBody" => $review['content']
            ];
        }, array_slice($product['reviews'], 0, 5)); // Limit to 5 reviews
    }

    return $knowledge_data;
}

function generateMetaTags($page_data) {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];
    $meta = [
        'title' => sanitizeOutput($page_data['title'] ?? 'Accessories By Dija - Premium Jewelry'),
        'description' => sanitizeOutput($page_data['description'] ?? 'Discover premium jewelry collection'),
        'keywords' => sanitizeOutput($page_data['keywords'] ?? 'jewelry, rings, necklaces, earrings'),
        'robots' => $page_data['robots'] ?? 'index, follow',
        'canonical' => $page_data['canonical'] ?? $base_url . $_SERVER['REQUEST_URI']
    ];

    return $meta;
}

function generateOpenGraphTags($page_data) {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];
    $og = [
        'og:type' => $page_data['og:type'] ?? 'website',
        'og:url' => $page_data['og:url'] ?? $base_url . $_SERVER['REQUEST_URI'],
        'og:title' => sanitizeOutput($page_data['og:title'] ?? $page_data['title'] ?? 'Accessories By Dija - Premium Jewelry'),
        'og:description' => sanitizeOutput($page_data['og:description'] ?? $page_data['description'] ?? 'Discover premium jewelry collection'),
        'og:image' => $page_data['og:image'] ?? $base_url . '/assets/images/logo.webp',
        'og:site_name' => 'Accessories By Dija'
    ];

    return $og;
}

function generateTwitterTags($page_data) {
    global $BASE_URL;
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];
    $twitter = [
        'twitter:card' => $page_data['twitter:card'] ?? 'summary_large_image',
        'twitter:title' => sanitizeOutput($page_data['twitter:title'] ?? $page_data['og:title'] ?? $page_data['title'] ?? 'Accessories By Dija - Premium Jewelry'),
        'twitter:description' => sanitizeOutput($page_data['twitter:description'] ?? $page_data['og:description'] ?? $page_data['description'] ?? 'Discover premium jewelry collection'),
        'twitter:image' => $page_data['twitter:image'] ?? $page_data['og:image'] ?? $base_url . '/assets/images/logo.webp'
    ];

    return $twitter;
}

function generateProductPageData($product, $images = []) {
    global $BASE_URL;
    $page_data = [
        'title' => $product['name'] . ' - Accessories By Dija',
        'description' => substr($product['description'], 0, 160) . '... Shop now at Accessories By Dija.',
        'keywords' => $product['category'] . ', ' . $product['material'] . ', jewelry, accessories, ' . $product['name'],
        'og:type' => 'product',
        'og:title' => $product['name'],
        'og:description' => substr($product['description'], 0, 160),
        'og:image' => !empty($images) ? 'https://accessoriesbydija.uk/' . $images[0]['image_url'] : 'https://accessoriesbydija.uk/assets/images/logo.webp',
        'twitter:card' => 'summary_large_image'
    ];

    return $page_data;
}
?>