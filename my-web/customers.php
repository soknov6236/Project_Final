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

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Customer List</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard/index.php">Home</a></li>
                    <li class="breadcrumb-item">Customer List</li>
                </ul>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <a href="add_new_customer.php" class="btn btn-outline-info">
                            <i class="ti ti-plus"></i> Add Customer
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="customers-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM customers ORDER BY created_at DESC";
                                    $result = mysqli_query($conn, $sql);

                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>
                                                <td>{$row['id']}</td>
                                                <td>{$row['customer_name']}</td>
                                                <td>{$row['email']}</td>
                                                <td>{$row['mobile_phone']}</td>
                                                <td>{$row['address']}</td>
                                                <td>".date('M d, Y', strtotime($row['created_at']))."</td>
                                                <td>
                                                    <a href='edit_customer.php?id={$row['id']}' class='btn btn-sm btn-outline-primary'>
                                                        <i class='ti ti-edit'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-outline-danger delete-btn' data-id='{$row['id']}'>
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
    // Delete customer
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this customer?')) {
            const customerId = $(this).data('id');
            $.post('customers/delete_customer.php', {id: customerId}, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    });
    
    // Initialize DataTable
    $('#customers-table').DataTable();
});
</script>

<?php include('include/footer.php'); ?>