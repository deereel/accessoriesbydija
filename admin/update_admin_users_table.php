<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

echo "<h1>Admin User Migration</h1>";

try {
    // Step 1: Alter the role column
    echo "<p>Attempting to modify 'role' column in 'admin_users' table...</p>";
    $pdo->exec("ALTER TABLE admin_users MODIFY COLUMN role ENUM('superadmin', 'admin', 'staff') DEFAULT 'staff'");
    echo "<p style='color:green;'>Successfully modified the 'role' column.</p>";

    // Step 2: Update the main 'admin' user to be a superadmin
    echo "<p>Attempting to promote 'admin' user to 'superadmin'...</p>";
    $stmt_promote = $pdo->prepare("UPDATE admin_users SET role = 'superadmin' WHERE username = 'admin'");
    $promoted = $stmt_promote->execute();
    if ($promoted && $stmt_promote->rowCount() > 0) {
        echo "<p style='color:green;'>Successfully promoted 'admin' user.</p>";
    } else {
        echo "<p style='color:orange;'>Could not find user 'admin' to promote, or they are already a superadmin.</p>";
    }
    
    // Step 3: Update password for 'admin' user to 'admin123'
    echo "<p>Attempting to reset password for 'admin' user to 'admin123'...</p>";
    $new_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt_pass = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
    $pass_updated = $stmt_pass->execute([$new_password_hash]);
    if ($pass_updated && $stmt_pass->rowCount() > 0) {
        echo "<p style='color:green;'>Successfully reset password for 'admin' user.</p>";
    } else {
        echo "<p style='color:orange;'>Could not find user 'admin' to reset password.</p>";
    }

    echo "<h2>Migration Complete!</h2>";
    echo "<p>You should now log out and log back in for the changes to take effect.</p>";
    echo "<p>You can remove this file ('update_admin_users_table.php') now.</p>";
    echo '<a href="index.php">Go to Dashboard</a>';

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>An Error Occurred</h2>";
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database schema and permissions.</p>";
}
