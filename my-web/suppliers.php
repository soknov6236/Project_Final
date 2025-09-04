<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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
                    <h5 class="mb-0 font-medium">Suppliers List</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Suppliers</a></li>
                    <li class="breadcrumb-item" aria-current="page">Suppliers List</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        
        <!-- Display session messages -->
        <?php if (isset($_SESSION['supplier_success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['supplier_success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['supplier_success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['supplier_error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['supplier_error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['supplier_error_message']); ?>
        <?php endif; ?>

        <!-- Suppliers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <a href="add_new_suppliers.php" class="btn btn-outline-info">+ Add</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="suppliers-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $sql = "SELECT supplier_id, name, phone, address, status FROM supplier";
                                        $stmt = mysqli_prepare($conn, $sql);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                                    <td>{$row['supplier_id']}</td>
                                                    <td>{$row['name']}</td>
                                                    <td>{$row['phone']}</td>
                                                    <td>{$row['address']}</td>
                                                    <td><span class='badge bg-" . ($row['status'] == 'active' ? 'success' : 'danger') . "'>" . ucfirst($row['status']) . "</span></td>
                                                    <td>
                                                        <a href='edit_suppliers.php?id={$row['supplier_id']}' class='btn btn-sm btn-outline-primary'>
                                                            <i class='ti ti-edit'></i>
                                                        </a>
                                                        <button class='btn btn-sm btn-outline-danger delete-btn' data-id='{$row['supplier_id']}'>
                                                            <i class='ti ti-trash'></i>
                                                        </button>
                                                    </td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6'>No suppliers found</td></tr>";
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
    // Initialize DataTable
    $('#suppliers-table').DataTable();
    
    // Delete supplier
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this supplier?')) {
            const supplierId = $(this).data('id');
            $.post('suppliers/delete_suppliers.php', {id: supplierId}, function(response) {
                if (response.success) {
                    window.location.href = 'suppliers.php?success=' + encodeURIComponent(response.message);
                } else {
                    window.location.href = 'suppliers.php?error=' + encodeURIComponent(response.message);
                }
            }, 'json');
        }  
    });
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        alertify.success(urlParams.get('success'));
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (urlParams.has('error')) {
        alertify.error(urlParams.get('error'));
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
<?php include ('include/footer.php'); ?>