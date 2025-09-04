<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Check if purchase ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid purchase ID";
    header("Location: purchase_list.php");
    exit();
}

$purchase_id = intval($_GET['id']);

// Fetch purchase header information
$purchase_query = "SELECT p.*, s.name AS supplier_name, u.username AS created_by_name
                   FROM purchases p
                   JOIN supplier s ON p.supplier_id = s.supplier_id
                   LEFT JOIN users u ON p.created_by = u.id
                   WHERE p.purchase_id = ?";
$stmt = mysqli_prepare($conn, $purchase_query);
mysqli_stmt_bind_param($stmt, 'i', $purchase_id);
mysqli_stmt_execute($stmt);
$purchase_result = mysqli_stmt_get_result($stmt);
$purchase = mysqli_fetch_assoc($purchase_result);

if (!$purchase) {
    $_SESSION['error_message'] = "Purchase not found";
    header("Location: purchase_list.php");
    exit();
}

// Fetch purchase items
$items_query = "SELECT pi.*, pr.product_code, pr.name AS product_name
                FROM purchase_items pi
                JOIN products pr ON pi.product_id = pr.product_id
                WHERE pi.purchase_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $purchase_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Purchase Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="purchase_list.php">Purchases</a></li>
                    <li class="breadcrumb-item" aria-current="page">Purchase Details</li>
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
                        <h5>Purchase #<?php echo $purchase['purchase_id']; ?></h5>
                        <div>
                            <a href="purchase_print.php?id=<?php echo $purchase_id; ?>" class="btn btn-outline-secondary me-2" target="_blank">
                                <i class="ti ti-printer"></i> Print
                            </a>
                            <a href="purchase_list.php" class="btn btn-outline-primary">
                                <i class="ti ti-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Purchase Header Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Supplier</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Purchase Date</label>
                                    <p class="form-control-static"><?php echo date('M d, Y h:i A', strtotime($purchase['purchase_date'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Created By</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($purchase['created_by_name'] ?? 'System'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($purchase['notes'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Items -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $item_count = 1;
                                    $grand_total = 0;
                                    while ($item = mysqli_fetch_assoc($items_result)) {
                                        $subtotal = $item['quantity'] * $item['unit_price'];
                                        $grand_total += $subtotal;
                                        echo "<tr>
                                                <td>{$item_count}</td>
                                                <td>{$item['product_code']}</td>
                                                <td>{$item['product_name']}</td>
                                                <td>{$item['quantity']}</td>
                                                <td>" . number_format($item['unit_price'], 2) . "</td>
                                                <td>" . number_format($subtotal, 2) . "</td>
                                            </tr>";
                                        $item_count++;
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Grand Total:</strong></td>
                                        <td><strong><?php echo number_format($grand_total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>