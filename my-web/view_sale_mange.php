<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');

// Get sale ID from URL
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sale_id <= 0) {
    $_SESSION['message'] = "Invalid sale ID";
    $_SESSION['message_type'] = "error";
    header("Location: manage_sales.php");
    exit();
}

// Fetch sale details
$sale_query = "SELECT s.*, c.customer_name, c.email, c.mobile_phone, c.address 
               FROM sales s
               LEFT JOIN customers c ON s.customer_id = c.id
               WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $sale_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$sale_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($sale_result) === 0) {
    $_SESSION['message'] = "Sale not found";
    $_SESSION['message_type'] = "error";
    header("Location: manage_sales.php");
    exit();
}

$sale = mysqli_fetch_assoc($sale_result);

// Fetch sale items
$items_query = "SELECT si.*, p.name as product_name 
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                WHERE si.sale_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Sale Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="manage_sales.php">Sales</a></li>
                    <li class="breadcrumb-item" aria-current="page">View Sale</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Invoice #<?= htmlspecialchars($sale['invoice_number']) ?></h5>
                        <div class="float-end">
                            <a href="print_invoice.php?id=<?= $sale_id ?>" class="btn btn-secondary btn-sm" target="_blank">
                                <i class="ti ti-printer"></i> Print Invoice
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = 1;
                                    while ($item = mysqli_fetch_assoc($items_result)) {
                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>";
                                        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                                        echo "<td>$" . number_format($item['price'], 2) . "</td>";
                                        echo "<td>" . $item['quantity'] . "</td>";
                                        echo "<td>$" . number_format($item['total'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Subtotal:</th>
                                        <th>$<?= number_format($sale['total'] - $sale['tax'] + $sale['discount'], 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Tax (10%):</th>
                                        <th>$<?= number_format($sale['tax'], 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Discount:</th>
                                        <th>$<?= number_format($sale['discount'], 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Total:</th>
                                        <th>$<?= number_format($sale['total'], 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Sale Information</h5>
                    </div>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th width="30%">Date:</th>
                        <td><?= date('M d, Y h:i A', strtotime($sale['date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Customer:</th>
                        <td>
                            <?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?>
                            <?php if ($sale['customer_name']): ?>
                                <br><?= htmlspecialchars($sale['email']) ?>
                                <br><?= htmlspecialchars($sale['mobile_phone']) ?>
                                <br><?= htmlspecialchars($sale['address']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Payment Method:</th>
                        <td><?= ucfirst($sale['payment_method']) ?></td>
                    </tr>
                    <tr>
                        <th>Payment Status:</th>
                        <td>
                            <span class="badge <?= getStatusBadgeClass($sale['payment_status']) ?>">
                                <?= ucfirst($sale['payment_status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php if (!empty($sale['notes'])): ?>
                    <tr>
                        <th>Notes:</th>
                        <td><?= htmlspecialchars($sale['notes']) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="mt-3">
                <a href="manage_sale.php" class="btn btn-secondary">Back to Sales</a>
                <a href="edit_sale.php?id=<?= $sale_id ?>" class="btn btn-primary">Edit Sale</a>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'paid': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'partial': return 'bg-info';
        case 'refunded': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

include('include/footer.php'); 
?>