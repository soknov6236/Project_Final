<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID";
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error_message'] = "Product not found";
    header("Location: products.php");
    exit();
}

// Get categories and suppliers
$categories = $conn->query("SELECT * FROM category")->fetch_all(MYSQLI_ASSOC);
$suppliers = $conn->query("SELECT * FROM supplier")->fetch_all(MYSQLI_ASSOC);

require_once('include/header.php');
require_once('include/sidebar.php');
require_once('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Edit Product</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item">Edit Product</li>
                </ul>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Product Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="productForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Code</label>
                                        <input type="text" class="form-control" name="product_code" 
                                            value="<?= htmlspecialchars($product['product_code']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" class="form-control" name="name" 
                                            value="<?= htmlspecialchars($product['name']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_name" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                    <?= $cat['name'] === $product['category_name'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Supplier</label>
                                        <select class="form-select" name="supplier_name" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers as $sup): ?>
                                                <option value="<?= htmlspecialchars($sup['name']) ?>" 
                                                    <?= $sup['name'] === $product['supplier_name'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($sup['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Price</label>
                                        <input type="number" step="0.01" class="form-control" name="cost_price" 
                                            value="<?= htmlspecialchars($product['cost_price']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Sale Price</label>
                                        <input type="number" step="0.01" class="form-control" name="sale_price" 
                                            value="<?= htmlspecialchars($product['sale_price']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" name="stock_quantity" 
                                            value="<?= htmlspecialchars($product['stock_quantity']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        <small class="text-muted">Leave blank to keep current image</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!empty($product['image'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current Image</label>
                                            <div>
                                                <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" width="100" class="img-thumbnail">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-outline-primary">Update Product</button>
                                <a href="products.php" class="btn btn-outline-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('products/update_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.href = 'products.php';
        } else {
            alert(data.message || 'Error updating product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the product');
    });
});
</script>

<?php include('include/footer.php'); ?>