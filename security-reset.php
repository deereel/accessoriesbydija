<?php
session_start();
require_once 'config/database.php';

// Centralized question pairs (must be identical to signup.php)
$security_question_pairs = [
    1 => ['q1' => 'What was the name of your first primary school?', 'q2' => 'In which city was your first primary school located?'],
    2 => ['q1' => 'What is your mother\'s maiden name?', 'q2' => 'What is your maternal grandmother\'s first name?'],
    3 => ['q1' => 'What was the model of your first car?', 'q2' => 'In what year did you buy your first car?'],
    4 => ['q1' => 'What is the name of your oldest sibling?', 'q2' => 'What is your oldest sibling\'s birthday (Month and Day)?'],
    5 => ['q1' => 'What was the name of your first pet?', 'q2' => 'What breed was your first pet?'],
    6 => ['q1' => 'In what city were you born?', 'q2' => 'What was the name of the hospital where you were born?'],
    7 => ['q1' => 'What is your favorite book?', 'q2' => 'Who is the author of your favorite book?'],
    8 => ['q1' => 'What is the name of the street you grew up on?', 'q2' => 'What was your house number on the street you grew up on?'],
    9 => ['q1' => 'What was your childhood nickname?', 'q2' => 'Who gave you your childhood nickname?'],
    10 => ['q1' => 'If you could get a wild pet, what animal would it be?', 'q2' => 'What would you name it?'],
    11 => ['q1' => 'What is your favorite movie?', 'q2' => 'Who was the lead actor/actress in your favorite movie?'],
];

$error = '';
$success = '';
$step = $_REQUEST['step'] ?? 'email'; // email, ask, verify, set

$customer_id_for_reset = $_SESSION['customer_id_for_reset'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'ask') {
        $email = $_POST['email'] ?? null;
        if ($email) {
            $stmt = $pdo->prepare("SELECT id, security_question_pair_id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && !empty($user['security_question_pair_id'])) {
                $_SESSION['customer_id_for_reset'] = $user['id'];
                $_SESSION['question_pair_id'] = $user['security_question_pair_id'];
                // We don't change the step here, we let the page re-render to show the questions
            } else {
                $error = "No account found with that email or no security questions set up.";
                $step = 'email';
            }
        } else {
            $error = "Please enter an email address.";
            $step = 'email';
        }
    } elseif ($step === 'verify') {
        if ($customer_id_for_reset) {
            $answer1 = $_POST['security_answer_1'];
            $answer2 = $_POST['security_answer_2'];

            $stmt = $pdo->prepare("SELECT security_answer_1_hash, security_answer_2_hash FROM customers WHERE id = ?");
            $stmt->execute([$customer_id_for_reset]);
            $hashes = $stmt->fetch();

            if ($hashes && password_verify($answer1, $hashes['security_answer_1_hash']) && password_verify($answer2, $hashes['security_answer_2_hash'])) {
                $_SESSION['reset_password_allowed'] = true;
                header('Location: security-reset.php?step=set');
                exit;
            } else {
                $error = "One or both answers were incorrect.";
                // Let the user try again, so we re-render the 'ask' step
                $step = 'ask';
            }
        } else {
             $error = "Your session has expired. Please start over.";
             $step = 'email';
        }
    } elseif ($step === 'set') {
        if ($customer_id_for_reset && ($_SESSION['reset_password_allowed'] ?? false)) {
            $new_password = $_POST['new_password'];
            if (strlen($new_password) >= 6) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE customers SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $customer_id_for_reset]);
                
                // Cleanup session
                unset($_SESSION['customer_id_for_reset'], $_SESSION['question_pair_id'], $_SESSION['reset_password_allowed']);
                
                $success = "Password updated successfully! You can now <a href='login.php'>sign in</a>.";
            } else {
                $error = "Password must be at least 6 characters long.";
            }
        } else {
            $error = "Invalid request or session expired. Please start over.";
            $step = 'email';
        }
    }
}

$page_title = "Password Reset - Dija Accessories";
include 'includes/header.php';
?>
<style>
.auth-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
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

