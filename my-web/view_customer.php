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

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    header("Location: customers.php");
    exit();
}

// Fetch customer details
$customer_query = "SELECT * FROM customers WHERE id = ?";
$customer_stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($customer_stmt, "i", $customer_id);
mysqli_stmt_execute($customer_stmt);
$customer_result = mysqli_stmt_get_result($customer_stmt);
$customer = mysqli_fetch_assoc($customer_result);

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Fetch customer's purchase history
$purchase_query = "SELECT 
                    s.id as sale_id,
                    s.invoice_number,
                    s.date,
                    s.total,
                    s.payment_status,
                    s.payment_method,
                    si.product_name,
                    si.quantity,
                    si.price,
                    (si.quantity * si.price) as item_total
                  FROM sales s
                  JOIN sale_items si ON s.id = si.sale_id
                  WHERE s.customer_id = ?
                  ORDER BY s.date DESC";
$purchase_stmt = mysqli_prepare($conn, $purchase_query);
mysqli_stmt_bind_param($purchase_stmt, "i", $customer_id);
mysqli_stmt_execute($purchase_stmt);
$purchase_result = mysqli_stmt_get_result($purchase_stmt);

// Calculate total spent
$total_spent_query = "SELECT SUM(total) as total_spent FROM sales WHERE customer_id = ? AND payment_status != 'refunded'";
$total_stmt = mysqli_prepare($conn, $total_spent_query);
mysqli_stmt_bind_param($total_stmt, "i", $customer_id);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_row = mysqli_fetch_assoc($total_result);
$total_spent = $total_row['total_spent'] ?? 0;

// Count total orders
$order_count_query = "SELECT COUNT(*) as order_count FROM sales WHERE customer_id = ?";
$count_stmt = mysqli_prepare($conn, $order_count_query);
mysqli_stmt_bind_param($count_stmt, "i", $customer_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$order_count = $count_row['order_count'] ?? 0;
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Customer Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
                    <li class="breadcrumb-item" aria-current="page">Customer Details</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Customer Information</h5>
                        <div>
                            <a href="edit_customer.php?id=<?= $customer_id ?>" class="btn btn-warning btn-sm">
                                <i class="ti ti-edit me-1"></i> Edit Customer
                            </a>
                            <a href="customers.php" class="btn btn-secondary btn-sm">
                                <i class="ti ti-arrow-left me-1"></i> Back to Customers
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Customer Name</label>
                                    <p class="form-control-static"><?= htmlspecialchars($customer['customer_name']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <p class="form-control-static"><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Phone</label>
                                    <p class="form-control-static"><?= htmlspecialchars($customer['mobile_phone'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Address</label>
                                    <p class="form-control-static"><?= htmlspecialchars($customer['address'] ?? 'N/A') ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Total Orders</label>
                                    <p class="form-control-static"><?= $order_count ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Total Amount Spent</label>
                                    <p class="form-control-static">$<?= number_format($total_spent, 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Purchase History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($purchase_result) > 0): ?>
                        <div class="table-responsive">
                            <table id="purchase-history-table" class="table table-striped table-bordered nowrap">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($purchase = mysqli_fetch_assoc($purchase_result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($purchase['invoice_number']) ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($purchase['date'])) ?></td>
                                        <td><?= htmlspecialchars($purchase['product_name']) ?></td>
                                        <td><?= $purchase['quantity'] ?></td>
                                        <td>$<?= number_format($purchase['price'], 2) ?></td>
                                        <td>$<?= number_format($purchase['item_total'], 2) ?></td>
                                        <td><?= ucfirst($purchase['payment_method']) ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($purchase['payment_status']) {
                                                    case 'paid': echo 'bg-success'; break;
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'partial': echo 'bg-info'; break;
                                                    case 'refunded': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                            ?>">
                                                <?= ucfirst($purchase['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_sale_customer.php?id=<?= $purchase['sale_id'] ?>" class="btn btn-info btn-sm" title="View Sale">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info text-center">
                            This customer hasn't made any purchases yet.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable for purchase history
    $('#purchase-history-table').DataTable({
        responsive: true,
        order: [[1, 'desc']], // Sort by date descending
        columnDefs: [
            { responsivePriority: 1, targets: 0 }, // Invoice #
            { responsivePriority: 2, targets: -1 }, // Actions
            { responsivePriority: 3, targets: 2 }  // Product name
        ]
    });
});
</script>

<?php include('include/footer.php'); ?>