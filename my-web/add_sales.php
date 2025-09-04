<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Fetch products and customers
$products = [];
$customers = [];
$product_query = "SELECT product_id, product_code, name, sale_price, stock_quantity, image FROM products WHERE stock_quantity > 0";
$customer_query = "SELECT id, customer_name FROM customers";

$product_result = mysqli_query($conn, $product_query);
$customer_result = mysqli_query($conn, $customer_query);

while ($row = mysqli_fetch_assoc($product_result)) {
    $products[] = $row;
}
while ($row = mysqli_fetch_assoc($customer_result)) {
    $customers[] = $row;
}

// Process sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    try {
        mysqli_begin_transaction($conn);
        
        // Generate invoice
        $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Handle customer
        $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : NULL;
        $customer_name = 'Walk-in Customer';
        
        if ($customer_id) {
            $stmt = mysqli_prepare($conn, "SELECT customer_name FROM customers WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $customer = mysqli_fetch_assoc($result);
                $customer_name = $customer['customer_name'];
            } else {
                $customer_id = NULL;
            }
        }
        
        // Calculate totals
        $subtotal = floatval($_POST['subtotal']);
        $tax_rate = 0.10;
        $tax_amount = $subtotal * $tax_rate;
        $discount = floatval($_POST['discount'] ?? 0);
        $total = $subtotal + $tax_amount - $discount;
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $payment_status = ($payment_method == 'cash') ? 'paid' : 'pending';
        $created_by = $_SESSION['user_id'] ?? NULL;

        // Insert sale
        $query = "INSERT INTO sales (
                    invoice_number, date, customer_id, customer_name, 
                    subtotal, tax, discount, total, 
                    payment_method, payment_status, created_by
                  ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param(
            $stmt, 
            'sisddddssi', 
            $invoice_number, 
            $customer_id, 
            $customer_name,
            $subtotal, 
            $tax_amount, 
            $discount, 
            $total,
            $payment_method, 
            $payment_status, 
            $created_by
        );
        mysqli_stmt_execute($stmt);
        $sale_id = mysqli_insert_id($conn);
        
        // Process items
        foreach ($_POST['product_id'] as $key => $product_id) {
            $product_id = intval($product_id);
            $quantity = intval($_POST['quantity'][$key]);
            $price = floatval($_POST['price'][$key]);
            $subtotal = $quantity * $price;
            
            // Insert item
            $query = "INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal)
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'iiidd', $sale_id, $product_id, $quantity, $price, $subtotal);
            mysqli_stmt_execute($stmt);
            
            // Update stock
            $query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ii', $quantity, $product_id);
            mysqli_stmt_execute($stmt);
            
            // Log inventory change
            $query = "INSERT INTO inventory_log (
                        product_id, quantity_change, previous_quantity, 
                        new_quantity, action_type, reference_id, 
                        notes, created_by
                      ) VALUES (?, ?, (SELECT stock_quantity FROM products WHERE product_id = ?), 
                      (SELECT stock_quantity - ? FROM products WHERE product_id = ?), 
                      'sale', ?, 'POS Sale', ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'iiiiisi', 
                $product_id, -$quantity, $product_id, 
                $quantity, $product_id, $sale_id, $created_by);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Sale completed successfully! Invoice #: $invoice_number";
        header("Location: sales.php");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Sale Error: " . $e->getMessage();
        error_log("POS Error: " . $e->getMessage());
    }
}
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Point of Sale</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Sales</a></li>
                    <li class="breadcrumb-item">POS</li>
                </ul>
            </div>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Products</h5>
                        <div class="input-group mb-3">
                            <input type="text" id="product-search" class="form-control" placeholder="Search products...">
                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="product-grid">
                            <?php foreach ($products as $product): 
                                $imagePath = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/default-product.png';
                            ?>
                                <div class="col-md-3 mb-3 product-item" 
                                     data-id="<?php echo $product['product_id']; ?>"
                                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                     data-price="<?php echo $product['sale_price']; ?>"
                                     data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <div class="card h-100 product-card">
                                        <div class="card-body text-center">
                                            <img src="<?php echo $imagePath; ?>" class="img-fluid mb-2" style="max-height:100px;object-fit:contain;">
                                            <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <p class="text-muted"><?php echo htmlspecialchars($product['product_code']); ?></p>
                                            <p class="fw-bold">$<?php echo number_format($product['sale_price'], 2); ?></p>
                                            <p class="text-success">Stock: <?php echo $product['stock_quantity']; ?></p>
                                            <button class="btn btn-sm btn-primary add-to-cart">Add to Cart</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="pos-form">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>">
                                            <?php echo htmlspecialchars($customer['customer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table class="table" id="cart-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items">
                                        <!-- Cart items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><span id="subtotal">$0.00</span></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tax (10%):</strong></td>
                                            <td><span id="tax">$0.00</span></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="discount" name="discount" value="0.00">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><span id="total">$0.00</span></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="payment_method" id="cash" value="cash" checked>
                                    <label class="btn btn-outline-primary" for="cash">Cash</label>
                                    
                                    <input type="radio" class="btn-check" name="payment_method" id="card" value="card">
                                    <label class="btn btn-outline-primary" for="card">Card</label>
                                    
                                    <input type="radio" class="btn-check" name="payment_method" id="transfer" value="transfer">
                                    <label class="btn btn-outline-primary" for="transfer">Transfer</label>
                                </div>
                            </div>
                            
                            <button type="submit" name="checkout" class="btn btn-success w-100 py-3">
                                <i class="ti ti-check"></i> Complete Sale
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let cart = [];
    
    // Product search
    $('#product-search').on('input', function() {
        const term = $(this).val().toLowerCase();
        $('.product-item').each(function() {
            const name = $(this).data('name').toLowerCase();
            const code = $(this).find('.text-muted').text().toLowerCase();
            $(this).toggle(name.includes(term) || code.includes(term));
        });
    });
    
    // Add to cart
    $(document).on('click', '.add-to-cart', function() {
        const card = $(this).closest('.product-item');
        const product = {
            id: card.data('id'),
            name: card.data('name'),
            price: card.data('price'),
            stock: card.data('stock'),
            quantity: 1
        };
        
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            if (existing.quantity < product.stock) existing.quantity++;
            else alert("Cannot exceed available stock");
        } else {
            cart.push(product);
        }
        
        updateCart();
    });
    
    // Remove from cart
    $(document).on('click', '.remove-cart-item', function() {
        const id = parseInt($(this).data('id'));
        cart = cart.filter(item => item.id !== id);
        updateCart();
    });
    
    // Update quantity
    $(document).on('change', '.cart-quantity', function() {
        const id = parseInt($(this).data('id'));
        const quantity = parseInt($(this).val());
        const stock = parseInt($(this).data('stock'));
        
        if (quantity < 1) $(this).val(1);
        else if (quantity > stock) {
            alert("Cannot exceed available stock");
            $(this).val(stock);
        } else {
            cart.find(item => item.id === id).quantity = quantity;
            updateCart();
        }
    });
    
    // Update price
    $(document).on('change', '.cart-price', function() {
        const id = parseInt($(this).data('id'));
        const price = parseFloat($(this).val());
        
        if (price <= 0) {
            $(this).val(0.01);
            return;
        }
        
        const item = cart.find(item => item.id === id);
        if (item) {
            item.price = price;
            updateCart();
        }
    });
    
    // Update discount
    $('#discount').on('change', updateTotals) ;
    
    // Update cart display
    function updateCart() {
        const tbody = $('#cart-items');
        tbody.empty();
        
        // Clear existing hidden inputs
        $('#pos-form').find('input[name^="product_id"]').remove();
        $('#pos-form').find('input[name^="quantity"]').remove();
        $('#pos-form').find('input[name^="price"]').remove();
        
        cart.forEach(item => {
            const subtotal = item.price * item.quantity;
            
            tbody.append(`
                <tr>
                    <td>${item.name}</td>
                    <td>
                        <input type="number" min="1" max="${item.stock}" 
                               class="form-control form-control-sm cart-quantity" 
                               data-id="${item.id}" data-stock="${item.stock}"
                               value="${item.quantity}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" 
                               class="form-control form-control-sm cart-price" 
                               data-id="${item.id}"
                               value="${item.price.toFixed(2)}">
                    </td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-cart-item" data-id="${item.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            
            // Add hidden inputs for form submission
            $('#pos-form').append(`<input type="hidden" name="product_id[]" value="${item.id}">`);
            $('#pos-form').append(`<input type="hidden" name="quantity[]" value="${item.quantity}">`);
            $('#pos-form').append(`<input type="hidden" name="price[]" value="${item.price}">`);
        });
        
        updateTotals();
    }
    
    // Calculate totals
    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.10;
        const discount = parseFloat($('#discount').val()) || 0;
        const total = subtotal + tax - discount;
        
        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#tax').text('$' + tax.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
        
        // Add hidden input for subtotal
        $('#pos-form').find('input[name="subtotal"]').remove();
        $('#pos-form').append(`<input type="hidden" name="subtotal" value="${subtotal}">`);
    }
    
    // Initialize empty cart
    updateCart();
});
</script>

<?php include('include/footer.php'); ?>