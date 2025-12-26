<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Products Management';
$active_nav = 'products';

require_once '../config/database.php';

// This top part handles the AJAX POST requests for adding/updating products
if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'superadmin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_product' || $action === 'update_product') {
            header('Content-Type: application/json');
            $is_update = ($action === 'update_product');
            $response = ['success' => false, 'message' => 'An unknown error occurred.'];
            $pdo->beginTransaction();

            try {
                $product_name = $_POST['product_name'] ?? '';
                $product_id = $is_update ? (int)$_POST['product_id'] : null;

                if (empty($product_name)) {
                    throw new Exception("Product name is required.");
                }

                // 1. Insert or Update Base Product
                if ($is_update) {
                    $stmt = $pdo->prepare("UPDATE products SET name=?, slug=?, description=?, price=?, stock_quantity=?, weight=?, size=?, gender=?, is_featured=?, is_active=? WHERE id=?");
                    $stmt->execute([$product_name, strtolower(str_replace(' ', '-', $product_name)), $_POST['description'], $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['size'], $_POST['gender'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0, $product_id]);
                } else {
                    $words = explode(' ', $product_name);
                    $initials = '';
                    foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
                    $sku = $initials . '01';
                    $counter = 1;
                    while (true) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
                        $stmt->execute([$sku]);
                        if ($stmt->fetchColumn() == 0) break;
                        $counter++;
                        $sku = $initials . str_pad($counter, 2, '0', STR_PAD_LEFT);
                    }
                    
                    $slug = strtolower(str_replace(' ', '-', $product_name));
                    $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, sku, price, stock_quantity, weight, size, gender, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_name, $slug, $_POST['description'], $sku, $_POST['price'], $_POST['stock'], $_POST['weight'], $_POST['size'], $_POST['gender'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0]);
                    $product_id = $pdo->lastInsertId();
                }

                // 2. Clear and Re-insert Relationships
                if ($is_update) {
                    $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$product_id]);
                    $pdo->prepare("DELETE FROM product_materials WHERE product_id = ?")->execute([$product_id]);
                    $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$product_id]);
                    $pdo->prepare("DELETE FROM product_adornments WHERE product_id = ?")->execute([$product_id]);
                    // Clean up variants and images before re-inserting
                    $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);
                    $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);
                }
                
                if (isset($_POST['category']) && !empty($_POST['category'])) { $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)")->execute([$product_id, (int)$_POST['category']]); }
                if (isset($_POST['materials']) && !empty($_POST['materials'])) { $stmt = $pdo->prepare("INSERT INTO product_materials (product_id, material_id) VALUES (?, ?)"); foreach ($_POST['materials'] as $id) { $stmt->execute([$product_id, (int)$id]); } }
                if (!empty($_POST['color'])) { $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)")->execute([$product_id, (int)$_POST['color']]); }
                if (isset($_POST['adornments']) && !empty($_POST['adornments'])) { $stmt = $pdo->prepare("INSERT INTO product_adornments (product_id, adornment_id) VALUES (?, ?)"); foreach ($_POST['adornments'] as $id) { $stmt->execute([$product_id, (int)$id]); } }

                // 3. Handle Variants & Images
                $variants_data = $_POST['variants'] ?? [];
                $uploads_dir = '../assets/images/products';
                if (!is_dir($uploads_dir)) { mkdir($uploads_dir, 0777, true); }
                $total_variant_stock = 0;
                
                // Process Main Image + Variant
                if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                    $variant_info = $variants_data['main_0'] ?? null;
                    if ($variant_info && !empty($variant_info['tag'])) {
                        $total_variant_stock += (int)($variant_info['stock'] ?? 0);
                        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, sku, price_override, size_override) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$product_id, $variant_info['tag'], $variant_info['price'] ?: null, $variant_info['size'] ?: null]);
                        $variant_id = $pdo->lastInsertId();
                        $pdo->prepare("INSERT INTO variant_tags (product_id, variant_id, tag) VALUES (?, ?, ?)")->execute([$product_id, $variant_id, $variant_info['tag']]);
                        $pdo->prepare("INSERT INTO variant_stock (variant_id, stock_quantity) VALUES (?, ?)")->execute([$variant_id, (int)$variant_info['stock']]);

                        $file_name = uniqid() . '-' . basename($_FILES['main_image']['name']);
                        if (move_uploaded_file($_FILES['main_image']['tmp_name'], "$uploads_dir/$file_name")) {
                            $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, variant_id) VALUES (?, ?, 1, ?)")->execute([$product_id, "assets/images/products/$file_name", $variant_id]);
                        }
                    }
                }
                
                // Process Additional Images + Variants
                if (isset($_FILES['additional_images']['name'])) {
                     foreach ($_FILES['additional_images']['name'] as $key => $name) {
                        if ($_FILES['additional_images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                        $variant_key = "additional_{$key}";
                        $variant_info = $variants_data[$variant_key] ?? null;

                         if ($variant_info && !empty($variant_info['tag'])) {
                            $total_variant_stock += (int)($variant_info['stock'] ?? 0);
                            $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, sku, price_override, size_override) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$product_id, $variant_info['tag'], $variant_info['price'] ?: null, $variant_info['size'] ?: null]);
                            $variant_id = $pdo->lastInsertId();
                            $pdo->prepare("INSERT INTO variant_tags (product_id, variant_id, tag) VALUES (?, ?, ?)")->execute([$product_id, $variant_id, $variant_info['tag']]);
                            $pdo->prepare("INSERT INTO variant_stock (variant_id, stock_quantity) VALUES (?, ?)")->execute([$variant_id, (int)$variant_info['stock']]);
                            $file_name = uniqid() . '-' . basename($name);
                            if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], "$uploads_dir/$file_name")) {
                                $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, variant_id) VALUES (?, ?, 0, ?)")->execute([$product_id, "assets/images/products/$file_name", $variant_id]);
                            }
                        }
                    }
                }

                // 4. Stock Validation
                $main_stock = (int)($_POST['stock'] ?? 0);
                if (!empty($variants_data) && $total_variant_stock > 0 && $total_variant_stock !== $main_stock) {
                    throw new Exception("The sum of variant stock quantities ($total_variant_stock) must equal the main product stock quantity ($main_stock).");
                }

                $pdo->commit();
                $response = ['success' => true, 'message' => 'Product ' . ($is_update ? 'updated' : 'added') . ' successfully!'];

            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => $e->getMessage()];
            }
            echo json_encode($response);
            exit;
        }
        
        if ($action === 'delete_product') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$_POST['product_id']]);
            header('Location: products.php');
            exit;
        }
    }
}

