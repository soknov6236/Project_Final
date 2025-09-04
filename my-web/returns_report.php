<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Handle date filter submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Get filter values from URL if present
if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    $_SESSION['error_message'] = "End date must be after start date";
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Get status filter if set
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the base query
$query = "SELECT 
            r.return_id,
            r.invoice_number,
            r.customer_name,
            r.return_date,
            r.status,
            r.total_amount,
            r.refund_amount,
            r.payment_method,
            u.username AS processed_by,
            COUNT(ri.return_item_id) AS item_count
          FROM returns r
          LEFT JOIN users u ON r.created_by = u.id
          LEFT JOIN return_items ri ON r.return_id = ri.return_id
          WHERE r.return_date BETWEEN ? AND ?";

// Add status filter if not 'all'
if ($status_filter != 'all') {
    $query .= " AND r.status = ?";
}

$query .= " GROUP BY r.return_id
            ORDER BY r.return_date DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if ($status_filter != 'all') {
    mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $status_filter);
} else {
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get summary statistics
$stats_query = "SELECT 
                COUNT(r.return_id) AS total_returns,
                SUM(r.total_amount) AS total_amount,
                SUM(r.refund_amount) AS total_refunds,
                r.status,
                COUNT(ri.return_item_id) AS total_items
                FROM returns r
                LEFT JOIN return_items ri ON r.return_id = ri.return_id
                WHERE r.return_date BETWEEN ? AND ?";

if ($status_filter != 'all') {
    $stats_query .= " AND r.status = ?";
}

$stats_query .= " GROUP BY r.status";

$stats_stmt = mysqli_prepare($conn, $stats_query);
if ($status_filter != 'all') {
    mysqli_stmt_bind_param($stats_stmt, 'sss', $start_date, $end_date, $status_filter);
} else {
    mysqli_stmt_bind_param($stats_stmt, 'ss', $start_date, $end_date);
}
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);

$status_counts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'processed' => 0,
    'total_amount' => 0,
    'total_refunds' => 0,
    'total_items' => 0
];

while ($stat = mysqli_fetch_assoc($stats_result)) {
    $status_counts[$stat['status']] = $stat['total_returns'];
    $status_counts['total_amount'] += $stat['total_amount'];
    $status_counts['total_refunds'] += $stat['total_refunds'];
    $status_counts['total_items'] += $stat['total_items'];
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Returns Report</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="returns.php">Returns</a></li>
                    <li class="breadcrumb-item" aria-current="page">Reports</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Returns Analysis</h5>
                        <div>
                            <button class="btn btn-primary" id="print-report">
                                <i class="ti ti-printer"></i> Print Report
                            </button>
                            <button class="btn btn-success" id="export-report">
                                <i class="ti ti-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $start_date; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $end_date; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        <option value="processed" <?php echo $status_filter == 'processed' ? 'selected' : ''; ?>>Processed</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-filter"></i> Filter
                                    </button>
                                    <a href="returns_report.php" class="btn btn-outline-secondary ms-2">
                                        <i class="ti ti-reload"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Returns</h5>
                                        <h2 class="mb-0"><?php echo array_sum(array_slice($status_counts, 0, 4)); ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Approved</h5>
                                        <h2 class="mb-0"><?php echo $status_counts['approved']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Pending</h5>
                                        <h2 class="mb-0"><?php echo $status_counts['pending']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Rejected</h5>
                                        <h2 class="mb-0"><?php echo $status_counts['rejected']; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Financial Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tr>
                                                    <th>Total Return Amount:</th>
                                                    <td class="text-end">$<?php echo number_format($status_counts['total_amount'], 2); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Refunded:</th>
                                                    <td class="text-end">$<?php echo number_format($status_counts['total_refunds'], 2); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Items Returned:</th>
                                                    <td class="text-end"><?php echo $status_counts['total_items']; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Status Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="statusChart" style="height: 250px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Report -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Detailed Returns</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="returns-report-table">
                                        <thead>
                                            <tr>
                                                <th>Return ID</th>
                                                <th>Date</th>
                                                <th>Invoice #</th>
                                                <th>Customer</th>
                                                <th>Items</th>
                                                <th>Amount</th>
                                                <th>Refund</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Processed By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($return = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo $return['return_id']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($return['invoice_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($return['customer_name']); ?></td>
                                                    <td><?php echo $return['item_count']; ?></td>
                                                    <td>$<?php echo number_format($return['total_amount'], 2); ?></td>
                                                    <td>$<?php echo number_format($return['refund_amount'] ?? 0, 2); ?></td>
                                                    <td><?php echo ucwords(str_replace('_', ' ', $return['payment_method'])); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php 
                                                            switch($return['status']) {
                                                                case 'approved': echo 'bg-success'; break;
                                                                case 'rejected': echo 'bg-danger'; break;
                                                                case 'processed': echo 'bg-info'; break;
                                                                default: echo 'bg-warning';
                                                            }
                                                            ?>">
                                                            <?php echo ucfirst($return['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($return['processed_by'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables and Charts -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#returns-report-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[1, 'desc']],
        responsive: true,
        pageLength: 25
    });

    // Export button handler
    $('#export-report').click(function() {
        $('#returns-report-table').DataTable().button('excel').trigger();
    });

    // Print button handler
    $('#print-report').click(function() {
        $('#returns-report-table').DataTable().button('print').trigger();
    });

    // Status chart
    var statusChart = new ApexCharts(document.querySelector("#statusChart"), {
        series: [
            <?php echo $status_counts['pending']; ?>,
            <?php echo $status_counts['approved']; ?>,
            <?php echo $status_counts['rejected']; ?>,
            <?php echo $status_counts['processed']; ?>
        ],
        chart: {
            type: 'donut',
            height: 350
        },
        labels: ['Pending', 'Approved', 'Rejected', 'Processed'],
        colors: ['#FFC107', '#28A745', '#DC3545', '#17A2B8'],
        legend: {
            position: 'bottom'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    });
    statusChart.render();

    // Update chart when filters change
    $('#status').change(function() {
        // This would ideally reload the page with new filters
        // or make an AJAX call to update the data
    });
});
</script>

<style>
    .card .card-title {
        font-size: 1rem;
        font-weight: 500;
    }
    .card .card-body h2 {
        font-weight: 600;
    }
    #statusChart {
        margin: 0 auto;
    }
</style>

<?php include('include/footer.php'); ?>