// Custom CTA Animation
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.3 });

    const ctaSection = document.querySelector('.custom-cta');
    if (ctaSection) {
        observer.observe(ctaSection);
    }
});