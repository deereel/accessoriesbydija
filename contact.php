<?php
$page_title = "Contact Us";
$page_description = "Get in touch with Accessories By Dija. We're here to help with your jewelry questions, custom orders, and customer support.";
include 'includes/header.php';
?>

<main>
    <section class="contact-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </section>

    <section class="contact-content">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-form">
                    <h2>Send us a Message</h2>
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="order">Order Support</option>
                                <option value="custom">Custom Jewelry</option>
                                <option value="returns">Returns & Exchanges</option>
                                <option value="shipping">Shipping Information</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>

                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Phone</h3>
                            <p>+44 20 7946 0958</p>
                            <p>Mon-Fri: 9AM-6PM GMT</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>hello@accessoriesbydija.com</p>
                            <p>We respond within 24 hours</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Address</h3>
                            <p>London, United Kingdom</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Business Hours</h3>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM GMT</p>
                            <p>Saturday: 10:00 AM - 4:00 PM GMT</p>
                            <p>Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.contact-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.contact-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.contact-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.contact-content {
    padding: 4rem 0;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: start;
}

.contact-form h2,
.contact-info h2 {
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #333;
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
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #C27BA0;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: #C27BA0;
    color: white;
}

.btn-primary:hover {
    background: #a66889;
}

.info-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    align-items: flex-start;
}

.info-item i {
    color: #C27BA0;
    font-size: 1.5rem;
    margin-top: 0.25rem;
}

.info-item h3 {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
    color: #333;
}

.info-item p {
    color: #666;
    margin: 0;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .contact-hero h1 {
        font-size: 2rem;
    }
}
</style>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Basic form validation
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    // Here you would typically send the data to your server
    // For now, just show a success message
    alert('Thank you for your message! We will get back to you soon.');

    // Reset form
    this.reset();
});
</script>

<?php include 'includes/footer.php'; ?>