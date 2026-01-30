<?php
$page_title = "Cookie Policy";
$page_description = "Learn about how Accessories By Dija uses cookies to improve your browsing experience and protect your privacy.";
include 'includes/header.php';
?>

<main>
    <section class="cookies-hero">
        <div class="container">
            <h1>Cookie Policy</h1>
            <p>Learn about how we use cookies and similar technologies to enhance your experience.</p>
        </div>
    </section>

    <section class="cookies-content">
        <div class="container">
            <div class="policy-section">
                <h2>What Are Cookies?</h2>
                <p>Cookies are small text files that are stored on your computer or mobile device when you visit our website. They help us provide you with a better browsing experience by remembering your preferences and understanding how you use our site.</p>
            </div>

            <div class="policy-section">
                <h2>How We Use Cookies</h2>
                <p>We use cookies for the following purposes:</p>
                <ul>
                    <li><strong>Essential Cookies:</strong> Required for the website to function properly, including secure checkout and account management.</li>
                    <li><strong>Analytics Cookies:</strong> Help us understand how visitors use our site so we can improve the user experience.</li>
                    <li><strong>Marketing Cookies:</strong> Used to show relevant advertisements and track the effectiveness of our marketing campaigns.</li>
                    <li><strong>Preference Cookies:</strong> Remember your settings and preferences for future visits.</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Types of Cookies We Use</h2>

                <div class="cookie-type">
                    <h3>Session Cookies</h3>
                    <p>Temporary cookies that expire when you close your browser. Used for essential site functionality.</p>
                </div>

                <div class="cookie-type">
                    <h3>Persistent Cookies</h3>
                    <p>Remain on your device for a set period or until deleted. Used for remembering preferences and analytics.</p>
                </div>

                <div class="cookie-type">
                    <h3>First-Party Cookies</h3>
                    <p>Set directly by our website. Used for site functionality and analytics.</p>
                </div>

                <div class="cookie-type">
                    <h3>Third-Party Cookies</h3>
                    <p>Set by third-party services we use, such as analytics providers and payment processors.</p>
                </div>
            </div>

            <div class="policy-section">
                <h2>Managing Cookies</h2>
                <p>You can control and manage cookies in various ways:</p>
                <ul>
                    <li>Most web browsers allow you to control cookies through their settings</li>
                    <li>You can delete all cookies that are already on your computer</li>
                    <li>You can set most browsers to prevent cookies from being placed</li>
                    <li>Note that disabling cookies may affect the functionality of our website</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Third-Party Services</h2>
                <p>We use the following third-party services that may set cookies:</p>
                <ul>
                    <li><strong>Google Analytics:</strong> For website analytics and performance monitoring</li>
                    <li><strong>Payment Processors:</strong> For secure payment processing</li>
                    <li><strong>Social Media:</strong> For social sharing functionality</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>Updates to This Policy</h2>
                <p>We may update this Cookie Policy from time to time. Any changes will be posted on this page with an updated revision date.</p>
            </div>

            <div class="policy-section">
                <h2>Contact Us</h2>
                <p>If you have any questions about our use of cookies, please contact us at privacy@accessoriesbydija.uk</p>
            </div>
        </div>
    </section>
</main>

<style>
.cookies-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.cookies-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.cookies-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.cookies-content {
    padding: 4rem 0;
}

.policy-section {
    margin-bottom: 3rem;
}

.policy-section h2 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
    color: #333;
}

.policy-section p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.policy-section ul {
    color: #666;
    padding-left: 1.5rem;
}

.policy-section li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.cookie-type {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f8f8;
    border-radius: 8px;
}

.cookie-type h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.cookie-type p {
    color: #666;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .cookies-hero h1 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>