<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Get selected periods from request or use defaults
$revenue_period = isset($_POST['revenue_period']) ? $_POST['revenue_period'] : 'last_6_months';
$category_period = isset($_POST['category_period']) ? $_POST['category_period'] : 'last_3_months';
$sales_period = isset($_POST['sales_period']) ? $_POST['sales_period'] : 'today';

// Function to get sales data based on period
function getSalesData($period, $conn) {
    $query = "";
    switch ($period) {
        case 'today':
            $query = "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE DATE(date) = CURDATE()";
            break;
        case 'this_week':
            $query = "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $query = "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
            break;
        case 'last_6_months':
            $query = "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            break;
        default:
            $query = "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE DATE(date) = CURDATE()";
    }
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get sales data for the selected period
$sales_data = getSalesData($sales_period, $conn);

// Get dashboard statistics
$stats = [];
$queries = [
    'total_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales",
    'today_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE DATE(date) = CURDATE()",
    'monthly_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())",
    'yearly_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE YEAR(date) = YEAR(CURDATE())",
    'total_customers' => "SELECT COUNT(*) as count FROM customers",
    'total_products' => "SELECT COUNT(*) as count FROM products",
    'low_stock' => "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10 AND stock_quantity > 0",
    'out_of_stock' => "SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 0",
    'pending_returns' => "SELECT COUNT(*) as count FROM returns WHERE status = 'pending'"
];

foreach ($queries as $key => $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $stats[$key] = mysqli_fetch_assoc($result);
    }
}

// Get recent sales based on selected period
$recent_sales = [];
$sales_where = "";

switch ($sales_period) {
    case 'today':
        $sales_where = "WHERE DATE(s.date) = CURDATE()";
        break;
    case 'this_week':
        $sales_where = "WHERE YEARWEEK(s.date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'this_month':
        $sales_where = "WHERE MONTH(s.date) = MONTH(CURDATE()) AND YEAR(s.date) = YEAR(CURDATE())";
        break;
    case 'last_6_months':
        $sales_where = "WHERE s.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        break;
    default:
        $sales_where = "WHERE DATE(s.date) = CURDATE()";
}

$sales_query = "SELECT s.id, s.invoice_number, s.date, s.total, c.customer_name, s.payment_status 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                $sales_where
                ORDER BY s.date DESC 
                LIMIT 5";
$sales_result = mysqli_query($conn, $sales_query);
while ($row = mysqli_fetch_assoc($sales_result)) {
    $recent_sales[] = $row;
}

// Get weekly sales data for chart
$weekly_sales = [];
$weekly_query = "SELECT 
                    DATE_FORMAT(date, '%Y-%m-%d') as day, 
                    SUM(total) as amount 
                 FROM sales 
                 WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                 GROUP BY day 
                 ORDER BY day";
$weekly_result = mysqli_query($conn, $weekly_query);
while ($row = mysqli_fetch_assoc($weekly_result)) {
    $weekly_sales[] = $row;
}

// Get top selling products
$top_products = [];
$top_products_query = "
    SELECT p.name, p.product_code, SUM(si.quantity) as total_sold, p.image 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.product_id 
    JOIN sales s ON si.sale_id = s.id 
    WHERE s.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.product_id 
    ORDER BY total_sold DESC 
    LIMIT 5
";
$top_products_result = mysqli_query($conn, $top_products_query);
while ($row = mysqli_fetch_assoc($top_products_result)) {
    $top_products[] = $row;
}

// Get low stock products
$low_stock_products = [];
$low_stock_query = "
    SELECT product_id, product_code, name, stock_quantity, image 
    FROM products 
    WHERE stock_quantity < 10 
    ORDER BY stock_quantity ASC 
    LIMIT 5
";
$low_stock_result = mysqli_query($conn, $low_stock_query);
while ($row = mysqli_fetch_assoc($low_stock_result)) {
    $low_stock_products[] = $row;
}

