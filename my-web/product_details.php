<?php
// product_details.php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID";
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$sql = "SELECT 
            p.*,
            c.name AS category_name,
            s.name AS supplier_name
        FROM products p
        LEFT JOIN category c ON p.category_name = c.name
        LEFT JOIN supplier s ON p.supplier_name = s.name
        WHERE p.product_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    $_SESSION['error_message'] = "Product not found";
    header("Location: products.php");
    exit();
}

// Format prices
$cost_price = number_format($product['cost_price'], 2);
$sale_price = number_format($product['sale_price'], 2);
$profit = number_format($product['sale_price'] - $product['cost_price'], 2);
$profit_margin = $product['cost_price'] > 0 ? round(($product['sale_price'] - $product['cost_price']) / $product['cost_price'] * 100, 2) : 0;

// Determine status
$status_class = $product['stock_quantity'] > 0 ? 'badge bg-success' : 'badge bg-danger';
$status_text = $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock';

// Get image path
$imagePath = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/default-product.png';
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Product Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item" aria-current="page">Product Details</li>
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Product Information</h5>
                        <div>
                            <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary btn-sm">
                                <i class="ti ti-edit"></i> Edit Product
                            </a>
                            <a href="products.php" class="btn btn-secondary btn-sm">
                                <i class="ti ti-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center mb-4">
                                    <img src="<?php echo $imagePath; ?>" class="img-fluid rounded" style="max-height: 300px;" alt="Product Image">
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6>Quick Stats</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Stock Quantity
                                                <span class="badge bg-primary rounded-pill"><?php echo $product['stock_quantity']; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Status
                                                <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Cost Price
                                                <span>$<?php echo $cost_price; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Sale Price
                                                <span>$<?php echo $sale_price; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Profit
                                                <span>$<?php echo $profit; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Profit Margin
                                                <span><?php echo $profit_margin; ?>%</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6>Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Product Code</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['product_code']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Product Name</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['name']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Category</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Supplier</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['supplier_name']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Size</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['size']); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Color</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['color']); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Gender</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($product['gender']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6>Additional Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Description</label>
                                                <p class="form-control-static"><?php echo !empty($product['description']) ? htmlspecialchars($product['description']) : 'No description available'; ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Created At</label>
                                                <p class="form-control-static"><?php echo date('M d, Y h:i A', strtotime($product['created_at'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Last Updated</label>
                                                <p class="form-control-static"><?php echo !empty($product['updated_at']) ? date('M d, Y h:i A', strtotime($product['updated_at'])) : 'Never updated'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<?php include('include/footer.php'); ?>