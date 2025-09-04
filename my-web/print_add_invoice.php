<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$sale_id) {
    die("Invalid sale id");
}

// Fetch sale
$query = "SELECT s.*, c.customer_name 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sale = mysqli_fetch_assoc($result);

if (!$sale) {
    die("Sale not found");
}

// Fetch items
$query = "SELECT * FROM sale_items WHERE sale_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate subtotal from items
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $sale['invoice_number'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table td, table th { border: 1px solid #eee; padding: 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .hidden-print { display: none; }
        @media print {
            .no-print { display: none; }
            body { font-size: 12px; }
            .invoice-box { border: none; padding: 10px; }
        }
    </style>
    <script>
    window.onload = function() {
        // Print invoice immediately
        window.print();
        
        // Redirect to sales page after 3 seconds
        setTimeout(function() {
            window.location.href = 'sales.php';
        }, 3000);
    };
    </script>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div>
                <h2>Invoice #<?= $sale['invoice_number'] ?></h2>
                <p>Date: <?= date('M d, Y H:i', strtotime($sale['date'])) ?></p>
            </div>
            <div>
                <h3>Your Store Name</h3>
                <p>123 Business Street, City, Country</p>
                <p>Phone: (123) 456-7890</p>
            </div>
        </div>

        <div class="customer">
            <p><strong>Customer:</strong> <?= $sale['customer_name'] ? $sale['customer_name'] : 'Walk-in Customer' ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= $item['product_name'] ?></td>
                        <td class="text-right">$<?= number_format($item['price'], 2) ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">Subtotal</td>
                    <td class="text-right">$<?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Tax (10%)</td>
                    <td class="text-right">$<?= number_format($sale['tax'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Discount</td>
                    <td class="text-right">$<?= number_format($sale['discount'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right"><strong>Grand Total</strong></td>
                    <td class="text-right"><strong>$<?= number_format($sale['total'], 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="payment-method mt-4">
            <p><strong>Payment Method:</strong> <?= ucfirst($sale['payment_method']) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($sale['payment_status']) ?></p>
        </div>

        <div class="note mt-4 text-center">
            <p>Thank you for your business!</p>
            <p>This is an auto-generated invoice. No signature required.</p>
        </div>
        
        <div class="no-print text-center mt-4">
            <p>You will be redirected to Sales page in 3 seconds...</p>
            <p><a href="sales.php" class="btn btn-primary">Go to Sales Now</a></p>
        </div>
    </div>
</body>
</html>