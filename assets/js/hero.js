// Hero Slider JavaScript
let heroSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');
const totalSlides = slides.length;

// Set background images from data-bg
slides.forEach(slide => {
    if (slide.dataset.bg) {
        slide.style.backgroundImage = `url(${slide.dataset.bg})`;
    }
});

// Auto-slide functionality
let slideInterval = setInterval(nextSlide, 5000);

function showSlide(index) {
    if (!slides.length || !slides[index]) return;
    
    // Remove active class from all slides and dots
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Add active class to current slide and dot
    slides[index].classList.add('active');
    if (dots[index]) dots[index].classList.add('active');
    
    heroSlideIndex = index;
}

function nextSlide() {
    if (!slides.length) return;
    const nextIndex = (heroSlideIndex + 1) % totalSlides;
    showSlide(nextIndex);
}

function prevSlide() {
    const prevIndex = (heroSlideIndex - 1 + totalSlides) % totalSlides;
    showSlide(prevIndex);
}

function changeSlide(direction) {
    // Reset auto-slide timer
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
    
    if (direction === 1) {
        nextSlide();
    } else {
        prevSlide();
    }
}

function currentSlide(index) {
    // Reset auto-slide timer
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
    
    showSlide(index - 1);
}

// Pause auto-slide on hover
const sliderContainer = document.querySelector('.hero-slider');
if (sliderContainer) {
    sliderContainer.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    sliderContainer.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });
}

// Touch/swipe support for mobile
let startX = 0;
let endX = 0;

sliderContainer?.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
});

sliderContainer?.addEventListener('touchend', (e) => {
    endX = e.changedTouches[0].clientX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = startX - endX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            changeSlide(1); // Swipe left - next slide
        } else {
            changeSlide(-1); // Swipe right - previous slide
        }
    }
}