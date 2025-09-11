<?php
ob_start(); // Start output buffering
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Initialize variables
$product_id = $product_code = $name = $category_name = $supplier_name = "";
$size = $color = $gender = $cost_price = $sale_price = $stock_quantity = "";
$image = $description = "";
$error_message = $success_message = "";

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Product ID is required.";
    header("Location: products.php");
    exit();
}

$product_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch product data
$sql = "SELECT * FROM products WHERE product_id = '$product_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "Product not found.";
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Populate variables with product data
$product_code = $product['product_code'];
$name = $product['name'];
$category_name = $product['category_name'];
$supplier_name = $product['supplier_name'];
$size = $product['size'];
$color = $product['color'];
$gender = $product['gender'];
$cost_price = $product['cost_price'];
$sale_price = $product['sale_price'];
$stock_quantity = $product['stock_quantity'];
$image = $product['image'];
$description = $product['description'];

// Fetch categories and suppliers for dropdowns
$categories = [];
$suppliers = [];

$cat_query = "SELECT * FROM category ORDER BY name";
$cat_result = mysqli_query($conn, $cat_query);
if ($cat_result) {
    while ($cat = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $cat;
    }
}

$sup_query = "SELECT * FROM supplier ORDER BY name";
$sup_result = mysqli_query($conn, $sup_query);
if ($sup_result) {
    while ($sup = mysqli_fetch_assoc($sup_result)) {
        $suppliers[] = $sup;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $product_code = mysqli_real_escape_string($conn, $_POST['product_code']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $cost_price = mysqli_real_escape_string($conn, $_POST['cost_price']);
    $sale_price = mysqli_real_escape_string($conn, $_POST['sale_price']);
    $stock_quantity = mysqli_real_escape_string($conn, $_POST['stock_quantity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a valid image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $error_message = "File is not a valid image.";
        } 
        // Check file size (max 5MB)
        else if ($_FILES["image"]["size"] > 5000000) {
            $error_message = "Sorry, your file is too large. Maximum size is 5MB.";
        } 
        // Allow certain file formats
        else if (!in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
            $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } 
        // Try to upload file
        else if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Delete old image if it exists and is not the default
            if (!empty($image) && $image != 'default-product.png' && file_exists('uploads/products/' . $image)) {
                unlink('uploads/products/' . $image);
            }
            $image = $new_filename;
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }
    
    // If no errors, update the product
    if (empty($error_message)) {
        $update_sql = "UPDATE products SET 
                        product_code = '$product_code',
                        name = '$name',
                        category_name = '$category_name',
                        supplier_name = '$supplier_name',
                        size = '$size',
                        color = '$color',
                        gender = '$gender',
                        cost_price = '$cost_price',
                        sale_price = '$sale_price',
                        stock_quantity = '$stock_quantity',
                        description = '$description'";
        
        if (!empty($image)) {
            $update_sql .= ", image = '$image'";
        }
        
        $update_sql .= " WHERE product_id = '$product_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['success_message'] = "Product updated successfully.";
            // Clear output buffer and redirect
            ob_end_clean();
            header("Location: products.php");
            exit();
        } else {
            $error_message = "Error updating product: " . mysqli_error($conn);
        }
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
                    <h5 class="mb-0 font-medium">Edit Product</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item" aria-current="page">Edit Product</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Product Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="product_code" class="form-label">Product Code *</label>
                                                <input type="text" class="form-control" id="product_code" name="product_code" value="<?php echo htmlspecialchars($product_code); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Product Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category_name" class="form-label">Category *</label>
                                                <select class="form-select" id="category_name" name="category_name" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['name']; ?>" <?php echo ($category_name == $cat['name']) ? 'selected' : ''; ?>>
                                                        <?php echo $cat['name']; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="supplier_name" class="form-label">Supplier *</label>
                                                <select class="form-select" id="supplier_name" name="supplier_name" required>
                                                    <option value="">Select Supplier</option>
                                                    <?php foreach ($suppliers as $sup): ?>
                                                    <option value="<?php echo $sup['name']; ?>" <?php echo ($supplier_name == $sup['name']) ? 'selected' : ''; ?>>
                                                        <?php echo $sup['name']; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="size" class="form-label">Size</label>
                                                <input type="text" class="form-control" id="size" name="size" value="<?php echo htmlspecialchars($size); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="color" class="form-label">Color</label>
                                                <input type="text" class="form-control" id="color" name="color" value="<?php echo htmlspecialchars($color); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select class="form-select" id="gender" name="gender">
                                                    <option value="">Select Gender</option>
                                                    <option value="Men" <?php echo ($gender == 'Men') ? 'selected' : ''; ?>>Men</option>
                                                    <option value="Women" <?php echo ($gender == 'Women') ? 'selected' : ''; ?>>Women</option>
                                                    <option value="Unisex" <?php echo ($gender == 'Unisex') ? 'selected' : ''; ?>>Unisex</option>
                                                    <option value="Kids" <?php echo ($gender == 'Kids') ? 'selected' : ''; ?>>Kids</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="cost_price" class="form-label">Cost Price ($) *</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="cost_price" name="cost_price" value="<?php echo $cost_price; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="sale_price" class="form-label">Sale Price ($) *</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="sale_price" name="sale_price" value="<?php echo $sale_price; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                                <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $stock_quantity; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="image" class="form-label">Product Image</label>
                                        <div class="mb-3">
                                            <?php
                                            $imagePath = !empty($image) ? 'uploads/products/' . $image : 'assets/images/default-product.png';
                                            if (!file_exists($imagePath)) {
                                                $imagePath = 'assets/images/default-product.png';
                                            }
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" id="imagePreview" class="img-thumbnail" style="max-height: 250px;">
                                        </div>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                                        <small class="form-text text-muted">Recommended size: 300x300 pixels. Max size: 5MB</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Update Product</button>
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Basic form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const costPrice = parseFloat(document.getElementById('cost_price').value);
    const salePrice = parseFloat(document.getElementById('sale_price').value);
    
    if (salePrice < costPrice) {
        e.preventDefault();
        alert('Sale price cannot be less than cost price.');
        return false;
    }
    
    if (costPrice <= 0 || salePrice <= 0) {
        e.preventDefault();
        alert('Prices must be greater than zero.');
        return false;
    }
});
</script>

<?php 
// End output buffering and flush
ob_end_flush();
include('include/footer.php'); 
?>