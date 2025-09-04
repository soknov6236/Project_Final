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

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Add New Category</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Category</a></li>
                    <li class="breadcrumb-item" aria-current="page">Add New Category</li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Category Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" id="categoryForm">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="text" class="form-label">description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-outline-primary">Save Category</button>
                                <a href="category.php" class="btn btn-outline-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>        <!-- [ breadcrumb ] end -->

    </div>
</div>
<script>
$(document).ready(function() {
    $("#categoryForm").on("submit", function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "category/add_category.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = "category.php";
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
<?php include ('include/footer.php'); ?>