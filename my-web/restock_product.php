<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID";
    header("Location: products_stock.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product data
$query = "SELECT product_id, product_code, name, stock_quantity FROM products WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt); // This line was missing
$product = mysqli_fetch_assoc($result); // Now using $result instead of $stmt

if (!$product) {
    $_SESSION['error_message'] = "Product not found";
    header("Location: products_stock.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate quantity
    if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || $_POST['quantity'] <= 0) {
        $errors['quantity'] = "Please enter a valid quantity";
    }
    
    if (empty($errors)) {
        $quantity = intval($_POST['quantity']);
        $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update product stock
            $update_query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, 'ii', $quantity, $product_id);
            mysqli_stmt_execute($stmt);
            
            // Record in inventory log
            $log_query = "INSERT INTO inventory_log (product_id, quantity_change, notes, created_at) 
                          VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($stmt, 'iis', $product_id, $quantity, $notes);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Successfully added $quantity units to stock";
            header("Location: products_stock.php");
            exit();
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $errors['database'] = "Error updating stock: " . mysqli_error($conn);
        }
    }
    
    // If we got here, there were errors
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
}

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Restock Product</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products_stock.php">Stock Management</a></li>
                    <li class="breadcrumb-item" aria-current="page">Restock Product</li>
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
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Restock Product</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Code</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Current Stock</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity to Add</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                        <?php if (isset($_SESSION['form_errors']['quantity'])): ?>
                                            <div class="text-danger"><?php echo $_SESSION['form_errors']['quantity']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional notes about this restock"></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="products_stock.php" class="btn btn-secondary me-md-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Add to Stock</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Clear form errors after displaying them
if (isset($_SESSION['form_errors'])) {
    unset($_SESSION['form_errors']);
}
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

include('include/footer.php'); 
?>