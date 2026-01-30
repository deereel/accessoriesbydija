<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../app/config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categories_to_add = [
        'anklets', 'bangles', 'bracelets', 'earrings', 'necklaces', 'pendants', 'rings', 'sets', 'studs', 'watches',
        'cufflinks', 'Hand Chains', 'lapels-clips'
    ];

    try {
        $pdo->beginTransaction();

        foreach ($categories_to_add as $category_name) {
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$category_name]);
            $existing = $stmt->fetch();

            if (!$existing) {
                // Generate slug: replace spaces and multiple hyphens with single hyphens
                $slug = strtolower(preg_replace('/-+/', '-', str_replace(' ', '-', $category_name)));
                $original_slug = $slug;
                $counter = 1;
    
                // Ensure unique slug
                while (true) {
                    $check_slug = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                    $check_slug->execute([$slug]);
                    if (!$check_slug->fetch()) {
                        break; // Slug is unique
                    }
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                }
    
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, is_active) VALUES (?, ?, 1)");
                $stmt->execute([$category_name, $slug]);
                $message .= "Added category: $category_name (slug: $slug)<br>";
            } else {
                $message .= "Category already exists: $category_name<br>";
            }
        }

        $pdo->commit();
        $message .= "<br>Categories setup completed.";
        $success = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $success = false;
    }
}

$page_title = "Add Missing Categories";
include '_layout_header.php';
?>

<div class="container">
    <h1>Add Missing Categories</h1>
    <p>This script will add the missing categories required for the megamenu and index page filtering to work properly.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_POST['run'])): ?>
        <form method="POST">
            <button type="submit" name="run" class="btn btn-primary">Add Categories</button>
        </form>
    <?php endif; ?>
</div>

<?php include '_layout_footer.php'; ?>