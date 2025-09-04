<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: suppliers.php");
    exit();
}

include('include/connect.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];

    // Validate inputs
    if (empty($name)) {
        $_SESSION['supplier_error_message'] = 'Supplier name is required';
        header("Location: edit_suppliers.php?id=$supplier_id");
        exit();
    }

    // Update supplier
    $sql = "UPDATE supplier SET name = ?, phone = ?, address = ?, status = ? WHERE supplier_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $address, $status, $supplier_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['supplier_success_message'] = 'Supplier updated successfully';
        header("Location: suppliers.php");
        exit();
    } else {
        $_SESSION['supplier_error_message'] = 'Error updating supplier: ' . mysqli_error($conn);
        header("Location: edit_suppliers.php?id=$supplier_id");
        exit();
    }
}

$supplier_id = $_GET['id'];
$query = "SELECT * FROM supplier WHERE supplier_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$supplier = mysqli_fetch_assoc($result);

if (!$supplier) {
    header("Location: suppliers.php");
    exit();
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
                    <h5 class="mb-0 font-medium">Edit Supplier</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="suppliers.php">Suppliers</a></li>
                    <li class="breadcrumb-item" aria-current="page">Edit Supplier</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        
        <!-- Display error message if exists -->
        <?php if (isset($_SESSION['supplier_error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['supplier_error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['supplier_error_message']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Supplier Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" id="editSupplierForm">
                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($supplier['phone']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php 
                                    echo htmlspecialchars($supplier['address']); 
                                ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $supplier['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $supplier['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary" id="updateBtn">Update Supplier</button>
                                <a href="suppliers.php" class="btn btn-outline-danger">Cancel</a>
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
    // Initialize alertify
    alertify.set('notifier','position', 'top-right');
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        alertify.error(urlParams.get('error'));
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    $("#editSupplierForm").on("submit", function(e) {
        e.preventDefault();
        
        var submitBtn = $("#updateBtn");
        var originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');
        
        // Submit form normally (not via AJAX)
        this.submit();
    });
});
</script>
<?php include ('include/footer.php'); ?>