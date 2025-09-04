<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');


// Initialize variables for form persistence
$formData = [
    'product_code' => '',
    'name' => '',
    'category_name' => '',
    'supplier_name' => '',
    'cost_price' => '0.00',
    'sale_price' => '0.00',
    'stock_quantity' => '0',
    'size' => '',
    'color' => '',
    'gender' => 'Unisex',
    'description' => ''
];

// Check for form submission errors in session
if (isset($_SESSION['form_errors'])) {
    $formErrors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
    
    // Repopulate form data if available
    if (isset($_SESSION['form_data'])) {
        $formData = array_merge($formData, $_SESSION['form_data']);
        unset($_SESSION['form_data']);
    }
}
?>

    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>
    <?php include('include/topbar.php'); ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Add New Product</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                        <li class="breadcrumb-item" aria-current="page">Add New</li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- Error Messages -->
            <?php if (!empty($formErrors['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($formErrors['general']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Product Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="productForm" action="products/add_products.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <!-- Left Column - Basic Information -->
                                    <div class="col-md-6">
                                        <div class="form-section mb-4">
                                            <h6 class="section-title mb-3">Basic Information</h6>
                                            
                                            <div class="form-group mb-3">
                                                <label for="name" class="form-label">Product Name *</label>
                                                <input type="text" class="form-control <?php echo isset($formErrors['name']) ? 'is-invalid' : ''; ?>" 
                                                    id="name" name="name" 
                                                    value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                                                <?php if (isset($formErrors['name'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($formErrors['name']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="category_name" class="form-label">Category *</label>
                                                        <select class="form-control <?php echo isset($formErrors['category_name']) ? 'is-invalid' : ''; ?>" 
                                                            id="category_name" name="category_name" required>
                                                            <option value="">Select category</option>
                                                            <?php
                                                            $query = "SELECT * FROM category ORDER BY name";
                                                            $result = mysqli_query($conn, $query);
                                                            while ($row = mysqli_fetch_assoc($result)) {
                                                                $selected = ($formData['category_name'] == $row['name']) ? 'selected' : '';
                                                                echo "<option value='{$row['name']}' $selected>{$row['name']}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                        <?php if (isset($formErrors['category_name'])): ?>
                                                        <div class="invalid-feedback">
                                                            <?php echo htmlspecialchars($formErrors['category_name']); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="supplier_name" class="form-label">Supplier *</label>
                                                        <select class="form-control <?php echo isset($formErrors['supplier_name']) ? 'is-invalid' : ''; ?>" 
                                                            id="supplier_name" name="supplier_name" required>
                                                            <option value="">Select supplier</option>
                                                            <?php
                                                            $query = "SELECT * FROM supplier ORDER BY name";
                                                            $result = mysqli_query($conn, $query);
                                                            while ($row = mysqli_fetch_assoc($result)) {
                                                                $selected = ($formData['supplier_name'] == $row['name']) ? 'selected' : '';
                                                                echo "<option value='{$row['name']}' $selected>{$row['name']}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                        <?php if (isset($formErrors['supplier_name'])): ?>
                                                        <div class="invalid-feedback">
                                                            <?php echo htmlspecialchars($formErrors['supplier_name']); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group mb-3">
                                                <label for="image" class="form-label">Product Image</label>
                                                <input type="file" class="form-control <?php echo isset($formErrors['image']) ? 'is-invalid' : ''; ?>" 
                                                    id="image" name="image" accept="image/*">
                                                <?php if (isset($formErrors['image'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($formErrors['image']); ?>
                                                </div>
                                                <?php endif; ?>
                                                <small class="text-muted">Max size: 2MB (JPEG, PNG, GIF)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Column - Pricing & Details -->
                                    <div class="col-md-6">
                                        <div class="form-section mb-4">
                                            <h6 class="section-title mb-3">Pricing & Inventory</h6>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="cost_price" class="form-label">Cost Price *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" min="0" 
                                                                class="form-control <?php echo isset($formErrors['cost_price']) ? 'is-invalid' : ''; ?>" 
                                                                id="cost_price" name="cost_price" 
                                                                value="<?php echo htmlspecialchars($formData['cost_price']); ?>" required>
                                                        </div>
                                                        <?php if (isset($formErrors['cost_price'])): ?>
                                                        <div class="invalid-feedback">
                                                            <?php echo htmlspecialchars($formErrors['cost_price']); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="sale_price" class="form-label">Sale Price *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" min="0" 
                                                                class="form-control <?php echo isset($formErrors['sale_price']) ? 'is-invalid' : ''; ?>" 
                                                                id="sale_price" name="sale_price" 
                                                                value="<?php echo htmlspecialchars($formData['sale_price']); ?>" required>
                                                        </div>
                                                        <?php if (isset($formErrors['sale_price'])): ?>
                                                        <div class="invalid-feedback">
                                                            <?php echo htmlspecialchars($formErrors['sale_price']); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group mb-3">
                                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                                <input type="number" min="0" 
                                                    class="form-control <?php echo isset($formErrors['stock_quantity']) ? 'is-invalid' : ''; ?>" 
                                                    id="stock_quantity" name="stock_quantity" 
                                                    value="<?php echo htmlspecialchars($formData['stock_quantity']); ?>" required>
                                                <?php if (isset($formErrors['stock_quantity'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($formErrors['stock_quantity']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <h6 class="section-title mb-3">Additional Details</h6>
                                            
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label for="size" class="form-label">Size</label>
                                                        <input type="text" class="form-control" 
                                                            id="size" name="size" 
                                                            value="<?php echo htmlspecialchars($formData['size']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label for="color" class="form-label">Color</label>
                                                        <input type="text" class="form-control" 
                                                            id="color" name="color" 
                                                            value="<?php echo htmlspecialchars($formData['color']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label for="gender" class="form-label">Gender</label>
                                                        <select class="form-control" id="gender" name="gender">
                                                            <option value="Unisex" <?php echo $formData['gender'] == 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                                                            <option value="Male" <?php echo $formData['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                            <option value="Female" <?php echo $formData['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                                    echo htmlspecialchars($formData['description']); 
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="ti ti-save"></i> Save Product
                                        </button>
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="ti ti-rotate-2"></i> Reset
                                        </button>
                                        <a href="products.php" class="btn btn-outline-danger">
                                            <i class="ti ti-x"></i> Cancel
                                        </a>
                                    </div>
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
    // Client-side validation
    $('#productForm').submit(function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        
        // Validate required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            }
        });
        
        // Validate numeric fields
        $('[type="number"]').each(function() {
            if ($(this).val() && isNaN(parseFloat($(this).val()))) {
                $(this).addClass('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            alert('Please fill all required fields correctly');
            return false;
        }
        
        // Submit form via AJAX
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData, 
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Redirect to products page with success message
                    window.location.href = response.redirect;
                } else {
                    // Show error message
                    alert(response.message);
                    
                    // Display field errors if any
                    if (response.errors) {
                        for (var field in response.errors) {
                            $('#' + field).addClass('is-invalid')
                                .after('<div class="invalid-feedback">' + response.errors[field] + '</div>');
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
        
        return false;
    });
    
    // Preview image before upload
    $('#image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').remove();
                $('#image').after('<div id="imagePreview" class="mt-2"><img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 150px;"></div>');
            }
            reader.readAsDataURL(file);
        }
    }); 
});
</script>

    <?php include('include/footer.php'); ?>