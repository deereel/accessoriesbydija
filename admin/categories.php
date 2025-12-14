<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Categories Management';
$active_nav = 'categories';

require_once '../config/database.php';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if ($name) {
            $slug = strtolower(str_replace(' ', '-', $name));
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $parent_id, 1]);
            $success = "Category added successfully!";
        }
    } elseif ($action === 'update_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if ($id && $name) {
            $slug = strtolower(str_replace(' ', '-', $name));
            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, parent_id=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $parent_id, $id]);
            $success = "Category updated successfully!";
        }
    } elseif ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            $success = "Category deleted successfully!";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Categories Management
        <button class="btn" style="float:right; margin-top:-8px;" onclick="document.getElementById('add-form').style.display='block';">+ Add Category</button>
    </div>
    <div class="card-body">
        <?php if (isset($success)): ?>
            <div style="background:#d4edda; color:#155724; padding:12px; border-radius:6px; margin-bottom:12px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div id="add-form" style="display:none; margin-bottom:20px; padding:16px; background:#f9f9f9; border-radius:6px; border:1px solid #eee;">
            <h3>Add New Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div style="margin-bottom:12px;">
                    <label>Category Name *</label>
                    <input type="text" name="name" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label>Description</label>
                    <textarea name="description" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; height:80px;"></textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label>Parent Category</label>
                    <select name="parent_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- No Parent --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Create Category</button>
                <button type="button" class="btn" style="background:#999;" onclick="document.getElementById('add-form').style.display='none';">Cancel</button>
            </form>
        </div>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f5f5f5;">
                    <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">ID</th>
                    <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Name</th>
                    <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Slug</th>
                    <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?php echo $category['id']; ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($category['name']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($category['slug']); ?></td>
                        <td style="padding:10px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" class="btn" style="background:#dc3545; font-size:12px;" onclick="return confirm('Delete this category?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '_layout_footer.php'; ?>
