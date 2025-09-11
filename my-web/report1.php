<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Inventory Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --info-color: #4895ef;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
            border: none;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .stat-card {
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .bg-primary-light {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary-color);
        }
        
        .bg-success-light {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success-color);
        }
        
        .bg-warning-light {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--warning-color);
        }
        
        .bg-info-light {
            background-color: rgba(72, 149, 239, 0.15);
            color: var(--info-color);
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .summary-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .report-filter {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
        }
        
        .badge {
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 20px;
        }
        
        .page-header {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar (simplified version) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam me-2"></i>
                Inventory Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-clipboard-data me-1"></i> Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-gear me-1"></i> Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 font-medium">System Reports</h5>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">System Reports</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Export Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="report-filter">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="dateRange" class="form-label">Date Range</label>
                    <select class="form-select" id="dateRange">
                        <option selected>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>Last 90 Days</option>
                        <option>This Month</option>
                        <option>Last Month</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate" value="2023-10-01">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate" value="2023-10-31">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-primary-light">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="summary-value text-primary">$12,589</div>
                                <div class="summary-label">TOTAL SALES</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success"><i class="bi bi-arrow-up"></i> 12.5%</span>
                            <span class="text-muted ms-2">vs previous period</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-success-light">
                                    <i class="bi bi-bag-check"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="summary-value text-success">247</div>
                                <div class="summary-label">TOTAL ORDERS</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success"><i class="bi bi-arrow-up"></i> 8.3%</span>
                            <span class="text-muted ms-2">vs previous period</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-warning-light">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="summary-value text-warning">$2,347</div>
                                <div class="summary-label">TOTAL RETURNS</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-danger"><i class="bi bi-arrow-down"></i> 3.2%</span>
                            <span class="text-muted ms-2">vs previous period</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-info-light">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="summary-value text-info">184</div>
                                <div class="summary-label">NEW CUSTOMERS</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success"><i class="bi bi-arrow-up"></i> 5.7%</span>
                            <span class="text-muted ms-2">vs previous period</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Graphs -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sales Overview</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Monthly
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Daily</a></li>
                                <li><a class="dropdown-item" href="#">Weekly</a></li>
                                <li><a class="dropdown-item" href="#">Monthly</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Top Selling Products</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Men's Running Shoes</td>
                                        <td>Footwear</td>
                                        <td>124</td>
                                        <td>$5,240</td>
                                    </tr>
                                    <tr>
                                        <td>Women's Yoga Pants</td>
                                        <td>Apparel</td>
                                        <td>98</td>
                                        <td>$3,920</td>
                                    </tr>
                                    <tr>
                                        <td>Training Gloves</td>
                                        <td>Accessories</td>
                                        <td>87</td>
                                        <td>$1,305</td>
                                    </tr>
                                    <tr>
                                        <td>Sports Water Bottle</td>
                                        <td>Accessories</td>
                                        <td>76</td>
                                        <td>$1,140</td>
                                    </tr>
                                    <tr>
                                        <td>Running Shorts</td>
                                        <td>Apparel</td>
                                        <td>65</td>
                                        <td>$1,690</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Low Stock Alert</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Men's Training Shoes</td>
                                        <td>Footwear</td>
                                        <td>3</td>
                                        <td><span class="badge bg-danger">Critical</span></td>
                                    </tr>
                                    <tr>
                                        <td>Women's Jacket</td>
                                        <td>Apparel</td>
                                        <td>5</td>
                                        <td><span class="badge bg-warning">Low</span></td>
                                    </tr>
                                    <tr>
                                        <td>Sports Bag</td>
                                        <td>Accessories</td>
                                        <td>7</td>
                                        <td><span class="badge bg-warning">Low</span></td>
                                    </tr>
                                    <tr>
                                        <td>Running Socks</td>
                                        <td>Accessories</td>
                                        <td>8</td>
                                        <td><span class="badge bg-warning">Low</span></td>
                                    </tr>
                                    <tr>
                                        <td>Yoga Mat</td>
                                        <td>Equipment</td>
                                        <td>12</td>
                                        <td><span class="badge bg-info">Medium</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Reports -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Transactions</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="transactionsTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#TRX-7892</td>
                                        <td>Oct 28, 2023</td>
                                        <td>John Smith</td>
                                        <td>Sale</td>
                                        <td>$245.99</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <tr>
                                        <td>#TRX-7891</td>
                                        <td>Oct 27, 2023</td>
                                        <td>Emma Johnson</td>
                                        <td>Return</td>
                                        <td>$89.50</td>
                                        <td><span class="badge bg-success">Processed</span></td>
                                    </tr>
                                    <tr>
                                        <td>#TRX-7890</td>
                                        <td>Oct 27, 2023</td>
                                        <td>Robert Davis</td>
                                        <td>Sale</td>
                                        <td>$412.75</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <tr>
                                        <td>#TRX-7889</td>
                                        <td>Oct 26, 2023</td>
                                        <td>Sarah Wilson</td>
                                        <td>Sale</td>
                                        <td>$156.30</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <tr>
                                        <td>#TRX-7888</td>
                                        <td>Oct 26, 2023</td>
                                        <td>Michael Brown</td>
                                        <td>Return</td>
                                        <td>$62.95</td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'Sales',
                        data: [8500, 12500, 9800, 11200, 15600, 18200, 14500, 16800, 19200, 21500],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Footwear', 'Apparel', 'Accessories', 'Equipment'],
                    datasets: [{
                        data: [35, 25, 20, 20],
                        backgroundColor: [
                            '#4361ee',
                            '#4cc9f0',
                            '#f72585',
                            '#4895ef'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</body>
</html>