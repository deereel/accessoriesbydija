<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Categories Management';
$active_nav = 'categories';

require_once '../app/config/database.php';

// Handle all operations via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_category') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            
            if ($name) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $parent_id, 1]);
                echo json_encode(['success' => true, 'message' => 'Category added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
            }
            exit;
        } elseif ($action === 'update_category') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            
            if ($id && $name) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, parent_id=? WHERE id=?");
                $stmt->execute([$name, $slug, $description, $parent_id, $id]);
                echo json_encode(['success' => true, 'message' => 'Category updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid category data']);
            }
            exit;
        } elseif ($action === 'delete_category') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            }
            exit;
        } elseif ($action === 'add_color') {
            $name = trim($_POST['name'] ?? '');
            $hex_code = trim($_POST['hex_code'] ?? '');
            
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO colors (name, hex_code, is_active) VALUES (?, ?, ?)");
                $stmt->execute([$name, $hex_code, 1]);
                echo json_encode(['success' => true, 'message' => 'Color added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Color name is required']);
            }
            exit;
        } elseif ($action === 'update_color') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $hex_code = trim($_POST['hex_code'] ?? '');
            
            if ($id && $name) {
                $stmt = $pdo->prepare("UPDATE colors SET name=?, hex_code=? WHERE id=?");
                $stmt->execute([$name, $hex_code, $id]);
                echo json_encode(['success' => true, 'message' => 'Color updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid color data']);
            }
            exit;
        } elseif ($action === 'delete_color') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM colors WHERE id=?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Color deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid color ID']);
            }
            exit;
        } elseif ($action === 'add_adornment') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($name) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $stmt = $pdo->prepare("INSERT INTO adornments (name, slug, description, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, 1]);
                echo json_encode(['success' => true, 'message' => 'Adornment added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Adornment name is required']);
            }
            exit;
        } elseif ($action === 'update_adornment') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($id && $name) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $stmt = $pdo->prepare("UPDATE adornments SET name=?, slug=?, description=? WHERE id=?");
                $stmt->execute([$name, $slug, $description, $id]);
                echo json_encode(['success' => true, 'message' => 'Adornment updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid adornment data']);
            }
            exit;
        } elseif ($action === 'delete_adornment') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM adornments WHERE id=?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Adornment deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid adornment ID']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch colors
try {
    $stmt = $pdo->query("SELECT * FROM colors ORDER BY name ASC");
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
    $colors = [];
}

// Fetch adornments
try {
    $stmt = $pdo->query("SELECT * FROM adornments ORDER BY name ASC");
    $adornments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
    $adornments = [];
}
?>

<?php include '_layout_header.php'; ?>

