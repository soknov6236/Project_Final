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

$logged_in_username = $_SESSION['username'] ?? 'Unknown';
?>
<script>
  var loggedInUsername = "<?php echo $logged_in_username; ?>";
</script>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="mb-0">Users Management</h5>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <ul class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)">System</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)">Users</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <a href="add_new_users.php" class="btn btn-outline-info">+ Add New User</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="users-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT id, username, email, created_at FROM users";
                                    $result = mysqli_query($conn, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                                    <td>{$row['id']}</td>
                                                    <td>{$row['username']}</td>
                                                    <td>{$row['email']}</td>
                                                    <td>{$row['created_at']}</td>
                                                    <td>
                                                        <a href='edit_users.php?id={$row['id']}' class='btn btn-sm btn-outline-primary'>
                                                            <i class='ti ti-edit'></i>
                                                        </a>
                                                        <button class='btn btn-sm btn-outline-danger delete-btn' data-id='{$row['id']}'>
                                                            <i class='ti ti-trash'></i>
                                                        </button>
                                                    </td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>No users found</td></tr>";
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
    // Delete user
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this user?')) {
            const userId = $(this).data('id');
            $.post('users/delete_users.php', {id: userId}, function(response) {
                if (response.success) {
                    window.location.href = 'users.php?success=' + encodeURIComponent(response.message);
                } else {
                    window.location.href = 'users.php?error=' + encodeURIComponent(response.message);
                }
            }, 'json');
        }
    });
    
    // Initialize DataTable
    $('#users-table').DataTable();
});
</script>

<?php include('include/footer.php'); ?> 