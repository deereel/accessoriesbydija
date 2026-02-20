<?php
require_once 'config/database.php';

$page_title = "Custom Jewelry Design";
$page_description = "Create your perfect custom jewelry piece with Dija Accessories. Work with our expert designers to bring your vision to life.";
include 'includes/header.php';

// Fetch materials from database
$stmt = $pdo->prepare("SELECT id, name FROM materials ORDER BY name");
$stmt->execute();
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for jewelry types
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch adornments from database
$stmt = $pdo->prepare("SELECT id, name FROM adornments ORDER BY name");
$stmt->execute();
$adornments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customized products
try {
    $stmt = $pdo->query("SELECT p.*, 
                          (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image 
                          FROM products p 
                          WHERE p.is_active = 1 AND p.is_customized = 1 
                          ORDER BY p.created_at DESC 
                          LIMIT 8");
    $customized_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fix image paths
    foreach ($customized_products as &$product) {
        if (!empty($product['main_image']) && strpos($product['main_image'], '/') !== 0) {
            $product['main_image'] = '/' . $product['main_image'];
        }
    }
} catch (Exception $e) {
    $customized_products = [];
}

// Fetch user's wishlist
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_wishlist = $pdo->prepare("SELECT product_id FROM wishlists WHERE user_id = ?");
        $stmt_wishlist->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt_wishlist->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $user_wishlist = [];
    }
}
?>

<main>
    <section class="custom-hero">
        <div class="container">
            <div class="custom-hero-content">
                <h1>Create Your Perfect Piece</h1>
                <p>Work with our expert designers to create custom jewelry that tells your unique story. From engagement rings to personalized gifts, we bring your vision to life.</p>
            </div>
        </div>
    </section>

    <!-- Customized Products Section -->
    <?php if (!empty($customized_products)): ?>
    <section class="customized-products-section">
        <div class="container">
            <h2>Featured Custom Pieces</h2>
            <p class="section-subtitle">Discover our collection of handcrafted customized jewelry</p>
            <div class="customized-products-grid">
                <?php foreach ($customized_products as $product): ?>
                <div class="customized-product-card">
                    <a href="product.php?slug=<?= $product['slug'] ?>">
                        <?php if ($product['main_image']): ?>
                            <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='/assets/images/placeholder.jpg'; this.onerror=null;">
                        <?php else: ?>
                            <div class="placeholder-img"><?= htmlspecialchars(substr($product['name'], 0, 3)) ?></div>
                        <?php endif; ?>
                        <div class="customized-product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-price">£<?= number_format($product['price'], 2) ?></p>
                        </div>
                    </a>
                    <button class="wishlist-btn<?= in_array($product['id'], $user_wishlist) ? ' active' : '' ?>" data-product-id="<?= $product['id'] ?>" onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                        <i class="<?= in_array($product['id'], $user_wishlist) ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="custom-process">
        <div class="container">
            <h2>Our Custom Design Process</h2>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Consultation</h3>
                    <p>Share your vision, budget, and preferences with our design team</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Design</h3>
                    <p>We create detailed sketches and 3D renderings of your piece</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Approval</h3>
                    <p>Review and approve the final design before crafting begins</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Crafting</h3>
                    <p>Our skilled artisans handcraft your unique piece with precision</p>
                </div>
            </div>
        </div>
    </section>

    <section class="custom-form-section">
        <div class="container">
            <div class="form-layout">
                <div class="form-content">
                    <h2>Start Your Custom Design</h2>
                    <form class="custom-form" id="custom-form">
                        <div class="form-group">
                             <label for="jewelry-type">Jewelry Type *</label>
                             <select id="jewelry-type" name="jewelry_type" required>
                                 <option value="">Select Type</option>
                                 <?php foreach ($categories as $category): ?>
                                 <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                 <?php endforeach; ?>
                                 <option value="other">Other</option>
                             </select>
                         </div>

                        <div class="form-group">
                            <label for="occasion">Occasion</label>
                            <select id="occasion" name="occasion">
                                <option value="">Select Occasion</option>
                                <option value="engagement">Engagement</option>
                                <option value="wedding">Wedding</option>
                                <option value="anniversary">Anniversary</option>
                                <option value="birthday">Birthday</option>
                                <option value="graduation">Graduation</option>
                                <option value="gift">Gift</option>
                                <option value="personal">Personal</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="budget">Budget Range *</label>
                            <select id="budget" name="budget" required>
                                <option value="">Select Budget</option>
                                <option value="under-100">Under £100</option>
                                <option value="100-500">£100 - £500</option>
                                <option value="500-1000">£500 - £1,000</option>
                                <option value="1000-2500">£1,000 - £2,500</option>
                                <option value="2500-5000">£2,500 - £5,000</option>
                                <option value="over-5000">Over £5,000</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="metal-preference">Metal Preference</label>
                            <div class="checkbox-group">
                                <?php foreach ($materials as $material): ?>
                                <label><input type="checkbox" name="metals[]" value="<?php echo htmlspecialchars($material['name']); ?>"> <?php echo htmlspecialchars($material['name']); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                             <label for="adornments">Adornment Preferences</label>
                             <div class="checkbox-group">
                                 <?php foreach ($adornments as $adornment): ?>
                                 <label><input type="checkbox" name="adornments[]" value="<?php echo htmlspecialchars($adornment['name']); ?>"> <?php echo htmlspecialchars($adornment['name']); ?></label>
                                 <?php endforeach; ?>
                             </div>
                         </div>

                        <div class="form-group">
                            <label for="description">Describe Your Vision *</label>
                            <textarea id="description" name="description" rows="5" placeholder="Tell us about your dream piece. Include any inspiration, style preferences, or special meaning..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="timeline">Desired Timeline</label>
                            <select id="timeline" name="timeline">
                                <option value="">Select Timeline</option>
                                <option value="2-weeks">2 weeks</option>
                                <option value="1-month">1 month</option>
                                <option value="2-months">2 months</option>
                                <option value="3-months">3+ months</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Design Request</button>
                    </form>
                </div>

                <div class="custom-gallery">
                    <h3>Custom Pieces We've Created</h3>
                    <div class="gallery-grid">
                        <div class="gallery-item">
                            <div class="gallery-placeholder" style="background: linear-gradient(135deg, #c487a5 0%, #b07591 100%);">
                                <span>✨</span>
                            </div>
                            <p>Custom Earrings</p>
                        </div>
                        <div class="gallery-item">
                            <div class="gallery-placeholder" style="background: linear-gradient(135deg, #d4a5a5 0%, #b07591 100%);">
                                <span>📿</span>
                            </div>
                            <p>Personalized Necklace</p>
                        </div>
                        <div class="gallery-item">
                            <div class="gallery-placeholder" style="background: linear-gradient(135deg, #a5c4d4 0%, #7591b0 100%);">
                                <span>💎</span>
                            </div>
                            <p>Custom Jewelry Set</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.custom-hero {
    background: linear-gradient(135deg, #c487a5 0%, #b07591 100%);
    color: white;
    padding: 4rem 0;
    margin-top: 80px;
    text-align: center;
}

.custom-hero h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.custom-hero p {
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
    opacity: 0.9;
}

/* Customized Products Section */
.customized-products-section {
    padding: 4rem 0;
    background: #f8f8f8;
}

.customized-products-section h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.section-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 3rem;
}

