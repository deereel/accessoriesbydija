<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_testimonial') {
        $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, email, rating, title, content, product_id, is_featured, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['customer_name'], $_POST['email'], $_POST['rating'], $_POST['title'], $_POST['content'], $_POST['product_id'] ?: null, isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_approved']) ? 1 : 0]);
        $success = "Testimonial added successfully!";
    } elseif ($action === 'update_testimonial') {
        $stmt = $pdo->prepare("UPDATE testimonials SET customer_name=?, email=?, rating=?, title=?, content=?, product_id=?, is_featured=?, is_approved=? WHERE id=?");
        $stmt->execute([$_POST['customer_name'], $_POST['email'], $_POST['rating'], $_POST['title'], $_POST['content'], $_POST['product_id'] ?: null, isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_approved']) ? 1 : 0, $_POST['testimonial_id']]);
        $success = "Testimonial updated successfully!";
    } elseif ($action === 'delete_testimonial') {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - Dija Accessories Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .admin-header { background: #333; color: white; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav { background: #C27BA0; padding: 1rem; margin: -20px -20px 20px; }
        .admin-nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); }
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
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Testimonials Management</h1>
    </header>

    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="banners.php">Banners</a>
        <a href="testimonials.php" class="active">Testimonials</a>
    </nav>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="controls">
        <h2>Customer Testimonials</h2>
        <button class="btn btn-success" onclick="openAddModal()">+ Add Testimonial</button>
    </div>

    <div class="testimonials-table">
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

    <!-- Add/Edit Modal -->
    <div id="testimonialModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Testimonial</h2>
            <form id="testimonialForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_testimonial">
                <input type="hidden" name="testimonial_id" id="testimonialId">
                
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Testimonial';
            document.getElementById('formAction').value = 'add_testimonial';
            document.getElementById('testimonialForm').reset();
            document.getElementById('is_approved').checked = true;
            document.getElementById('testimonialModal').style.display = 'block';
        }
        
        function editTestimonial(id) {
            // Implementation for editing testimonials
            alert('Edit testimonial ' + id + ' - Feature to be implemented');
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
</body>
</html>