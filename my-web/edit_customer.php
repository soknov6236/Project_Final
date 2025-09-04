<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

include('include/connect.php');

$customer_id = $_GET['id'] ?? 0;
$stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found";
    header("Location: customers.php");
    exit();
}

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Edit Customer</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
                    <li class="breadcrumb-item" aria-current="page">Edit Customer</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <form id="editCustomerForm" method="post">
                            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($customer['customer_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($customer['email']) ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mobile_phone" class="form-label">Mobile Phone</label>
                                    <input type="tel" class="form-control" id="mobile_phone" name="mobile_phone" 
                                           value="<?= htmlspecialchars($customer['mobile_phone']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?= htmlspecialchars($customer['address']) ?>">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-outline-primary">Update Customer</button>
                                <a href="customers.php" class="btn btn-outline-danger">Cancel</a>
                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
$(document).ready(function() {
    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'customers/update_customer.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'customers.php';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    });
});
</script>

<?php include('include/footer.php'); ?>