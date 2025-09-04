<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid return ID";
    header("Location: returns.php");
    exit();
}

$return_id = intval($_GET['id']);

// Fetch return details
$return_query = "SELECT r.*, u.username AS processed_by_name
                 FROM returns r
                 LEFT JOIN users u ON r.created_by = u.id
                 WHERE r.return_id = ?";
$stmt = mysqli_prepare($conn, $return_query);
mysqli_stmt_bind_param($stmt, 'i', $return_id);
mysqli_stmt_execute($stmt);
$return_result = mysqli_stmt_get_result($stmt);
$return = mysqli_fetch_assoc($return_result);

if (!$return) {
    $_SESSION['error_message'] = "Return not found";
    header("Location: returns.php");
    exit();
}

// Fetch return items
$items_query = "SELECT * FROM return_items WHERE return_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $return_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Return Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="returns.php">Returns</a></li>
                    <li class="breadcrumb-item" aria-current="page">Return #<?php echo $return_id; ?></li>
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Return #<?php echo $return_id; ?></h5>
                        <div>
                            <a href="returns.php" class="btn btn-secondary">Back to Returns</a>
                            <a href="print_return.php?id=<?php echo $return_id; ?>" class="btn btn-primary" target="_blank">Print</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Invoice Number</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($return['invoice_number']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Customer</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($return['customer_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Return Date</label>
                                    <p class="form-control-static"><?php echo date('M d, Y h:i A', strtotime($return['return_date'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <p class="form-control-static">
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
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Refund Method</label>
                                    <p class="form-control-static"><?php echo ucfirst($return['payment_method']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Refund Amount</label>
                                    <p class="form-control-static">$<?php echo number_format($return['refund_amount'], 2); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Return Reason</label>
                            <p class="form-control-static"><?php echo nl2br(htmlspecialchars($return['return_reason'])); ?></p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['reason']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($return['total_amount'], 2); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if (!empty($return['notes'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <p class="form-control-static"><?php echo nl2br(htmlspecialchars($return['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Processed By</label>
                            <p class="form-control-static"><?php echo htmlspecialchars($return['processed_by_name'] ?? 'System'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>