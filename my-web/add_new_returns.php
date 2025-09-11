<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Initialize variables
$sale_id = $return_date = $reason = $status = '';
$return_items = [];
$error_message = '';

// Get all sales for dropdown
$sales_query = "SELECT s.id, s.invoice_number, s.date, s.total, c.customer_name 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                ORDER BY s.date DESC";
$sales_result = mysqli_query($conn, $sales_query);

// If sale is selected, get sale items
if (isset($_GET['sale_id']) && !empty($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    
    // Get customer_id from the sale
    $sale_info_query = "SELECT customer_id FROM sales WHERE id = $sale_id";
    $sale_info_result = mysqli_query($conn, $sale_info_query);
    $sale_info = mysqli_fetch_assoc($sale_info_result);
    $customer_id = $sale_info['customer_id'];
    
    $items_query = "SELECT si.*, p.product_code, p.name as product_name 
                    FROM sale_items si 
                    JOIN products p ON si.product_id = p.product_id 
                    WHERE si.sale_id = $sale_id";
    $items_result = mysqli_query($conn, $items_query);
    
    // Initialize return items with quantity 0
    while ($item = mysqli_fetch_assoc($items_result)) {
        $return_items[] = [
            'product_id' => $item['product_id'],
            'product_code' => $item['product_code'],
            'product_name' => $item['product_name'],
            'quantity' => 0,
            'price' => $item['price'],
            'max_quantity' => $item['quantity'] // Maximum returnable is the quantity sold
        ];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sale_id = intval($_POST['sale_id']);
    $return_date = $_POST['return_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $status = 'pending'; // Default status
    
    // Get customer_id from the sale
    $sale_info_query = "SELECT customer_id FROM sales WHERE id = $sale_id";
    $sale_info_result = mysqli_query($conn, $sale_info_query);
    $sale_info = mysqli_fetch_assoc($sale_info_result);
    $customer_id = $sale_info['customer_id'];
    
    // Calculate total amount from return items
    $total_amount = 0;
    $return_items_data = [];
    
    if (isset($_POST['return_items'])) {
        foreach ($_POST['return_items'] as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            
            if ($quantity > 0) {
                $total_amount += $quantity * $price;
                $return_items_data[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $price
                ];
            }
        }
    }
    
    // Validate data
    if ($sale_id <= 0 || $total_amount <= 0) {
        $error_message = "Please select a valid sale and add at least one item to return.";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert return record
            $insert_return = "INSERT INTO returns (sale_id, customer_id, return_date, total_amount, reason, status) 
                              VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_return);
            mysqli_stmt_bind_param($stmt, "iisdss", $sale_id, $customer_id, $return_date, $total_amount, $reason, $status);
            mysqli_stmt_execute($stmt);
            $return_id = mysqli_insert_id($conn);
            
            // Insert return items - FIXED: Using correct column names
            if (!empty($return_items_data)) {
                foreach ($return_items_data as $item) {
                    // Check if the table uses 'unit_price' or 'price' column
                    $check_column = "SHOW COLUMNS FROM return_items LIKE 'unit_price'";
                    $column_result = mysqli_query($conn, $check_column);
                    
                    if (mysqli_num_rows($column_result) > 0) {
                        // Table has unit_price column
                        $insert_item = "INSERT INTO return_items (return_id, product_id, quantity, unit_price) 
                                        VALUES (?, ?, ?, ?)";
                    } else {
                        // Table has price column
                        $insert_item = "INSERT INTO return_items (return_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)";
                    }
                    
                    $stmt_item = mysqli_prepare($conn, $insert_item);
                    mysqli_stmt_bind_param($stmt_item, "iiid", $return_id, $item['product_id'], $item['quantity'], $item['price']);
                    mysqli_stmt_execute($stmt_item);
                    
                    // Update product stock
                    $update_stock = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
                    $stmt_stock = mysqli_prepare($conn, $update_stock);
                    mysqli_stmt_bind_param($stmt_stock, "ii", $item['quantity'], $item['product_id']);
                    mysqli_stmt_execute($stmt_stock);
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Set success message and redirect
            $_SESSION['success_message'] = "Return record added successfully!";
            header("Location: returns.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error_message = "Error processing return: " . $e->getMessage();
        }
    }
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Add New Return</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="returns.php">Returns</a></li>
                    <li class="breadcrumb-item" aria-current="page">Add New Return</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Return Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ti ti-alert-circle me-2"></i>
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="add_new_returns.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="saleSelect" class="form-label">Sale Invoice</label>
                                        <select class="form-select" id="saleSelect" name="sale_id" required 
                                                onchange="if(this.value) window.location.href='add_new_returns.php?sale_id='+this.value">
                                            <option value="">Select Sale Invoice</option>
                                            <?php while ($sale = mysqli_fetch_assoc($sales_result)): ?>
                                                <option value="<?= $sale['id'] ?>" 
                                                    <?= $sale_id == $sale['id'] ? 'selected' : '' ?>>
                                                    #<?= $sale['invoice_number'] ?> - 
                                                    <?= date('M d, Y', strtotime($sale['date'])) ?> - 
                                                    <?= $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'Walk-in Customer' ?> - 
                                                    $<?= number_format($sale['total'], 2) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <?php if ($sale_id): ?>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="returnDate" class="form-label">Return Date</label>
                                        <input type="date" class="form-control" id="returnDate" name="return_date" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($sale_id && !empty($return_items)): ?>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="mb-3">Return Items</h6>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Max Qty</th>
                                                    <th>Return Qty</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $total_amount = 0;
                                                foreach ($return_items as $index => $item): 
                                                    $subtotal = $item['quantity'] * $item['price'];
                                                    $total_amount += $subtotal;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($item['product_name']) ?>
                                                        <small class="text-muted d-block"><?= $item['product_code'] ?></small>
                                                        <input type="hidden" name="return_items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                                                        <input type="hidden" name="return_items[<?= $index ?>][price]" value="<?= $item['price'] ?>">
                                                    </td>
                                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                                    <td><?= $item['max_quantity'] ?></td>
                                                    <td>
                                                        <input type="number" 
                                                               name="return_items[<?= $index ?>][quantity]" 
                                                               class="form-control return-qty" 
                                                               min="0" 
                                                               max="<?= $item['max_quantity'] ?>" 
                                                               value="<?= $item['quantity'] ?>" 
                                                               data-price="<?= $item['price'] ?>"
                                                               onchange="calculateSubtotal(this)">
                                                    </td>
                                                    <td class="subtotal">$<?= number_format($subtotal, 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                                    <td><strong id="total-amount">$<?= number_format($total_amount, 2) ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason for Return</label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Submit Return</button>
                                <a href="returns.php" class="btn btn-secondary">Cancel</a>
                            </div>
                            <?php elseif ($sale_id && empty($return_items)): ?>
                            <div class="alert alert-warning mt-3">
                                No items found for this sale or all items have already been returned.
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateSubtotal(input) {
    const row = input.closest('tr');
    const price = parseFloat(input.getAttribute('data-price'));
    const quantity = parseInt(input.value) || 0;
    const maxQty = parseInt(input.getAttribute('max'));
    
    // Validate quantity doesn't exceed maximum
    if (quantity > maxQty) {
        alert(`Cannot return more than ${maxQty} units of this product.`);
        input.value = maxQty;
        quantity = maxQty;
    }
    
    const subtotal = price * quantity;
    row.querySelector('.subtotal').textContent = '$' + subtotal.toFixed(2);
    
    // Update total amount
    updateTotalAmount();
}

function updateTotalAmount() {
    let total = 0;
    document.querySelectorAll('.subtotal').forEach(cell => {
        total += parseFloat(cell.textContent.replace('$', ''));
    });
    document.getElementById('total-amount').textContent = '$' + total.toFixed(2);
}

// Set today's date as default if not already set
document.addEventListener('DOMContentLoaded', function() {
    const returnDate = document.getElementById('returnDate');
    if (returnDate && !returnDate.value) {
        returnDate.value = new Date().toISOString().split('T')[0];
    }
});
</script>

<?php 
include('include/footer.php'); 
?>