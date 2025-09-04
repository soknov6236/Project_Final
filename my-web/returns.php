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

// Handle return status change
if (isset($_GET['change_status']) && isset($_GET['id'])) {
    $return_id = intval($_GET['id']);
    $new_status = $_GET['change_status'];
    
    $valid_statuses = ['pending', 'approved', 'rejected', 'processed'];
    if (in_array($new_status, $valid_statuses)) {
        mysqli_begin_transaction($conn);
        
        try {
            // Update return status
            $query = "UPDATE returns SET status = ? WHERE return_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'si', $new_status, $return_id);
            mysqli_stmt_execute($stmt);
            
            // If status is approved, update product stock and inventory history
            if ($new_status == 'approved') {
                // Get return items
                $items_query = "SELECT product_id, quantity FROM return_items WHERE return_id = ?";
                $stmt = mysqli_prepare($conn, $items_query);
                mysqli_stmt_bind_param($stmt, 'i', $return_id);
                mysqli_stmt_execute($stmt);
                $items_result = mysqli_stmt_get_result($stmt);
                
                while ($item = mysqli_fetch_assoc($items_result)) {
                    // Update product stock
                    $update_query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, 'ii', $item['quantity'], $item['product_id']);
                    mysqli_stmt_execute($stmt);
                    
                    // Record in inventory history
                    $history_query = "INSERT INTO inventory_history 
                                    (product_id, action, quantity, user_name, notes, reference_id) 
                                    VALUES (?, 'in', ?, ?, ?, ?)";
                    $notes = "Return approved for return ID: $return_id";
                    $stmt = mysqli_prepare($conn, $history_query);
                    mysqli_stmt_bind_param($stmt, 'iissi', $item['product_id'], $item['quantity'], 
                                         $_SESSION['username'], $notes, $return_id);
                    mysqli_stmt_execute($stmt);
                }
                
                // Create refund record if not exists
                $check_refund = "SELECT refund_id FROM refunds WHERE return_id = ?";
                $stmt = mysqli_prepare($conn, $check_refund);
                mysqli_stmt_bind_param($stmt, 'i', $return_id);
                mysqli_stmt_execute($stmt);
                $refund_exists = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($refund_exists) == 0) {
                    $get_return = "SELECT refund_amount, payment_method FROM returns WHERE return_id = ?";
                    $stmt = mysqli_prepare($conn, $get_return);
                    mysqli_stmt_bind_param($stmt, 'i', $return_id);
                    mysqli_stmt_execute($stmt);
                    $return_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    
                    $refund_query = "INSERT INTO refunds 
                                    (return_id, amount, payment_method, processed_by, notes)
                                    VALUES (?, ?, ?, ?, ?)";
                    $refund_notes = "Refund for return #$return_id";
                    $stmt = mysqli_prepare($conn, $refund_query);
                    mysqli_stmt_bind_param($stmt, 'idsss', $return_id, $return_data['refund_amount'], 
                                     $return_data['payment_method'], $_SESSION['username'], $refund_notes);
                    mysqli_stmt_execute($stmt);
                }
            }
            
            mysqli_commit($conn);
            $_SESSION['success_message'] = "Return status updated successfully";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Error updating return status: " . $e->getMessage();
        }
        
        header("Location: returns.php");
        exit();
    }
}

// Fetch all returns with additional information
$query = "SELECT r.*, 
                 u.username AS processed_by,
                 COUNT(ri.return_item_id) AS item_count,
                 SUM(ri.subtotal) AS total_amount,
                 rf.amount AS refund_amount
          FROM returns r
          LEFT JOIN users u ON r.created_by = u.id
          LEFT JOIN return_items ri ON r.return_id = ri.return_id
          LEFT JOIN refunds rf ON r.return_id = rf.return_id
          GROUP BY r.return_id
          ORDER BY r.return_date DESC";
$result = mysqli_query($conn, $query);
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Return Management</h5>
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
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

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
                        <h5>Return List</h5>
                        <div>
                            <a href="add_new_returns.php" class="btn btn-primary">
                                <i class="ti ti-plus"></i> New Return
                            </a>
                            <button class="btn btn-outline-secondary" id="export-btn">
                                <i class="ti ti-download"></i> Export
                            </button>
                            <a href="returns_report.php" class="btn btn-info">
                                <i class="ti ti-report"></i> Reports
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="returns-table">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Date</th>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Refund</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($return = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $return['return_id']; ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($return['return_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($return['invoice_number']); ?></td>
                                            <td><?php echo htmlspecialchars($return['customer_name']); ?></td>
                                            <td><?php echo $return['item_count']; ?></td>
                                            <td><?php echo number_format($return['total_amount'], 2); ?></td>
                                            <td><?php echo isset($return['refund_amount']) ? number_format($return['refund_amount'], 2) : 'N/A'; ?></td>
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
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_return.php?id=<?php echo $return['return_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($return['status'] != 'approved'): ?>
                                                        <li><a class="dropdown-item" href="returns.php?change_status=approved&id=<?php echo $return['return_id']; ?>">
                                                            <i class="ti ti-check"></i> Approve
                                                        </a></li>
                                                        <?php endif; ?>
                                                        <?php if ($return['status'] != 'rejected'): ?>
                                                        <li><a class="dropdown-item" href="returns.php?change_status=rejected&id=<?php echo $return['return_id']; ?>">
                                                            <i class="ti ti-x"></i> Reject
                                                        </a></li>
                                                        <?php endif; ?>
                                                        <?php if ($return['status'] != 'processed'): ?>
                                                        <li><a class="dropdown-item" href="returns.php?change_status=processed&id=<?php echo $return['return_id']; ?>">
                                                            <i class="ti ti-checkbox"></i> Mark as Processed
                                                        </a></li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="delete_return.php?id=<?php echo $return['return_id']; ?>" onclick="return confirm('Are you sure you want to delete this return?');">
                                                            <i class="ti ti-trash"></i> Delete
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </td>
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

<!-- Include DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#returns-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[0, 'desc']],
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search returns...",
            lengthMenu: "Show _MENU_ entries per page",
            zeroRecords: "No matching returns found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)"
        }
    }); 
    
    // Export button handler
    $('#export-btn').click(function() {
        $('#returns-table').DataTable().button('excel').trigger();
    });
});
</script>

<?php include('include/footer.php'); ?>