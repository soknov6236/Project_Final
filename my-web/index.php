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

// Get dashboard statistics
$stats = [];
$queries = [
    'total_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales",
    'today_sales' => "SELECT COUNT(*) as count, SUM(total) as amount FROM sales WHERE DATE(date) = CURDATE()",
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

// Get recent sales
$recent_sales = [];
$sales_query = "SELECT s.invoice_number, s.date, s.total, c.customer_name, s.payment_status 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
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

<!-- Summary Cards -->
<div class="row">
    <!-- Total Sales -->
    <div class="col-sm-6 col-md-6 col-xl-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Total Sales</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($stats['total_sales']['amount'] ?? 0, 2) ?></h3>
                        <span class="badge bg-light-success mt-2">
                            <i class="ti ti-trending-up me-1"></i>
                            <?= $stats['total_sales']['count'] ?? 0 ?> transactions
                        </span>
                    </div>
                    <div class="avatar-sm bg-success bg-opacity-10 rounded-circle p-2">
                        <i class="ti ti-shopping-cart fs-4 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Sales -->
    <div class="col-sm-6 col-md-6 col-xl-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Today's Sales</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($stats['today_sales']['amount'] ?? 0, 2) ?></h3>
                        <span class="badge bg-light-primary mt-2">
                            <i class="ti ti-clock me-1"></i>
                            <?= $stats['today_sales']['count'] ?? 0 ?> today
                        </span>
                    </div>
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="ti ti-currency-dollar fs-4 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customers -->
    <div class="col-sm-6 col-md-6 col-xl-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Customers</h6>
                        <h3 class="mb-0 fw-bold"><?= $stats['total_customers']['count'] ?? 0 ?></h3>
                        <a href="customers.php" class="text-primary small mt-2 d-block">
                            <i class="ti ti-arrow-right me-1"></i> View all
                        </a>
                    </div>
                    <div class="avatar-sm bg-info bg-opacity-10 rounded-circle p-2">
                        <i class="ti ti-users fs-4 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Status -->
    <div class="col-sm-6 col-md-6 col-xl-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Inventory</h6>
                        <h3 class="mb-0 fw-bold"><?= $stats['total_products']['count'] ?? 0 ?></h3>
                        <span class="badge bg-light-danger mt-2">
                            <i class="ti ti-alert-triangle me-1"></i>
                            <?= $stats['low_stock']['count'] ?? 0 ?> low, <?= $stats['out_of_stock']['count'] ?? 0 ?> out
                        </span>
                    </div>
                    <div class="avatar-sm bg-warning bg-opacity-10 rounded-circle p-2">
                        <i class="ti ti-package fs-4 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="row">
            <!-- Sales Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Weekly Sales</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                This Week
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">This Week</a></li>
                                <li><a class="dropdown-item" href="#">This Month</a></li>
                                <li><a class="dropdown-item" href="#">This Year</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
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
                        <?php if ($stats['pending_returns']['count'] > 0): ?>
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
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                // Generate labels for the last 7 days
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    echo "'" . date('M j', strtotime($date)) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Sales Amount',
                data: [
                    <?php
                    // Initialize an array with 0 for each day
                    $dailySales = array_fill(0, 7, 0);
                    
                    // Fill in actual sales data
                    foreach ($weekly_sales as $sale) {
                        $dayIndex = array_search(date('Y-m-d', strtotime($sale['day'])), 
                            array_map(function($i) { 
                                return date('Y-m-d', strtotime("-$i days")); 
                            }, range(6, 0)));
                        
                        if ($dayIndex !== false) {
                            $dailySales[$dayIndex] = $sale['amount'];
                        }
                    }
                    
                    // Output the data
                    echo implode(',', $dailySales);
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
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
</style>

<?php include('include/footer.php'); ?>