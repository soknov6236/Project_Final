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
    header("Location: manage_sale.php");
    exit();
}

// Fetch sale details
$sale_query = "SELECT * FROM sales WHERE id = ?";
$stmt = mysqli_prepare($conn, $sale_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$sale_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($sale_result) === 0) {
    $_SESSION['message'] = "Sale not found";
    $_SESSION['message_type'] = "error";
    header("Location: manage_sale.php");
    exit();
}

$sale = mysqli_fetch_assoc($sale_result);

// Fetch all customers
$customers_query = "SELECT id, customer_name FROM customers ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : 'paid';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    $update_query = "UPDATE sales SET 
                    customer_id = ?, 
                    payment_method = ?, 
                    payment_status = ?, 
                    notes = ? 
                    WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, 'isssi', $customer_id, $payment_method, $payment_status, $notes, $sale_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Sale updated successfully";
        $_SESSION['message_type'] = "success";
        header("Location: manage_sale.php?id=" . $sale_id);
        exit();
    } else {
        $_SESSION['message'] = "Error updating sale: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
    }
}

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Edit Sale</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="manage_sales.php">Sales</a></li>
                    <li class="breadcrumb-item"><a href="view_sale.php?id=<?= $sale_id ?>">View Sale</a></li>
                    <li class="breadcrumb-item" aria-current="page">Edit Sale</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Sale #<?= htmlspecialchars($sale['invoice_number']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="edit_sale.php?id=<?= $sale_id ?>">
                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-control">
                                    <option value="0">Walk-in Customer</option>
                                    <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                        <option value="<?= $customer['id'] ?>" <?= $sale['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($customer['customer_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="cash" <?= $sale['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="credit" <?= $sale['payment_method'] === 'credit' ? 'selected' : '' ?>>Credit</option>
                                    <option value="debit" <?= $sale['payment_method'] === 'debit' ? 'selected' : '' ?>>Debit</option>
                                    <option value="transfer" <?= $sale['payment_method'] === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                                    <option value="other" <?= $sale['payment_method'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-control">
                                    <option value="paid" <?= $sale['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="pending" <?= $sale['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="partial" <?= $sale['payment_status'] === 'partial' ? 'selected' : '' ?>>Partial</option>
                                    <option value="refunded" <?= $sale['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($sale['notes'] ?? '') ?></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Sale</button>
                                <a href="manage_sale.php?id=<?= $sale_id ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Sale Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Invoice #</th>
                                    <td><?= htmlspecialchars($sale['invoice_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td><?= date('M d, Y h:i A', strtotime($sale['date'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td>$<?= number_format($sale['total'], 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>$<?= number_format($sale['tax'], 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td>$<?= number_format($sale['discount'], 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?> 