<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Database Management';
$active_nav = 'database';

require_once '../app/config/database.php';

$error = null;
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// --- Data for Table Viewer ---
$tables = [];
$selected_table = $_GET['table'] ?? null;
$table_data = [];
$columns = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$total_rows = 0;
$total_pages = 0;

// --- Data for Backups ---
$backups = [];

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if ($selected_table && in_array($selected_table, $tables)) {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `$selected_table`");
        $count_stmt->execute();
        $total_rows = $count_stmt->fetchColumn();
        $total_pages = ceil($total_rows / $limit);

        $data_stmt = $pdo->prepare("SELECT * FROM `$selected_table` LIMIT :limit OFFSET :offset");
        $data_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $data_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $data_stmt->execute();
        $table_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns_stmt = $pdo->query("DESCRIBE `$selected_table`");
        $columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fetch backup history
    $backup_sql = "SELECT b.*, u.username FROM database_backups b LEFT JOIN admin_users u ON b.user_id = u.id ORDER BY b.created_at DESC";
    $backups = $pdo->query($backup_sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If the backups table doesn't exist, don't fail the whole page
    if (strpos($e->getMessage(), "database_backups' doesn't exist") === false) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<?php include '_layout_header.php'; ?>

<style>
    .table-list { list-style-type: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 10px; }
    .table-list li a { display: block; padding: 8px 12px; background: #eee; border-radius: 6px; color: #333; }
    .table-list li a:hover { background: #ddd; }
    .table-list li a.active { background: var(--accent); color: #fff; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    .data-table th, .data-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    .data-table .cell-content { white-space: nowrap; max-width: 250px; overflow: hidden; text-overflow: ellipsis; }
    .data-table th { background: #f5f5f5; }
    .data-table td .actions { display: flex; gap: 5px; }
    .data-table td .actions .btn { padding: 4px 8px; font-size: 12px; }
    .alert-message { padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; }
    .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
</style>

<!-- Backup Management -->
<div class="card">
    <div class="card-header"><i class="fas fa-shield-alt"></i> Backup &amp; Restore</div>
    <div class="card-body">
         <div class="alert" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffeeba;">
            <strong>Warning:</strong> The backup & restore functionality is powerful. Always store backups in a secure, off-site location.
        </div>
        
        <?php if ($flash_message): ?><div class="alert-message alert-success"><?php echo $flash_message; ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="alert-message alert-error"><?php echo $flash_error; ?></div><?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="font-size: 1.2rem; margin: 0;">Backup History</h3>
            <a href="backup_db.php" class="btn btn-success"><i class="fas fa-plus"></i> Create New Backup</a>
        </div>

        <?php if (empty($backups) && !$error): ?>
             <p style="text-align: center; color: #666; padding: 20px;">No backups found. Click "Create New Backup" to start. <br><small>If you see this after creating a backup, ensure you have run the `create_backups_table.php` script.</small></p>
        <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Filename</th><th>Size</th><th>Created At</th><th>Created By</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($backups as $backup): ?>
                    <tr>
                        <td><i class="fas fa-file-archive"></i> <?php echo htmlspecialchars($backup['filename']); ?></td>
                        <td><?php echo round($backup['filesize'] / 1024, 2); ?> KB</td>
                        <td><?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($backup['username'] ?? 'System'); ?></td>
                        <td>
                            <div class="actions">
                                <a href="download_backup.php?id=<?php echo $backup['id']; ?>" class="btn"><i class="fas fa-download"></i> Download</a>
                                <a href="delete_backup.php?id=<?php echo $backup['id']; ?>" class="btn logout" onclick="return confirm('Are you sure you want to permanently delete this backup?');"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Table Viewer -->
<div class="card">
    <div class="card-header"><i class="fas fa-eye"></i> Database Table Viewer</div>
    <div class="card-body">
        <h3 style="font-size: 1.2rem; margin: 0; margin-bottom: 10px;">Tables</h3>
        <?php if ($error): ?>
            <div class="alert-message alert-error"><?php echo $error; ?></div>
        <?php else: ?>
            <ul class="table-list">
                <?php foreach ($tables as $table): ?>
                    <li><a href="?table=<?php echo htmlspecialchars($table); ?>" class="<?php echo $selected_table === $table ? 'active' : ''; ?>"><?php echo htmlspecialchars($table); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($selected_table && !$error): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-table"></i> Viewing Table: <strong><?php echo htmlspecialchars($selected_table); ?></strong> (<?php echo $total_rows; ?> rows)</div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead><tr><?php foreach ($columns as $column) echo '<th>' . htmlspecialchars($column['Field']) . '</th>'; ?></tr></thead>
                <tbody>
                    <?php if (empty($table_data)): ?>
                        <tr><td colspan="<?php echo count($columns); ?>" style="text-align:center; padding:20px;">This table is empty.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($table_data as $row): ?>
                        <tr><?php foreach ($columns as $column) echo '<td><div class="cell-content" title="' . htmlspecialchars($row[$column['Field']]) . '">' . htmlspecialchars($row[$column['Field']]) . '</div></td>'; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div style="margin-top:20px; text-align:center;">
            <?php if ($total_pages > 1): ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?table=<?php echo urlencode($selected_table); ?>&page=<?php echo $i; ?>" class="btn <?php echo ($i === $page) ? 'btn-success' : ''; ?>" style="margin-right:5px;"><?php echo $i; ?></a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '_layout_footer.php'; ?>
