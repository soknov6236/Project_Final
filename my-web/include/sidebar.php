<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Define page groups
$product_pages = ['products.php', 'add_new_products.php', 'products_stock.php', 'product_details.php', 'edit_product.php', 'restock_product.php'];
$users_pages = ['users.php', 'add_new_users.php', 'edit_users.php'];
$customer_pages = ['customers.php', 'add_new_customer.php', 'edit_customer.php'];
$supplier_pages = ['suppliers.php', 'add_new_suppliers.php', 'edit_supplier.php'];
$category_pages = ['category.php', 'add_new_category.php', 'edit_category.php'];
$purchase_pages = ['purchase.php', 'purchase_list.php', 'purchase_history.php'];
$sale_pages = ['sales.php', 'sale_pos.php','add_new_sale.php', 'manage_sale.php', 'view_sale.php', 'edit_sale.php'];
$return_pages = ['returns.php', 'add_new_returns.php'];
$report_pages = ['reports/users.php', 'reports/customers.php', 'reports/suppliers.php', 'reports/categories.php', 'reports/products.php', 'reports/sales.php', 'reports/returns.php'];

// Check current page group
$is_product_page = in_array($current_page, $product_pages) || str_contains($current_page, 'product');
$is_users_page = in_array($current_page, $users_pages) || str_contains($current_page, 'user');
$is_customer_page = in_array($current_page, $customer_pages) || str_contains($current_page, 'customer');
$is_supplier_page = in_array($current_page, $supplier_pages) || str_contains($current_page, 'supplier');
$is_category_page = in_array($current_page, $category_pages) || str_contains($current_page, 'category');
$is_purchase_page = in_array($current_page, $purchase_pages) || str_contains($current_page, 'purchase');
$is_sale_page = in_array($current_page, $sale_pages) || str_contains($current_page, 'sale');
$is_return_page = in_array($current_page, $return_pages) || str_contains($current_page, 'return');
$is_report_page = in_array($current_page, $report_pages) || str_contains($current_page, 'report');
?>

<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header flex items-center py-4 px-6 h-header-height">
            <a href="index.php" class="b-brand flex items-center gap-3">
                <img src="../assets/images/logo_nisai.png"  alt="Company Logo" />
                <img src="../assets/images/profile.jpg" class="block sm:hidden h-6 w-6 rounded-full object-cover" alt="Company Icon" />
            </a>
        </div>
    </div>
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
        <ul class="pc-navbar">
            <li class="pc-item pc-caption">
                <label>Navigation</label>
            </li>
            
            <!-- Dashboard -->
            <li class="pc-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <a href="index.php" class="pc-link">
                    <span class="pc-micon"><i data-feather="home"></i></span>
                    <span class="pc-mtext">Dashboard</span>
                </a>
            </li>
            
            <!-- Users -->
            <li class="pc-item pc-hasmenu <?= $is_users_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="users"></i></span>
                    <span class="pc-mtext">Users</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_users_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'users.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="users.php">Users List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_users.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_users.php">Add New User</a>
                    </li>
                </ul>
            </li>
            
            <!-- Customer -->
            <li class="pc-item pc-hasmenu <?= $is_customer_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="user-plus"></i></span>
                    <span class="pc-mtext">Customer</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_customer_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'customers.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="customers.php">Customer List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_customer.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_customer.php">Add Customer</a>
                    </li>
                </ul>
            </li>
            
            <!-- Supplier -->
            <li class="pc-item pc-hasmenu <?= $is_supplier_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="truck"></i></span>
                    <span class="pc-mtext">Supplier</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_supplier_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="suppliers.php">Supplier List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_suppliers.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_suppliers.php">Add Supplier</a>
                    </li>
                </ul>
            </li>
            
            <!-- Category -->
            <li class="pc-item pc-hasmenu <?= $is_category_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="list"></i></span>
                    <span class="pc-mtext">Category</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_category_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'category.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="category.php">Category List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_category.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_category.php">Add Category</a>
                    </li>
                </ul>
            </li>
            
            <!-- Product -->
            <li class="pc-item pc-hasmenu <?= $is_product_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="shopping-bag"></i></span>
                    <span class="pc-mtext">Product</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_product_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'products.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="products.php">Product List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_products.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_products.php">Add Product</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'products_stock.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="products_stock.php">Product Stock</a>
                    </li>
                </ul>
            </li>
            
            <!-- Purchase -->
            <li class="pc-item pc-hasmenu <?= $is_purchase_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="package"></i></span>
                    <span class="pc-mtext">Purchase</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_purchase_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'purchase.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="purchase.php">New Purchase</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'purchase_list.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="purchase_list.php">Purchase List</a>
                    </li>
                </ul>
            </li>
            
            <!-- Sale - Fixed Structure -->
            <li class="pc-item pc-hasmenu <?= $is_sale_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="shopping-cart"></i></span>
                    <span class="pc-mtext">Sale</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_sale_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'sale_pos.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_sale.php">Add Sale</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'sale_pos.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="sale_pos.php">POS</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'sales.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="sales.php">Sale List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'manage_sale.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="manage_sale.php">Manage Sales</a>
                    </li>
                </ul>
            </li>
            
            <!-- Return -->
            <li class="pc-item pc-hasmenu <?= $is_return_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="rotate-ccw"></i></span>
                    <span class="pc-mtext">Return</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_return_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'returns.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="returns.php">Return List</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'add_new_returns.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="add_new_returns.php">Add Return</a>
                    </li>
                </ul>
            </li>
            
            <!-- Report -->
            <li class="pc-item pc-hasmenu <?= $is_report_page ? 'active pc-trigger' : '' ?>">
                <a href="#!" class="pc-link">
                    <span class="pc-micon"><i data-feather="file-text"></i></span>
                    <span class="pc-mtext">Report</span>
                    <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                </a>
                <ul class="pc-submenu" style="<?= $is_report_page ? 'display: block;' : '' ?>">
                    <li class="pc-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="reports.php">Sales Report</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'inventory.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="inventory.php">Inventory Report</a>
                    </li>
                    <li class="pc-item <?= $current_page == 'reports/customers.php' ? 'active' : '' ?>">
                        <a class="pc-link" href="reports/customers.php">Customer Report</a>
                    </li>
                </ul>
            </li>
            
            <!-- Setting -->
            <li class="pc-item <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <a href="settings.php" class="pc-link">
                    <span class="pc-micon"><i data-feather="settings"></i></span>
                    <span class="pc-mtext">Setting</span>
                </a>
            </li>
            
            <!-- Logout -->
            <li class="pc-item">
                <a href="logout.php" class="pc-link">
                    <span class="pc-micon"><i data-feather="log-out"></i></span>
                    <span class="pc-mtext">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
<!-- [ Sidebar Menu ] end -->

<script>
// Initialize feather icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // Auto-collapse other menus when one is opened
    document.querySelectorAll('.pc-hasmenu .pc-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.parentElement.classList.contains('active')) {
                document.querySelectorAll('.pc-hasmenu').forEach(menu => {
                    menu.classList.remove('active');
                    menu.querySelector('.pc-submenu').style.display = 'none';
                });
                this.parentElement.classList.add('active');
                this.nextElementSibling.style.display = 'block';
            }
        });
    });
});
</script>