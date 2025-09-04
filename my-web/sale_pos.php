<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Fetch categories and products
$categories = [];
$products = [];

// Get all categories
$category_query = "SELECT * FROM category";
$category_result = mysqli_query($conn, $category_query);

if (!$category_result) {
    die("Database error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row;
}

// Get all active products with stock
$product_query = "SELECT p.*, c.name AS category_name 
                 FROM products p 
                 JOIN category c ON p.category_name = c.name 
                 WHERE p.stock_quantity > 0
                 ORDER BY c.name, p.name";
$product_result = mysqli_query($conn, $product_query);

if (!$product_result) {
    die("Database error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($product_result)) {
    $products[$row['category_name']][] = $row;
}

// Build $allProducts array here in the correct scope
$allProducts = [];
foreach ($categories as $category) {
    if (isset($products[$category['name']])) {
        $allProducts = array_merge($allProducts, $products[$category['name']]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/logo_report_icon.png">
    <title>Nisai Fashion Store - POS</title>
    <link rel="stylesheet" href="../assets/css_pos/style_pos.css">
    <style>
        /* Search bar styles */
        .search-container {
            margin: 20px 0;
            position: relative;
        }
        
        .search-box {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border: 1px solid #ddd;
            border-radius: 50px;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 2px 10px rgba(78, 115, 223, 0.25);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .no-results {
            text-align: center;
            padding: 30px;
            color: #777;
            font-style: italic;
            grid-column: 1 / -1;
        }
        
        /* Quick stats */
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4e73df;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Category filter improvements */
        .category-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
            }
            
            .category-sidebar {
                width: 200px;
            }
        }
        /* Product code and stock styles */
        .product-code, .stock-quantity {
            display: flex;
            font-size: 14px;
            margin-bottom: 5px;
            color: #555;
        }

        .product-code .label, 
        .stock-quantity .label {
            font-weight: 600;
            margin-right: 5px;
            min-width: 40px;
        }

        .stock-quantity .value {
            font-weight: bold;
        }

        .low-stock {
            color: #e74c3c;
        }

        .low-stock-warning {
            margin-left: 5px;
            color: #e74c3c;
            font-size: 12px;
            font-weight: bold;
        }

/* Back to Sales button styles */
.back-to-sales-btn {
    display: flex;
    align-items: center;
    background-color: #4e73df;
    color: white !important;
    padding: 8px 15px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.back-to-sales-btn:hover {
    background-color: #2e59d9;
    text-decoration: none;
}

.back-to-sales-btn svg {
    stroke: white;
    margin-right: 5px;
}

.back-to-sales-btn span {
    color: white;
}
    </style>
</head>
<body>

<header class="pc-header">
    <div class="header-wrapper">
        <div class="header-logo">
            <img src="../assets/images/logo.png" alt="Nisai Fashion Store Logo">
        </div>
        <div class="me-auto">
            <ul class="header-nav">
                <li class="pc-h-item">
                    <a href="#" class="pc-head-link" id="sidebar-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </a>
                </li>
                <!-- Add Back to Sales button -->
                <li class="pc-h-item">
                <!-- Update this -->
                <a href="sales.php" class="pc-head-link back-to-sales-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </a>
                </li>
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="header-nav">
                <li class="pc-h-item">
                    <a href="#" class="pc-head-link" id="theme-toggle">
                        <span class="light-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="5"></circle>
                                <path d="M12 1v2"></path>
                                <path d="M12 21v2"></path>
                                <path d="M4.22 4.22l1.42 1.42"></path>
                                <path d="M18.36 18.36l1.42 1.42"></path>
                                <path d="M1 12h2"></path>
                                <path d="M21 12h2"></path>
                                <path d="M4.22 19.78l1.42-1.42"></path>
                                <path d="M18.36 5.64l1.42-1.42"></path>
                            </svg>
                        </span>
                        <span class="dark-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </span>
                    </a>
                </li>
                <li class="pc-h-item">
                    <a href="#" class="pc-head-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="badge">3</span>
                    </a>
                </li>
                <li class="pc-h-item header-user-profile">
                    <a href="#" class="pc-head-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                    <div class="dropdown-menu">
                        <a href="#" class="dropdown-item">Profile</a>
                        <a href="#" class="dropdown-item">Settings</a>
                        <a href="logout.php" class="dropdown-item">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Category Sidebar -->
<div class="category-sidebar">
    <h2>Categories</h2>
    <ul class="category-list">
        <li class="category-item active" data-category="all">
            <span class="category-icon">•</span>
            All Products
        </li>
        <?php foreach ($categories as $category): ?>
            <li class="category-item" data-category="<?php echo htmlspecialchars($category['name']); ?>">
                <span class="category-icon">•</span>
                <?php echo htmlspecialchars($category['name']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <section class="menu-section">
        <div class="section-header">
            <h2 class="section-title">All Products</h2>
            
            <!-- Quick Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($allProducts); ?></div>
                    <div class="stat-label">Available Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="search-container">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="productSearch" class="search-box" placeholder="Search products by name...">
            </div>
        </div>
        
<div class="menu-grid" id="productGrid">
    <?php foreach ($allProducts as $product): ?>
        <div class="menu-item" data-id="<?php echo $product['product_id']; ?>" 
             data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
             data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>">
            <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/default-product.png'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <div class="item-details">
                <!-- Product Name -->
                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                
                <!-- Product Code -->
                <div class="product-code">
                    <strong>Code:</strong> <?php echo htmlspecialchars($product['product_code']); ?>
                </div>
                
                <!-- Stock Quantity -->
                <div class="stock-quantity">
                    <strong>Stock:</strong> <?php echo $product['stock_quantity']; ?>
                </div>
                
                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                <?php if (!empty($product['description'])): ?>
                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                <?php endif; ?>
                <span class="price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                <button class="cta-button add-to-cart">Add to Cart</button>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="no-results" id="noResults" style="display: none;">No products found matching your search</div>
</div>
    </section>
</div>

<!-- Floating Cart Button -->
<div class="cart-button">
    <span class="cart-count">0</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>
</div>

<!-- Shopping Cart Sidebar -->
<div class="cart-sidebar">
    <div class="cart-header">
        <h3>Your Order</h3>
        <button class="close-cart">&times;</button>
    </div>
    <div class="cart-items">
        <!-- Cart items will be added here dynamically -->
    </div>
    <div class="cart-totals">
        <div class="cart-subtotal">
            <span>Subtotal:</span>
            <span class="subtotal-amount">$0.00</span>
        </div>
        <div class="cart-tax">
            <span>Tax (10%):</span>
            <span class="tax-amount">$0.00</span>
        </div>
        <div class="cart-discount">
            <label for="discount">Discount:</label>
            <input type="number" id="discount" min="0" step="0.01" value="0.00">
        </div>
        <div class="cart-total">
            <span>Total:</span>
            <span class="total-amount">$0.00</span>
        </div>
    </div>
    <div class="cart-actions">
        <button class="checkout-btn">Proceed to Checkout</button>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cart = [];
    const cartSidebar = document.querySelector('.cart-sidebar');
    const cartItemsContainer = document.querySelector('.cart-items');
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    const closeCartButton = document.querySelector('.close-cart');
    const categoryItems = document.querySelectorAll('.category-item');
    const cartButton = document.querySelector('.cart-button');
    const cartCount = document.querySelector('.cart-count');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const categorySidebar = document.querySelector('.category-sidebar');
    const productSearch = document.getElementById('productSearch');
    const productGrid = document.getElementById('productGrid');
    const noResults = document.getElementById('noResults');
    
    // Toggle sidebar on mobile
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        categorySidebar.classList.toggle('active');
    });
    
    // Category filtering
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            // Update active state
            categoryItems.forEach(cat => cat.classList.remove('active'));
            this.classList.add('active');
            
            filterProducts();
        });
    });
    
    // Toggle cart visibility
    function toggleCart() {
        cartSidebar.classList.toggle('active');
    }
    
    // Floating cart button click
    cartButton.addEventListener('click', toggleCart);
    
    // Close cart button
    closeCartButton.addEventListener('click', toggleCart);
    
    // Add to cart functionality
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const menuItem = this.closest('.menu-item');
            const productId = menuItem.dataset.id;
            const productName = menuItem.querySelector('h4').textContent;
            const productPrice = parseFloat(menuItem.querySelector('.price').textContent.replace('$', ''));
            const productImage = menuItem.querySelector('img').src;
            
            // Check if product already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: 1
                });
            } 
            
            updateCartDisplay();
            toggleCart();
        });
    });
    
    // Update cart display
    function updateCartDisplay() {
        cartItemsContainer.innerHTML = '';
        let subtotal = 0;
        let itemCount = 0;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            itemCount += item.quantity;
            
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="cart-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus" data-id="${item.id}">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-btn plus" data-id="${item.id}">+</button>
                    </div>
                    <div class="cart-item-total">$${itemTotal.toFixed(2)}</div>
                    <button class="remove-item" data-id="${item.id}">Remove</button>
                </div>
            `;
            cartItemsContainer.appendChild(cartItem);
        });
        
        // Update cart count
        cartCount.textContent = itemCount;
        cartButton.style.display = itemCount > 0 ? 'flex' : 'none';
        
        // Calculate totals
        const tax = subtotal * 0.10;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const total = subtotal + tax - discount;
        
        // Update totals display
        document.querySelector('.subtotal-amount').textContent = `$${subtotal.toFixed(2)}`;
        document.querySelector('.tax-amount').textContent = `$${tax.toFixed(2)}`;
        document.querySelector('.total-amount').textContent = `$${total.toFixed(2)}`;
        
        // Add event listeners to new buttons
        addQuantityEventListeners();
    }
    
    // Add event listeners to quantity buttons
    function addQuantityEventListeners() {
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                const item = cart.find(item => item.id === productId);
                
                if (this.classList.contains('minus')) {
                    if (item.quantity > 1) {
                        item.quantity--;
                    } else {
                        // Remove item if quantity reaches 0
                        const index = cart.findIndex(item => item.id === productId);
                        cart.splice(index, 1);
                    }
                } else if (this.classList.contains('plus')) {
                    item.quantity++;
                }
                
                updateCartDisplay();
            });
        });
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                const index = cart.findIndex(item => item.id === productId);
                cart.splice(index, 1);
                updateCartDisplay();
            });
        });
    }
    
    // Discount input change
    document.getElementById('discount').addEventListener('change', updateCartDisplay);
    
    // Product search functionality
    productSearch.addEventListener('input', function() {
        filterProducts();
    });
    
    // Filter products based on search term and category
    function filterProducts() {
        const searchTerm = productSearch.value.toLowerCase();
        const activeCategory = document.querySelector('.category-item.active').dataset.category;
        const menuItems = document.querySelectorAll('.menu-item');
        
        let visibleItems = 0;
        
        menuItems.forEach(item => {
            const itemCategory = item.dataset.category;
            const itemName = item.dataset.name;
            
            const categoryMatch = activeCategory === 'all' || itemCategory === activeCategory;
            const searchMatch = itemName.includes(searchTerm);
            
            if (categoryMatch && searchMatch) {
                item.style.display = 'block';
                visibleItems++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (visibleItems === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }
    
    // Clear search when clicking on category
    categoryItems.forEach(item => {
        if (!item.classList.contains('active')) {
            item.addEventListener('click', function() {
                productSearch.value = '';
                filterProducts();
            });
        }
    });
    
    // Checkout button
    document.querySelector('.checkout-btn').addEventListener('click', function() {
        if (cart.length === 0) {
            alert('Your cart is empty!');
            return;
        }
        
        // Prepare form data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_sale.php';
        
        // Add customer ID (you would get this from a form field)
        const customerInput = document.createElement('input');
        customerInput.type = 'hidden';
        customerInput.name = 'customer_id';
        customerInput.value = ''; // Set this from a form field
        form.appendChild(customerInput);
        
        // Add payment method (you would get this from a form field)
        const paymentInput = document.createElement('input');
        paymentInput.type = 'hidden';
        paymentInput.name = 'payment_method';
        paymentInput.value = 'cash'; // Set this from a form field
        form.appendChild(paymentInput);
        
        // Add cart items
        cart.forEach((item, index) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `product_id[${index}]`;
            idInput.value = item.id;
            form.appendChild(idInput);
            
            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `quantity[${index}]`;
            qtyInput.value = item.quantity;
            form.appendChild(qtyInput);
            
            const priceInput = document.createElement('input');
            priceInput.type = 'hidden';
            priceInput.name = `price[${index}]`;
            priceInput.value = item.price;
            form.appendChild(priceInput);
        });
        
        // Add discount
        const discountInput = document.createElement('input');
        discountInput.type = 'hidden';
        discountInput.name = 'discount';
        discountInput.value = document.getElementById('discount').value;
        form.appendChild(discountInput);
        
        // Submit form
        document.body.appendChild(form);
        form.submit(); 
    });
    
    // Initialize the filter on page load
    filterProducts();
});
</script>
</body>
</html> 