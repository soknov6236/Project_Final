<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Fetch recent sales for dropdown (last 90 days)
$sales_query = "SELECT s.id, s.invoice_number, s.customer_name, s.total, s.date, 
                       s.customer_id
                FROM sales s
                WHERE s.date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                AND s.payment_status = 'paid'
                ORDER BY s.date DESC";
$sales_result = mysqli_query($conn, $sales_query);

// Define payment methods directly (since settings table doesn't exist)
$payment_methods = ['cash', 'credit', 'bank_transfer', 'credit_card'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        mysqli_begin_transaction($conn);
        
        // Insert return record
        $sale_id = intval($_POST['sale_id']);
        $invoice_number = mysqli_real_escape_string($conn, $_POST['invoice_number']);
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $return_reason = mysqli_real_escape_string($conn, $_POST['return_reason']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        $created_by = $_SESSION['user_id'];
        $status = in_array($_POST['status'], ['pending', 'approved', 'rejected']) ? $_POST['status'] : 'pending';
        $return_date = mysqli_real_escape_string($conn, $_POST['return_date']);
        
        $total_amount = 0;
        $refund_amount = 0;
        
        // Insert return header
        $query = "INSERT INTO returns (sale_id, invoice_number, customer_id, customer_name, 
                  return_reason, payment_method, notes, created_by, status, total_amount, 
                  refund_amount, return_date)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'isissssiddss', $sale_id, $invoice_number, $customer_id, 
                             $customer_name, $return_reason, $payment_method, $notes, 
                             $created_by, $status, $total_amount, $refund_amount, $return_date);
        mysqli_stmt_execute($stmt);
        $return_id = mysqli_insert_id($conn);
        
        // Process return items
        $total_amount = 0;
        foreach ($_POST['product_id'] as $key => $product_id) {
            $product_id = intval($product_id);
            $quantity = intval($_POST['quantity'][$key]);
            $unit_price = floatval($_POST['unit_price'][$key]);
            $reason = mysqli_real_escape_string($conn, $_POST['item_reason'][$key]);
            
            if ($quantity > 0) {
                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;
                
                // Get product details
                $product_query = "SELECT product_code, name, stock_quantity FROM products WHERE product_id = ?";
                $stmt = mysqli_prepare($conn, $product_query);
                mysqli_stmt_bind_param($stmt, 'i', $product_id);
                mysqli_stmt_execute($stmt);
                $product_result = mysqli_stmt_get_result($stmt);
                $product = mysqli_fetch_assoc($product_result);
                
                // Insert return item
                $query = "INSERT INTO return_items (return_id, product_id, product_code, product_name, 
                          quantity, unit_price, subtotal, reason)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iissidds', $return_id, $product_id, 
                                     $product['product_code'], $product['name'], 
                                     $quantity, $unit_price, $subtotal, $reason);
                mysqli_stmt_execute($stmt);
                
                // Update product stock if return is approved
                if ($status == 'approved') {
                    $new_stock = $product['stock_quantity'] + $quantity;
                    
                    $update_query = "UPDATE products SET stock_quantity = ? WHERE product_id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, 'ii', $new_stock, $product_id);
                    mysqli_stmt_execute($stmt);
                    
                    // Record in inventory history
                    $log_query = "INSERT INTO inventory_history 
                                (product_id, action, quantity, user_name, notes, reference_id)
                                VALUES (?, 'in', ?, ?, ?, ?)";
                    $notes = "Return #$return_id for invoice #$invoice_number";
                    $stmt = mysqli_prepare($conn, $log_query);
                    mysqli_stmt_bind_param($stmt, 'iissi', $product_id, $quantity, 
                                         $_SESSION['username'], $notes, $return_id);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
        
        // Calculate refund amount
        $refund_amount = $total_amount;
        
        // Update return total amount and status
        $query = "UPDATE returns SET total_amount = ?, refund_amount = ? WHERE return_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ddi', $total_amount, $refund_amount, $return_id);
        mysqli_stmt_execute($stmt);
        
        // If approved, create a refund record
        if ($status == 'approved') {
            $refund_query = "INSERT INTO refunds 
                            (return_id, amount, payment_method, processed_by, notes)
                            VALUES (?, ?, ?, ?, ?)";
            $refund_notes = "Refund for return #$return_id";
            $stmt = mysqli_prepare($conn, $refund_query);
            mysqli_stmt_bind_param($stmt, 'idsss', $return_id, $refund_amount, 
                                 $payment_method, $_SESSION['username'], $refund_notes);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Return processed successfully! Return ID: $return_id";
        header("Location: view_return.php?id=$return_id");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error processing return: " . $e->getMessage();
        header("Location: add_new_returns.php");
        exit();
    }
}

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Process New Return</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="returns.php">Returns</a></li>
                    <li class="breadcrumb-item" aria-current="page">New Return</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

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
                        <h5>New Product Return</h5>
                        <div class="d-flex gap-2">
                            <a href="returns.php" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> Back to Returns
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="return-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sale_id" class="form-label">Select Sale</label>
                                        <select class="form-select" id="sale_id" name="sale_id" required>
                                            <option value="">Select Sale</option>
                                            <?php while ($sale = mysqli_fetch_assoc($sales_result)): ?>
                                                <option value="<?php echo $sale['id']; ?>" 
                                                    data-invoice="<?php echo htmlspecialchars($sale['invoice_number']); ?>"
                                                    data-customer="<?php echo htmlspecialchars($sale['customer_name']); ?>"
                                                    data-customer-id="<?php echo $sale['customer_id']; ?>">
                                                    <?php echo htmlspecialchars($sale['invoice_number'] . ' - ' . $sale['customer_name'] . ' (' . date('M d, Y', strtotime($sale['date'])) . ')'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="invoice_number" class="form-label">Invoice Number</label>
                                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" readonly required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Return Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" readonly required>
                                        <input type="hidden" id="customer_id" name="customer_id">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Refund Method</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <?php foreach ($payment_methods as $method): ?>
                                                <option value="<?php echo htmlspecialchars($method); ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $method)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="return_reason" class="form-label">Return Reason</label>
                                <select class="form-select" id="return_reason" name="return_reason" required>
                                    <option value="">Select Reason</option>
                                    <option value="Defective Product">Defective Product</option>
                                    <option value="Wrong Item Shipped">Wrong Item Shipped</option>
                                    <option value="No Longer Needed">No Longer Needed</option>
                                    <option value="Size/Color Not Suitable">Size/Color Not Suitable</option>
                                    <option value="Customer Changed Mind">Customer Changed Mind</option>
                                    <option value="Other">Other (Specify in Notes)</option>
                                </select>
                            </div>
                            
                            <!-- Product Search Section -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="productSearch" 
                                               placeholder="Search products by name or code...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive mb-4">
                                <table class="table" id="return-items">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="30%">Product</th>
                                            <th width="15%">Available</th>
                                            <th width="15%">Return Qty</th>
                                            <th width="15%">Unit Price</th>
                                            <th width="15%">Subtotal</th>
                                            <th width="20%">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-container">
                                        <!-- Items will be loaded here via AJAX -->
                                        <tr id="no-items">
                                            <td colspan="6" class="text-center text-muted">Select a sale to load items</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total Refund Amount:</strong></td>
                                            <td colspan="3">
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="text" class="form-control" id="total-amount" name="total_amount" readonly>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='returns.php'">
                                    <i class="ti ti-arrow-left"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check"></i> Process Return
                                </button>
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
    // When sale is selected, load its items
    $('#sale_id').change(function() {
        const saleId = $(this).val();
        if (saleId) {
            // Update invoice and customer fields
            const selectedOption = $(this).find('option:selected');
            $('#invoice_number').val(selectedOption.data('invoice'));
            $('#customer_name').val(selectedOption.data('customer'));
            $('#customer_id').val(selectedOption.data('customer-id'));
            
            // Show loading state
            $('#no-items').html('<td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td>');
                        
            $.get('returns/get_sale_items.php', {sale_id: saleId}, function(data) {
                $('#items-container').html(data);
                calculateTotal();
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
                $('#no-items').html('<td colspan="6" class="text-center text-danger">Error loading items: ' + 
                                textStatus + '</td>');
            });
        } else {
            $('#items-container').html('<tr id="no-items"><td colspan="6" class="text-center text-muted">Select a sale to load items</td></tr>');
            $('#invoice_number').val('');
            $('#customer_name').val('');
            $('#customer_id').val('');
            $('#total-amount').val('');
        }
    });
    
    // Product search functionality
    $('#productSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        if (searchTerm.length > 1) {
            $('#items-container tr').each(function() {
                const productName = $(this).find('.product-name').text().toLowerCase();
                const productCode = $(this).find('.product-code').text().toLowerCase();
                if (productName.includes(searchTerm) || productCode.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else {
            $('#items-container tr').show();
        }
    });
    
    // Clear search button
    $('#clearSearchBtn').click(function() {
        $('#productSearch').val('');
        $('#items-container tr').show();
    });
    
    // Calculate total when quantities change
    $(document).on('input', '.return-quantity', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat($(this).val()) || 0;
        const maxQty = parseFloat($(this).data('max')) || 0;
        
        // Validate quantity doesn't exceed original sale quantity
        if (quantity > maxQty) {
            $(this).val(maxQty);
            showToast('warning', 'Return quantity cannot exceed original sale quantity');
            return;
        }
        
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const subtotal = quantity * unitPrice;
        row.find('.subtotal').val(subtotal.toFixed(2));
        calculateTotal();
    });
    
    // Form validation before submission
    $('#return-form').submit(function(e) {
        let hasItems = false;
        $('.return-quantity').each(function() {
            if (parseFloat($(this).val()) > 0) {
                hasItems = true;
                return false; // break loop
            }
        });
        
        if (!hasItems) {
            e.preventDefault();
            showToast('error', 'Please add at least one item to return');
        }
    });
    
    function calculateTotal() {
        let total = 0;
        $('.return-item').each(function() {
            const subtotal = parseFloat($(this).find('.subtotal').val()) || 0;
            total += subtotal;
        });
        $('#total-amount').val(total.toFixed(2));
    }
    
    function showToast(type, message) {
        const toast = `<div class="toast align-items-center text-white bg-${type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                      </div>`;
        
        // Remove any existing toasts
        $('.toast').remove();
        
        // Add new toast
        $('body').append(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => $('.toast').remove(), 5000);
    }
});
</script>

<style>
    #return-items tbody tr {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    #return-items tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .return-quantity {
        width: 70px;
    }
    
    #productSearch {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    #clearSearchBtn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
</style>

<?php include('include/footer.php'); ?>