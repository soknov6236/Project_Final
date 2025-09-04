<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

include('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Add New Customer</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
                    <li class="breadcrumb-item">Add New Customer</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Customer Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" id="customerForm">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="mobile_phone" class="form-label">Mobile Phone</label>
                                <input type="tel" class="form-control" id="mobile_phone" name="mobile_phone">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-outline-primary">Save Customer</button>
                                <a href="customers.php" class="btn btn-outline-danger">Cancel</a>
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
    $("#customerForm").on("submit", function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "customers/add_customer.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = "customers.php";
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert("Error: " + error);
            }
        });
    });
});
</script>

<?php include('include/footer.php'); ?>