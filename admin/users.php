<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
if ($_SESSION['admin_role'] !== 'superadmin') {
    exit('Access Denied: You do not have permission to access this page.');
}

$page_title = 'User Management';
$active_nav = 'users';

require_once '../app/config/database.php';

$error = null;
$success_message = null;

// --- Handle POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create_user' || $action === 'update_user') {
            $user_id = $_POST['user_id'] ?? null;
            $username = trim($_POST['username']);
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validation
            if (empty($username) || empty($email) || empty($role)) {
                throw new Exception("Username, email, and role are required.");
            }
            if ($action === 'create_user' && empty($password)) {
                 throw new Exception("Password is required for new users.");
            }

            if ($action === 'create_user') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, full_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $full_name, $email, $hash, $role, $is_active]);
                $success_message = "User '{$username}' created successfully.";
            } elseif ($action === 'update_user') {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admin_users SET username=?, full_name=?, email=?, password_hash=?, role=?, is_active=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $email, $hash, $role, $is_active, $user_id]);
                } else {
                    // Don't update password if it's left blank
                    $stmt = $pdo->prepare("UPDATE admin_users SET username=?, full_name=?, email=?, role=?, is_active=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $email, $role, $is_active, $user_id]);
                }
                $success_message = "User '{$username}' updated successfully.";
            }
        } elseif ($action === 'delete_user') {
            $user_id = $_POST['user_id'] ?? 0;
            if ($user_id == $_SESSION['admin_user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deleted successfully.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// --- Fetch Data ---
$users = [];
try {
    $users = $pdo->query("SELECT id, username, full_name, email, role, last_login, is_active FROM admin_users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

?>

<?php include '_layout_header.php'; ?>

<style>
    .data-table th, .data-table td { vertical-align: middle; }
    .data-table td .actions { display: flex; gap: 5px; }
    .data-table td .actions .btn { padding: 4px 8px; font-size: 12px; }
    .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
    .role-superadmin { background: #dc3545; color: white; }
    .role-admin { background: #007bff; color: white; }
    .role-staff { background: #6c757d; color: white; }
    .alert-message { padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; }
    .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
</style>

<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.2rem; margin: 0;"><i class="fas fa-users-cog"></i> Admin User Management</h3>
            <button class="btn btn-success" onclick="openUserModal()"><i class="fas fa-plus"></i> Create New User</button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert-message alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="alert-message alert-success"><?php echo $success_message; ?></div><?php endif; ?>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span style="color: <?php echo $user['is_active'] ? '#28a745' : '#dc3545'; ?>;"><i class="fas fa-circle" style="font-size: 10px;"></i> <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn" onclick="openUserModal(<?php echo $user['id']; ?>)">Edit</button>
                                    <?php if ($user['id'] != $_SESSION['admin_user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn logout">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Create User</h2>
        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create_user">
            <input type="hidden" name="user_id" id="user_id">

            <div class="form-row">
                <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" required></div>
                <div class="form-group"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name"></div>
            </div>
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" required></div>
            <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password"><small id="passwordHelp" style="color: #666; font-size: 12px;"></small></div>
            <div class="form-row">
                <div class="form-group"><label for="role">Role</label><select id="role" name="role" required><option value="staff">Staff</option><option value="admin">Admin</option><option value="superadmin">Superadmin</option></select></div>
                <div class="form-group" style="align-self: center;"><label><input type="checkbox" id="is_active" name="is_active" value="1"> Is Active</label></div>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn" onclick="closeModal()" style="background:#6c757d;">Cancel</button>
                <button type="submit" class="btn btn-success">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal(userId = null) {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('modalTitle');
    const action = document.getElementById('formAction');
    const passwordHelp = document.getElementById('passwordHelp');
    
    form.reset();

    if (userId) {
        // Edit mode
        title.textContent = 'Edit User';
        action.value = 'update_user';
        document.getElementById('user_id').value = userId;
        passwordHelp.textContent = 'Leave blank to keep current password.';

        fetch(`get_admin_user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('username').value = user.username;
                    document.getElementById('full_name').value = user.full_name;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('is_active').checked = user.is_active == 1;
                } else {
                    alert('Error: ' + data.error);
                    closeModal();
                }
            });

    } else {
        // Create mode
        title.textContent = 'Create User';
        action.value = 'create_user';
        document.getElementById('user_id').value = '';
        passwordHelp.textContent = 'Password is required for new users.';
    }

    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('userModal')) {
        closeModal();
    }
}
</script>

<?php include '_layout_footer.php'; ?>
