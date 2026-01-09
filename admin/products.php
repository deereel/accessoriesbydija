<?php
$page_title = "Products Management";
$active_nav = "products";
include '_layout_header.php';

require_once '../config/database.php';

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $pdo->beginTransaction();
        try {
            $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, sku, price, stock_quantity, gender, category_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['product_name'], $slug, $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['gender'], $_POST['category_id']]);
            $product_id = $pdo->lastInsertId();
            
            // Add variations
            if (isset($_POST['variations'])) {
                foreach ($_POST['variations'] as $variation) {
                    $stmt = $pdo->prepare("INSERT INTO product_variations (product_id, material_id, tag, color, adornment, price_adjustment, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $variation['material_id'], $variation['tag'], $variation['color'], $variation['adornment'], $variation['price_adjustment'], $variation['stock']]);
                    $variation_id = $pdo->lastInsertId();
                    
                    // Add sizes for this variation
                    if (isset($variation['sizes'])) {
                        foreach ($variation['sizes'] as $size) {
                            $stmt = $pdo->prepare("INSERT INTO variation_sizes (variation_id, size, stock_quantity, price_adjustment) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$variation_id, $size['size'], $size['stock'], $size['price_adjustment']]);
                        }
                    }
                }
            }
            
            // Handle image uploads
            if (isset($_FILES['images'])) {
                $upload_dir = '../assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Process each image field
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if (!empty($_FILES['images']['name'][$i]['file'])) {
                        $filename = $_FILES['images']['name'][$i]['file'];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = $product_id . '_' . time() . '_' . $i . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            $image_url = 'assets/images/products/' . $new_filename;

                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i]['file'], $upload_path)) {
                                $tag = $_POST['images'][$i]['tag'] ?? null;
                                $alt_text = $_POST['images'][$i]['alt_text'] ?? '';
                                $is_primary = isset($_POST['images'][$i]['is_primary']) ? 1 : 0;

                                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary, tag) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([$product_id, $image_url, $alt_text, $is_primary, $tag]);
                            }
                        }
                    }
                }
            }
            
            $pdo->commit();
            $success = "Product added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
    
    if ($action === 'edit_product') {
        $pdo->beginTransaction();
        try {
            $product_id = $_POST['product_id'];
            $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
            
            // Update product
            $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, description = ?, sku = ?, price = ?, stock_quantity = ?, gender = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$_POST['product_name'], $slug, $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], $_POST['gender'], $_POST['category_id'], $product_id]);
            
            // Delete existing variations and sizes
            $stmt = $pdo->prepare("DELETE FROM variation_sizes WHERE variation_id IN (SELECT id FROM product_variations WHERE product_id = ?)");
            $stmt->execute([$product_id]);
            $stmt = $pdo->prepare("DELETE FROM product_variations WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Add new variations
            if (isset($_POST['variations'])) {
                foreach ($_POST['variations'] as $variation) {
                    $stmt = $pdo->prepare("INSERT INTO product_variations (product_id, material_id, tag, color, adornment, price_adjustment, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $variation['material_id'], $variation['tag'], $variation['color'], $variation['adornment'], $variation['price_adjustment'], $variation['stock']]);
                    $variation_id = $pdo->lastInsertId();
                    
                    // Add sizes for this variation
                    if (isset($variation['sizes'])) {
                        foreach ($variation['sizes'] as $size) {
                            if (!empty($size['size'])) {
                                $stmt = $pdo->prepare("INSERT INTO variation_sizes (variation_id, size, stock_quantity, price_adjustment) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$variation_id, $size['size'], $size['stock'], $size['price_adjustment']]);
                            }
                        }
                    }
                }
            }
            
            // Handle image deletion
            if (!empty($_POST['deleted_images'])) {
                $deleted_images = explode(',', rtrim($_POST['deleted_images'], ','));
                foreach ($deleted_images as $deleted_image_id) {
                    // Get the image URL to delete the file
                    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
                    $stmt->execute([$deleted_image_id]);
                    $image = $stmt->fetch();
                    if ($image) {
                        $file_path = '../' . $image['image_url'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
            
                    // Delete from the database
                    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                    $stmt->execute([$deleted_image_id]);
                }
            }
            
            // Handle image updates and uploads
            if (isset($_POST['images']) || isset($_FILES['images'])) {
                $upload_dir = '../assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            
                foreach ($_POST['images'] as $index => $imageData) {
                    $image_id = $imageData['id'] ?? null;
                    $tag = $imageData['tag'] ?? null;
                    $alt_text = $imageData['alt_text'] ?? '';
                    $is_primary = isset($imageData['is_primary']) ? 1 : 0;
            
                    // Check if a new file is uploaded
                    if (!empty($_FILES['images']['name'][$index]['file'])) {
                        $filename = $_FILES['images']['name'][$index]['file'];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
                        if (in_array($file_ext, $allowed_exts)) {
                            // If it's an existing image, delete the old file
                            if ($image_id) {
                                $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
                                $stmt->execute([$image_id]);
                                $image = $stmt->fetch();
                                if ($image) {
                                    $file_path = '../' . $image['image_url'];
                                    if (file_exists($file_path)) {
                                        unlink($file_path);
                                    }
                                }
                            }
            
                            // Upload the new file
                            $new_filename = $product_id . '_' . time() . '_' . $index . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            $image_url = 'assets/images/products/' . $new_filename;
            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$index]['file'], $upload_path)) {
                                if ($image_id) {
                                    // Update existing image record
                                    $stmt = $pdo->prepare("UPDATE product_images SET image_url = ?, tag = ?, alt_text = ?, is_primary = ? WHERE id = ?");
                                    $stmt->execute([$image_url, $tag, $alt_text, $is_primary, $image_id]);
                                } else {
                                    // Insert new image record
                                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary, tag) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$product_id, $image_url, $alt_text, $is_primary, $tag]);
                                }
                            }
                        }
                    } else {
                        // If no new file is uploaded, just update the text fields for existing images
                        if ($image_id) {
                            $stmt = $pdo->prepare("UPDATE product_images SET tag = ?, alt_text = ?, is_primary = ? WHERE id = ?");
                            $stmt->execute([$tag, $alt_text, $is_primary, $image_id]);
                        }
                    }
                }
            }            $pdo->commit();
            $success = "Product updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Fetch products
