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

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: category.php");
    exit();
}

$category_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch category details
$category_query = "SELECT * FROM category WHERE category_id = '$category_id'";
$category_result = mysqli_query($conn, $category_query);
$category = mysqli_fetch_assoc($category_result);

if (!$category) {
    header("Location: category.php");
    exit();
}

// Fetch products in this category
$products_query = "SELECT * FROM products WHERE category_id = '$category_id' ORDER BY name";
$products_result = mysqli_query($conn, $products_query);

// Count products by status
$in_stock_count = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

while ($product = mysqli_fetch_assoc($products_result)) {
    if ($product['stock_quantity'] <= 0) {
        $out_of_stock_count++;
    } elseif ($product['stock_quantity'] < 10) {
        $low_stock_count++;
    } else {
        $in_stock_count++;
    }
}

// Reset pointer for products result
mysqli_data_seek($products_result, 0);

// Calculate total inventory value
$inventory_value_query = "SELECT SUM(stock_quantity * cost_price) as total_value FROM products WHERE category_id = '$category_id'";
$inventory_value_result = mysqli_query($conn, $inventory_value_query);
$inventory_value = mysqli_fetch_assoc($inventory_value_result)['total_value'] ?? 0;
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Category Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="category.php">Categories</a></li>
                    <li class="breadcrumb-item" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Category Information</h5>
                        <a href="edit_category.php?id=<?php echo $category_id; ?>" class="btn btn-outline-primary">
                            <i class="ti ti-edit me-1"></i> Edit Category
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="30%">Category ID</th>
                                        <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Category Name</th>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td><?php echo !empty($category['description']) ? htmlspecialchars($category['description']) : 'No description provided'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td><?php echo date('M d, Y h:i A', strtotime($category['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-primary text-white mb-3">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Total Products</h6>
                                                <h3 class="card-text"><?php echo mysqli_num_rows($products_result); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-info text-white mb-3">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Inventory Value</h6>
                                                <h3 class="card-text">$<?php echo number_format($inventory_value, 2); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white mb-3">
                                            <div class="card-body text-center py-2">
                                                <h6 class="card-title mb-1">In Stock</h6>
                                                <h4 class="card-text mb-0"><?php echo $in_stock_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-warning text-white mb-3">
                                            <div class="card-body text-center py-2">
                                                <h6 class="card-title mb-1">Low Stock</h6>
                                                <h4 class="card-text mb-0"><?php echo $low_stock_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-danger text-white mb-3">
                                            <div class="card-body text-center py-2">
                                                <h6 class="card-title mb-1">Out of Stock</h6>
                                                <h4 class="card-text mb-0"><?php echo $out_of_stock_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Products in This Category</h5>
                        <a href="add_new_products.php?category_id=<?php echo $category_id; ?>" class="btn btn-outline-primary">
                            <i class="ti ti-plus me-1"></i> Add Product
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($products_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped" id="products-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Cost Price</th>
                                        <th>Sale Price</th>
                                        <th>Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_value = 0;
                                    while ($product = mysqli_fetch_assoc($products_result)) {
                                        $stock_value = $product['stock_quantity'] * $product['cost_price'];
                                        $total_value += $stock_value;
                                        
                                        // Determine status
                                        if ($product['stock_quantity'] <= 0) {
                                            $status_class = 'badge bg-danger';
                                            $status_text = 'Out of Stock';
                                        } elseif ($product['stock_quantity'] < 10) {
                                            $status_class = 'badge bg-warning';
                                            $status_text = 'Low Stock';
                                        } else {
                                            $status_class = 'badge bg-success';
                                            $status_text = 'In Stock';
                                        }
                                        
                                        echo "<tr>
                                                <td>{$product['product_id']}</td>
                                                <td>{$product['product_code']}</td>
                                                <td>{$product['name']}</td>
                                                <td>{$product['stock_quantity']}</td>
                                                <td><span class='{$status_class}'>{$status_text}</span></td>
                                                <td>".number_format($product['cost_price'], 2)."</td>
                                                <td>".number_format($product['sale_price'], 2)."</td>
                                                <td>".number_format($stock_value, 2)."</td>
                                                <td>
                                                    <div class='btn-group' role='group'>
                                                        <a href='edit_product.php?id={$product['product_id']}' class='btn btn-sm btn-outline-primary' title='Edit'>
                                                            <i class='ti ti-edit'></i>
                                                        </a>
                                                        <a href='product_details.php?id={$product['product_id']}' class='btn btn-sm btn-outline-info' title='View'>
                                                            <i class='ti ti-eye'></i>
                                                        </a>
                                                        <a href='restock_product.php?id={$product['product_id']}' class='btn btn-sm btn-outline-success' title='Restock'>
                                                            <i class='ti ti-package'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>";
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-end"><strong>Total Inventory Value:</strong></td>
                                        <td colspan="2"><strong>$<?php echo number_format($total_value, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="ti ti-package-off" style="font-size: 3rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Products Found</h4>
                            <p class="text-muted">This category doesn't have any products yet.</p>
                            <a href="add_new_products.php?category_id=<?php echo $category_id; ?>" class="btn btn-primary mt-2">
                                <i class="ti ti-plus me-1"></i> Add First Product
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    <?php if (mysqli_num_rows($products_result) > 0): ?>
    // Initialize DataTable with export buttons
    $('#products-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true
    });
    <?php endif; ?>
});
</script>

<?php 
mysqli_close($conn);
include('include/footer.php'); 
?>