<?php
session_start();
$page_title = "Sign In - Dija Accessories";
$page_description = "Sign in to your account to access your orders and saved items";

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: account.php');
    exit;
}

// Get redirect URL if provided
$redirect = $_GET['redirect'] ?? 'account.php';

include 'includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1>Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account</p>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    
                    <div id="error-message" class="error-message" style="display: none;"></div>
                    <div id="success-message" class="success-message" style="display: none;"></div>
                    
                    <button type="submit" class="auth-btn" id="login-btn">Sign In</button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Create Account</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.auth-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    padding: 2rem 0;
    background: #f8f8f8;
}

.auth-container {
    max-width: 450px;
    margin: 0 auto;
}

.auth-card {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.auth-card h1 {
    text-align: center;
    font-size: 2rem;
    font-weight: 300;
    margin-bottom: 0.5rem;
    color: #222;
}

.auth-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #C27BA0;
}

.form-check {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.form-check input {
    margin-right: 0.5rem;
}

.error-message, .success-message {
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.error-message {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.success-message {
    background: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

.auth-btn {
    width: 100%;
    padding: 1rem;
    background: #C27BA0;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
}

.auth-btn:hover {
    background: #a66889;
}

.auth-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.auth-footer a {
    color: #C27BA0;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .auth-card {
        padding: 1.5rem;
    }
}
</style>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorMsg = document.getElementById('error-message');
    const successMsg = document.getElementById('success-message');
    const submitBtn = document.getElementById('login-btn');
    
    errorMsg.style.display = 'none';
    successMsg.style.display = 'none';
    
    const formData = {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        remember_me: document.getElementById('remember_me').checked
    };
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    
    try {
        const response = await fetch('auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            successMsg.textContent = data.message;
            successMsg.style.display = 'block';
            
            // If there's a localStorage cart, send it to the API to merge server-side, then redirect
            try {
                // Support both DIJACart (new) and cart (older key)
                const raw = localStorage.getItem('DIJACart') || localStorage.getItem('cart') || '[]';
                const localCart = JSON.parse(raw);
                if (Array.isArray(localCart) && localCart.length > 0) {
                    await fetch('/api/cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ migrate_cart: true, items: localCart })
                    });
                    localStorage.removeItem('DIJACart');
                    localStorage.removeItem('cart');
                }
            } catch (e) {
                console.warn('Failed to migrate local cart', e);
            }

            setTimeout(() => {
                window.location.href = '<?php echo htmlspecialchars($redirect); ?>';
            }, 500);
        } else {
            errorMsg.textContent = data.message;
            errorMsg.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In';
        }
    } catch (error) {
        errorMsg.textContent = 'An error occurred. Please try again.';
        errorMsg.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In';
    }
});
</script>

<?php include 'includes/footer.php'; ?>