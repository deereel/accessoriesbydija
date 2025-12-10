// Category Section JavaScript
let activeCategory = null;

function toggleCategory(category) {
    const categoryBtn = document.querySelector(`[data-category="${category}"]`);
    const subcategoriesContainer = document.getElementById('subcategories');
    const womenSubcategories = document.getElementById('women-subcategories');
    const menSubcategories = document.getElementById('men-subcategories');
    
    // Remove active class from all buttons
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Hide all subcategories
    womenSubcategories.style.display = 'none';
    menSubcategories.style.display = 'none';
    
    // If clicking the same category, just close it
    if (activeCategory === category) {
        activeCategory = null;
        subcategoriesContainer.style.display = 'none';
        return;
    }
    
    // Show selected category
    activeCategory = category;
    categoryBtn.classList.add('active');
    subcategoriesContainer.style.display = 'block';
    
    if (category === 'women') {
        womenSubcategories.style.display = 'grid';
    } else if (category === 'men') {
        menSubcategories.style.display = 'grid';
    }
    
    // Smooth scroll to subcategories
    setTimeout(() => {
        subcategoriesContainer.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }, 100);
}

// Initialize category section
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for subcategory cards
    document.querySelectorAll('.subcategory-card').forEach(card => {
        card.addEventListener('click', function() {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});