<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Check for messages
if (isset($_SESSION['message'])) {
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
    echo '<div class="alert alert-'.$message_type.' alert-dismissible fade show" role="alert">
            '.htmlspecialchars($_SESSION['message']).'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$invoice_number = isset($_GET['invoice_number']) ? trim($_GET['invoice_number']) : '';

// Build the base query
$query = "SELECT s.*, c.customer_name 
          FROM sales s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE 1=1";

// Add filters to the query
$params = [];
if (!empty($start_date)) {
    $query .= " AND s.date >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND s.date <= ?";
    $params[] = $end_date . ' 23:59:59';
}
if (!empty($status)) {
    $query .= " AND s.payment_status = ?";
    $params[] = $status;
}
if ($customer_id > 0) {
    $query .= " AND s.customer_id = ?";
    $params[] = $customer_id;
}
if (!empty($invoice_number)) {
    $query .= " AND s.invoice_number LIKE ?";
    $params[] = '%' . $invoice_number . '%';
}

$query .= " ORDER BY s.date DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get customers for filter dropdown
$customers_query = "SELECT id, customer_name FROM customers ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Store customers in array for multiple use
$customers = [];
while ($row = mysqli_fetch_assoc($customers_result)) {
    $customers[] = $row;
}

// Get total sales amount for the filtered results
$total_query = str_replace("SELECT s.*, c.customer_name", "SELECT SUM(s.total) as total_amount", $query);
$total_stmt = mysqli_prepare($conn, $total_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($total_stmt, $types, ...$params);
}
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_row = mysqli_fetch_assoc($total_result);
$total_sales = $total_row['total_amount'] ?? 0;
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Sales Management</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Sales</a></li>
                    <li class="breadcrumb-item" aria-current="page">Manage Sales</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Sales Records</h5>
                        <div>
                            <a href="add_new_sale.php" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus"></i> New Sale
                            </a>
                            <a href="sale_pos.php" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> Sale POS
                            </a>
                            <?php if (!empty($start_date) || !empty($end_date) || !empty($status) || !empty($invoice_number)): ?>
                                <a href="export_sales.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm ms-2">
                                    <i class="ti ti-download"></i> Export
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Form -->
                        <form id="salesFilterForm" method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date" 
                                           value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="end_date" 
                                           value="<?= htmlspecialchars($end_date) ?>" <?= empty($start_date) ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-2">
                                    <label for="statusSelect" class="form-label">Status</label>
                                    <select class="form-select" id="statusSelect" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                                        <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="customerSelect" class="form-label">Customer</label>
                                    <select class="form-select" id="customerSelect" name="customer_id">
                                        <option value="0">All Customers</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" 
                                                <?= $customer_id == $customer['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="invoiceNumber" class="form-label">Invoice #</label>
                                    <input type="text" class="form-control" id="invoiceNumber" name="invoice_number" 
                                           placeholder="Search invoice" value="<?= htmlspecialchars($invoice_number) ?>">
                                </div>
                                <div class="col-md-12 d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="ti ti-filter"></i> Apply Filters
                                    </button>
                                    <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                                        <i class="ti ti-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Total Sales Summary -->
                        <div class="alert alert-info mb-3">
                            <strong>Total Sales:</strong> $<?= number_format($total_sales, 2) ?>
                        </div>

                        <div class="table-responsive">
                            <table id="sales-table" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Tax</th>
                                        <th>Discount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['invoice_number']) . "</td>";
                                            echo "<td>" . date('M d, Y h:i A', strtotime($row['date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['customer_name'] ?: 'Walk-in Customer') . "</td>";
                                            echo "<td>$" . number_format($row['total'], 2) . "</td>";
                                            echo "<td>$" . number_format($row['tax'], 2) . "</td>";
                                            echo "<td>$" . number_format($row['discount'], 2) . "</td>";
                                            echo "<td>" . htmlspecialchars(ucfirst($row['payment_method'])) . "</td>";
                                            echo "<td><span class='badge " . getStatusBadgeClass($row['payment_status']) . "'>" . ucfirst($row['payment_status']) . "</span></td>";
                                            echo "<td>
                                                    <div class='btn-group' role='group'>
                                                        <a href='view_sale_mange.php?id=" . $row['id'] . "' class='btn btn-info btn-sm' title='View'><i class='ti ti-eye'></i></a>
                                                        <a href='edit_sale.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm' title='Edit'><i class='ti ti-edit'></i></a>
                                                        <a href='print_invoice.php?id=" . $row['id'] . "' class='btn btn-secondary btn-sm' title='Print'><i class='ti ti-printer'></i></a>
                                                        <a href='delete_sale.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this sale?\")'><i class='ti ti-trash'></i></a>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No sales found</td></tr>";
                                    }

                                    // Helper function for status badge styling
                                    function getStatusBadgeClass($status) {
                                        switch ($status) {
                                            case 'paid': return 'bg-success';
                                            case 'pending': return 'bg-warning';
                                            case 'partial': return 'bg-info';
                                            case 'refunded': return 'bg-danger';
                                            default: return 'bg-secondary';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Disable end date until start date is selected
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    // Initialize end date state
    if (!startDate.value) {
        endDate.disabled = true;
    } 
    
    startDate.addEventListener('change', function() {
        if (this.value) {
            endDate.disabled = false;
            endDate.min = this.value;
        } else {
            endDate.disabled = true;
            endDate.value = '';
        }
    });
    
    // Reset form handler - redirect to same page without query string
    document.getElementById('resetFilters').addEventListener('click', function() {
        window.location.href = 'mange_sale.php';
    });
}); 
</script>
<!-- Additional CSS -->
<style>
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
    }
    .form-label {
        font-weight: 500;
    }
    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
</style>