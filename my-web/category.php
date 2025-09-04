<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
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
                    <h5 class="mb-0 font-medium">Category List</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Category</a></li>
                    <li class="breadcrumb-item" aria-current="page">Category List</li>
                </ul>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <a href="add_new_category.php" class="btn btn-outline-info">
                            <i class="ti ti-plus"></i> Add Category
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="categorys-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM category ORDER BY created_at DESC";
                                    $result = mysqli_query($conn, $sql);

                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>
                                                <td>{$row['category_id']}</td>
                                                <td>{$row['name']}</td>
                                                <td>{$row['description']}</td>
                                                <td>".date('M d, Y', strtotime($row['created_at']))."</td>
                                                <td>
                                                    <a href='edit_category.php?id={$row['category_id']}' class='btn btn-sm btn-outline-primary'>
                                                        <i class='ti ti-edit'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-outline-danger delete-btn' data-id='{$row['category_id']}'>
                                                        <i class='ti ti-trash'></i>
                                                    </button>
                                                </td>
                                            </tr>";
                                    }
                                    mysqli_close($conn);
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Delete category
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this category?')) {
            const categoryId = $(this).data('id');
            $.post('category/delete_category.php', {id: categoryId}, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    });
    
    // Initialize DataTable
    $('#categorys-table').DataTable();
});
</script>
<?php include('include/footer.php'); ?>