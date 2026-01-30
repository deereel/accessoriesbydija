<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Banners Management';
$active_nav = 'banners';

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

<?php include '_layout_header.php'; ?>

<style>
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

    <?php if (isset($success)): ?>
        <div class="card">
            <div class="card-body" style="background:#d4edda; color:#155724;">
                <?php echo $success; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-image"></i> Current Banners</div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-bottom:20px;">
                <?php foreach ($banners as $banner): ?>
                <div style="border:1px solid #ddd; border-radius:8px; padding:16px;">
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

    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-plus"></i> Add New Banner</div>
        <div class="card-body">
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
        </div>
    </div>
    </div>

<?php include '_layout_footer.php'; ?>
