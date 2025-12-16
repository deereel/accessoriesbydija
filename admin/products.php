<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Products Management';
$active_nav = 'products';

require_once '../config/database.php';

// Pagination setup
$limit = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtering and Search
$where = ["1=1"]; // Start with a true condition
$params = [];

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($_GET['gender'])) {
    $where[] = "p.gender = ?";
    $params[] = $_GET['gender'];
}

if (!empty($_GET['status'])) {
    $status = $_GET['status'] === 'active' ? 1 : 0;
    $where[] = "p.is_active = ?";
    $params[] = $status;
}

// Get total number of products for pagination
$count_sql = "SELECT COUNT(*) FROM products p WHERE " . implode(" AND ", $where);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_products_count / $limit);


// Handle product operations for authorized users
if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])) {
    if ($_POST) {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_product') {
            $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, sku, price, stock_quantity, weight, material, stone_type, gender, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['product_name'], $slug, $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['material'], $_POST['stone_type'], $_POST['gender'], $_POST['category'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0]);
            $product_id = $pdo->lastInsertId();

            $uploads_dir = '../assets/images/products';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }

            // Handle main image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '-' . basename($_FILES['main_image']['name']);
                $destination = "$uploads_dir/$file_name";
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                    $image_url = "assets/images/products/$file_name";
                    $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                    $img_stmt->execute([$product_id, $image_url, 1]);
                }
            }

            // Handle additional images upload
            if (isset($_FILES['additional_images'])) {
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '-' . basename($_FILES['additional_images']['name'][$key]);
                        $destination = "$uploads_dir/$file_name";
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $image_url = "assets/images/products/$file_name";
                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $image_url, 0]);
                        }
                    }
                }
            }
            $success = "Product added successfully!";
        } elseif ($action === 'update_product') {
            $product_id = (int)$_POST['product_id'];
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, sku=?, price=?, stock_quantity=?, weight=?, material=?, stone_type=?, gender=?, is_featured=?, is_active=? WHERE id=?");
            $stmt->execute([$_POST['product_name'], $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['material'], $_POST['stone_type'], $_POST['gender'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0, $product_id]);

            $uploads_dir = '../assets/images/products';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }

            // Handle main image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                // Find and delete old primary image
                $find_old_primary_stmt = $pdo->prepare("SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 1");
                $find_old_primary_stmt->execute([$product_id]);
                $old_primary = $find_old_primary_stmt->fetch();

                if ($old_primary) {
                    if (file_exists('../' . $old_primary['image_url'])) {
                        unlink('../' . $old_primary['image_url']);
                    }
                    $delete_old_primary_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                    $delete_old_primary_stmt->execute([$old_primary['id']]);
                }

                $file_name = uniqid() . '-' . basename($_FILES['main_image']['name']);
                $destination = "$uploads_dir/$file_name";
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                    $image_url = "assets/images/products/$file_name";
                    $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                    $img_stmt->execute([$product_id, $image_url, 1]);
                }
            }

            // Handle additional images upload
            if (isset($_FILES['additional_images'])) {
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '-' . basename($_FILES['additional_images']['name'][$key]);
                        $destination = "$uploads_dir/$file_name";
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $image_url = "assets/images/products/$file_name";
                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $image_url, 0]);
                        }
                    }
                }
            }
            $success = "Product updated successfully!";
        } elseif ($action === 'delete_product') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$_POST['product_id']]);
            $success = "Product deleted successfully!";
        }
    }
}

// Fetch products from database with filters and pagination
$sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image 
        FROM products p 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind filter parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}