// NEW: Get revenue overview data based on selected period
$revenue_data = [];
$revenue_where = "";

switch ($revenue_period) {
    case 'today':
        $revenue_where = "WHERE DATE(date) = CURDATE()";
        $revenue_group = "DATE_FORMAT(date, '%Y-%m-%d %H:00')";
        break;
    case 'this_week':
        $revenue_where = "WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $revenue_group = "DATE_FORMAT(date, '%Y-%m-%d')";
        break;
    case 'this_month':
        $revenue_where = "WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        $revenue_group = "DATE_FORMAT(date, '%Y-%m-%d')";
        break;
    case 'last_6_months':
    default:
        $revenue_where = "WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $revenue_group = "DATE_FORMAT(date, '%Y-%m')";
        break;
}

$revenue_query = "
    SELECT 
        $revenue_group as period,
        SUM(total) as revenue,
        COUNT(*) as transactions
    FROM sales 
    $revenue_where
    GROUP BY period 
    ORDER BY period DESC
";
$revenue_result = mysqli_query($conn, $revenue_query);
while ($row = mysqli_fetch_assoc($revenue_result)) {
    $revenue_data[] = $row;
}

// NEW: Get sales by category data based on selected period
$category_where = "";

switch ($category_period) {
    case 'today':
        $category_where = "AND s.date >= CURDATE()";
        break;
    case 'this_week':
        $category_where = "AND s.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'this_month':
        $category_where = "AND MONTH(s.date) = MONTH(CURDATE()) AND YEAR(s.date) = YEAR(CURDATE())";
        break;
    case 'last_3_months':
    default:
        $category_where = "AND s.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
}

$category_sales = [];
$category_query = "
    SELECT 
        p.category_name,
        SUM(si.quantity * si.price) as total_sales,
        COUNT(DISTINCT s.id) as order_count
    FROM sale_items si
    JOIN products p ON si.product_id = p.product_id
    JOIN sales s ON si.sale_id = s.id
    WHERE 1=1 $category_where
    GROUP BY p.category_name
    ORDER BY total_sales DESC
