// Collection Banner Swiper JavaScript
let currentBannerIndex = 0;
const bannerSlides = document.querySelectorAll('.banner-slide');
const bannerDots = document.querySelectorAll('.banner-dot');
const totalBannerSlides = bannerSlides.length;

// Auto-slide functionality
let bannerInterval = setInterval(nextBannerSlide, 6000);

function showBannerSlide(index) {
    // Remove active class from all slides and dots
    bannerSlides.forEach(slide => slide.classList.remove('active'));
    bannerDots.forEach(dot => dot.classList.remove('active'));
    
    // Add active class to current slide and dot
    if (bannerSlides[index]) {
        bannerSlides[index].classList.add('active');
    }
    if (bannerDots[index]) {
        bannerDots[index].classList.add('active');
    }
    
    currentBannerIndex = index;
}

function nextBannerSlide() {
    const nextIndex = (currentBannerIndex + 1) % totalBannerSlides;
    showBannerSlide(nextIndex);
}

function prevBannerSlide() {
    const prevIndex = (currentBannerIndex - 1 + totalBannerSlides) % totalBannerSlides;
    showBannerSlide(prevIndex);
}

function changeBannerSlide(direction) {
    // Reset auto-slide timer
    clearInterval(bannerInterval);
    bannerInterval = setInterval(nextBannerSlide, 6000);
    
    if (direction === 1) {
        nextBannerSlide();
    } else {
        prevBannerSlide();
    }
}

function currentBannerSlide(index) {
    // Reset auto-slide timer
    clearInterval(bannerInterval);
    bannerInterval = setInterval(nextBannerSlide, 6000);
    
    showBannerSlide(index - 1);
}

// Pause auto-slide on hover
const bannerContainer = document.querySelector('.collection-banners');
if (bannerContainer) {
    bannerContainer.addEventListener('mouseenter', () => {
        clearInterval(bannerInterval);
    });
    
    bannerContainer.addEventListener('mouseleave', () => {
        bannerInterval = setInterval(nextBannerSlide, 6000);
    });
}

// Touch/swipe support for mobile
let bannerStartX = 0;
let bannerEndX = 0;

bannerContainer?.addEventListener('touchstart', (e) => {
    bannerStartX = e.touches[0].clientX;
});

bannerContainer?.addEventListener('touchend', (e) => {
    bannerEndX = e.changedTouches[0].clientX;
    handleBannerSwipe();
});

function handleBannerSwipe() {
    const swipeThreshold = 50;
    const diff = bannerStartX - bannerEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            changeBannerSlide(1); // Swipe left - next slide
        } else {
            changeBannerSlide(-1); // Swipe right - previous slide
        }
    }
}

// Initialize banner on page load
document.addEventListener('DOMContentLoaded', function() {
    if (bannerSlides.length > 0) {
        showBannerSlide(0);
    }
});