// Testimonials Slider
document.addEventListener('DOMContentLoaded', function() {
    loadTestimonials();
});

async function loadTestimonials() {
    const container = document.getElementById('testimonials-slider');
    if (!container) return; // No testimonials section on this page
    
    try {
        const response = await fetch('api/testimonials.php');
        const data = await response.json();
        
        if (data.success && data.testimonials.length > 0) {
            displayTestimonials(data.testimonials);
            initTestimonialsSlider();
        } else {
            // Show fallback testimonials if none in database
            displayFallbackTestimonials();
        }
    } catch (error) {
        console.error('Error loading testimonials:', error);
        displayFallbackTestimonials();
    }
}

function displayFallbackTestimonials() {
    const fallbackTestimonials = [
        {
            customer_name: "Sarah M.",
            rating: 5,
            content: "Beautiful quality jewelry and excellent customer service. My custom engagement ring exceeded all expectations!",
            created_at: "2024-01-15",
            product_id: null
        },
        {
            customer_name: "Michael R.",
            rating: 5,
            content: "Fast shipping and gorgeous pieces. The necklace I ordered looks even better in person.",
            created_at: "2024-01-10",
            product_id: null
        },
        {
            customer_name: "Emma L.",
            rating: 5,
            content: "Amazing craftsmanship and attention to detail. Will definitely be ordering again!",
            created_at: "2024-01-05",
            product_id: null
        }
    ];
    
    displayTestimonials(fallbackTestimonials);
    initTestimonialsSlider();
}

function displayTestimonials(testimonials) {
    const container = document.getElementById('testimonials-slider');
    if (!container) {
        console.warn('displayTestimonials: #testimonials-slider not found in DOM');
        return;
    }

    const wrapper = container.querySelector('.swiper-wrapper');
    if (!wrapper) {
        console.warn('displayTestimonials: .swiper-wrapper not found inside #testimonials-slider');
        return;
    }

    const html = testimonials.map(testimonial => `
        <div class="swiper-slide">
            <div class="testimonial-card">
                <div class="testimonial-avatar">
                    ${getAvatarHTML(testimonial)}
                </div>
                <div class="testimonial-rating">
                    ${generateStars(testimonial.rating)}
                </div>
                <div class="testimonial-content">
                    "${testimonial.content}"
                </div>
                <div class="testimonial-author">${testimonial.customer_name}</div>
                <div class="testimonial-date">${formatDate(testimonial.created_at)}</div>
                ${(testimonial.product_slug || testimonial.product_id) ? 
                    `<a href="product.php?${testimonial.product_slug ? `slug=${encodeURIComponent(testimonial.product_slug)}` : `id=${encodeURIComponent(testimonial.product_id)}`}" class="testimonial-cta">Shop This Style</a>` :
                    `<a href="products.php" class="testimonial-cta">Browse Collection</a>`
                }
            </div>
        </div>
    `).join('');
    
    wrapper.innerHTML = html;
}

function getAvatarHTML(testimonial) {
    if (testimonial.client_image) {
        // Display the base64 image from database
        return `<img src="${testimonial.client_image}" alt="${testimonial.customer_name}" class="avatar-image">`;
    } else {
        // Display first letter as fallback
        const initial = testimonial.customer_name.charAt(0).toUpperCase();
        return `<div class="avatar-initial">${initial}</div>`;
    }
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
}

function generateStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<span class="star">${i <= rating ? '★' : '☆'}</span>`;
    }
    return stars;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { 
        year: 'numeric', 
        month: 'long' 
    });
}

function initTestimonialsSlider() {
    const sliderEl = document.querySelector('.testimonials-slider');
    if (!sliderEl || typeof Swiper === 'undefined') return;

    if (sliderEl.swiper) {
        sliderEl.swiper.destroy(true, true);
    }

    new Swiper(sliderEl, {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: true,
        centeredSlides: false,
        watchOverflow: true,

        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },

        pagination: {
            el: sliderEl.querySelector('.swiper-pagination'),
            clickable: true,
        },

        navigation: {
            nextEl: '.testimonials-next',
            prevEl: '.testimonials-prev',
        },

        breakpoints: {
            768: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            }
        }
    });
}