<style>
    .tabs-container {
        margin-bottom: 20px;
    }
    
    .tabs {
        display: flex;
        border-bottom: 2px solid #ddd;
        background: #f8f8f8;
        border-radius: 8px 8px 0 0;
    }
    
    .tab-btn {
        padding: 15px 25px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.3s;
    }
    
    .tab-btn:hover {
        color: #333;
        background: #f0f0f0;
    }
    
    .tab-btn.active {
        color: var(--accent);
        background: white;
        border-bottom-color: var(--accent);
    }
    
    .tab-content {
        display: none;
        padding: 20px 0;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .item-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .item-table th,
    .item-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .item-table th {
        background: #f8f8f8;
        font-weight: 600;
    }
    
    .item-table tr:hover {
        background: #fafafa;
    }
    
    .color-swatch {
        width: 30px;
        height: 30px;
        border-radius: 4px;
        border: 1px solid #ddd;
        display: inline-block;
        vertical-align: middle;
        margin-right: 8px;
    }
    
    .add-form {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #eee;
    }
    
    .add-form h3 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: Arial, sans-serif;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--accent);
    }
    
    .color-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .color-input-group input[type="color"] {
        width: 50px;
        height: 40px;
        padding: 2px;
        cursor: pointer;
    }
    
    .btn {
        padding: 10px 20px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: inline-block;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .btn-secondary {
        background: #999;
    }
    
    .btn-danger {
        background: #dc3545;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .actions {
        display: flex;
        gap: 5px;
    }
    
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
</style>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-list"></i> Categories, Colors & Adornments Management</span>
    </div>
    <div class="card-body">
        <div id="message-container"></div>
        
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('categories')">
                    <i class="fas fa-list"></i> Categories
                </button>
                <button class="tab-btn" onclick="switchTab('colors')">
                    <i class="fas fa-palette"></i> Colors
                </button>
                <button class="tab-btn" onclick="switchTab('adornments')">
                    <i class="fas fa-gem"></i> Adornments
                </button>
            </div>
            
            <!-- Categories Tab -->
            <div id="categories-tab" class="tab-content active">
                <div class="add-form">
                    <h3>Add New Category</h3>
                    <form id="category-form" onsubmit="handleCategorySubmit(event)">
                        <input type="hidden" name="action" value="add_category">
                        <input type="hidden" name="ajax" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category Name *</label>
                                <input type="text" name="name" required placeholder="e.g., Rings">
                            </div>
                            <div class="form-group">
                                <label>Parent Category</label>
                                <select name="parent_id">
                                    <option value="">-- No Parent --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="2" placeholder="Category description..."></textarea>
                        </div>
                        <button type="submit" class="btn">Create Category</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Parent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No categories yet. Add your first category above.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td><?php echo $category['parent_id'] ? 'Yes' : '-'; ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description'] ?? ''); ?>', <?php echo $category['parent_id'] ?? 'null'; ?>)">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="ajax" value="1">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Colors Tab -->
            <div id="colors-tab" class="tab-content">
                <div class="add-form">
                    <h3>Add New Color</h3>
                    <form id="color-form" onsubmit="handleColorSubmit(event)">
                        <input type="hidden" name="action" value="add_color">
                        <input type="hidden" name="ajax" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Color Name *</label>
                                <input type="text" name="name" required placeholder="e.g., Gold">
                            </div>
                            <div class="form-group">
                                <label>Hex Code</label>
                                <div class="color-input-group">
                                    <input type="color" id="color-picker" value="#C0C0C0" onchange="document.getElementById('hex-input').value = this.value">
                                    <input type="text" id="hex-input" name="hex_code" placeholder="#C0C0C0" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn">Add Color</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Color</th>
                                <th>Name</th>
                                <th>Hex Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($colors)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-palette"></i>
                                        <p>No colors yet. Add your first color above.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($colors as $color): ?>
                                    <tr>
                                        <td><?php echo $color['id']; ?></td>
                                        <td>
                                            <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($color['hex_code'] ?? '#C0C0C0'); ?>"></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($color['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($color['hex_code'] ?? '-'); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm" onclick="editColor(<?php echo $color['id']; ?>, '<?php echo htmlspecialchars($color['name']); ?>', '<?php echo htmlspecialchars($color['hex_code'] ?? ''); ?>')">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this color?');">
                                                <input type="hidden" name="action" value="delete_color">
                                                <input type="hidden" name="ajax" value="1">
                                                <input type="hidden" name="id" value="<?php echo $color['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Adornments Tab -->
            <div id="adornments-tab" class="tab-content">
                <div class="add-form">
                    <h3>Add New Adornment</h3>
                    <form id="adornment-form" onsubmit="handleAdornmentSubmit(event)">
                        <input type="hidden" name="action" value="add_adornment">
                        <input type="hidden" name="ajax" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Adornment Name *</label>
                                <input type="text" name="name" required placeholder="e.g., Diamond">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="2" placeholder="Description of this adornment type..."></textarea>
                        </div>
                        <button type="submit" class="btn">Add Adornment</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($adornments)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-gem"></i>
                                        <p>No adornments yet. Add your first adornment above.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($adornments as $adornment): ?>
                                    <tr>
                                        <td><?php echo $adornment['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($adornment['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($adornment['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($adornment['description'] ?? '-'); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm" onclick="editAdornment(<?php echo $adornment['id']; ?>, '<?php echo htmlspecialchars($adornment['name']); ?>', '<?php echo htmlspecialchars($adornment['description'] ?? ''); ?>')">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this adornment?');">
                                                <input type="hidden" name="action" value="delete_adornment">
                                                <input type="hidden" name="ajax" value="1">
                                                <input type="hidden" name="id" value="<?php echo $adornment['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.closest('.tab-btn').classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
    }
    
    function showMessage(message, type) {
        const container = document.getElementById('message-container');
        container.innerHTML = `<div class="${type}-message">${message}</div>`;
        setTimeout(() => container.innerHTML = '', 5000);
    }
    
    async function handleCategorySubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                form.reset();
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            showMessage('An error occurred. Please try again.', 'error');
        }
    }
    
    async function handleColorSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                form.reset();
                document.getElementById('color-picker').value = '#C0C0C0';
                document.getElementById('hex-input').value = '';
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            showMessage('An error occurred. Please try again.', 'error');
        }
    }
    
    async function handleAdornmentSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                form.reset();
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            showMessage('An error occurred. Please try again.', 'error');
        }
    }
    
    function editCategory(id, name, description, parentId) {
        const form = document.getElementById('category-form');
        form.querySelector('input[name="action"]').value = 'update_category';
        form.querySelector('input[name="name"]').value = name;
        form.querySelector('textarea[name="description"]').value = description || '';
        
        const parentSelect = form.querySelector('select[name="parent_id"]');
        if (parentId) {
            parentSelect.value = parentId;
        } else {
            parentSelect.value = '';
        }
        
        // Add hidden ID field
        let idField = form.querySelector('input[name="id"]');
        if (!idField) {
            idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id';
            form.appendChild(idField);
        }
        idField.value = id;
        
        // Change submit button text
        form.querySelector('button[type="submit"]').textContent = 'Update Category';
        
        // Add cancel button if not exists
        if (!form.querySelector('.cancel-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-secondary cancel-btn';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = resetCategoryForm;
            form.querySelector('button[type="submit"]').before(cancelBtn);
        }
        
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth' });
    }
    
    function resetCategoryForm() {
        const form = document.getElementById('category-form');
        form.reset();
        form.querySelector('input[name="action"]').value = 'add_category';
        
        const idField = form.querySelector('input[name="id"]');
        if (idField) idField.remove();
        
        const cancelBtn = form.querySelector('.cancel-btn');
        if (cancelBtn) cancelBtn.remove();
        
        form.querySelector('button[type="submit"]').textContent = 'Create Category';
    }
    
    function editColor(id, name, hexCode) {
        const form = document.getElementById('color-form');
        form.querySelector('input[name="action"]').value = 'update_color';
        form.querySelector('input[name="name"]').value = name;
        form.querySelector('input[name="hex_code"]').value = hexCode || '#C0C0C0';
        document.getElementById('color-picker').value = hexCode || '#C0C0C0';
        document.getElementById('hex-input').value = hexCode || '';
        
        // Add hidden ID field
        let idField = form.querySelector('input[name="id"]');
        if (!idField) {
            idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id';
            form.appendChild(idField);
        }
        idField.value = id;
        
        // Change submit button text
        form.querySelector('button[type="submit"]').textContent = 'Update Color';
        
        // Add cancel button if not exists
        if (!form.querySelector('.cancel-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-secondary cancel-btn';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = resetColorForm;
            form.querySelector('button[type="submit"]').before(cancelBtn);
        }
        
        form.scrollIntoView({ behavior: 'smooth' });
    }
    
    function resetColorForm() {
        const form = document.getElementById('color-form');
        form.reset();
        form.querySelector('input[name="action"]').value = 'add_color';
        
        const idField = form.querySelector('input[name="id"]');
        if (idField) idField.remove();
        
        const cancelBtn = form.querySelector('.cancel-btn');
        if (cancelBtn) cancelBtn.remove();
        
        form.querySelector('button[type="submit"]').textContent = 'Add Color';
        document.getElementById('color-picker').value = '#C0C0C0';
        document.getElementById('hex-input').value = '';
    }
    
    function editAdornment(id, name, description) {
        const form = document.getElementById('adornment-form');
        form.querySelector('input[name="action"]').value = 'update_adornment';
        form.querySelector('input[name="name"]').value = name;
        form.querySelector('textarea[name="description"]').value = description || '';
        
        // Add hidden ID field
        let idField = form.querySelector('input[name="id"]');
        if (!idField) {
            idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id';
            form.appendChild(idField);
        }
        idField.value = id;
        
        // Change submit button text
        form.querySelector('button[type="submit"]').textContent = 'Update Adornment';
        
        // Add cancel button if not exists
        if (!form.querySelector('.cancel-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-secondary cancel-btn';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = resetAdornmentForm;
            form.querySelector('button[type="submit"]').before(cancelBtn);
        }
        
        form.scrollIntoView({ behavior: 'smooth' });
    }
    
    function resetAdornmentForm() {
        const form = document.getElementById('adornment-form');
        form.reset();
        form.querySelector('input[name="action"]').value = 'add_adornment';
        
        const idField = form.querySelector('input[name="id"]');
        if (idField) idField.remove();
        
        const cancelBtn = form.querySelector('.cancel-btn');
        if (cancelBtn) cancelBtn.remove();
        
        form.querySelector('button[type="submit"]').textContent = 'Add Adornment';
    }
</script>

<?php include '_layout_footer.php'; ?>
