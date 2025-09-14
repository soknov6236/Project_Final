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
                                    <div class="input-group">
                                        <select class="form-select" id="customerSelect" name="customer_id">
                                            <option value="0">Walk-in Customer</option>
                                            <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                                <option value="<?= $customer['id'] ?>">
                                                    <?= htmlspecialchars($customer['customer_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                            <i class="ti ti-plus"></i> New Customer
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentMethod" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod" name="payment_method">
                                        <option value="cash">Cash</option>
                                        <option value="qr_code">QR Code</option>
                                        <option value="debit_card">Other</option>
                                    </select>
                                    
                                    <!-- QR Code Image (initially hidden) -->
                                    <div id="qrCodeContainer" class="mt-3 text-center" style="display: none;">
                                        <img src="../assets/images/QRCode.jpg" alt="QR Code" class="img-fluid" style="max-height: 100px;">
                                        <p class="small text-muted mt-2">Scan this QR code to complete payment</p>
                                    </div>
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
                                                    <td colspan="4" class="text-end fw-bold">Tax</td>
                                                    <td colspan="2">
                                                        <div class="input-group">
                                                            <span class="input-group-text">%</span>
                                                            <input type="number" class="form-control" id="taxRate" 
                                                                   name="tax_rate" value="0.1" min="0" step="0.01">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" class="form-control" id="taxAmount" 
                                                                   name="tax_amount" value="0.00" readonly>
                                                        </div>
                                                    </td>
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

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-labelledby="newCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="newCustomerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newCustomerForm">
                    <div class="alert alert-danger d-none" id="customerErrorMsg"></div>
                    <div class="row">
                        <div class="col-md-6">                    
                            <div class="mb-3">
                                <label for="customerName" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customerName" name="customer_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customerEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customerEmail" name="email">
                            </div>                       
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="customerPhone" name="mobile_phone">
                    </div>
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="customerAddress" name="address" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCustomerBtn">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img id="detailProductImage" src="" class="img-fluid rounded" alt="Product Image" style="max-height: 200px;">
                    </div>
                    <div class="col-md-6">
                        <h4 id="detailProductName"></h4>
                        <p class="text-muted" id="detailProductCode"></p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Price:</strong> $<span id="detailProductPrice"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Stock:</strong> <span id="detailProductStock"></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Color:</strong> <span id="detailProductColor">N/A</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Size:</strong> <span id="detailProductSize">N/A</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p id="detailProductDescription" class="mt-1">No description available</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-left gap-3 mt-4 pt-3">                
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary px-4 py-2" id="addFromDetails">Add to Sale</button>
            </div>
        </div>
    </div>
</div>

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
    
    // Payment method change handler
    $('#paymentMethod').change(function() {
        const method = $(this).val();
        
        if (method === 'qr_code') {
            $('#qrCodeContainer').show();
        } else {
            $('#qrCodeContainer').hide();
        }
    });

    // Save new customer
    $('#saveCustomerBtn').click(function() {
        let valid = true;
        $('#newCustomerForm input[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        const email = $('#customerEmail').val().trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#customerEmail').addClass('is-invalid');
            valid = false;
        } else {
            $('#customerEmail').removeClass('is-invalid');
        }
        if (!valid) return;

        // Hide error message
        $('#customerErrorMsg').addClass('d-none').text('');

        const customerName = $('#customerName').val().trim();
        const customerEmail = $('#customerEmail').val().trim();
        const customerPhone = $('#customerPhone').val().trim();
        const customerAddress = $('#customerAddress').val().trim();

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $.ajax({
            url: 'customers/add_customer_sale.php',
            type: 'POST',
            data: {
                customer_name: customerName,
                email: customerEmail,
                mobile_phone: customerPhone,
                address: customerAddress
            },
            dataType: 'json',
            success: function(response) {
                $('#saveCustomerBtn').prop('disabled', false).html('Save Customer');
                if (response.status === 'success') {
                    $('#customerSelect').append(
                        $('<option>', {
                            value: response.customer_id,
                            text: customerName,
                            selected: true
                        })
                    );
                    $('#newCustomerModal').modal('hide');
                    $('#newCustomerForm')[0].reset();
                    showAlert('Customer added successfully!', 'success');
                } else {
                    $('#customerErrorMsg').removeClass('d-none').text(response.message || 'Error adding customer.');
                }
            },
            error: function(xhr, status, error) {
                $('#saveCustomerBtn').prop('disabled', false).html('Save Customer');
                $('#customerErrorMsg').removeClass('d-none').text('Error: ' + error);
            }
        });
    });

    // View product details
    $(document).on('click', '.view-details', function() {
        const productCard = $(this).closest('.product-card');
        const productId = productCard.data('id');
        const productName = productCard.data('name');
        const price = parseFloat(productCard.data('price'));
        const stock = parseInt(productCard.data('stock'));
        const image = productCard.data('image');
        const color = productCard.data('color');
        const size = productCard.data('size');
        const description = productCard.data('description');
        const code = productCard.data('code');
        
        // Populate modal with product details
        $('#detailProductName').text(productName);
        $('#detailProductCode').text('Code: ' + code);
        $('#detailProductPrice').text(price.toFixed(2));
        $('#detailProductStock').text(stock);
        $('#detailProductColor').text(color || 'N/A');
        $('#detailProductSize').text(size || 'N/A');
        $('#detailProductDescription').text(description || 'No description available');
        
        // Set product image
        if (image) {
            $('#detailProductImage').attr('src', 'uploads/products/' + image);
        } else {
            $('#detailProductImage').attr('src', '');
            $('#detailProductImage').html('<div class="bg-light d-flex align-items-center justify-content-center h-100"><i class="ti ti-photo" style="font-size: 3rem;"></i></div>');
        }
        
        // Store product ID for the "Add to Sale" button
        $('#productDetailsModal').data('product-id', productId);
    });

    // Add product to cart from details modal
    $('#addFromDetails').click(function() {
        const productId = $('#productDetailsModal').data('product-id');
        const productCard = $(`.product-card[data-id="${productId}"]`);
        
        if (productCard.length) {
            // Simulate click on the add-to-cart button
            productCard.find('.add-to-cart').click();
        }
        
        // Close the modal
        $('#productDetailsModal').modal('hide');
    });

    // Show alert function
    function showAlert(message, type) {
        // Remove any existing alerts
        $('.alert-dismissible').remove();
        
        // Create alert
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Prepend alert to content
        $('.pc-content').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert-dismissible').alert('close');
        }, 5000);
    }

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
                                 data-stock="${product.stock}" data-image="${product.image}"
                                 data-color="${product.color || ''}" data-size="${product.size || ''}"
                                 data-description="${product.description || ''}" data-code="${product.product_code}">
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
                                    
                                    <!-- Display Color and Size if available -->
                                    ${product.color ? `<p class="card-text small mb-1"><strong>Color:</strong> ${product.color}</p>` : ''}
                                    ${product.size ? `<p class="card-text small mb-1"><strong>Size:</strong> ${product.size}</p>` : ''}
                                    
                                    <p class="card-text small ${product.stock > 0 ? 'text-success' : 'text-danger'}">
                                        ${product.stock > 0 ? `In Stock: ${product.stock}` : 'Out of Stock'}
                                    </p>
                                    
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-primary add-to-cart" 
                                            ${product.stock < 1 ? 'disabled' : ''}>
                                            <i class="ti ti-shopping-cart-plus me-1"></i>
                                        </button>
                                        
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-sm btn-info view-details" 
                                            data-bs-toggle="modal" data-bs-target="#productDetailsModal">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </div>
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
    
    // Update discount, tax and totals
    $('#discount, #taxRate').on('input', updateTotals);
    
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
        const taxRate = parseFloat($('#taxRate').val()) || 0;
        
        // Calculate tax amount
        const taxAmount = subtotal * (taxRate / 100);
        
        const grandTotal = subtotal + taxAmount - discount;
        
        $('#taxAmount').val(taxAmount.toFixed(2));
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

.input-group > .form-select {
    flex: 1;
}

.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 10px 10px 0 0;
}

.modal-title {
    font-weight: 600;
    color: #4a4a4a;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 10px 10px;
}
</style>

<?php include('include/footer.php'); ?>