<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Handle banner operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_banner') {
        // Add new banner logic here
        $success = "Banner added successfully!";
    } elseif ($action === 'update_banner') {
        // Update banner logic here
        $success = "Banner updated successfully!";
    } elseif ($action === 'delete_banner') {
        // Delete banner logic here
        $success = "Banner deleted successfully!";
    }
}

// Sample banner data
$banners = [
    [
        'id' => 1,
        'title' => 'Golden Hour Glow',
        'subtitle' => 'Discover our exclusive collection',
        'description' => 'Warm-toned jewelry that captures the magic of golden hour',
        'image' => 'golden-hour-banner.jpg',
        'link' => 'collection.php?name=golden-hour',
        'button_text' => 'Shop Collection',
        'is_active' => true
    ],
    [
        'id' => 2,
        'title' => 'Luxury Collection',
        'subtitle' => 'Exquisite craftsmanship',
        'description' => 'Finest materials and exceptional attention to detail',
        'image' => 'luxury-banner.jpg',
        'link' => 'collection.php?name=luxury',
        'button_text' => 'Explore Luxury',
        'is_active' => true
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Management - Dija Accessories Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .admin-header { background: #333; color: white; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav { background: #C27BA0; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover { background: rgba(255,255,255,0.2); }
        .banner-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 2rem; }
        .banner-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .banner-form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.5rem 1rem; background: #C27BA0; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #a66889; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Banner Management</h1>
    </header>

    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="banners.php" style="background: rgba(255,255,255,0.2);">Banners</a>
        <a href="testimonials.php">Testimonials</a>
    </nav>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <h2>Current Banners</h2>
    <div class="banner-grid">
        <?php foreach ($banners as $banner): ?>
        <div class="banner-card">
            <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
            <p><strong>Subtitle:</strong> <?php echo htmlspecialchars($banner['subtitle']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($banner['description']); ?></p>
            <p><strong>Image:</strong> <?php echo htmlspecialchars($banner['image']); ?></p>
            <p><strong>Link:</strong> <?php echo htmlspecialchars($banner['link']); ?></p>
            <p><strong>Button:</strong> <?php echo htmlspecialchars($banner['button_text']); ?></p>
            <p><strong>Status:</strong> <?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?></p>
            <div>
                <button class="btn" onclick="editBanner(<?php echo $banner['id']; ?>)">Edit</button>
                <button class="btn btn-danger" onclick="deleteBanner(<?php echo $banner['id']; ?>)">Delete</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <h2>Add New Banner</h2>
    <form class="banner-form" method="POST">
        <input type="hidden" name="action" value="add_banner">
        
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="subtitle">Subtitle</label>
            <input type="text" id="subtitle" name="subtitle">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Background Image</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>
        
        <div class="form-group">
            <label for="link">Link URL</label>
            <input type="url" id="link" name="link" required>
        </div>
        
        <div class="form-group">
            <label for="button_text">Button Text</label>
            <input type="text" id="button_text" name="button_text" value="Shop Now" required>
        </div>
        
        <div class="form-group">
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        
        <button type="submit" class="btn">Add Banner</button>
    </form>

    <script>
        function editBanner(id) {
            // Edit banner functionality
            alert('Edit banner ' + id + ' - Feature to be implemented');
        }
        
        function deleteBanner(id) {
            if (confirm('Are you sure you want to delete this banner?')) {
                // Delete banner functionality
                alert('Delete banner ' + id + ' - Feature to be implemented');
            }
        }
    </script>
</body>
</html>