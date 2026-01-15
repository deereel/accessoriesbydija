<?php
require_once 'config/database.php';

$message = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Update newsletter subscribers
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET is_active = 0, unsubscribed_at = NOW() WHERE email = ?");
            $stmt->execute([$email]);

            // Also mark as unsubscribed in abandoned_carts for future reference
            $stmt2 = $pdo->prepare("UPDATE abandoned_carts SET email_sent = 0 WHERE guest_email = ? OR customer_id IN (SELECT id FROM customers WHERE email = ?)");
            $stmt2->execute([$email, $email]);

            $message = "You have been successfully unsubscribed from marketing emails.";
        } catch (PDOException $e) {
            $message = "An error occurred. Please try again later.";
        }
    } else {
        $message = "Please enter a valid email address.";
    }
}

$page_title = "Unsubscribe";
$page_description = "Unsubscribe from marketing emails";
include 'includes/header.php';
?>

<style>
.unsubscribe-container {
    max-width: 500px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #c27ba0;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background: #a66889;
}

.message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="unsubscribe-container">
    <h1>Unsubscribe from Marketing Emails</h1>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <p>We're sorry to see you go! If you no longer wish to receive marketing emails from Dija Accessories, please enter your email address below.</p>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <button type="submit" class="btn">Unsubscribe</button>
    </form>

    <p style="margin-top: 2rem; font-size: 0.9rem; color: #666;">
        If you have any questions or need assistance, please contact our support team at
        <a href="mailto:support@accessoriesbydija.uk">support@accessoriesbydija.uk</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>