";
$category_result = mysqli_query($conn, $category_query);
while ($row = mysqli_fetch_assoc($category_result)) {
    $category_sales[] = $row;
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Dashboard</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard/index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Dashboard</a></li>
                    <li class="breadcrumb-item" aria-current="page">Dashboard</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
            <!-- Total Sales -->
            <div class="col-span-12 xl:col-span-4 md:col-span-6">
                <div class="card">
                    <div class="card-header !pb-0 !border-b-0">
                        <h5>Total Sales</h5>
                    </div>
                    <div class="card card-social">
                        <div class="card-body border-b border-theme-border dark:border-themedark-border">
                            <div class="flex items-center justify-center">
                                <div class="shrink-0">
                                    <i class="ti ti-shopping-cart fs-4 text-success text-primary-500 text-[36px]"></i>
                                </div>
                                <div class="grow ltr:text-right rtl:text-left">
                                    <h3 class="mb-2">$ <?= number_format($stats['total_sales']['amount'] ?? 0, 2) ?></h3>
                                    <h5 class="text-success-500 mb-0">
                                        <i class="ti ti-trending-up me-1"></i> 
                                        <?= $stats['total_sales']['count'] ?? 0 ?>  
                                        <span class="text-muted">transactions</span>
                                    </h5>
                                </div>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-theme-bg-1 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
<!-- [ Previous code remains the same ] -->

<!-- Filterable Sales Card -->
<div class="col-span-12 xl:col-span-4 md:col-span-6">
    <div class="card">
        <div class="card-header !pb-0 !border-b-0 d-flex justify-content-between align-items-center">
            <h5>Sales Overview</h5>
            <div>
                <form method="POST" class="d-inline" id="salesFilterForm">
                    <select name="sales_period" id="sales_period" class="form-select form-select-sm" onchange="document.getElementById('salesFilterForm').submit()">
                        <option value="today" <?= $sales_period == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="this_week" <?= $sales_period == 'this_week' ? 'selected' : '' ?>>This Week</option>
                        <option value="this_month" <?= $sales_period == 'this_month' ? 'selected' : '' ?>>This Month</option>
                        <option value="last_6_months" <?= $sales_period == 'last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
                    </select>
                    <!-- Preserve other filter selections when submitting -->
                    <input type="hidden" name="revenue_period" value="<?= $revenue_period ?>">
                    <input type="hidden" name="category_period" value="<?= $category_period ?>">
                </form>
            </div>
        </div>
        <div class="card card-social">
            <div class="card-body border-b border-theme-border dark:border-themedark-border">
                <div class="flex items-center justify-center">
                    <div class="shrink-0">
                        <i class="ti ti-currency-dollar fs-4 text-primary text-primary-500 text-[36px]"></i>
                    </div>
                    <div class="grow ltr:text-right rtl:text-left">
                        <?php
                        $label = "today";
                        switch ($sales_period) {
                            case 'today':
                                $label = "today";
                                break;
                            case 'this_week':
                                $label = "this week";
                                break;
                            case 'this_month':
                                $label = "this month";
                                break;
                            case 'last_6_months':
                                $label = "last 6 months";
                                break;
                        }
                        ?>
                        <h3 class="mb-2">$ <?= number_format($sales_data['amount'] ?? 0, 2) ?></h3>
                        <h5 class="text-success-500 mb-0">
                            <i class="ti ti-clock me-1"></i>    
                            <?= $sales_data['count'] ?? 0 ?>  
                            <span class="text-muted"><?= $label ?></span>
                        </h5>
                    </div>
                </div>
                <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                    <div class="bg-theme-bg-1 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar" style="width: 50%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- [ Rest of the code remains the same ] -->
            

            <!-- Customers -->
            <div class="col-span-12 xl:col-span-4 md:col-span-6">
                <div class="card">
                    <div class="card-header !pb-0 !border-b-0">
                        <h5>Customers</h5>
                    </div>
                    <div class="card card-social">
                        <div class="card-body border-b border-theme-border dark:border-themedark-border">
                            <div class="flex items-center justify-center">
                                <div class="shrink-0">
                                    <i class="ti ti-users fs-4 text-secondary text-[36px]"></i>
                                </div>
                                <div class="grow ltr:text-right rtl:text-left">
                                    <h3 class="mb-2"><?= number_format($stats['total_customers']['count'] ?? 0) ?></h3>
                                    <h5 class="text-secondary-500 mb-0">
                                        <i class="ti ti-user me-1"></i>    
                                        <span class="text-muted">registered customers</span>
                                    </h5>
                                </div>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-secondary-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Product -->
            <div class="col-span-12 xl:col-span-4 md:col-span-6">
                <div class="card">
                    <div class="card-header !pb-0 !border-b-0">
                        <h5>Total Products</h5>
                    </div>
                    <div class="card card-social">
                        <div class="card-body border-b border-theme-border dark:border-themedark-border">
                            <div class="flex items-center justify-center">
                                <div class="shrink-0">
                                    <i class="ti ti-package fs-4 text-danger text-[36px]"></i>
                                </div>
                                <div class="grow ltr:text-right rtl:text-left">
                                    <h3 class="mb-2"><?= number_format($stats['total_products']['count'] ?? 0) ?></h3>
                                    <h5 class="text-danger-500 mb-0">
                                        <i class="ti ti-box me-1"></i>    
                                        <span class="text-muted">products in inventory</span>
                                    </h5>
                                </div>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-danger-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- NEW: Revenue Overview -->
<div class="col-span-12 xl:col-span-8">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Revenue Overview</h5>
            <div>
                <form method="POST" class="d-inline" id="revenueFilterForm">
                    <select name="revenue_period" id="revenue_period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" <?= $revenue_period == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="this_week" <?= $revenue_period == 'this_week' ? 'selected' : '' ?>>This Week</option>
                        <option value="this_month" <?= $revenue_period == 'this_month' ? 'selected' : '' ?>>This Month</option>
                        <option value="last_6_months" <?= $revenue_period == 'last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
                    </select>
                    <!-- Keep category period selection when submitting -->
                    <input type="hidden" name="category_period" value="<?= $category_period ?>">
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($revenue_data)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Revenue</th>
                                <th>Transactions</th>
                                <th>Average Order Value</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prev_revenue = null;
                            foreach ($revenue_data as $revenue): 
                                // Format period based on selected time range
                                $period_name = '';
                                if ($revenue_period == 'today') {
                                    $period_name = date('g A', strtotime($revenue['period']));
                                } else if ($revenue_period == 'this_week' || $revenue_period == 'this_month') {
                                    $period_name = date('M j', strtotime($revenue['period']));
                                } else {
                                    $period_name = date('F Y', strtotime($revenue['period'] . '-01'));
                                }
                                
                                $avg_order = $revenue['transactions'] > 0 ? $revenue['revenue'] / $revenue['transactions'] : 0;
                                
                                // Calculate trend
                                $trend_icon = 'ti-minus';
                                $trend_class = 'text-muted';
                                if ($prev_revenue !== null) {
                                    if ($revenue['revenue'] > $prev_revenue) {
                                        $trend_icon = 'ti-trending-up';
                                        $trend_class = 'text-success';
                                    } elseif ($revenue['revenue'] < $prev_revenue) {
                                        $trend_icon = 'ti-trending-down';
                                        $trend_class = 'text-danger';
                                    }
                                }
                                $prev_revenue = $revenue['revenue'];
                            ?>
                            <tr>
                                <td><?= $period_name ?></td>
                                <td>$<?= number_format($revenue['revenue'], 2) ?></td>
                                <td><?= $revenue['transactions'] ?></td>
                                <td>$<?= number_format($avg_order, 2) ?></td>
                                <td class="<?= $trend_class ?>">
                                    <i class="ti <?= $trend_icon ?>"></i>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="ti ti-chart-line text-muted text-4xl mb-2"></i>
                    <p class="text-muted">No revenue data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- NEW: Sales by Category -->
<div class="col-span-12 xl:col-span-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Sales by Category</h5>
            <div>
                <form method="POST" class="d-inline" id="categoryFilterForm">
                    <select name="category_period" id="category_period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" <?= $category_period == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="this_week" <?= $category_period == 'this_week' ? 'selected' : '' ?>>This Week</option>
                        <option value="this_month" <?= $category_period == 'this_month' ? 'selected' : '' ?>>This Month</option>
                        <option value="last_3_months" <?= $category_period == 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
                    </select>
                    <!-- Keep revenue period selection when submitting -->
                    <input type="hidden" name="revenue_period" value="<?= $revenue_period ?>">
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($category_sales)): ?>
                <div class="mb-4" style="height: 250px;">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Sales</th>
                                <th>Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_sales as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category_name']) ?></td>
                                <td>$<?= number_format($category['total_sales'], 2) ?></td>
                                <td><?= $category['order_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="ti ti-chart-pie text-muted text-4xl mb-2"></i>
                    <p class="text-muted">No category sales data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

            <!-- [ Low Stock Alert ] -->
            <div class="col-span-12 xl:col-span-4 md:col-span-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_products)): ?>
                            <div class="space-y-4">
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="shrink-0 me-3">
                                                <?php 
                                                $imagePath = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/default-product.png';
                                                ?>
                                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-full" style="width: 40px; height: 40px; object-fit: cover;">
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($product['name']) ?></h6>
                                                <p class="text-muted text-xs mb-0"><?= htmlspecialchars($product['product_code']) ?></p>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?= $product['stock_quantity'] == 0 ? 'danger' : 'warning' ?>">
                                                <?= $product['stock_quantity'] ?> left
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="products_stock.php" class="btn btn-sm btn-outline-warning">View All Low Stock</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="ti ti-check text-success text-4xl mb-2"></i>
                                <p class="text-muted">All products are sufficiently stocked</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="col-span-12 xl:col-span-8 md:col-span-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($top_products)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Code</th>
                                            <th>Units Sold</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="flex items-center">
                                                        <div class="shrink-0 me-3">
                                                            <?php 
                                                            $imagePath = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/default-product.png';
                                                            ?>
                                                            <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-full" style="width: 40px; height: 40px; object-fit: cover;">
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($product['name']) ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($product['product_code']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $product['total_sold'] ?> sold</span>
                                                </td>
                                                <td>
                                                    <a href="restock_product.php?id=<?= $product['product_id'] ?? '' ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="ti ti-package me-1"></i> Restock
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="ti ti-shopping-cart text-muted text-4xl mb-2"></i>
                                <p class="text-muted">No sales data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="sale_pos.php" class="btn btn-primary">
                                <i class="ti ti-shopping-cart me-2"></i> New Sale
                            </a>
                            <a href="add_new_products.php" class="btn btn-outline-primary">
                                <i class="ti ti-package me-2"></i> Add Product
                            </a>
                            <a href="add_new_customer.php" class="btn btn-outline-primary">
                                <i class="ti ti-user-plus me-2"></i> Add Customer
                            </a>
                            <a href="products_stock.php" class="btn btn-outline-warning">
                                <i class="ti ti-alert-triangle me-2"></i> View Low Stock
                            </a>
                            <a href="manage_sale.php" class="btn btn-outline-info">
                                <i class="ti ti-report-analytics me-2"></i> Sales Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Sales -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Recent Sales</h5>
                        <a href="sales.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sale['invoice_number']) ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($sale['date'])) ?></td>
                                        <td><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in') ?></td>
                                        <td>$<?= number_format($sale['total'], 2) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $sale['payment_status'] === 'paid' ? 'bg-success' : 
                                                   ($sale['payment_status'] === 'pending' ? 'bg-warning' : 'bg-info') ?>">
                                                <?= ucfirst($sale['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_sale_mange.php?id=<?= $sale['id'] ?? '' ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_sales)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent sales found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Dashboard Widgets -->
        <div class="row mt-4">
            <!-- Pending Returns -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Pending Returns</h5>
                        <span class="badge bg-danger"><?= $stats['pending_returns']['count'] ?? 0 ?> pending</span>
                    </div>
                    <div class="card-body">
                        <?php if (($stats['pending_returns']['count'] ?? 0) > 0): ?>
                            <p>You have <?= $stats['pending_returns']['count'] ?> returns awaiting processing.</p>
                            <a href="returns.php" class="btn btn-sm btn-outline-danger">Process Returns</a>
                        <?php else: ?>
                            <p class="text-muted">No pending returns at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm bg-light-success rounded-circle p-2 me-3">
                                <i class="ti ti-server fs-4 text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Database Connection</h6>
                                <span class="text-success">Active</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-light-success rounded-circle p-2 me-3">
                                <i class="ti ti-shield-check fs-4 text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Security Status</h6>
                                <span class="text-success">All systems secure</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // NEW: Category Sales Chart
    <?php if (!empty($category_sales)): ?>
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($category_sales as $category): ?>
                    '<?= addslashes($category['category_name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($category_sales as $category): ?>
                        <?= $category['total_sales'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
}); 
</script>

<style>
.stat-card {
    transition: all 0.3s ease;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}  
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

/* Style for filter dropdowns */
.form-select-sm {
    padding: 0.25rem 1.5rem 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>

<?php include('include/footer.php'); ?>