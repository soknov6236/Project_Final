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

// Check for success message
if (isset($_SESSION['message'])) {
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
    echo '<div class="alert alert-'.$message_type.' alert-dismissible fade show" role="alert">
            '.htmlspecialchars($_SESSION['message']).'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
// Display success message
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}

// Display error message
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error_message']);
}

?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Sale List</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Sale</a></li>
                    <li class="breadcrumb-item" aria-current="page">Sale List</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>All Sales</h5>
                        <div>
                            <a href="add_new_sale.php" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> New Sale
                            </a>
                            <a href="sale_pos.php" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> Sale POS
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="sales-table" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Products</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Improved query to get sales with product information
                                    $query = "SELECT s.*, c.customer_name, 
                                              GROUP_CONCAT(DISTINCT CONCAT(p.product_code, ' (', si.quantity, ')') SEPARATOR ', ') as product_info
                                              FROM sales s
                                              LEFT JOIN customers c ON s.customer_id = c.id
                                              LEFT JOIN sale_items si ON s.id = si.sale_id
                                              LEFT JOIN products p ON si.product_id = p.product_id
                                              GROUP BY s.id
                                              ORDER BY s.date DESC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['invoice_number']) . "</td>";
                                            echo "<td>" . date('M d, Y h:i A', strtotime($row['date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['customer_name'] ?: 'Walk-in Customer') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['product_info'] ?: 'N/A') . "</td>";
                                            echo "<td>$" . number_format($row['total'], 2) . "</td>";
                                            echo "<td>" . htmlspecialchars(ucfirst($row['payment_method'])) . "</td>";
                                            echo "<td><span class='badge " . getStatusBadgeClass($row['payment_status']) . "'>" . ucfirst($row['payment_status']) . "</span></td>";
                                            echo "<td>
                                                    <div class='btn-group' role='group'>
                                                        <a href='view_sale_mange.php?id=" . $row['id'] . "' class='btn btn-info btn-sm' title='View'><i class='ti ti-eye'></i></a>
                                                        <a href='edit_sale.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm' title='Edit'><i class='ti ti-edit'></i></a>
                                                        <a href='print_invoice_sale.php?id=" . $row['id'] . "' class='btn btn-secondary btn-sm' title='Print' target='_blank'><i class='ti ti-printer'></i></a>
                                                        <a href='delete_sales.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this sale?\")'><i class='ti ti-trash'></i></a>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No sales found</td></tr>";
                                    }

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

<!-- DataTables Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

<!-- DataTables Buttons JS -->
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


<!-- Additional JavaScript for DataTables -->
<script>
$(document).ready(function() {
    // Initialize DataTable for sales table
    $('#sales-table').DataTable({
        responsive: true,
        dom: '<"top"Bf>rt<"bottom"lip><"clear">',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copy',
                    'excel',
                    'csv',
                    'pdf',
                    'print'
                ]
            }
        ],
        columnDefs: [
            { responsivePriority: 1, targets: 0 }, // Invoice #
            { responsivePriority: 2, targets: -1 }, // Actions
            { responsivePriority: 3, targets: 2 }, // Customer
            { responsivePriority: 4, targets: 3 }, // Products
            { 
                targets: [3, 4, 5, 6], // Products, Total, Payment, Status
                render: function(data, type, row) {
                    if (type === 'display' && data.length > 50) {
                        return data.substr(0, 50) + '...';
                    }
                    return data;
                }
            }
        ],
        language: {
            search: "Search sales:",
            paginate: {
                previous: "<i class='ti ti-chevron-left'></i>",
                next: "<i class='ti ti-chevron-right'></i>"
            }
        }
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
    #sales-table td {
        vertical-align: middle;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 4px;
        padding: 4px 8px;
        border: 1px solid #ddd;
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 4px;
        padding: 4px 8px;
        border: 1px solid #ddd;
    }
    .page-item.active .page-link {
        background-color: #7367f0;
        border-color: #7367f0;
    }
</style>

<?php include('include/footer.php'); ?>