$stmt = $pdo->query("SELECT p.*, COUNT(pv.id) as variation_count FROM products p LEFT JOIN product_variations pv ON p.id = pv.product_id GROUP BY p.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();

// Fetch materials for form
$stmt = $pdo->query("SELECT * FROM materials ORDER BY name");
$materials = $stmt->fetchAll();

// Fetch categories for form
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>


<style>
    .controls { background: var(--card); padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .products-table { background: var(--card); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: #f8f8f8; font-weight: 600; }
    .tab-container { display: flex; background: #f8f8f8; border-bottom: 1px solid #ddd; }
    .tab-btn { padding: 15px 20px; background: none; border: none; cursor: pointer; font-weight: 500; }
    .tab-btn.active { background: white; border-bottom: 2px solid var(--accent); }
    .tab-content { display: none; padding: 20px; max-height: 70vh; overflow-y: auto; }
    .tab-content.active { display: block; }
    .image-item { border: 1px solid var(--border); padding: 15px; margin-bottom: 10px; border-radius: 8px; background: #f9f9f9; position: relative; }
    .variation-item { border: 1px solid var(--border); padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9; position: relative; border-left: 4px solid var(--accent); }
    .variation-item:not(:last-child) { border-bottom: 3px solid var(--accent); margin-bottom: 25px; }
    .image-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; margin-right: 10px; }
    .remove-image { position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; }
    .size-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
</style>

        <?php if (isset($success)): ?>
            <div class="card" style="background: #d4edda; color: #155724;">
                <div class="card-body"><?= $success ?></div>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="card" style="background: #f8d7da; color: #721c24;">
                <div class="card-body"><?= $error ?></div>
            </div>
        <?php endif; ?>

        <div class="controls">
            <h2>Products (<?= count($products) ?>)</h2>
            <button class="btn btn-success" onclick="openAddModal()" id="addProductBtn">+ Add Product</button>
        </div>

        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Variations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                        <td><?= htmlspecialchars($product['sku']) ?></td>
                        <td>£<?= number_format($product['price'], 2) ?></td>
                        <td><?= $product['stock_quantity'] ?></td>
                        <td><?= $product['variation_count'] ?> variations</td>
                        <td>
                            <button class="btn" onclick="editProduct(<?= $product['id'] ?>)" style="margin-right: 5px;">Edit</button>
                            <button class="btn btn-danger" onclick="deleteProduct(<?= $product['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    </main>
  </div>
</div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add Product</h2>
            
            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab('basic')">Basic Info</button>
                <button class="tab-btn" onclick="switchTab('variations')">Variations</button>
                <button class="tab-btn" onclick="switchTab('images')">Images</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="deleted_images" id="deleted_images" value="">
                
                <div id="basic-tab" class="tab-content active">                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required onblur="generateSKU()">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SKU (Auto-generated)</label>
                            <input type="text" name="sku" required readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Base Price (£)</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="Unisex">Unisex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Total Stock</label>
                        <input type="number" name="stock" required>
                    </div>
                </div>
                
                <div id="variations-tab" class="tab-content">
                    <h3>Product Variations</h3>
                    <div id="variations-container"></div>
                    <button type="button" onclick="addVariation()" class="btn">+ Add Variation</button>
                </div>
                
                <div id="images-tab" class="tab-content">
                    <h3>Product Images</h3>
                    <div id="images-container">
                        <div class="image-item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Image File</label>
                                    <input type="file" name="images[0][file]" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label>Tag (Optional)</label>
                                    <select name="images[0][tag]">
                                        <option value="">General Product Image</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Alt Text</label>
                                    <input type="text" name="images[0][alt_text]" placeholder="Image description">
                                </div>
                                <div class="form-group">
                                    <label>Primary Image</label>
                                    <input type="checkbox" name="images[0][is_primary]" value="1">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addImageField()" class="btn">+ Add Image</button>
                </div>
                
                <div style="text-align: right; margin-top: 20px; padding: 20px; border-top: 1px solid #ddd; background: white;">
                    <div id="stockError" class="error" style="display: none; text-align: left; margin-bottom: 10px;"></div>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" onclick="return validateStock()">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let variationCount = 1;
        let imageCount = 1;
        
        function updateImageTagOptions() {
    const variations = document.querySelectorAll('.variation-item');
    const tagOptions = ['<option value="">General Product Image</option>'];
    
    variations.forEach(variation => {
        const tagInput = variation.querySelector('input[name*="[tag]"]');
        if (tagInput && tagInput.value) {
            tagOptions.push(`<option value="${tagInput.value}">${tagInput.value}</option>`);
        }
    });
    
    document.querySelectorAll('select[name*="[tag]"]').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = tagOptions.join('');
        select.value = currentValue;
    });
}        
        function handlePrimaryImageChange(checkbox) {
    const checkboxes = document.querySelectorAll('.primary-image-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    checkbox.checked = true;
}

function addImageField() {
            const container = document.getElementById('images-container');
            const imageHtml = `
                <div class="image-item">
                    <button type="button" class="remove-image" onclick="removeImage(this)">&times;</button>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Image File</label>
                                    <input type="file" name="images[${imageCount}][file]" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label>Tag (Optional)</label>
                                    <select name="images[${imageCount}][tag]">
                                        <option value="">General Product Image</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Alt Text</label>
                                    <input type="text" name="images[${imageCount}][alt_text]" placeholder="Image description">
                                </div>
                                <div class="form-group">
                                    <label>Primary Image</label>
                                    <input type="checkbox" name="images[${imageCount}][is_primary]" value="1" class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                                </div>
                            </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', imageHtml);
            imageCount++;
            updateImageTagOptions();
        }
        
        function addImageFieldWithData(imageData, index) {
            const container = document.getElementById('images-container');
            const imageSrc = imageData.image_url.startsWith('assets/') ? '../' + imageData.image_url : imageData.image_url;
            const imageHtml = `
                <div class="image-item" data-image-id="${imageData.id}">
                    <input type="hidden" name="images[${index}][id]" value="${imageData.id}">
                    <button type="button" class="remove-image" onclick="removeImage(this)">&times;</button>
                    <div class="existing-image" style="margin-bottom: 10px;">
                        <img src="${imageSrc}" alt="Current image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                        <p style="margin: 5px 0; font-size: 12px; color: #666;">Current: ${imageData.image_url}</p>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Replace Image (Optional)</label>
                            <input type="file" name="images[${index}][file]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Tag (Optional)</label>
                            <select name="images[${index}][tag]">
                                <option value="">General Product Image</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Alt Text</label>
                            <input type="text" name="images[${index}][alt_text]" value="${imageData.alt_text || ''}" placeholder="Image description">
                        </div>
                        <div class="form-group">
                            <label>Primary Image</label>
                            <input type="checkbox" name="images[${index}][is_primary]" value="1" ${imageData.is_primary ? 'checked' : ''} class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', imageHtml);
            updateImageTagOptions();
            
            // Set the tag value after insertion
            const tagSelect = container.querySelector(`select[name="images[${index}][tag]"]`);
            if (tagSelect && imageData.tag) {
                tagSelect.value = imageData.tag;
            }
        }        function removeImage(button) {
            const imageItem = button.closest('.image-item');
            const imageId = imageItem.dataset.imageId;
            if (imageId) {
                const deletedImagesInput = document.getElementById('deleted_images');
                deletedImagesInput.value += imageId + ',';
            }
            imageItem.remove();
        }        
        function generateSKU() {
            const productName = document.querySelector('input[name="product_name"]').value;
            if (!productName) return;
            
            // Generate SKU from first letters of each word
            const words = productName.trim().split(/\s+/);
            const letters = words.map(word => word.charAt(0).toUpperCase()).join('');
            
            // Check for existing SKUs and increment
            fetch('check_sku.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({base: letters})
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('input[name="sku"]').value = data.sku;
                generateVariationTags();
                updateImageTagOptions();
            });
        }
        
        function generateVariationTags() {
            const sku = document.querySelector('input[name="sku"]').value;
            if (!sku) return;
            
            const variations = document.querySelectorAll('.variation-item');
            variations.forEach((variation, index) => {
                const tagInput = variation.querySelector('input[name*="[tag]"]');
                if (tagInput) {
                    tagInput.value = `${sku}-${index + 1}`;
                }
            });
        }
        
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }
        
        function openAddModal() {
            console.log('openAddModal called');
            // Reset form for adding new product
            document.querySelector('#productModal form').reset();
            document.querySelector('input[name="action"]').value = 'add_product';
            
            // Remove product ID if exists
            const productIdInput = document.querySelector('input[name="product_id"]');
            if (productIdInput) {
                productIdInput.remove();
            }
            
            // Update modal title
            document.querySelector('#productModal h2').textContent = 'Add Product';
            
            // Reset variations to default
            const variationsContainer = document.getElementById('variations-container');
            variationsContainer.innerHTML = '';
            addVariationToForm({}, 0);
            variationCount = 1;
            imageCount = 1;
            
            // Reset to first tab
            switchTab('basic');
            
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function addVariation() {
            addVariationToForm({}, variationCount);
            variationCount++;
            generateVariationTags();
            updateImageTagOptions();
        }
        
        function addSize(button) {
            const sizesContainer = button.closest('.sizes-container');
            const sizeCount = sizesContainer.querySelectorAll('.size-item').length - 1; // Exclude the add button row
            const variationIndex = button.closest('.variation-item').querySelector('select').name.match(/\[(\d+)\]/)[1];
            
            const newSize = document.createElement('div');
            newSize.className = 'size-item';
            newSize.innerHTML = `
                <input type="text" name="variations[${variationIndex}][sizes][${sizeCount}][size]" placeholder="Size" style="width: 150px;">
                <input type="number" name="variations[${variationIndex}][sizes][${sizeCount}][stock]" placeholder="Stock" style="width: 100px;">
                <input type="number" name="variations[${variationIndex}][sizes][${sizeCount}][price_adjustment]" placeholder="New Price" step="0.01" style="width: 120px;">
                <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
            `;
            
            // Insert before the add button row
            const addButtonRow = button.parentElement;
            sizesContainer.insertBefore(newSize, addButtonRow);
        }
        
        function removeSize(button) {
            button.closest('.size-item').remove();
        }
        
        function editProduct(id) {
            console.log('editProduct called with id:', id);
            fetch(`get_product.php?id=${id}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(product => {
                    console.log('Product data:', product);
                    if (product.error) {
                        alert('Error: ' + product.error);
                        return;
                    }
                    
                    // Populate form with product data
                    document.querySelector('input[name="product_name"]').value = product.name || '';
                    document.querySelector('input[name="sku"]').value = product.sku || '';
                    document.querySelector('input[name="price"]').value = product.price || '';
                    document.querySelector('textarea[name="description"]').value = product.description || '';
                    document.querySelector('input[name="stock"]').value = product.stock_quantity || '';
                    document.querySelector('select[name="gender"]').value = product.gender || 'Unisex';
                    document.querySelector('select[name="category_id"]').value = product.category_id || '';
                    
                    // Update form action for editing
                    const form = document.querySelector('#productModal form');
                    form.querySelector('input[name="action"]').value = 'edit_product';
                    
                    // Add product ID for editing
                    let productIdInput = form.querySelector('input[name="product_id"]');
                    if (!productIdInput) {
                        productIdInput = document.createElement('input');
                        productIdInput.type = 'hidden';
                        productIdInput.name = 'product_id';
                        form.appendChild(productIdInput);
                    }
                    productIdInput.value = id;
                    
                    // Update modal title
                    document.querySelector('#productModal h2').textContent = 'Edit Product';
                    
                    // Clear existing variations
                    const variationsContainer = document.getElementById('variations-container');
                    variationsContainer.innerHTML = '';
                    
                    // Add variations
                    if (product.variations && product.variations.length > 0) {
                        product.variations.forEach((variation, index) => {
                            addVariationToForm(variation, index);
                        });
                        variationCount = product.variations.length;
                    } else {
                        addVariationToForm({}, 0);
                        variationCount = 1;
                    }
                    
                    // Reset images container and add existing images
                    const imagesContainer = document.getElementById('images-container');
                    imagesContainer.innerHTML = '';
                    imageCount = 0;                    
                    // Add existing images if any
                    if (product.images && product.images.length > 0) {
                        product.images.forEach((image, index) => {
                            addImageFieldWithData(image, index);
                        });
                        imageCount = product.images.length;
                    }                    
                    // Add one empty image field
                    addImageField();
                    
                    // Show modal
                    switchTab('basic');
                    document.getElementById('productModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching product details');
                });
        }
        
        function addVariationToForm(variation, index) {
            const container = document.getElementById('variations-container');
            const variationHtml = `
                <div class="variation-item">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tag (Auto-generated)</label>
                            <input type="text" name="variations[${index}][tag]" value="${variation.tag || ''}" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Material</label>
                            <select name="variations[${index}][material_id]" required>
                                <option value="">Select Material</option>
                                <?php foreach ($materials as $material): ?>
                                <option value="<?= $material['id'] ?>" ${variation.material_id == <?= $material['id'] ?> ? 'selected' : ''}><?= htmlspecialchars($material['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Color</label>
                            <select name="variations[${index}][color]">
                                <option value="">Select Color</option>
                                <option value="Gold" ${variation.color === 'Gold' ? 'selected' : ''}>Gold</option>
                                <option value="Silver" ${variation.color === 'Silver' ? 'selected' : ''}>Silver</option>
                                <option value="Rose Gold" ${variation.color === 'Rose Gold' ? 'selected' : ''}>Rose Gold</option>
                                <option value="White Gold" ${variation.color === 'White Gold' ? 'selected' : ''}>White Gold</option>
                                <option value="Black" ${variation.color === 'Black' ? 'selected' : ''}>Black</option>
                                <option value="Blue" ${variation.color === 'Blue' ? 'selected' : ''}>Blue</option>
                                <option value="Red" ${variation.color === 'Red' ? 'selected' : ''}>Red</option>
                                <option value="Pink" ${variation.color === 'Pink' ? 'selected' : ''}>Pink</option>
                                <option value="Green" ${variation.color === 'Green' ? 'selected' : ''}>Green</option>
                                <option value="Purple" ${variation.color === 'Purple' ? 'selected' : ''}>Purple</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Adornments</label>
                            <select name="variations[${index}][adornment]">
                                <option value="">Select Adornment</option>
                                <option value="Diamond" ${variation.adornment === 'Diamond' ? 'selected' : ''}>Diamond</option>
                                <option value="Ruby" ${variation.adornment === 'Ruby' ? 'selected' : ''}>Ruby</option>
                                <option value="Emerald" ${variation.adornment === 'Emerald' ? 'selected' : ''}>Emerald</option>
                                <option value="Zirconia" ${variation.adornment === 'Zirconia' ? 'selected' : ''}>Zirconia</option>
                                <option value="Sapphire" ${variation.adornment === 'Sapphire' ? 'selected' : ''}>Sapphire</option>
                                <option value="Pearl" ${variation.adornment === 'Pearl' ? 'selected' : ''}>Pearl</option>
                                <option value="Moissanite" ${variation.adornment === 'Moissanite' ? 'selected' : ''}>Moissanite</option>
                                <option value="Blue Gem" ${variation.adornment === 'Blue Gem' ? 'selected' : ''}>Blue Gem</option>
                                <option value="Pink Gem" ${variation.adornment === 'Pink Gem' ? 'selected' : ''}>Pink Gem</option>
                                <option value="White Gem" ${variation.adornment === 'White Gem' ? 'selected' : ''}>White Gem</option>
                                <option value="Red Gem" ${variation.adornment === 'Red Gem' ? 'selected' : ''}>Red Gem</option>
                                <option value="White Stone" ${variation.adornment === 'White Stone' ? 'selected' : ''}>White Stone</option>
                                <option value="Black Stone" ${variation.adornment === 'Black Stone' ? 'selected' : ''}>Black Stone</option>
                                <option value="Red Stone" ${variation.adornment === 'Red Stone' ? 'selected' : ''}>Red Stone</option>
                                <option value="Pink Stone" ${variation.adornment === 'Pink Stone' ? 'selected' : ''}>Pink Stone</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Price (£)</label>
                            <input type="number" name="variations[${index}][price_adjustment]" step="0.01" value="${variation.price_adjustment || 0}" placeholder="Enter new price">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="variations[${index}][stock]" value="${variation.stock_quantity || ''}" required>
                    </div>
                    
                    <h4>Sizes for this variation</h4>
                    <div class="sizes-container">
                        ${variation.sizes && variation.sizes.length > 0 ? 
                            variation.sizes.map((size, sizeIndex) => `
                                <div class="size-item">
                                    <input type="text" name="variations[${index}][sizes][${sizeIndex}][size]" value="${size.size || ''}" placeholder="Size" style="width: 150px;">
                                    <input type="number" name="variations[${index}][sizes][${sizeIndex}][stock]" value="${size.stock_quantity || ''}" placeholder="Stock" style="width: 100px;">
                                    <input type="number" name="variations[${index}][sizes][${sizeIndex}][price_adjustment]" value="${size.price_adjustment || 0}" placeholder="New Price" step="0.01" style="width: 120px;">
                                    <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
                                </div>
                            `).join('') : ''
                        }
                        <div class="size-item">
                            <input type="text" name="variations[${index}][sizes][${variation.sizes ? variation.sizes.length : 0}][size]" placeholder="Size (e.g., S, M, L, 6, 7, 8)" style="width: 150px;">
                            <input type="number" name="variations[${index}][sizes][${variation.sizes ? variation.sizes.length : 0}][stock]" placeholder="Stock" style="width: 100px;">
                            <input type="number" name="variations[${index}][sizes][${variation.sizes ? variation.sizes.length : 0}][price_adjustment]" placeholder="New Price" step="0.01" style="width: 120px;">
                            <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
                        </div>
                        <div style="margin-top: 10px;">
                            <button type="button" onclick="addSize(this)" class="btn">+ Size</button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', variationHtml);
        }
        
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = `?delete=${id}`;
            }
        }
        
        function validateStock() {
            const baseStock = parseInt(document.querySelector('input[name="stock"]').value) || 0;
            const variations = document.querySelectorAll('.variation-item');
            let totalVariationStock = 0;
            const errors = [];
            
            variations.forEach((variation, vIndex) => {
                const variationStock = parseInt(variation.querySelector('input[name*="[stock]"]').value) || 0;
                const sizes = variation.querySelectorAll('.size-item input[name*="[stock]"]');
                let totalSizeStock = 0;
                
                sizes.forEach(sizeInput => {
                    if (sizeInput.value) {
                        totalSizeStock += parseInt(sizeInput.value) || 0;
                    }
                });
                
                if (sizes.length > 0 && totalSizeStock !== variationStock) {
                    errors.push(`Variation ${vIndex + 1}: Size stocks (${totalSizeStock}) don't match variation stock (${variationStock})`);
                }
                
                totalVariationStock += variationStock;
            });
            
            if (totalVariationStock !== baseStock) {
                errors.push(`Total variation stocks (${totalVariationStock}) don't match base stock (${baseStock})`);
            }
            
            if (errors.length > 0) {
                document.getElementById('stockError').innerHTML = errors.join('<br>');
                document.getElementById('stockError').style.display = 'block';
                return false;
            }
            
            document.getElementById('stockError').style.display = 'none';
            return true;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

<?php include '_layout_footer.php'; ?>