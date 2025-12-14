<?php
session_start();
$page_title = "Create Account - Dija Accessories";
$page_description = "Create your account to enjoy exclusive benefits and personalized shopping experience";

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
                <h1>Create Account</h1>
                <p class="auth-subtitle">Join Dija Accessories for exclusive benefits</p>
                
                <form id="signupForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div id="error-message" class="error-message" style="display: none;"></div>
                    <div id="success-message" class="success-message" style="display: none;"></div>
                    
                    <button type="submit" class="auth-btn" id="signup-btn">Create Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
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
    max-width: 500px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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

.form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
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
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .auth-card {
        padding: 1.5rem;
    }
}
</style>

<script>
document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorMsg = document.getElementById('error-message');
    const successMsg = document.getElementById('success-message');
    const submitBtn = document.getElementById('signup-btn');
    
    errorMsg.style.display = 'none';
    successMsg.style.display = 'none';
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        errorMsg.textContent = 'Passwords do not match';
        errorMsg.style.display = 'block';
        return;
    }
    
    const formData = {
        first_name: document.getElementById('first_name').value,
        last_name: document.getElementById('last_name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        password: password,
        redirect: (new URLSearchParams(window.location.search)).get('redirect') || 'account.php'
    };
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';
    
    try {
        const response = await fetch('auth/signup.php', {
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
            // Prefer server-provided redirect when available
            const dest = data.redirect ? data.redirect : (new URLSearchParams(window.location.search)).get('redirect') || 'account.php';
            setTimeout(() => {
                window.location.href = dest;
            }, 800);
        } else {
            errorMsg.textContent = data.message;
            errorMsg.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
    } catch (error) {
        errorMsg.textContent = 'An error occurred. Please try again.';
        errorMsg.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
});
</script>

<?php include 'includes/footer.php'; ?>