.customized-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
}

.customized-product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.customized-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.customized-product-card a {
    text-decoration: none;
    color: inherit;
}

.customized-product-card img,
.customized-product-card .placeholder-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

.customized-product-card .placeholder-img {
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #999;
    font-weight: bold;
}

.customized-product-info {
    padding: 1rem;
}

.customized-product-info h3 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.customized-product-info .product-price {
    font-weight: 600;
    color: #c487a5;
}

.customized-product-card .wishlist-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.customized-product-card .wishlist-btn:hover {
    transform: scale(1.1);
}

.customized-product-card .wishlist-btn.active {
    background: #C27BA0;
    color: white;
}

.customized-product-card .wishlist-btn.active i {
    color: white;
}

.custom-process {
    padding: 4rem 0;
    background: #f8f8f8;
}

.custom-process h2 {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: #333;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.step-number {
    width: 60px;
    height: 60px;
    background: #c487a5;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 auto 1rem;
}

.step h3 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
    color: #333;
}

.custom-form-section {
    padding: 4rem 0;
    background: white;
}

.form-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 4rem;
    align-items: start;
}

.custom-form {
    background: #f8f8f8;
    padding: 2rem;
    border-radius: 12px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #c487a5;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    font-weight: normal;
    margin-bottom: 0;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin-right: 0.5rem;
}

.custom-gallery h3 {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    color: #333;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.gallery-placeholder {
    width: 100%;
    height: 150px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.gallery-item p {
    text-align: center;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

@media (max-width: 768px) {
    .custom-hero h1 {
        font-size: 2rem;
    }
    
    .form-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .process-steps {
        grid-template-columns: 1fr;
    }
    
    .checkbox-group {
        grid-template-columns: 1fr;
    }
    
    .gallery-grid {
        grid-template-columns: 1fr;
    }
    
    .customized-products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Wishlist functionality
window.toggleWishlist = function(productId, btn) {
    fetch('/api/wishlist.php?product_id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }

            const isInWishlist = data.in_wishlist;
            const method = isInWishlist ? 'DELETE' : 'POST';
            const url = isInWishlist ? '/api/wishlist.php?product_id=' + productId : '/api/wishlist.php';

            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: method === 'POST' ? JSON.stringify({ product_id: productId }) : null
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (isInWishlist) {
                        btn.classList.remove('active');
                        btn.querySelector('i').className = 'far fa-heart';
                    } else {
                        btn.classList.add('active');
                        btn.querySelector('i').className = 'fas fa-heart';
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        })
        .catch(error => {
            console.error('Error checking wishlist:', error);
            alert('An error occurred. Please try again.');
        });
}

document.getElementById('custom-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        // Collect form data
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Handle checkboxes
        data.metals = formData.getAll('metals[]');
        data.adornments = formData.getAll('adornments[]');

        const response = await fetch('/api/custom-request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            this.reset();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
