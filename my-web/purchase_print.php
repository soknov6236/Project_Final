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

// Calculate grand total
$grand_total = 0;
$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $subtotal = $item['quantity'] * $item['unit_price'];
    $grand_total += $subtotal;
    $items[] = $item;
}

// Set header for print layout
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/logo_report_icon.png">
    <title>Purchase Order #<?php echo $purchase_id; ?></title>`

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-box {
            flex: 1;
            padding: 10px;
        }
        .info-box:first-child {
            margin-right: 20px;
        }
        .info-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="header">
            <div class="company-name">Nisai Fashion Store````</div>
            <div class="document-title">PURCHASE ORDER</div>
            <div>PO #<?php echo $purchase_id; ?></div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <div class="info-label">Supplier Information</div>
                <div><?php echo htmlspecialchars($purchase['supplier_name']); ?></div>
                <div>Purchase Date: <?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></div>
            </div>
            <div class="info-box">
                    <h1>Nisai Fashion Store</h1>
                <p>Samraong Commune<br>
                Prey Chhor District<br>
                Kampong Cham Province <br>
                Phone: (+855) 097 564 9532<br>
                Email: info@nisai.com</p>
            </div>
        </div>

        <table>
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
                <?php foreach ($items as $index => $item): 
                    $subtotal = $item['quantity'] * $item['unit_price'];
                ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">Grand Total:</td>
                    <td class="text-right"><?php echo number_format($grand_total, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <div class="signature">
                Prepared By: <?php echo htmlspecialchars($purchase['created_by_name'] ?? 'System'); ?>
            </div>
            <div class="signature">
                Supplier Signature
            </div>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" style="padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;">
                Print Document
            </button>
            <button onclick="window.close()" style="padding: 8px 15px; background: #f44336; color: white; border: none; cursor: pointer;">
                Close Window
            </button>
        </div>
    </div>

    <script>
        // Automatically trigger print dialog when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>