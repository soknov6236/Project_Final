<?php
session_start();
require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$report_type = 'sales_summary';  // Set default report type

// Get filter parameters if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $report_type = $_POST['report_type'] ?? 'sales_summary';
}

// Also handle GET requests for direct linking to reports
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['report_type'])) {
    $report_type = $_GET['report_type'];
    $start_date = $_GET['start_date'] ?? $start_date;
    $end_date = $_GET['end_date'] ?? $end_date;
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Sales & Inventory Reports</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Reports</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Report Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="report_type" class="form-label">Report Type</label>
                                        <select class="form-select" id="report_type" name="report_type">
                                            <optgroup label="Sales Reports">
                                                <option value="sales_summary" <?php echo $report_type == 'sales_summary' ? 'selected' : ''; ?>>Sales Summary</option>
                                                <option value="sale_report" <?php echo $report_type == 'sale_report' ? 'selected' : ''; ?>>Sale Report</option>
                                                <option value="sales_by_product" <?php echo $report_type == 'sales_by_product' ? 'selected' : ''; ?>>Sales by Product</option>
                                                <option value="sales_by_customer" <?php echo $report_type == 'sales_by_customer' ? 'selected' : ''; ?>>Sales by Customer</option>
                                                <option value="sales_by_payment" <?php echo $report_type == 'sales_by_payment' ? 'selected' : ''; ?>>Sales by Payment Method</option>
                                            </optgroup>
                                            <optgroup label="Inventory Reports">
                                                <option value="stock_level" <?php echo $report_type == 'stock_level' ? 'selected' : ''; ?>>Stock Level</option>
                                                <option value="inventory_movement" <?php echo $report_type == 'inventory_movement' ? 'selected' : ''; ?>>Inventory Movement</option>
                                                <option value="expiry_tracking" <?php echo $report_type == 'expiry_tracking' ? 'selected' : ''; ?>>Expiry Tracking</option>
                                            </optgroup>
                                            <optgroup label="Returns Reports">
                                                <option value="returns_refunds" <?php echo $report_type == 'returns_refunds' ? 'selected' : ''; ?>>Returns & Refunds</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <?php 
                            switch($report_type) {
                                case 'sales_summary': echo 'Sales Summary Report'; break;
                                case 'sale_report': echo 'Detailed Sale Report'; break;
                                case 'sales_by_product': echo 'Sales by Product Report'; break;
                                case 'sales_by_customer': echo 'Sales by Customer Report'; break;
                                case 'sales_by_payment': echo 'Sales by Payment Method Report'; break;
                                case 'stock_level': echo 'Stock Level Report'; break;
                                case 'inventory_movement': echo 'Inventory Movement Report'; break;
                                case 'expiry_tracking': echo 'Expiry Tracking Report'; break;
                                case 'returns_refunds': echo 'Returns & Refunds Report'; break;
                                default: echo 'Sales Summary Report';
                            }
                            ?>
                            <small class="d-block text-muted mt-1 fw-normal"><?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></small>
                        </h5>
                        <div>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="ti ti-printer"></i> Print
                            </button>
                            <button class="btn btn-outline-success" id="exportExcel">
                                <i class="ti ti-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Include the appropriate report based on selection
                        switch($report_type) {
                            case 'sales_summary':
                                include('reports/sales_summary.php');
                                break;
                            case 'sale_report':
                                include('reports/sale_report.php');
                                break;
                            case 'sales_by_product':
                                include('reports/sales_by_product.php');
                                break;
                            case 'sales_by_customer':
                                include('reports/sales_by_customer.php');
                                break;
                            case 'sales_by_payment':
                                include('reports/sales_by_payment.php');
                                break;
                            case 'stock_level':
                                include('reports/stock_level.php');
                                break;
                            case 'inventory_movement':
                                include('reports/inventory_movement.php');
                                break;
                            case 'expiry_tracking':
                                include('reports/expiry_tracking.php');
                                break;
                            case 'returns_refunds':
                                include('reports/returns_refunds.php');
                                break;
                            default:
                                include('reports/sales_summary.php');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Export to Excel functionality
document.getElementById('exportExcel').addEventListener('click', function() {
    let table = document.querySelector('.report-table');
    if (!table) {
        alert('No report table found to export');
        return;
    }
    
    // Create HTML string for table
    let html = table.outerHTML;
    
    // Create download link
    let blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    let a = document.createElement('a');
    
    // Generate filename based on report type
    let reportName = "<?= str_replace(' ', '_', strtolower($report_title)) ?>";
    a.download = reportName + '_<?= date("Y-m-d") ?>.xls';
    
    a.href = URL.createObjectURL(blob);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
});
</script>

<?php include('include/footer.php'); ?>