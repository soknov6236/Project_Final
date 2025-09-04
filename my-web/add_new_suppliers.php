<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('include/connect.php');
    
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];

    // Validate inputs
    if (empty($name)) {
        $_SESSION['supplier_error_message'] = 'Supplier name is required';
        header("Location: add_new_suppliers.php");
        exit();
    }

    // Insert new supplier
    $sql = "INSERT INTO supplier (name, phone, address, status) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $address, $status);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['supplier_success_message'] = 'Supplier added successfully';
        header("Location: suppliers.php");
        exit();
    } else {
        $_SESSION['supplier_error_message'] = 'Error adding supplier: ' . mysqli_error($conn);
        header("Location: add_new_suppliers.php");
        exit();
    }
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
                    <h5 class="mb-0 font-medium">Add New Supplier</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Suppliers</a></li>
                    <li class="breadcrumb-item" aria-current="page">Add New Supplier</li>
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
                        <form method="post" id="suppliersForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-outline-primary" id="submitBtn">Save Supplier</button>
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

    $("#suppliersForm").on("submit", function(e) {
        e.preventDefault();
        
        var submitBtn = $("#submitBtn");
        var originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
        
        // Submit form normally (not via AJAX)
        this.submit();
    });
});
</script>
<?php include ('include/footer.php'); ?>