// Bind LIMIT and OFFSET as integers
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Fetch categories for the modal dropdown
$categories_from_db = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>

    <?php if (isset($success)): ?>
        <div class="card">
            <div class="card-body" style="background:#d4edda; color:#155724;">
                <?php echo $success; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-gem"></i> Products Management
            <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])): ?>
            <button class="btn" style="float:right; margin-top:-8px;" onclick="openAddModal()">+ Add Product</button>
            <?php endif; ?>
            <a href="products.php" class="btn" style="float:right; margin-top:-8px; margin-right:10px; background:#6c757d;">Clear Filters</a>
        </div>
        <div class="card-body">
            <div style="margin-bottom:16px; display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <select name="gender" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">All Genders</option>
                    <option value="unisex" <?php echo ($_GET['gender'] ?? '') === 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                    <option value="women" <?php echo ($_GET['gender'] ?? '') === 'women' ? 'selected' : ''; ?>>Women</option>
                    <option value="men" <?php echo ($_GET['gender'] ?? '') === 'men' ? 'selected' : ''; ?>>Men</option>
                </select>
                <select name="status" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit" class="btn">Filter</button>
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Image</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Product</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">SKU</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Price</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Weight</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Stock</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Material</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Status</th>
                            <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])): ?>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;"> 
                                <?php if (!empty($product['main_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                                <?php else: ?>
                                    <div style="width:50px; height:50px; background:#f0f0f0; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:12px; color:#666;">
                                        No Img
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;">
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['is_featured']): ?>
                                    <br><span style="background:#fff3cd; color:#856404; padding:2px 6px; border-radius:3px; font-size:11px;">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td style="padding:10px;">£<?php echo number_format($product['price'], 2); ?></td>
                            <td style="padding:10px;"><?php echo $product['weight'] ? htmlspecialchars($product['weight']) . 'g' : '-'; ?></td>
                            <td style="padding:10px;"><?php echo $product['stock_quantity']; ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($product['material']); ?></td>
                            <td style="padding:10px;">
                                <span style="background:<?php echo $product['is_active']?'#d4edda':'#f8d7da'; ?>; color:<?php echo $product['is_active']?'#155724':'#721c24'; ?>; padding:4px 8px; border-radius:3px; font-size:12px;">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])): ?>
                            <td style="padding:10px;">
                                <button class="btn" style="background:#007bff; font-size:11px;" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                <button class="btn" style="background:#ffc107; color:#000; font-size:11px;" onclick="toggleFeatured(<?php echo $product['id']; ?>)">
                                    <?php echo $product['is_featured'] ? 'Unfeature' : 'Feature'; ?>
                                </button>
                                <button class="btn" style="background:#dc3545; font-size:11px;" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div style="margin-top:20px; text-align:center;">
                <?php if ($total_pages > 1): ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET, '', '&'); ?>" class="btn" style="margin-right:5px;">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET, '', '&'); ?>" class="btn <?php echo ($i === $page) ? 'btn-success' : ''; ?>" style="margin-right:5px;"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET, '', '&'); ?>" class="btn">Next</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Product</h2>
            <form id="productForm" method="POST" enctype="multipart/form-data">
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
                        <label for="material">Material</label>
                        <select id="material" name="material" required>
                            <option value="">Select Material</option>
                            <option value="Gold">Gold</option>
                            <option value="Silver">Silver</option>
                            <option value="Platinum">Platinum</option>
                            <option value="Rose Gold">Rose Gold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="unisex">Unisex</option>
                            <option value="women">Women</option>
                            <option value="men">Men</option>
                        </select>
                    </div>
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
                </div>

                <!-- Existing Images Section -->
                <div id="existingImagesSection" style="display: none; margin-bottom: 1rem;">
                    <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Existing Images</label>
                    <div id="existingImagesContainer" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;"></div>
                </div>

                <!-- Image Upload Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="main_image">Main Image</label>
                        <input type="file" id="main_image" name="main_image" accept="image/*">
                        <small style="color: #666; font-size: 12px;">Upload a new main image to replace the current one</small>
                    </div>
                    <div class="form-group">
                        <label for="additional_images">Additional Images</label>
                        <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                        <small style="color: #666; font-size: 12px;">Upload additional images (multiple files allowed)</small>
                    </div>
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
        // Categories fetched from the database
        const categoriesFromDb = <?php echo json_encode($categories_from_db); ?>;

        function populateCategoryDropdown(selectedCategoryId = null) {
            const categorySelect = document.getElementById('category');
            categorySelect.innerHTML = '<option value="">Select Category</option>'; // Clear existing

            categoriesFromDb.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id; // Assuming category ID is stored
                option.textContent = cat.name;
                if (selectedCategoryId && parseInt(selectedCategoryId) === parseInt(cat.id)) {
                    option.selected = true;
                }
                categorySelect.appendChild(option);
            });
        }

        // Call on page load to populate for add modal
        document.addEventListener('DOMContentLoaded', populateCategoryDropdown);

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = ''; // Clear product ID for new product
            document.getElementById('is_active').checked = true;
            document.getElementById('is_featured').checked = false; // Default to not featured
            populateCategoryDropdown(); // Populate category dropdown
            document.getElementById('existingImagesSection').style.display = 'none'; // Hide existing images section
            document.getElementById('productModal').style.display = 'block';
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productForm').reset();
            document.getElementById('is_active').checked = true;
            updateCategoryOptions(); // Initialize category options
            document.getElementById('productModal').style.display = 'block';
        }

        function editProduct(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.error);
                        return;
                    }
                    const product = data.product;
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

                    // Update category options based on gender and set the selected category
                    updateCategoryOptions();
                    setTimeout(() => {
                        document.getElementById('category').value = product.category || '';
                    }, 10);

                    // Display existing images
                    const existingImagesSection = document.getElementById('existingImagesSection');
                    const existingImagesContainer = document.getElementById('existingImagesContainer');
                    existingImagesContainer.innerHTML = '';

                    if (product.images && product.images.length > 0) {
                        existingImagesSection.style.display = 'block';

                        product.images.forEach(image => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'img-preview-wrapper';
                            wrapper.style.cssText = 'position: relative; display: inline-block; margin: 5px;';

                            const img = document.createElement('img');
                            img.src = '../' + image.image_url;
                            img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 2px solid #ddd;';

                            const label = document.createElement('div');
                            label.textContent = image.is_primary ? 'Main Image' : 'Additional Image';
                            label.style.cssText = 'font-size: 10px; text-align: center; margin-top: 2px; color: #666;';

                            const deleteBtn = document.createElement('button');
                            deleteBtn.innerHTML = '&times;';
                            deleteBtn.style.cssText = 'position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1;';
                            deleteBtn.onclick = () => deleteImage(image.id, wrapper);

                            wrapper.appendChild(img);
                            wrapper.appendChild(label);
                            wrapper.appendChild(deleteBtn);
                            existingImagesContainer.appendChild(wrapper);
                        });
                    } else {
                        existingImagesSection.style.display = 'none';
                    }

                    document.getElementById('productModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching product:', error);
                    alert('Could not fetch product details.');
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

        function deleteImage(imageId, elementToRemove) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }
            fetch('delete_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image_id=${imageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    elementToRemove.remove();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the image.');
            });
        }
    </script>

<?php include '_layout_footer.php'; ?>