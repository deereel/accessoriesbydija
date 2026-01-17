<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Testimonials Management';
$active_nav = 'testimonials';

require_once '../config/database.php';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_testimonial') {
        $client_image = null;
        
        // Handle image upload - convert to base64
        if (isset($_FILES['client_image']) && $_FILES['client_image']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['client_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Read file and convert to base64
                $image_data = file_get_contents($_FILES['client_image']['tmp_name']);
                $mime_type = $_FILES['client_image']['type'];
                $base64_image = 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
                $client_image = $base64_image;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, email, rating, title, content, product_id, client_image, is_featured, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['customer_name'], $_POST['email'], $_POST['rating'], $_POST['title'], $_POST['content'], $_POST['product_id'] ?: null, $client_image, isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_approved']) ? 1 : 0]);
        $success = "Testimonial added successfully!";
    } elseif ($action === 'update_testimonial') {
        $client_image = $_POST['existing_image'] ?? null;
        
        // Handle image upload - convert to base64
        if (isset($_FILES['client_image']) && $_FILES['client_image']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['client_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Read file and convert to base64
                $image_data = file_get_contents($_FILES['client_image']['tmp_name']);
                $mime_type = $_FILES['client_image']['type'];
                $base64_image = 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
                $client_image = $base64_image;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE testimonials SET customer_name=?, email=?, rating=?, title=?, content=?, product_id=?, client_image=?, is_featured=?, is_approved=? WHERE id=?");
        $stmt->execute([$_POST['customer_name'], $_POST['email'], $_POST['rating'], $_POST['title'], $_POST['content'], $_POST['product_id'] ?: null, $client_image, isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_approved']) ? 1 : 0, $_POST['testimonial_id']]);
        $success = "Testimonial updated successfully!";
    } elseif ($action === 'delete_testimonial') {
        // Delete testimonial (image is stored in DB, so no file cleanup needed)
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id=?");
        $stmt->execute([$_POST['testimonial_id']]);
        $success = "Testimonial deleted successfully!";
    }
}

$stmt = $pdo->query("SELECT t.*, p.name as product_name FROM testimonials t LEFT JOIN products p ON t.product_id = p.id ORDER BY t.created_at DESC");
$testimonials = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name");
$products = $stmt->fetchAll();
?>

<?php include '_layout_header.php'; ?>

<style>
    .controls { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .testimonials-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #f8f8f8; font-weight: 600; }
    .rating { color: #ffc107; }
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .featured-badge { background: #C27BA0; color: white; }
    .btn { padding: 6px 12px; background: #C27BA0; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
    .btn:hover { background: #a66889; }
    .btn-success { background: #28a745; }
    .btn-danger { background: #dc3545; }
    .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 600px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .client-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px; vertical-align: middle; }
    .client-initial { width: 40px; height: 40px; border-radius: 50%; background: #C27BA0; color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; margin-right: 10px; vertical-align: middle; }
    .image-preview { max-width: 150px; max-height: 150px; margin-top: 10px; border-radius: 8px; display: none; }
    .image-preview.show { display: block; }
    .remove-image { color: #dc3545; cursor: pointer; font-size: 12px; margin-left: 10px; }
</style>

    <?php if (isset($success)): ?>
        <div class="card">
            <div class="card-body" style="background:#d4edda; color:#155724;">
                <?php echo $success; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-comments"></i> Customer Testimonials</div>
        <div class="card-body">
            <button class="btn btn-success" onclick="openAddModal()" style="margin-bottom:15px;"><i class="fas fa-plus"></i> Add Testimonial</button>
            <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testimonials as $testimonial): ?>
                <tr>
                    <td>
                        <?php if ($testimonial['client_image']): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['client_image']); ?>" alt="<?php echo htmlspecialchars($testimonial['customer_name']); ?>" class="client-avatar">
                        <?php else: ?>
                            <span class="client-initial"><?php echo strtoupper(substr($testimonial['customer_name'], 0, 1)); ?></span>
                        <?php endif; ?>
                        <strong><?php echo htmlspecialchars($testimonial['customer_name']); ?></strong>
                        <?php if ($testimonial['is_featured']): ?>
                            <span class="status-badge featured-badge">Featured</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="rating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php echo $i <= $testimonial['rating'] ? '★' : '☆'; ?>
                            <?php endfor; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(substr($testimonial['content'], 0, 100)) . '...'; ?></td>
                    <td><?php echo htmlspecialchars($testimonial['product_name'] ?? 'General'); ?></td>
                    <td>
                        <span class="status-badge <?php echo $testimonial['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                            <?php echo $testimonial['is_approved'] ? 'Approved' : 'Pending'; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?></td>
                    <td>
                        <button class="btn" onclick="editTestimonial(<?php echo $testimonial['id']; ?>)">Edit</button>
                        <button class="btn btn-danger" onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            </div>
        </div>

    <!-- Add/Edit Modal -->
    <div id="testimonialModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Testimonial</h2>
            <form id="testimonialForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add_testimonial">
                <input type="hidden" name="testimonial_id" id="testimonialId">
                <input type="hidden" name="existing_image" id="existingImage">
                
                <div class="form-group">
                    <label for="client_image">Client Picture</label>
                    <input type="file" id="client_image" name="client_image" accept="image/*" onchange="previewImage(this)">
                    <img id="imagePreview" class="image-preview" src="" alt="Preview">
                    <span id="removeImage" class="remove-image" style="display: none;" onclick="removeImagePreview()">Remove Image</span>
                    <small style="display: block; margin-top: 5px; color: #666;">If no image is uploaded, the first letter of the client's name will be displayed.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating" required>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_id">Related Product</label>
                        <select id="product_id" name="product_id">
                            <option value="">General Review</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Review Title</label>
                    <input type="text" id="title" name="title">
                </div>
                
                <div class="form-group">
                    <label for="content">Review Content</label>
                    <textarea id="content" name="content" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_featured" name="is_featured" value="1">
                            Featured on Homepage
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_approved" name="is_approved" value="1" checked>
                            Approved
                        </label>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Testimonial</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const removeBtn = document.getElementById('removeImage');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('show');
                    removeBtn.style.display = 'inline';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImagePreview() {
            document.getElementById('client_image').value = '';
            document.getElementById('imagePreview').classList.remove('show');
            document.getElementById('removeImage').style.display = 'none';
            document.getElementById('existingImage').value = '';
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Testimonial';
            document.getElementById('formAction').value = 'add_testimonial';
            document.getElementById('testimonialForm').reset();
            document.getElementById('is_approved').checked = true;
            document.getElementById('imagePreview').classList.remove('show');
            document.getElementById('removeImage').style.display = 'none';
            document.getElementById('existingImage').value = '';
            document.getElementById('testimonialModal').style.display = 'block';
        }
        
        function editTestimonial(id) {
            // Fetch testimonial data
            fetch('get_testimonial.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    // Populate the form with testimonial data
                    document.getElementById('modalTitle').textContent = 'Edit Testimonial';
                    document.getElementById('formAction').value = 'update_testimonial';
                    document.getElementById('testimonialId').value = data.id;
                    document.getElementById('customer_name').value = data.customer_name;
                    document.getElementById('email').value = data.email || '';
                    document.getElementById('rating').value = data.rating;
                    document.getElementById('title').value = data.title || '';
                    document.getElementById('content').value = data.content;
                    document.getElementById('product_id').value = data.product_id || '';
                    document.getElementById('is_featured').checked = data.is_featured == 1;
                    document.getElementById('is_approved').checked = data.is_approved == 1;
                    
                    // Handle existing image
                    const preview = document.getElementById('imagePreview');
                    const removeBtn = document.getElementById('removeImage');
                    document.getElementById('existingImage').value = data.client_image || '';
                    
                    if (data.client_image) {
                        preview.src = data.client_image; // Base64 data URL
                        preview.classList.add('show');
                        removeBtn.style.display = 'inline';
                    } else {
                        preview.classList.remove('show');
                        removeBtn.style.display = 'none';
                    }
                    
                    // Open the modal
                    document.getElementById('testimonialModal').style.display = 'block';
                })
                .catch(error => {
                    alert('Error fetching testimonial: ' + error);
                });
        }
        
        function deleteTestimonial(id) {
            if (confirm('Are you sure you want to delete this testimonial?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_testimonial"><input type="hidden" name="testimonial_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('testimonialModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('testimonialModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
        </div>
    </div>

<?php include '_layout_footer.php'; ?>