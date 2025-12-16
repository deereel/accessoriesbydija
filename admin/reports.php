<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Sales Reports';
$active_nav = 'reports';

require_once '../config/database.php';

try {
    // --- Get data for filter dropdowns ---
    $years_sql = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y') as year FROM orders ORDER BY year DESC";
    $available_years = $pdo->query($years_sql)->fetchAll(PDO::FETCH_COLUMN);

    $months_sql = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month FROM orders ORDER BY month DESC";
    $available_months = $pdo->query($months_sql)->fetchAll(PDO::FETCH_COLUMN);

    // --- Process Filters ---
    $filter_type = $_GET['filter_type'] ?? 'all';
    $report_title_suffix = ' (All Time)';

    $start_date = null;
    $end_date = null;
    $params = [];

    switch ($filter_type) {
        case 'date_range':
            $start_date_filter = $_GET['start_date'] ?? '';
            $end_date_filter = $_GET['end_date'] ?? '';
            if ($start_date_filter && $end_date_filter) {
                $start_date = $start_date_filter . ' 00:00:00';
                $end_date = $end_date_filter . ' 23:59:59';
                $report_title_suffix = ' (from ' . htmlspecialchars($start_date_filter) . ' to ' . htmlspecialchars($end_date_filter) . ')';
            }
            break;
        case 'monthly':
            $filter_value = $_GET['filter_value'] ?? '';
            if ($filter_value) {
                $start_date = $filter_value . '-01 00:00:00';
                $end_date = date('Y-m-t', strtotime($start_date)) . ' 23:59:59';
                $report_title_suffix = ' (for ' . date('F Y', strtotime($start_date)) . ')';
            }
            break;
        case 'yearly':
            $filter_value = $_GET['filter_value'] ?? '';
            if ($filter_value) {
                $start_date = $filter_value . '-01-01 00:00:00';
                $end_date = $filter_value . '-12-31 23:59:59';
                $report_title_suffix = ' (for Year ' . htmlspecialchars($filter_value) . ')';
            }
            break;
    }

    $date_condition = "";
    if ($start_date && $end_date) {
        $date_condition = "AND o.created_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }

    // --- Run Queries ---
    $main_where_clause = "o.status NOT IN ('cancelled', 'pending', 'failed') $date_condition";

    // Summary Stats
    $summary_sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total_revenue, COALESCE(COUNT(o.id), 0) as total_orders, COALESCE(SUM(o.total_amount) / NULLIF(COUNT(o.id), 0), 0) as average_order_value FROM orders o WHERE $main_where_clause";
    $summary_stmt = $pdo->prepare($summary_sql);
    $summary_stmt->execute($params);
    $summary_stats = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    $items_sql = "SELECT COALESCE(SUM(oi.quantity), 0) as total_items_sold FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE $main_where_clause";
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->execute($params);
    $summary_stats['total_items_sold'] = $items_stmt->fetchColumn();
    
    // Sales Over Time
    $group_by_format = '%Y-%m'; $x_axis_label = 'Month';
    if ($filter_type === 'date_range' || $filter_type === 'monthly') { $group_by_format = '%Y-%m-%d'; $x_axis_label = 'Date'; }

    $sales_over_time_sql = "SELECT DATE_FORMAT(o.created_at, '$group_by_format') as period, SUM(o.total_amount) as revenue, COUNT(o.id) as orders FROM orders o WHERE $main_where_clause GROUP BY period ORDER BY period DESC LIMIT 31";
    $sales_over_time_stmt = $pdo->prepare($sales_over_time_sql);
    $sales_over_time_stmt->execute($params);
    $sales_over_time = $sales_over_time_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Selling Products
    $top_products_sql = "SELECT p.id, p.name, p.sku, SUM(oi.quantity) as units_sold, SUM(oi.total_price) as product_revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE $main_where_clause GROUP BY p.id, p.name, p.sku ORDER BY units_sold DESC LIMIT 10";
    $top_products_stmt = $pdo->prepare($top_products_sql);
    $top_products_stmt->execute($params);
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Customers
    $top_customers_sql = "SELECT c.id, c.first_name, c.last_name, c.email, COUNT(o.id) as total_orders, SUM(o.total_amount) as total_spent FROM customers c JOIN orders o ON c.id = o.customer_id WHERE $main_where_clause GROUP BY c.id, c.first_name, c.last_name, c.email ORDER BY total_spent DESC LIMIT 10";
    $top_customers_stmt = $pdo->prepare($top_customers_sql);
    $top_customers_stmt->execute($params);
    $top_customers = $top_customers_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<?php include '_layout_header.php'; ?>

<style>
    .stat-card { background: var(--card); border-radius: 8px; padding: 16px; text-align: center; border: 1px solid var(--border); }
    .stat-card h4 { margin: 0 0 8px; color: #666; font-size: 14px; text-transform: uppercase; }
    .stat-card .stat-value { font-size: 24px; font-weight: 700; color: var(--accent); }
    .report-section { margin-bottom: 20px; }
    .filter-form { display: flex; gap: 16px; align-items: flex-end; background: var(--card); padding: 16px; border-radius: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-form .form-group { margin-bottom: 0; }
</style>

<form class="filter-form" method="GET">
    <div class="form-group">
        <label for="filter_type">Filter By</label>
        <select name="filter_type" id="filter_type">
            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Time</option>
            <option value="date_range" <?php echo $filter_type === 'date_range' ? 'selected' : ''; ?>>Date Range</option>
            <option value="monthly" <?php echo $filter_type === 'monthly' ? 'selected' : ''; ?>>Month</option>
            <option value="yearly" <?php echo $filter_type === 'yearly' ? 'selected' : ''; ?>>Year</option>
        </select>
    </div>
    
    <div id="date_range_inputs" class="form-group" style="display:none; gap: 16px; align-items: flex-end;">
        <div class="form-group"><label for="start_date">Start Date</label><input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>"></div>
        <div class="form-group"><label for="end_date">End Date</label><input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>"></div>
    </div>

    <div id="single_value_inputs" class="form-group" style="display:none;">
        <label for="filter_value">Value</label>
        <select name="filter_value" id="filter_value"></select>
    </div>

    <button type="submit" class="btn">Apply Filter</button>
    <a href="reports.php" class="btn" style="background:#6c757d;">Reset</a>
</form>

<?php if (isset($error)): ?>
    <div class="card"><div class="card-body" style="background:#f8d7da; color:#721c24;"><?php echo $error; ?></div></div>
<?php else: ?>
    <!-- Summary Stats -->
    <div class="report-section">
        <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Summary<?php echo $report_title_suffix; ?></h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div class="stat-card"><h4>Total Revenue</h4><div class="stat-value">£<?php echo number_format($summary_stats['total_revenue'], 2); ?></div></div>
            <div class="stat-card"><h4>Total Orders</h4><div class="stat-value"><?php echo number_format($summary_stats['total_orders']); ?></div></div>
            <div class="stat-card"><h4>Items Sold</h4><div class="stat-value"><?php echo number_format($summary_stats['total_items_sold']); ?></div></div>
            <div class="stat-card"><h4>Avg. Order Value</h4><div class="stat-value">£<?php echo number_format($summary_stats['average_order_value'], 2); ?></div></div>
        </div>
    </div>

    <!-- Charts/Tables -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card report-section">
            <div class="card-header"><i class="fas fa-chart-line"></i> Sales Over Time<?php echo $report_title_suffix; ?></div>
            <div class="card-body"><table style="width:100%"><thead><tr style="background:#f5f5f5;"><th style="padding:10px;text-align:left;"><?php echo $x_axis_label; ?></th><th style="padding:10px;text-align:right;">Orders</th><th style="padding:10px;text-align:right;">Revenue</th></tr></thead><tbody><?php foreach($sales_over_time as $sot): ?><tr><td style="padding:10px;"><?php echo $x_axis_label === 'Month' ? date("F Y", strtotime($sot['period'] . '-01')) : $sot['period']; ?></td><td style="padding:10px;text-align:right;"><?php echo number_format($sot['orders']); ?></td><td style="padding:10px;text-align:right;font-weight:bold;">£<?php echo number_format($sot['revenue'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
        <div class="card report-section">
            <div class="card-header"><i class="fas fa-star"></i> Top Selling Products<?php echo $report_title_suffix; ?></div>
            <div class="card-body"><table style="width:100%"><thead><tr style="background:#f5f5f5;"><th style="padding:10px;text-align:left;">Product</th><th style="padding:10px;text-align:right;">Units Sold</th><th style="padding:10px;text-align:right;">Revenue</th></tr></thead><tbody><?php if(empty($top_products)): ?><tr><td colspan="3" style="text-align:center; padding:20px; color:#666;">No products sold in this period.</td></tr><?php endif; ?><?php foreach($top_products as $tp): ?><tr><td style="padding:10px;"><strong><?php echo htmlspecialchars($tp['name']); ?></strong><div style="font-size:12px; color:#666;">SKU: <?php echo htmlspecialchars($tp['sku']); ?></div></td><td style="padding:10px;text-align:right;font-weight:bold;"><?php echo number_format($tp['units_sold']); ?></td><td style="padding:10px;text-align:right;">£<?php echo number_format($tp['product_revenue'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card report-section">
        <div class="card-header"><i class="fas fa-user-friends"></i> Top Customers<?php echo $report_title_suffix; ?></div>
        <div class="card-body"><table style="width:100%"><thead><tr style="background:#f5f5f5;"><th style="padding:10px;text-align:left;">Customer</th><th style="padding:10px;text-align:right;">Orders</th><th style="padding:10px;text-align:right;">Total Spent</th></tr></thead><tbody><?php if(empty($top_customers)): ?><tr><td colspan="3" style="text-align:center; padding:20px; color:#666;">No customer data for this period.</td></tr><?php endif; ?><?php foreach($top_customers as $tc): ?><tr><td style="padding:10px;"><strong><?php echo htmlspecialchars($tc['first_name'] . ' ' . $tc['last_name']); ?></strong><div style="font-size:12px; color:#666;"><?php echo htmlspecialchars($tc['email']); ?></div></td><td style="padding:10px;text-align:right;"><?php echo number_format($tc['total_orders']); ?></td><td style="padding:10px;text-align:right;font-weight:bold;">£<?php echo number_format($tc['total_spent'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterTypeSelect = document.getElementById('filter_type');
    const dateRangeInputs = document.getElementById('date_range_inputs');
    const singleValueInputs = document.getElementById('single_value_inputs');
    const singleValueSelect = document.getElementById('filter_value');
    
    const availableYears = <?php echo json_encode($available_years ?? []); ?>;
    const availableMonths = <?php echo json_encode($available_months ?? []); ?>;
    
    const currentFilterType = '<?php echo htmlspecialchars($filter_type); ?>';
    const currentFilterValue = '<?php echo htmlspecialchars($_GET['filter_value'] ?? ''); ?>';

    function updateFilterUI(type) {
        dateRangeInputs.style.display = 'none';
        singleValueInputs.style.display = 'none';
        
        switch (type) {
            case 'date_range':
                dateRangeInputs.style.display = 'flex';
                break;
            case 'monthly':
                singleValueInputs.style.display = 'block';
                singleValueSelect.innerHTML = '<option value="">--Select Month--</option>';
                availableMonths.forEach(month => {
                    const selected = month === currentFilterValue ? ' selected' : '';
                    const monthName = new Date(month + '-02').toLocaleString('default', { month: 'long', year: 'numeric' });
                    singleValueSelect.innerHTML += `<option value="${month}"${selected}>${monthName}</option>`;
                });
                break;
            case 'yearly':
                singleValueInputs.style.display = 'block';
                singleValueSelect.innerHTML = '<option value="">--Select Year--</option>';
                availableYears.forEach(year => {
                    const selected = year === currentFilterValue ? ' selected' : '';
                    singleValueSelect.innerHTML += `<option value="${year}"${selected}>${year}</option>`;
                });
                break;
        }
    }

    filterTypeSelect.addEventListener('change', function() {
        updateFilterUI(this.value);
    });

    // Initial UI setup on page load
    updateFilterUI(currentFilterType);
});
</script>

<?php include '_layout_footer.php'; ?>