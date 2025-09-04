[file name]: invoice.php
[file content]
<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Get sale ID from URL
$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;

if (!$sale_id) {
    die("Invalid sale ID");
}

// Fetch sale details
$sale_query = "SELECT s.*, u.username, c.name AS customer_name 
               FROM sales s
               LEFT JOIN customers c ON s.customer_id = c.customer_id
               JOIN users u ON s.user_id = u.user_id
               WHERE s.sale_id = $sale_id";
$sale_result = mysqli_query($conn, $sale_query);
$sale = mysqli_fetch_assoc($sale_result);

if (!$sale) {
    die("Sale not found");
}

// Fetch sale items
$items_query = "SELECT si.*, p.name AS product_name 
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                WHERE si.sale_id = $sale_id";
$items_result = mysqli_query($conn, $items_query);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Sale #<?php echo $sale_id; ?></title>
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #555;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .invoice-logo {
            max-width: 150px;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .invoice-table th {
            background-color: #f9f9f9;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals-section {
            margin-left: auto;
            width: 300px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <img src="../assets/images/logo.png" alt="Nisai Fashion Store" class="invoice-logo">
            <h1>Nisai Fashion Store</h1>
            <p>123 Fashion Street, City Center</p>
            <p>Phone: (123) 456-7890 | Email: info@nisai.com</p>
        </div>
        
        <div class="invoice-details">
            <div>
                <h3>Bill To:</h3>
                <p>
                    <?php echo $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'Walk-in Customer'; ?><br>
                </p>
            </div>
            <div>
                <h3>Invoice Details</h3>
                <p>Invoice #: <?php echo $sale_id; ?></p>
                <p>Date: <?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></p>
                <p>Cashier: <?php echo htmlspecialchars($sale['username']); ?></p>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-right">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals-section">
            <div class="totals-row">
                <strong>Subtotal:</strong>
                <span>$<?php echo number_format($sale['total_amount'] - $sale['tax'] + $sale['discount'], 2); ?></span>
            </div>
            <div class="totals-row">
                <strong>Tax (10%):</strong>
                <span>$<?php echo number_format($sale['tax'], 2); ?></span>
            </div>
            <div class="totals-row">
                <strong>Discount:</strong>
                <span>($<?php echo number_format($sale['discount'], 2); ?>)</span>
            </div>
            <div class="totals-row" style="border-top: 2px solid #333; padding-top: 10px; font-size: 1.2em;">
                <strong>Grand Total:</strong>
                <strong>$<?php echo number_format($sale['total_amount'], 2); ?></strong>
            </div>
            <div class="totals-row">
                <strong>Payment Method:</strong>
                <span><?php echo htmlspecialchars(ucfirst($sale['payment_method'])); ?></span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>Nisai Fashion Store</p>
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()">Print Invoice</button>
            <button onclick="window.location.href='sales.php'">Back to Sales</button>
        </div>
    </div>
</body>
</html> 