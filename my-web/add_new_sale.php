<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Check for messages
if (isset($_SESSION['message'])) {
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
    echo '<div class="alert alert-'.$message_type.' alert-dismissible fade show" role="alert">
            '.htmlspecialchars($_SESSION['message']).'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch customers for dropdown
$customers_query = "SELECT id, customer_name FROM customers ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">New Sale</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Sales</a></li>
                    <li class="breadcrumb-item" aria-current="page">New Sale</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Create New Sale</h5>
                    </div>
                    <div class="card-body">
                        <form id="newSaleForm" method="POST" action="process_add_sale.php">
                            <!-- Customer Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="customerSelect" class="form-label">Customer</label>
                                    <select class="form-select" id="customerSelect" name="customer_id">
                                        <option value="0">Walk-in Customer</option>
                                        <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                            <option value="<?= $customer['id'] ?>">
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentMethod" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod" name="payment_method">
                                        <option value="cash">Cash</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="online">Online Payment</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Product Search Section -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="productSearch" 
                                               placeholder="Search by product code or name...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Start typing to search products</div>
                                </div>
                            </div>

                            <!-- Loading Indicator -->
                            <div class="row mb-3" id="loadingIndicator" style="display: none;">
                                <div class="col-md-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Searching products...</span>
                                </div>
                            </div>

                            <!-- Product Search Results -->
                            <div class="row mb-4" id="productResults" style="display: none;">
                                <div class="col-md-12">
                                    <h6 class="mb-3">Search Results</h6>
                                    <div class="row" id="productsContainer">
                                        <!-- Products will be loaded here via AJAX -->
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6 class="mb-3">Sale Items</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="saleItemsTable">
                                            <thead>
                                                <tr>
                                                    <th width="80">Image</th>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th width="120">Quantity</th>
                                                    <th>Total</th>
                                                    <th width="50">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cartItems">
                                                <!-- Cart items will be added here -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                                    <td colspan="2" class="fw-bold">$<span id="subtotal">0.00</span></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Tax (10%)</td>
                                                    <td colspan="2" class="fw-bold">$<span id="tax">0.00</span></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Discount</td>
                                                    <td colspan="2">
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" class="form-control" id="discount" 
                                                                   name="discount" value="0.00" min="0" step="0.01">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Grand Total</td>
                                                    <td colspan="2" class="fw-bold">$<span id="grandTotal">0.00</span></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="row">
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" id="cancelSale">
                                        <i class="ti ti-x me-1"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti ti-check me-1"></i> Complete Sale
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Cart array to store items
    let cart = [];
    let searchTimeout;
    const searchDelay = 300; // milliseconds
    
    // Auto-search functionality
    $('#productSearch').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide results if search is empty
        if (searchTerm === '') {
            $('#productResults').hide();
            return;
        }
        
        // Show loading indicator
        $('#loadingIndicator').show();
        $('#productResults').hide();
        
        // Set new timeout for search
        searchTimeout = setTimeout(() => {
            searchProducts(searchTerm);
        }, searchDelay);
    });
    
    // Clear search button
    $('#clearSearchBtn').click(function() {
        $('#productSearch').val('');
        $('#productResults').hide();
    });
    
    // Search products function
    function searchProducts(searchTerm) {
        if (searchTerm.length < 2) {
            $('#loadingIndicator').hide();
            return;
        }
        
        $.ajax({
            url: 'search_products.php',
            type: 'GET',
            data: { term: searchTerm },
            dataType: 'json',
            success: function(products) {
                $('#loadingIndicator').hide();
                
                if (products.length > 0) {
                    let html = '';
                    products.forEach(product => {
                        html += `
                        <div class="col-md-3 mb-3">
                            <div class="card product-card" data-id="${product.product_id}" 
                                 data-name="${product.product_name}" data-price="${product.selling_price}" 
                                 data-stock="${product.stock}" data-image="${product.image}">
                                <div class="card-body text-center">
                                    ${product.image ? 
                                        `<img src="uploads/products/${product.image}" alt="${product.product_name}" 
                                            class="img-fluid mb-2" style="max-height: 120px;">` : 
                                        `<div class="bg-light text-center py-4 mb-2">
                                            <i class="ti ti-photo" style="font-size: 3rem;"></i>
                                        </div>`
                                    }
                                    <h6 class="card-title">${product.product_name}</h6>
                                    <p class="card-text mb-1">$${parseFloat(product.selling_price).toFixed(2)}</p>
                                    <p class="card-text text-muted small mb-2">Code: ${product.product_code}</p>
                                    <p class="card-text small ${product.stock > 0 ? 'text-success' : 'text-danger'}">
                                        ${product.stock > 0 ? `In Stock: ${product.stock}` : 'Out of Stock'}
                                    </p>
                                    <button type="button" class="btn btn-sm btn-primary add-to-cart" 
                                        ${product.stock < 1 ? 'disabled' : ''}>
                                        <i class="ti ti-shopping-cart-plus me-1"></i> Add to Sale
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    });
                    
                    $('#productsContainer').html(html);
                    $('#productResults').show();
                } else {
                    $('#productsContainer').html('<div class="col-md-12"><div class="alert alert-info">No products found</div></div>');
                    $('#productResults').show();
                }
            },
            error: function() {
                $('#loadingIndicator').hide();
                $('#productsContainer').html('<div class="col-md-12"><div class="alert alert-danger">Error searching products</div></div>');
                $('#productResults').show();
            }
        });
    }
    
    // Add product to cart
    $(document).on('click', '.add-to-cart', function() {
        const productCard = $(this).closest('.product-card');
        const productId = productCard.data('id');
        const productName = productCard.data('name');
        const price = parseFloat(productCard.data('price'));
        const stock = parseInt(productCard.data('stock'));
        const image = productCard.data('image');
        
        // Check if product already in cart
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            if (existingItem.quantity < stock) {
                existingItem.quantity += 1;
            } else {
                alert('Cannot add more than available stock');
            }
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: price,
                quantity: 1,
                stock: stock,
                image: image  // Store image path in cart item
            });
        }
        
        updateCartDisplay();
    });
    
    // Update quantity in cart
    $(document).on('change', '.item-quantity', function() {
        const id = $(this).data('id');
        let quantity = parseInt($(this).val());
        const maxStock = parseInt($(this).data('stock'));
        
        if (isNaN(quantity)) {
            $(this).val(1);
            quantity = 1;
        }
        
        if (quantity < 1) {
            $(this).val(1);
            quantity = 1;
        }
        
        if (quantity > maxStock) {
            alert(`Only ${maxStock} available in stock`);
            $(this).val(maxStock);
            quantity = maxStock;
        }
        
        const item = cart.find(item => item.id === id);
        if (item) {
            item.quantity = quantity;
            updateCartDisplay();
        }
    });
    
    // Remove item from cart
    $(document).on('click', '.remove-item', function() {
        const id = $(this).data('id');
        cart = cart.filter(item => item.id !== id);
        updateCartDisplay();
    });
    
    // Update discount and totals
    $('#discount').on('input', updateTotals);
    
    // Cancel sale
    $('#cancelSale').click(function() {
        if (confirm('Are you sure you want to cancel this sale?')) {
            window.location.href = 'sales.php';
        }
    });
    
    // Update cart display
    function updateCartDisplay() {
        let html = '';
        let subtotal = 0;
        
        if (cart.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No items added</td></tr>';
        } else {
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                <tr>
                    <td class="text-center">
                        ${item.image ? 
                            `<img src="uploads/products/${item.image}" alt="${item.name}" 
                                  class="img-thumbnail" style="max-width: 60px; max-height: 60px;">` : 
                            `<div class="bg-light d-flex align-items-center justify-content-center" 
                                  style="width: 60px; height: 60px;">
                                <i class="ti ti-photo"></i>
                             </div>`
                        }
                    </td>
                    <td>${item.name}</td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control item-quantity" 
                               min="1" max="${item.stock}" value="${item.quantity}"
                               data-id="${item.id}" data-stock="${item.stock}">
                    </td>
                    <td>$${itemTotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        }
        
        $('#cartItems').html(html);
        $('#subtotal').text(subtotal.toFixed(2));
        updateTotals();
    }
    
    // Update totals
    function updateTotals() {
        const subtotal = parseFloat($('#subtotal').text()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;
        
        // Calculate tax (10% of subtotal)
        const tax = subtotal * 0.1;
        
        const grandTotal = subtotal + tax - discount;
        
        $('#tax').text(tax.toFixed(2));
        $('#grandTotal').text(grandTotal.toFixed(2));
    }
    
    // Form submission
    $('#newSaleForm').on('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('Please add at least one product to the sale');
            return;
        }
        
        // Add cart items to form as hidden inputs
        cart.forEach((item, index) => {
            $(this).append(`<input type="hidden" name="items[${index}][id]" value="${item.id}">`);
            $(this).append(`<input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">`);
        });
    });
});
</script>

<style>
.product-card {
    cursor: pointer;
    transition: transform 0.2s;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#saleItemsTable tbody tr td {
    vertical-align: middle;
}

.item-quantity {
    width: 70px;
}

#clearSearchBtn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

#saleItemsTable tbody tr td:first-child {
    text-align: center;
    width: 80px;
}

#saleItemsTable tbody tr td:last-child {
    text-align: center;
    width: 50px;
}

#saleItemsTable tbody tr td:nth-child(3),
#saleItemsTable tbody tr td:nth-child(5) {
    text-align: right;
}
</style>

<?php include('include/footer.php'); ?>