// --- This part is for the GET request to display the page ---
$limit = 10; $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; $offset = ($page - 1) * $limit;
$where = ["p.is_active IN (0,1)"]; $params = [];
if (!empty($_GET['search'])) { $searchTerm = '%' . $_GET['search'] . '%'; $where[] = "(p.name LIKE ? OR p.sku LIKE ?)"; array_push($params, $searchTerm, $searchTerm); }
if (!empty($_GET['gender'])) { $where[] = "p.gender = ?"; $params[] = $_GET['gender']; }
if (isset($_GET['status']) && $_GET['status'] !== '') { $where[] = "p.is_active = ?"; $params[] = (int)$_GET['status']; }
$count_sql = "SELECT COUNT(*) FROM products p WHERE " . implode(" AND ", $where);
$count_stmt = $pdo->prepare($count_sql); $count_stmt->execute($params); $total_products_count = $count_stmt->fetchColumn(); $total_pages = ceil($total_products_count / $limit);
$sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image, GROUP_CONCAT(DISTINCT m.name SEPARATOR ', ') as materials
        FROM products p 
        LEFT JOIN product_materials pm ON p.id = pm.product_id
        LEFT JOIN materials m ON pm.material_id = m.id
        WHERE " . implode(" AND ", $where) . " 
        GROUP BY p.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql); foreach ($params as $key => $value) { $stmt->bindValue($key + 1, $value); }
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT); $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute(); $products = $stmt->fetchAll();
$categories_from_db = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$materials_from_db = $pdo->query("SELECT id, name FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$colors_from_db = $pdo->query("SELECT id, name FROM colors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$adornments_from_db = $pdo->query("SELECT id, name FROM adornments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
include '_layout_header.php'; 
?>
<style> .modal-content .checkbox-group { max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; } .image-preview-wrapper { position: relative; display: inline-block; margin: 5px; } .image-preview-wrapper img { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; } .remove-image-btn { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 14px; line-height: 1; display: flex; align-items: center; justify-content: center; } table th, table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; } </style>
<div class="card">
    <div class="card-header"><i class="fas fa-gem"></i> Products Management <button class="btn" style="float:right; margin-top:-8px;" onclick="openAddModal()">+ Add Product</button> <a href="products.php" class="btn" style="float:right; margin-top:-8px; margin-right:10px; background:#6c757d;">Clear Filters</a></div>
    <div class="card-body">
        <form method="GET" action="products.php" style="margin-bottom:16px; display:flex; gap:10px; align-items: center;">
             <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex:1;">
            <select name="gender"><option value="">Gender</option><option value="unisex" <?= ($_GET['gender']??'')==='unisex'?'selected':'';?>>Unisex</option><option value="women" <?= ($_GET['gender']??'')==='women'?'selected':'';?>>Women</option><option value="men" <?= ($_GET['gender']??'')==='men'?'selected':'';?>>Men</option></select>
            <select name="status"><option value="">Status</option><option value="1" <?= (isset($_GET['status'])&&$_GET['status']==='1')?'selected':'';?>>Active</option><option value="0" <?= (isset($_GET['status'])&&$_GET['status']==='0')?'selected':'';?>>Inactive</option></select>
            <button type="submit" class="btn">Filter</button>
        </form>
        <div class="table-container">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr style="background:#f5f5f5;"><th>Image</th><th>Product</th><th>SKU</th><th>Price</th><th>Weight</th><th>Materials</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php if(!empty($product['main_image'])):?><img src="../<?= htmlspecialchars($product['main_image']);?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"><?php else:?><div style="width:50px;height:50px;background:#f0f0f0;"></div><?php endif;?></td>
                        <td><strong><?= htmlspecialchars($product['name']);?></strong></td><td><?= htmlspecialchars($product['sku']);?></td><td>£<?= number_format($product['price'],2);?></td>
                        <td><?= $product['weight'] ? htmlspecialchars($product['weight']).'g' : '-';?></td><td><?= htmlspecialchars($product['materials'] ?? '-');?></td><td><?= $product['stock_quantity'];?></td>
                        <td><span style="padding:4px 8px;border-radius:3px;font-size:12px;background:<?=$product['is_active']?'#d4edda':'#f8d7da';?>;color:<?=$product['is_active']?'#155724':'#721c24';?>"><?=$product['is_active']?'Active':'Inactive';?></span></td>
                        <td><button class="btn" style="font-size:11px;" onclick="editProduct(<?=$product['id'];?>)">Edit</button><form method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="<?=$product['id'];?>"><button class="btn" style="background:#dc3545;font-size:11px;">Delete</button></form></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="productModal" class="modal"><div class="modal-content" style="width:80%;max-width:900px;"><span class="close" onclick="closeModal()">&times;</span><h2 id="modalTitle">Add</h2><form id="productForm"><input type="hidden" name="action" id="formAction"><input type="hidden" name="product_id" id="productId"><div class="form-group"><label>Name</label><input type="text" id="product_name" name="product_name" required oninput="generateSku()"></div><div class="form-row"><div class="form-group"><label>SKU</label><input type="text" id="sku" name="sku" required readonly></div><div class="form-group"><label>Price (£)</label><input type="number" id="price" name="price" step="0.01" required></div></div><div class="form-row"><div class="form-group"><label>Stock</label><input type="number" id="stock" name="stock" required></div><div class="form-group"><label>Weight (g)</label><input type="number" id="weight" name="weight" step="0.1"></div><div class="form-group"><label>Size</label><input type="text" id="size" name="size" placeholder="e.g., 22mm, 9cm, comma-separated"></div></div><div class="form-group"><label>Desc</label><textarea id="description" name="description" rows="3" required></textarea></div><div class="form-row"><div class="form-group"><label>Category</label><select id="category" name="category" required><option value="">Select</option><?php foreach($categories_from_db as $c):?><option value="<?=$c['id'];?>"><?=$c['name'];?></option><?php endforeach;?></select></div><div class="form-group"><label>Gender</label><select id="gender" name="gender" required><option value="unisex">Unisex</option><option value="women">Women</option><option value="men">Men</option></select></div></div><div class="form-group"><label>Materials <span id="selected-materials-display" style="font-weight:normal;font-style:italic;"></span></label><div class="checkbox-group" id="materials-group"><?php foreach($materials_from_db as $m):?><label><input type="checkbox" name="materials[]" value="<?=$m['id'];?>"> <?=$m['name'];?></label><?php endforeach;?></div></div><div class="form-group"><label>Colors <span id="selected-colors-display" style="font-weight:normal;font-style:italic;"></span></label><div class="checkbox-group" id="colors-group"><?php foreach($colors_from_db as $c):?><label><input type="checkbox" name="colors[]" value="<?=$c['id'];?>"> <?=$c['name'];?></label><?php endforeach;?></div></div><div class="form-group"><label>Adornments <span id="selected-adornments-display" style="font-weight:normal;font-style:italic;"></span></label><div class="checkbox-group" id="adornments-group"><?php foreach($adornments_from_db as $a):?><label><input type="checkbox" name="adornments[]" value="<?=$a['id'];?>"> <?=$a['name'];?></label><?php endforeach;?></div></div><hr><h4>Variants</h4><div class="form-group"><label>Tags</label><div id="tag-list" style="margin-bottom:10px;"></div><button type="button" class="btn" onclick="generateTag()">Gen Tag</button></div><div id="existingImagesSection" style="display:none;"><label>Existing</label><div id="existingImagesContainer"></div></div><div class="form-row"><div class="form-group"><label>Main Img</label><input type="file" id="main_image" name="main_image" accept="image/*"><div id="main-image-preview-container"></div></div><div class="form-group"><label>More Imgs</label><input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple><div id="additional-images-preview-container"></div></div></div><div id="image-variant-assignment"></div><div class="form-row"><div class="form-group"><label><input type="checkbox" id="is_featured" name="is_featured" value="1"> Featured</label></div><div class="form-group"><label><input type="checkbox" id="is_active" name="is_active" value="1" checked> Active</label></div></div><div id="form-error" class="alert alert-danger" style="display:none;"></div><div style="text-align:right;margin-top:20px;"><button type="button" class="btn" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-success">Save</button></div></form></div></div>
<script>
// --- CORRECTED AND COMPLETE SCRIPT ---
const categoriesFromDb=<?php echo json_encode($categories_from_db);?>;let generatedTags=[],additionalImagesFiles=[],mainImageFile=null,imageVariantData={};
const mainImageInput=document.getElementById('main_image'),additionalImagesInput=document.getElementById('additional_images'),mainImagePreviewContainer=document.getElementById('main-image-preview-container'),additionalImagesPreviewContainer=document.getElementById('additional-images-preview-container'),productForm=document.getElementById('productForm');
document.addEventListener('DOMContentLoaded',()=>{['materials','adornments'].forEach(g=>document.querySelector(`#${g}-group`)?.addEventListener('change',()=>updateSelectedDisplay(g)));mainImageInput.addEventListener('change',handleMainImageChange);additionalImagesInput.addEventListener('change',handleAdditionalImagesChange);productForm.addEventListener('submit',handleFormSubmit);window.onclick=e=>{if(e.target==document.getElementById('productModal'))closeModal()};populateCategoryDropdown()});
function handleFormSubmit(e){e.preventDefault();const formData=new FormData(productForm);formData.delete('additional_images[]');if(mainImageFile)formData.set('main_image',mainImageFile,mainImageFile.name);else formData.delete('main_image');additionalImagesFiles.forEach((f,i)=>formData.append(`additional_images[${i}]`,f.file,f.file.name));for(const k in imageVariantData){const d=imageVariantData[k];for(const p in d)if(d[p]!==null&&d[p]!==undefined)formData.append(`variants[${k}][${p}]`,d[p])}
const errorDiv=document.getElementById('form-error'),submitButton=productForm.querySelector('button[type="submit"]'),originalButtonText=submitButton.textContent;submitButton.textContent='Saving...';submitButton.disabled=true;errorDiv.style.display='none';
fetch('products.php',{method:'POST',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(res=>res.json()).then(data=>{if(data.success){closeModal();location.reload()}else{errorDiv.textContent=data.message;errorDiv.style.display='block'}}).catch(err=>{console.error('Error:',err);errorDiv.textContent='An unexpected network error occurred: '+err.message;errorDiv.style.display='block'}).finally(()=>{submitButton.textContent=originalButtonText;submitButton.disabled=false})}
function handleMainImageChange(e){mainImageFile=e.target.files[0];if(mainImageFile)imageVariantData.main_0=imageVariantData.main_0||{};renderMainImagePreview();updateImageVariantAssignment()}
function handleAdditionalImagesChange(e){Array.from(e.target.files).forEach(f=>{const k=`additional_${additionalImagesFiles.length}`;if(!additionalImagesFiles.some(fi=>fi.key===k)){additionalImagesFiles.push({key:k,file:f});imageVariantData[k]=imageVariantData[k]||{}}});e.target.value='';renderAdditionalImagePreviews();updateImageVariantAssignment()}
function saveVariantData(k,field,value){if(!imageVariantData[k])imageVariantData[k]={};imageVariantData[k][field]=value}
function renderMainImagePreview(){mainImagePreviewContainer.innerHTML='';if(mainImageFile)mainImagePreviewContainer.appendChild(createPreview(mainImageFile,'main_0','main'))}
function renderAdditionalImagePreviews(){additionalImagesPreviewContainer.innerHTML='';additionalImagesFiles.forEach(f=>additionalImagesPreviewContainer.appendChild(createPreview(f.file,f.key,'additional')))}
function createPreview(file,key,type){const reader=new FileReader(),wrapper=document.createElement('div'),img=document.createElement('img'),btn=document.createElement('button');wrapper.className='image-preview-wrapper';img.className='preview-img';btn.className='remove-image-btn';btn.innerHTML='&times;';btn.type='button';btn.onclick=()=>{delete imageVariantData[key];if(type==='main'){mainImageFile=null;mainImageInput.value='';renderMainImagePreview()}else{additionalImagesFiles=additionalImagesFiles.filter(f=>f.key!==key);renderAdditionalImagePreviews()}updateImageVariantAssignment()};wrapper.append(img,btn);reader.onload=e=>img.src=e.target.result;reader.readAsDataURL(file);return wrapper}
function updateImageVariantAssignment(){const container=document.getElementById('image-variant-assignment');container.innerHTML='<h5>Assign Variants to Images</h5>';const allFiles=mainImageFile?[{key:'main_0',file:mainImageFile}].concat(additionalImagesFiles):additionalImagesFiles;allFiles.forEach(f=>{const data=imageVariantData[f.key]||{},url=URL.createObjectURL(f.file),el=document.createElement('div');el.className='image-variant-row';el.style.cssText='display:flex;align-items:center;gap:15px;margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:15px;';el.innerHTML=`<img src="${url}" style="width:80px;height:80px;object-fit:cover;border-radius:4px;"><div style="flex-grow:1;"><div class="form-group"><label>Tag</label><select class="variant-tag-select" onchange="saveVariantData('${f.key}','tag',this.value)"><option value="">Select</option>${generatedTags.map(t=>`<option value="${t}" ${data.tag===t?'selected':''}>${t}</option>`).join('')}</select></div><div class="form-row"><div class="form-group"><label>Price</label><input type="number" value="${data.price||''}" oninput="saveVariantData('${f.key}','price',this.value)" step="0.01"></div><div class="form-group"><label>Size</label><input type="text" value="${data.size||''}" oninput="saveVariantData('${f.key}','size',this.value)"></div><div class="form-group"><label>Stock</label><input type="number" value="${data.stock||''}" oninput="saveVariantData('${f.key}','stock',this.value)"></div></div></div>`;container.appendChild(el)})}
function updateSelectedDisplay(group){const el=document.getElementById(`selected-${group}-display`);if(el){const s=Array.from(document.querySelectorAll(`#${group}-group input:checked`)).map(cb=>cb.parentElement.textContent.trim());el.textContent=s.length>0?`(${s.join(', ')})`:''}}
function generateSku(){const name=document.getElementById('product_name').value;if(!name)return;document.getElementById('sku').value=(name.split(' ').filter(w=>w).map(w=>w[0]).join('')+"01").toUpperCase()}
function renderTags(){const list=document.getElementById('tag-list');list.innerHTML='';generatedTags.forEach(t=>{const el=document.createElement('span');el.className='tag';el.textContent=t;list.appendChild(el)})}
function generateTag(){const sku=document.getElementById('sku').value;if(!sku)return alert('Gen SKU first');const newTag=`${sku}-${generatedTags.length+1}`;if(generatedTags.includes(newTag))return alert('Tag exists');generatedTags.push(newTag);renderTags();updateImageVariantAssignment()}
function openAddModal(){productForm.reset();document.getElementById('modalTitle').textContent='Add Product';document.getElementById('formAction').value='add_product';document.getElementById('productId').value='';document.getElementById('is_active').checked=true;document.getElementById('is_featured').checked=false;document.getElementById('existingImagesSection').style.display='none';document.getElementById('form-error').style.display='none';mainImageFile=null;additionalImagesFiles=[];imageVariantData={};generatedTags=[];renderMainImagePreview();renderAdditionalImagePreviews();renderTags();updateImageVariantAssignment();['materials','adornments'].forEach(updateSelectedDisplay);document.getElementById('productModal').style.display='block'}
function populateExistingImages(images,variants){const container=document.getElementById('existingImagesContainer');container.innerHTML='';images.forEach(img=>{const div=document.createElement('div');div.className='existing-image-item';div.innerHTML=`<img src="../${img.image_url}" style="width:100px;height:100px;object-fit:cover;"><br><small>Tag: ${variants.find(v=>v.id==img.variant_id)?.tag||'None'}</small>`;container.appendChild(div)})}
function editProduct(id){fetch(`get_product.php?id=${id}`).then(res=>res.json()).then(data=>{if(!data.success)throw new Error(data.error);openAddModal();const product=data.product;document.getElementById('modalTitle').textContent='Edit Product';document.getElementById('formAction').value='update_product';document.getElementById('productId').value=product.id;document.getElementById('product_name').value=product.name;document.getElementById('sku').value=product.sku;document.getElementById('price').value=product.price;document.getElementById('stock').value=product.stock_quantity;document.getElementById('weight').value=product.weight||'';document.getElementById('size').value=product.size||'';document.getElementById('description').value=product.description||'';document.getElementById('gender').value=product.gender;document.getElementById('is_featured').checked=product.is_featured==1;document.getElementById('is_active').checked=product.is_active==1;populateCategoryDropdown(product.category_id);if(document.getElementById('color'))document.getElementById('color').value=product.color_id||'';['materials','adornments'].forEach(group=>{document.querySelectorAll(`#${group}-group input`).forEach(c=>{c.checked=product[group]?.map(String).includes(c.value)??false});updateSelectedDisplay(group)});generatedTags=product.variants?.map(v=>v.tag).filter(Boolean)||[];renderTags();populateExistingImages(product.images||[],product.variants||[]);document.getElementById('existingImagesSection').style.display='block';document.getElementById('productModal').style.display='block'}).catch(err=>alert('Could not fetch product details: '+err.message))}
function closeModal(){document.getElementById('productModal').style.display='none'}
function populateCategoryDropdown(selected){const sel=document.getElementById('category');sel.innerHTML='<option value="">Select</option>';categoriesFromDb.forEach(c=>{const o=document.createElement('option');o.value=c.id;o.textContent=c.name;if(selected==c.id)o.selected=true;sel.appendChild(o)})}
</script>
<?php include '_layout_footer.php'; ?>