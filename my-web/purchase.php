<?php
ob_start(); // Start output buffering
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        mysqli_begin_transaction($conn);
        
        // Insert purchase record
        $supplier_id = intval($_POST['supplier_id']);
        $purchase_date = date('Y-m-d H:i:s');
        $total_amount = 0;
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        $query = "INSERT INTO purchases (supplier_id, purchase_date, total_amount, notes, created_by) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        $created_by = $_SESSION['user_id'] ?? null;
        mysqli_stmt_bind_param($stmt, 'isdii', $supplier_id, $purchase_date, $total_amount, $notes, $created_by);
        mysqli_stmt_execute($stmt);
        $purchase_id = mysqli_insert_id($conn);
        
        // Process purchase items
        $total_amount = 0;
        foreach ($_POST['product_id'] as $key => $product_id) {
            $product_id = intval($product_id);
            $quantity = intval($_POST['quantity'][$key]);
            $unit_price = floatval($_POST['unit_price'][$key]);
            
            if ($quantity > 0 && $unit_price > 0) {
                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;
                
                // Insert purchase item
                $query = "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, subtotal)
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iiidd', $purchase_id, $product_id, $quantity, $unit_price, $subtotal);
                mysqli_stmt_execute($stmt);
                
                // Update product stock
                $query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ii', $quantity, $product_id);
                mysqli_stmt_execute($stmt);
            }
        }
        
        // Update purchase total amount
        $query = "UPDATE purchases SET total_amount = ? WHERE purchase_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'di', $total_amount, $purchase_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Purchase recorded successfully!";
        // Clear output buffer and redirect
        ob_end_clean();
        header("Location: purchase_list.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error processing purchase: " . $e->getMessage();
    }
}

// Fetch suppliers and products for dropdowns
$suppliers = [];
$products = [];
$supplier_query = "SELECT supplier_id, name FROM supplier WHERE status = 'active'";
$product_query = "SELECT product_id, product_code, name FROM products";

$supplier_result = mysqli_query($conn, $supplier_query);
$product_result = mysqli_query($conn, $product_query);

if ($supplier_result) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $row;
    }
}
if ($product_result) {
    while ($row = mysqli_fetch_assoc($product_result)) {
        $products[] = $row;
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
                    <h5 class="mb-0 font-medium">Purchase Management</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Inventory</a></li>
                    <li class="breadcrumb-item" aria-current="page">Purchase</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

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
                        <h5>New Purchase</h5>
                        <a href="purchase_list.php" class="btn btn-outline-primary">View Purchase History</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="purchase-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">Supplier</label>
                                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?php echo $supplier['supplier_id']; ?>">
                                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table" id="purchase-items">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="purchase-item">
                                            <td>
                                                <select class="form-select product-select" name="product_id[]" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['cost_price'] ?? 0; ?>">
                                                            <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity" name="quantity[]" min="1" value="1" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control unit-price" name="unit_price[]" min="0.01" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control subtotal" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger remove-item">Remove</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td>
                                                <input type="text" class="form-control" id="total-amount" readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <button type="button" class="btn btn-primary" id="add-item">Add Item</button>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-success">Save Purchase</button>
                                <a href="purchase_list.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add new item row
    $('#add-item').click(function() {
        const newRow = $('.purchase-item:first').clone();
        newRow.find('input').val('');
        newRow.find('.product-select').val('');
        newRow.find('.quantity').val('1');
        newRow.find('.unit-price').val('');
        newRow.find('.subtotal').val('');
        $('#purchase-items tbody').append(newRow);
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        if ($('.purchase-item').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('You must have at least one item');
        }
    });

    // Auto-fill unit price when product is selected
    $(document).on('change', '.product-select', function() {
        const selectedOption = $(this).find('option:selected');
        const unitPrice = selectedOption.data('price') || 0;
        $(this).closest('tr').find('.unit-price').val(unitPrice);
        
        // Calculate subtotal
        const quantity = parseFloat($(this).closest('tr').find('.quantity').val()) || 0;
        const subtotal = quantity * unitPrice;
        $(this).closest('tr').find('.subtotal').val(subtotal.toFixed(2));
        calculateTotal();
    });

    // Calculate subtotal and total
    $(document).on('input', '.quantity, .unit-price', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const subtotal = quantity * unitPrice;
        row.find('.subtotal').val(subtotal.toFixed(2));
        calculateTotal();
    });

    function calculateTotal() {
        let total = 0;
        $('.purchase-item').each(function() {
            const subtotal = parseFloat($(this).find('.subtotal').val()) || 0;
            total += subtotal;
        });
        $('#total-amount').val(total.toFixed(2));
    }
    
    // Form validation
    $('#purchase-form').on('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Check if at least one item has been added
        if ($('.purchase-item').length === 0) {
            isValid = false;
            errorMessage = 'Please add at least one product to the purchase.';
        }
        
        // Check if all required fields are filled
        $('.purchase-item').each(function(index) {
            const product = $(this).find('.product-select').val();
            const quantity = $(this).find('.quantity').val();
            const unitPrice = $(this).find('.unit-price').val();
            
            if (!product || !quantity || !unitPrice) {
                isValid = false;
                errorMessage = 'Please fill in all required fields for all items.';
                return false; // Break out of the loop
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
});
</script>

<?php 
ob_end_flush(); // End output buffering and flush
include('include/footer.php'); 
?>