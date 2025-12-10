<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Handle product operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
        $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, sku, price, stock_quantity, weight, material, stone_type, gender, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['product_name'], $slug, $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['material'], $_POST['stone_type'], $_POST['gender'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0]);
        $success = "Product added successfully!";
    } elseif ($action === 'update_product') {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, sku=?, price=?, stock_quantity=?, weight=?, material=?, stone_type=?, gender=?, is_featured=?, is_active=? WHERE id=?");
        $stmt->execute([$_POST['product_name'], $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['material'], $_POST['stone_type'], $_POST['gender'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0, $_POST['product_id']]);
        $success = "Product updated successfully!";
    } elseif ($action === 'delete_product') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$_POST['product_id']]);
        $success = "Product deleted successfully!";
    }
}

// Fetch products from database
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Dija Accessories Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .admin-header { background: #333; color: white; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav { background: #C27BA0; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); }
        .controls { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .products-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f8f8; font-weight: 600; }
        .product-image { width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .featured-badge { background: #fff3cd; color: #856404; }
        .btn { padding: 6px 12px; background: #C27BA0; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
        .btn:hover { background: #a66889; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 600px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #C27BA0; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Product Management</h1>
    </header>

    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php" class="active">Products</a>
        <a href="categories.php">Categories</a>
        <a href="banners.php">Banners</a>
        <a href="testimonials.php">Testimonials</a>
    </nav>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="controls">
        <div class="search-box">
            <input type="text" placeholder="Search products..." id="search-input">
            <select id="category-filter">
                <option value="">All Categories</option>
                <option value="women">Women</option>
                <option value="men">Men</option>
            </select>
            <select id="status-filter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button class="btn btn-success" onclick="openAddModal()">+ Add Product</button>
    </div>

    <div class="products-table">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Material</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div class="product-image"><?php echo substr($product['name'], 0, 3); ?></div>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                        <?php if ($product['is_featured']): ?>
                            <span class="status-badge featured-badge">Featured</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td>£<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($product['material'] ?? 'N/A'); ?></td>
                    <td><?php echo $product['stock_quantity']; ?></td>
                    <td><?php echo htmlspecialchars($product['material']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                        <button class="btn" onclick="toggleFeatured(<?php echo $product['id']; ?>)">
                            <?php echo $product['is_featured'] ? 'Unfeature' : 'Feature'; ?>
                        </button>
                        <button class="btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Product</h2>
            <form id="productForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_product">
                <input type="hidden" name="product_id" id="productId">
                
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" id="product_name" name="product_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input type="text" id="sku" name="sku" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (£)</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock" required>
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (g)</label>
                        <input type="number" id="weight" name="weight" step="0.1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="women-rings">Women > Rings</option>
                            <option value="women-earrings">Women > Earrings</option>
                            <option value="women-necklaces">Women > Necklaces</option>
                            <option value="women-bracelets">Women > Bracelets</option>
                            <option value="men-rings">Men > Rings</option>
                            <option value="men-bracelets">Men > Bracelets</option>
                            <option value="men-necklaces">Men > Necklaces</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material">Material</label>
                        <select id="material" name="material" required>
                            <option value="">Select Material</option>
                            <option value="Gold">Gold</option>
                            <option value="Silver">Silver</option>
                            <option value="Platinum">Platinum</option>
                            <option value="Rose Gold">Rose Gold</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="stone_type">Stone Type</label>
                        <select id="stone_type" name="stone_type">
                            <option value="">No Stone</option>
                            <option value="Diamond">Diamond</option>
                            <option value="Ruby">Ruby</option>
                            <option value="Emerald">Emerald</option>
                            <option value="Sapphire">Sapphire</option>
                            <option value="Pearl">Pearl</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="unisex">Unisex</option>
                            <option value="women">Women</option>
                            <option value="men">Men</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_featured" name="is_featured" value="1">
                            Featured Product
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            Active
                        </label>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('productModal').style.display = 'block';
        }
        
        function editProduct(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('formAction').value = 'update_product';
                    document.getElementById('productId').value = product.id;
                    document.getElementById('product_name').value = product.name;
                    document.getElementById('sku').value = product.sku;
                    document.getElementById('price').value = product.price;
                    document.getElementById('stock').value = product.stock_quantity;
                    document.getElementById('weight').value = product.weight || '';
                    document.getElementById('description').value = product.description || '';
                    document.getElementById('material').value = product.material || '';
                    document.getElementById('stone_type').value = product.stone_type || '';
                    document.getElementById('gender').value = product.gender;
                    document.getElementById('is_featured').checked = product.is_featured == 1;
                    document.getElementById('is_active').checked = product.is_active == 1;
                    document.getElementById('productModal').style.display = 'block';
                });
        }
        
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleFeatured(id) {
            fetch('toggle_featured.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `product_id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error toggling featured status');
            });
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('input', filterProducts);
        document.getElementById('category-filter').addEventListener('change', filterProducts);
        document.getElementById('status-filter').addEventListener('change', filterProducts);
        
        function filterProducts() {
            // Filter functionality to be implemented
            console.log('Filtering products...');
        }
    </script>
</body>
</html>