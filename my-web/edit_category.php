<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');

// Initialize variables
$category_id = $name = $description = '';
$error = '';

// Check if category ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $category_id = $_GET['id'];
    
    // Fetch category details
    $sql = "SELECT * FROM category WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $category = mysqli_fetch_assoc($result);
        $name = $category['name'];
        $description = $category['description'];
    } else {
        $_SESSION['error_message'] = 'Category not found';
        header("Location: category.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = 'No category specified';
    header("Location: category.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        // Check if category name already exists (excluding current category)
        $check_sql = "SELECT category_id FROM category WHERE name = ? AND category_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'si', $name, $category_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = 'Category name already exists';
        } else {
            // Update category
            $update_sql = "UPDATE category SET name = ?, description = ?, updated_at = NOW() WHERE category_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'ssi', $name, $description, $category_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success_message'] = 'Category updated successfully';
                header("Location: category.php");
                exit();
            } else {
                $error = 'Error updating category: ' . mysqli_error($conn);
            }
        }
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
                    <h5 class="mb-0 font-medium">Edit Category</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="category.php">Categories</a></li>
                    <li class="breadcrumb-item" aria-current="page">Edit Category</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Category Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-outline-primary">Update Category</button>
                                <a href="category.php" class="btn btn-outline-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<?php include('include/footer.php'); ?>