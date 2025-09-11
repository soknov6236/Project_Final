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
                    <h5 class="mb-0 font-medium">Product List</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Product</a></li>
                    <li class="breadcrumb-item" aria-current="page">Product List</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        
        <!-- Success and Error Message Display -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
            <i class="ti ti-check me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['success_message']); 
        endif; 
        
        if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
            <i class="ti ti-alert-circle me-2"></i>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['error_message']); 
        endif; 
        ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <a href="add_new_products.php" class="btn btn-outline-info">
                            <i class="ti ti-plus"></i> Add Product
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="products-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Product Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Size</th>
                                        <th>Color</th>
                                        <th>Gender</th>
                                        <th>Cost</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Build the base query
                                    $sql = "SELECT 
                                                p.product_id,
                                                p.product_code,
                                                p.name,
                                                p.category_name,
                                                p.supplier_name,
                                                p.size,
                                                p.color,
                                                p.gender,
                                                p.cost_price,
                                                p.sale_price,
                                                p.stock_quantity,
                                                p.image,
                                                p.created_at
                                            FROM products p
                                            WHERE 1=1";
                                    
                                    // Add category filter if specified
                                    if (isset($_GET['category'])) {
                                        $category_name = mysqli_real_escape_string($conn, $_GET['category']);
                                        $sql .= " AND p.category_name = '$category_name'";
                                    }
                                    
                                    $sql .= " ORDER BY p.created_at DESC";
                                    
                                    $result = mysqli_query($conn, $sql);
                                    $no_results = true;

                                    if ($result && mysqli_num_rows($result) > 0) {
                                        $no_results = false;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $imagePath = !empty($row['image']) ? 'uploads/products/' . $row['image'] : 'assets/images/default-product.png';
                                            $status_class = $row['stock_quantity'] > 0 ? 'badge bg-success' : 'badge bg-danger';
                                            $status_text = $row['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock';
                                            
                                            echo "<tr>
                                                    <td>{$row['product_id']}</td>
                                                    <td><img src='{$imagePath}' width='60' height='60' class='img-thumbnail'></td>
                                                    <td>{$row['product_code']}</td> 
                                                    <td>{$row['name']}</td>
                                                    <td>{$row['category_name']}</td>
                                                    <td>{$row['supplier_name']}</td>
                                                    <td>{$row['size']}</td>
                                                    <td>{$row['color']}</td>
                                                    <td>{$row['gender']}</td>
                                                    <td>".number_format($row['cost_price'], 2)."</td>
                                                    <td>".number_format($row['sale_price'], 2)."</td>
                                                    <td>{$row['stock_quantity']}</td>
                                                    <td><span class='{$status_class}'>{$status_text}</span></td>
                                                    <td>
                                                        <div class='btn-group' role='group'>
                                                            <a href='edit_product.php?id={$row['product_id']}' class='btn btn-sm btn-outline-primary' title='Edit'>
                                                                <i class='ti ti-edit'></i>
                                                            </a>
                                                            <button class='btn btn-sm btn-outline-danger delete-btn' data-id='{$row['product_id']}' title='Delete'>
                                                                <i class='ti ti-trash'></i>
                                                            </button>
                                                            <a href='product_details.php?id={$row['product_id']}' class='btn btn-sm btn-outline-info' title='View'>
                                                                <i class='ti ti-eye'></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>";
                                        }
                                    }
                                    
                                    if ($no_results) {
                                        echo "<tr><td colspan='13' class='text-center'>No products found</td></tr>";
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
<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeTo(1000, 0).slideUp(1000, function(){
            $(this).alert('close');
        });
    }, 5000);
    
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            const productId = $(this).data('id');
            $.post('products/delete_product.php', {id: productId}, function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                } 
            }, 'json').fail(function() {
                showAlert('danger', 'Error: Unable to connect to server');
            });
        }
    });
    
    // Initialize DataTable
    $('#products-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']]
    }); 
});

// Helper function to show alert messages
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="ti ${type === 'success' ? 'ti-check' : 'ti-alert-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert-dismissible').alert('close');
    
    // Add new alert at the top of the content area
    $('.pc-content').prepend(alertHtml);
    
    // Auto-close after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeTo(1000, 0).slideUp(1000, function(){
            $(this).alert('close');
        });
    }, 5000);
}  

</script>
<?php include ('include/footer.php'); ?>