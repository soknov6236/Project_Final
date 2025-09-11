<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch summary statistics
$total_products = 0;
$total_sales = 0;
$total_purchases = 0;
$total_returns = 0;
$inventory_value = 0;

// Get product count
$product_query = "SELECT COUNT(*) as count FROM products";
$product_result = mysqli_query($conn, $product_query);
if ($product_result) {
    $product_data = mysqli_fetch_assoc($product_result);
    $total_products = $product_data['count'];
}

// Get sales total
$sales_query = "SELECT SUM(total) as total FROM sales WHERE date BETWEEN '$start_date' AND '$end_date 23:59:59'";
$sales_result = mysqli_query($conn, $sales_query);
if ($sales_result) {
    $sales_data = mysqli_fetch_assoc($sales_result);
    $total_sales = $sales_data['total'] ? $sales_data['total'] : 0;
}

// Get purchases total
$purchases_query = "SELECT SUM(total_amount) as total FROM purchases WHERE purchase_date BETWEEN '$start_date' AND '$end_date 23:59:59'";
$purchases_result = mysqli_query($conn, $purchases_query);
if ($purchases_result) {
    $purchases_data = mysqli_fetch_assoc($purchases_result);
    $total_purchases = $purchases_data['total'] ? $purchases_data['total'] : 0;
}

// Get returns total
$returns_query = "SELECT SUM(total_amount) as total FROM returns WHERE return_date BETWEEN '$start_date' AND '$end_date 23:59:59'";
$returns_result = mysqli_query($conn, $returns_query);
if ($returns_result) {
    $returns_data = mysqli_fetch_assoc($returns_result);
    $total_returns = $returns_data['total'] ? $returns_data['total'] : 0;
}

// Get inventory value
$inventory_query = "SELECT SUM(stock_quantity * cost_price) as value FROM products";
$inventory_result = mysqli_query($conn, $inventory_query);
if ($inventory_result) {
    $inventory_data = mysqli_fetch_assoc($inventory_result);
    $inventory_value = $inventory_data['value'] ? $inventory_data['value'] : 0;
}

// Get sales by category
$sales_by_category = [];
$category_query = "
    SELECT c.name as category, SUM(si.quantity * si.unit_price) as total 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.product_id 
    JOIN category c ON p.category_name = c.name 
    JOIN sales s ON si.sale_id = s.id 
    WHERE s.date BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY c.name 
    ORDER BY total DESC
";
$category_result = mysqli_query($conn, $category_query);
if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $sales_by_category[] = $row;
    }
}

// Get top selling products
$top_products = [];
$products_query = "
    SELECT p.name, p.product_code, SUM(si.quantity) as total_sold, SUM(si.quantity * si.unit_price) as revenue 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.product_id 
    JOIN sales s ON si.sale_id = s.id 
    WHERE s.date BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY p.product_id 
    ORDER BY total_sold DESC 
    LIMIT 10
";
$products_result = mysqli_query($conn, $products_query);
if ($products_result) {
    while ($row = mysqli_fetch_assoc($products_result)) {
        $top_products[] = $row;
    }
}

// Get low stock products
$low_stock = [];
$low_stock_query = "SELECT product_id, product_code, name, stock_quantity FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 10";
$low_stock_result = mysqli_query($conn, $low_stock_query);
if ($low_stock_result) {
    while ($row = mysqli_fetch_assoc($low_stock_result)) {
        $low_stock[] = $row;
    }
}

// Get recent activities
$recent_activities = [];
$activities_query = "
    (SELECT 'sale' as type, id as ref_id, date as activity_date, total as amount, 'Sale Completed' as description FROM sales ORDER BY date DESC LIMIT 5)
    UNION
    (SELECT 'purchase' as type, purchase_id as ref_id, purchase_date as activity_date, total_amount as amount, 'Purchase Made' as description FROM purchases ORDER BY purchase_date DESC LIMIT 5)
    UNION
    (SELECT 'return' as type, return_id as ref_id, return_date as activity_date, total_amount as amount, 'Return Processed' as description FROM returns ORDER BY return_date DESC LIMIT 5)
    ORDER BY activity_date DESC 
    LIMIT 10
";
$activities_result = mysqli_query($conn, $activities_query);
if ($activities_result) {
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $recent_activities[] = $row;
    }
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">System Reports</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Reports</a></li>
                    <li class="breadcrumb-item" aria-current="page">System Reports</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <div class="row">
            <!-- Date Range Filter -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Report Period</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-success" id="export-report">
                                    <i class="ti ti-download me-1"></i> Export Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-primary">
                                    <i class="ti ti-package f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Total Products</h6>
                                <h4 class="mb-0"><?php echo number_format($total_products); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-success">
                                    <i class="ti ti-shopping-cart f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Total Sales</h6>
                                <h4 class="mb-0">$<?php echo number_format($total_sales, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-info">
                                    <i class="ti ti-truck-loading f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Total Purchases</h6>
                                <h4 class="mb-0">$<?php echo number_format($total_purchases, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-danger">
                                    <i class="ti ti-arrow-back-up f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Total Returns</h6>
                                <h4 class="mb-0">$<?php echo number_format($total_returns, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Sales by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Sales Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($sales_by_category)): ?>
                                        <?php foreach ($sales_by_category as $category): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                <td class="text-end">$<?php echo number_format($category['total'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No sales data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th class="text-end">Quantity Sold</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_products)): ?>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                                <td class="text-end"><?php echo number_format($product['total_sold']); ?></td>
                                                <td class="text-end">$<?php echo number_format($product['revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No sales data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th class="text-end">Current Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($low_stock)): ?>
                                        <?php foreach ($low_stock as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-<?php echo $product['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                                        <?php echo number_format($product['stock_quantity']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="restock_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="ti ti-package me-1"></i> Restock
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">All products are sufficiently stocked</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_activities)): ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y h:i A', strtotime($activity['activity_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        if ($activity['type'] == 'sale') echo 'success';
                                                        elseif ($activity['type'] == 'purchase') echo 'info';
                                                        else echo 'danger';
                                                    ?>">
                                                        <?php echo ucfirst($activity['type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td class="text-end">$<?php echo number_format($activity['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent activities</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Value -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h3>Total Inventory Value</h3>
                        <h1 class="display-4 text-primary">$<?php echo number_format($inventory_value, 2); ?></h1>
                        <p class="text-muted">Based on current stock and cost prices</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable for all tables
    $('table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        ordering: false,
        searching: false,
        info: false,
        paging: false
    });

    // Export full report
    $('#export-report').click(function() {
        // Create a combined report with all data
        const reportData = {
            period: {
                start: '<?php echo $start_date; ?>',
                end: '<?php echo $end_date; ?>'
            },
            summary: {
                total_products: <?php echo $total_products; ?>,
                total_sales: <?php echo $total_sales; ?>,
                total_purchases: <?php echo $total_purchases; ?>,
                total_returns: <?php echo $total_returns; ?>,
                inventory_value: <?php echo $inventory_value; ?>
            },
            sales_by_category: <?php echo json_encode($sales_by_category); ?>,
            top_products: <?php echo json_encode($top_products); ?>,
            low_stock: <?php echo json_encode($low_stock); ?>,
            recent_activities: <?php echo json_encode($recent_activities); ?>
        };

        // Convert to JSON and download
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(reportData, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "system_report_<?php echo date('Y-m-d'); ?>.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove(); 
    });
});
</script>

<?php include ('include/footer.php'); ?>