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
    die("Invalid sale ID");
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
    die("Sale not found");
}

$sale = mysqli_fetch_assoc($sale_result);

// Fetch sale items with product codes, color, and size
$items_query = "SELECT si.*, p.name as product_name, p.product_code, p.color, p.size 
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                WHERE si.sale_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $sale_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

// Store logo path (adjust as needed)
$logo_path = "../assets/images/logo_report_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($sale['invoice_number']) ?></title>
    <link rel="icon" href="../assets/images/logo_report_icon.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 18px;
        }
        .logo {
            max-width: 130px;
            max-height: 65px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .date {
            color: #777;
        }
        .from-to {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .from, .to {
            width: 48%;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            text-align: left;
            padding: 8px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            width: 100%;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .paid {
            background-color: #d4edda;
            color: #155724;
        }
        .pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
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
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <?php if (file_exists($logo_path)): ?>
                    <img src="<?= $logo_path ?>" alt="Company Logo" class="logo">
                <?php else: ?>
                    <h1>Nisai Fashion Store</h1>
                <?php endif; ?>
                <p>Samraong Commune<br>
                Prey Chhor District<br>
                Kampong Cham Province <br>
                Phone: (+855) 097 564 9532<br>
                Email: info@nisai.com</p>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#<?= htmlspecialchars($sale['invoice_number']) ?></div>
                <div class="date">Date: <?= date('M d, Y', strtotime($sale['date'])) ?></div>
                <div class="status <?= $sale['payment_status'] ?>">
                    <?= ucfirst($sale['payment_status']) ?>
                </div>
            </div>
        </div>

        <div class="from-to">
            <div class="from">
                <div class="section-title">From:</div>
                <p><strong>Nisai Fashion Store</strong><br>
                123 Business Street<br>
                City, State 10001<br>
                Phone: (+855) 097 564 9532</p>
            </div>
            <div class="to">
                <div class="section-title">To:</div>
                <p>
                    <strong><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?></strong><br>
                    <?php if (!empty($sale['email'])): ?>
                        <?= htmlspecialchars($sale['email']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($sale['mobile_phone'])): ?>
                        Phone: <?= htmlspecialchars($sale['mobile_phone']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($sale['address'])): ?>
                        <?= htmlspecialchars($sale['address']) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Code</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                while ($item = mysqli_fetch_assoc($items_result)) {
                    echo "<tr>";
                    echo "<td>" . $counter++ . "</td>";
                    echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['product_code']) . "</td>";
                    echo "<td>" . (!empty($item['color']) ? htmlspecialchars($item['color']) : 'N/A') . "</td>";
                    echo "<td>" . (!empty($item['size']) ? htmlspecialchars($item['size']) : 'N/A') . "</td>";
                    echo "<td class='text-right'>$" . number_format($item['price'], 2) . "</td>";
                    echo "<td class='text-right'>" . $item['quantity'] . "</td>";
                    echo "<td class='text-right'>$" . number_format($item['total'], 2) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">$<?= number_format($sale['total'] - $sale['tax'] + $sale['discount'], 2) ?></td>
                </tr>
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">$<?= number_format($sale['tax'], 2) ?></td>
                </tr>
                <?php if ($sale['discount'] > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">$<?= number_format($sale['discount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td class="text-right"><strong>$<?= number_format($sale['total'], 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Payment Method:</td>
                    <td class="text-right"><?= ucfirst($sale['payment_method']) ?></td>
                </tr>

            </table>
            <div>
                    <img src="../assets/images/QRCode.jpg" alt="QR Code" class="img-fluid" style="max-height: 80px;">
                </div>
        </div>

        <?php if (!empty($sale['notes'])): ?>
        <div class="notes">
            <div class="section-title">Notes</div>
            <p><?= htmlspecialchars($sale['notes']) ?></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Thank you <strong><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?></strong><br></p>
            <p>If you have any questions about this invoice, please contact<br>
            our customer service at nisai.pashion@gmail.com or (+855) 88-22-56-288</p>
            <button onclick="window.print()" class="no-print" style="padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Invoice</button>
        </div>
    </div>
</body>
</html>