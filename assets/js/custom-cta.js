// Custom CTA Animation
document.addEventListener('DOMContentLoaded', function() {
    const ctaSection = document.querySelector('.custom-cta');
    if (!ctaSection) return;
    
    // Fallback: animate immediately after a short delay
    const fallbackTimer = setTimeout(() => {
        ctaSection.classList.add('animate');
    }, 500);
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                clearTimeout(fallbackTimer);
                entry.target.classList.add('animate');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    observer.observe(ctaSection);
});