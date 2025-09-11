<?php
ob_start(); // Start output buffering
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Build the base query
$query = "SELECT r.*, c.customer_name, s.invoice_number 
          FROM returns r
          LEFT JOIN customers c ON r.customer_id = c.id
          LEFT JOIN sales s ON r.sale_id = s.id
          WHERE 1=1";

// Add filters to the query
$params = [];
if (!empty($start_date)) {
    $query .= " AND r.return_date >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND r.return_date <= ?";
    $params[] = $end_date . ' 23:59:59';
}
if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}
if ($customer_id > 0) {
    $query .= " AND r.customer_id = ?";
    $params[] = $customer_id;
}

$query .= " ORDER BY r.return_date DESC";

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
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Returns Management</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Inventory</a></li>
                    <li class="breadcrumb-item" aria-current="page">Returns</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Return Records</h5>
                        <a href="add_new_returns.php" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> New Return
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Filter Form -->
                        <form id="returnsFilterForm" method="GET" class="mb-4">
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
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="customerSelect" class="form-label">Customer</label>
                                    <select class="form-select" id="customerSelect" name="customer_id">
                                        <option value="0">All Customers</option>
                                        <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                            <option value="<?= $customer['id'] ?>" 
                                                <?= $customer_id == $customer['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table id="returns-table" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Date</th>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['return_id']) . "</td>";
                                            echo "<td>" . date('M d, Y', strtotime($row['return_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['invoice_number']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                            echo "<td>$" . number_format($row['total_amount'], 2) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                            echo "<td><span class='badge " . getStatusBadgeClass($row['status']) . "'>" . ucfirst($row['status']) . "</span></td>";
                                            echo "<td>
                                                    <div class='btn-group' role='group'>
                                                        <a href='view_return.php?id=" . $row['return_id'] . "' class='btn btn-info btn-sm' title='View'><i class='ti ti-eye'></i></a>
                                                        <a href='edit_return.php?id=" . $row['return_id'] . "' class='btn btn-warning btn-sm' title='Edit'><i class='ti ti-edit'></i></a>
                                                        <a href='delete_return.php?id=" . $row['return_id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this return?\")'><i class='ti ti-trash'></i></a>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No returns found</td></tr>";
                                    }

                                    function getStatusBadgeClass($status) {
                                        switch ($status) {
                                            case 'approved': return 'bg-success';
                                            case 'pending': return 'bg-warning';
                                            case 'rejected': return 'bg-danger';
                                            case 'completed': return 'bg-info';
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

<!-- DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#returns-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Disable end date until start date is selected
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
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
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeTo(1000, 0).slideUp(1000, function(){
            $(this).alert('close');
        });
    }, 5000);
});  
</script>

<?php 
ob_end_flush(); // End output buffering and flush
include('include/footer.php'); 
?>