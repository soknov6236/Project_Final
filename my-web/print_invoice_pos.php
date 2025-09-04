<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Get sale ID from URL
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$sale_id) {
    die("Invalid sale ID");
}

// Fetch sale details
$sale_query = "SELECT s.*, c.customer_name, c.email, c.phone, c.address 
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id 
               WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $sale_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$sale_result = mysqli_stmt_get_result($stmt);
$sale = mysqli_fetch_assoc($sale_result);

if (!$sale) {
    die("Sale not found");
}

// Fetch sale items
$items_query = "SELECT si.*, p.name, p.description 
                FROM sale_items si 
                JOIN products p ON si.product_id = p.product_id 
                WHERE si.sale_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = [];
$subtotal = 0;

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
    $subtotal += $item['total'];
}

// Calculate totals
$tax = $sale['tax'];
$discount = $sale['discount'];
$total = $sale['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $sale['invoice_number']; ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        
        .store-info h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        
        .store-details {
            color: #7f8c8d;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin: 0;
        }
        
        .invoice-meta {
            margin-top: 10px;
            font-size: 14px;
        }
        
        .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .billing-address {
            flex: 1;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-weight: bold;
            border-bottom: 2px solid #eee;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .totals-label {
            font-weight: bold;
        }
        
        .total-row {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .paid {
            background: #2ecc71;
            color: white;
        }
        
        .pending {
            background: #f39c12;
            color: white;
        }
        
        .partial {
            background: #3498db;
            color: white;
        }
        
        .refunded {
            background: #e74c3c;
            color: white;
        }
        
        .notes {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="store-info">
                <h1>Nisai Fashion Store</h1>
                <div class="store-details">
                    <div>123 Fashion Street, Style City</div>
                    <div>Phone: (123) 456-7890 | Email: info@nisai-fashion.com</div>
                    <div>www.nisaifashion.com</div>
                </div>
            </div>
            <div class="invoice-info">
                <h2 class="invoice-title">INVOICE</h2>
                <div class="invoice-meta">
                    <div><strong>Invoice #:</strong> <?php echo $sale['invoice_number']; ?></div>
                    <div><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($sale['date'])); ?></div>
                    <div>
                        <strong>Status:</strong> 
                        <span class="status-badge <?php echo $sale['payment_status']; ?>">
                            <?php echo ucfirst($sale['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="customer-info">
            <div class="billing-address">
                <div class="section-title">Billed To:</div>
                <div><strong><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></strong></div>
                <?php if ($sale['email']): ?>
                    <div><?php echo $sale['email']; ?></div>
                <?php endif; ?>
                <?php if ($sale['phone']): ?>
                    <div><?php echo $sale['phone']; ?></div>
                <?php endif; ?>
                <?php if ($sale['address']): ?>
                    <div><?php echo $sale['address']; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?: 'N/A'); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <div class="totals-row">
                <div class="totals-label">Subtotal:</div>
                <div>$<?php echo number_format($subtotal, 2); ?></div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Tax (10%):</div>
                <div>$<?php echo number_format($tax, 2); ?></div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Discount:</div>
                <div>-$<?php echo number_format($discount, 2); ?></div>
            </div>
            <div class="totals-row total-row">
                <div class="totals-label">Total:</div>
                <div>$<?php echo number_format($total, 2); ?></div>
            </div>
        </div>
        
        <div class="notes">
            <div class="section-title">Payment Method</div>
            <p><strong><?php echo ucfirst($sale['payment_method']); ?></strong></p>
            
            <div class="section-title">Notes</div>
            <p>Thank you for your business! All sales are final.</p>
        </div>
        
        <div class="footer">
            <div>If you have any questions about this invoice, please contact</div>
            <div><strong>support@nisaifashion.com</strong> or call <strong>(123) 456-7890</strong></div>
            <div style="margin-top: 10px;">This is a computer-generated invoice. No signature is required.</div>
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" style="
                background: #3498db;
                color: white;
                border: none;
                padding: 12px 24px;
                font-size: 16px;
                border-radius: 4px;
                cursor: pointer;
            ">
                Print Invoice
            </button> 
        </div>
    </div>
</body>
</html> 