.auth-card h2 {
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

.error-message, .success-message {
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: block;
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

</style>
<main class="auth-page">
<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Password Reset</h2>
            <?php if ($error): ?><div class="error-message" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="success-message" style="display:block; background:#efe; color:#3c3; border:1px solid #cfc;"><?= $success ?></div><?php endif; ?>

            <?php if ($step === 'email'): ?>
                <p>Enter your email to begin the password reset process.</p>
                <form method="POST" action="?step=ask">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <button type="submit" class="auth-btn">Continue</button>
                </form>
                <div style="text-align:center; margin-top:12px;">
                    <button id="open-support-btn" class="auth-btn" style="width:auto; padding:0.6rem 1rem; background:#6c757d;">Can't reset? Get help</button>
                </div>
            <?php elseif ($step === 'ask' && isset($_SESSION['customer_id_for_reset'])):
                $pair_id = $_SESSION['question_pair_id'];
                $question1 = $security_question_pairs[$pair_id]['q1'];
                $question2 = $security_question_pairs[$pair_id]['q2'];
            ?>
                <p>Please answer your security questions.</p>
                <form method="POST" action="?step=verify">
                    <div class="form-group">
                        <label><?= htmlspecialchars($question1) ?></label>
                        <input type="text" name="security_answer_1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?= htmlspecialchars($question2) ?></label>
                        <input type="text" name="security_answer_2" required class="form-control">
                    </div>
                    <button type="submit" class="auth-btn">Verify Answers</button>
                </form>
                <div style="text-align:center; margin-top:12px;">
                    <button id="open-support-btn-2" class="auth-btn" style="width:auto; padding:0.6rem 1rem; background:#6c757d;">Can't reset? Get help</button>
                </div>
            <?php elseif ($step === 'set' && ($_SESSION['reset_password_allowed'] ?? false)): ?>
                <p>You may now set a new password.</p>
                <form method="POST" action="?step=set">
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6">
                    </div>
                    <button type="submit" class="auth-btn">Set New Password</button>
                </form>
                <div style="text-align:center; margin-top:12px;">
                    <button id="open-support-btn-3" class="auth-btn" style="width:auto; padding:0.6rem 1rem; background:#6c757d;">Need help? Contact support</button>
                </div>
            <?php elseif (!$success): // Fallback / Error case ?>
                 <p>If you did not see your questions, please <a href="?step=email">start over</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<!-- Support modal -->
<div id="support-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1200; align-items:center; justify-content:center;">
    <div style="background:#fff; width:90%; max-width:520px; padding:20px; border-radius:10px; box-shadow:0 6px 30px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Get help with password reset</h3>
        <p style="color:#666; font-size:0.95rem;">If you're unable to reset your password using the form, open a support ticket and our team will assist.</p>
        <div id="support-alert" style="display:none; margin-bottom:12px;"></div>
        <div style="margin-bottom:10px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Your name</label>
            <input id="support-name" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Email</label>
            <input id="support-email" type="email" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Message</label>
            <textarea id="support-message" rows="5" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">I cannot reset my password. Please assist.</textarea>
        </div>
        <div style="display:flex; gap:8px; justify-content:flex-end;">
            <button id="support-cancel" class="auth-btn" style="background:#ddd; color:#222; padding:0.6rem 1rem; width:auto;">Cancel</button>
            <button id="support-send" class="auth-btn" style="background:#2d8cff; padding:0.6rem 1rem; width:auto;">Send Ticket</button>
        </div>
    </div>
</div>

<script>
// Support modal handling and POST to API
(function(){
    function el(id){return document.getElementById(id);}
    var modal = el('support-modal');
    var openBtns = [el('open-support-btn'), el('open-support-btn-2'), el('open-support-btn-3')].filter(Boolean);
    openBtns.forEach(function(b){ b && b.addEventListener('click', open); });
    el('support-cancel').addEventListener('click', close);
    el('support-send').addEventListener('click', sendTicket);

    // Prefill email field from page email input if present
    function open(e){
        e && e.preventDefault();
        var pageEmail = document.querySelector('input[name="email"]');
        if (pageEmail && pageEmail.value) el('support-email').value = pageEmail.value;
        modal.style.display = 'flex';
    }
    function close(){
        modal.style.display = 'none';
    }

    function showAlert(text, ok){
        var a = el('support-alert');
        a.style.display = 'block';
        a.style.padding = '10px';
        a.style.borderRadius = '6px';
        if (ok){ a.style.background='#e6ffed'; a.style.color='#116633'; a.style.border='1px solid #ccecd2'; }
        else { a.style.background='#fff2f2'; a.style.color='#8b1d1d'; a.style.border='1px solid #f5c6cb'; }
        a.innerText = text;
    }

    function sendTicket(){
        var name = el('support-name').value.trim();
        var email = el('support-email').value.trim();
        var message = el('support-message').value.trim();
        if (!email || !message) { showAlert('Please provide your email and a short message.', false); return; }

        var payload = { name: name || 'Guest', email: email, subject: 'Password reset help', message: message, category: 'password-reset', priority: 'high' };

        fetch('/api/support/create-ticket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function(res){ return res.json(); }).then(function(json){
            if (json && json.success){
                showAlert('Ticket created (ID: ' + (json.ticket_id || 'N/A') + '). Our team will contact you.', true);
                setTimeout(close, 2200);
            } else {
                showAlert(json && json.message ? json.message : 'Failed to create ticket', false);
            }
        }).catch(function(err){ showAlert('Network error creating ticket', false); });
    }
})();
</script>
<?php include 'includes/footer.php'; ?>
