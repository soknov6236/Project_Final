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
                    <h5 class="mb-0 font-medium">Product Stock Management</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Inventory</a></li>
                    <li class="breadcrumb-item" aria-current="page">Stock Management</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

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
                        <h5>Current Stock Levels</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-filter"></i> Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?filter=all">All Products</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?filter=low">Low Stock (< 10)</a></li>
                                <li><a class="dropdown-item" href="?filter=out">Out of Stock</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php
                                $category_query = "SELECT * FROM category";
                                $category_result = mysqli_query($conn, $category_query);
                                if ($category_result) {
                                    while ($cat = mysqli_fetch_assoc($category_result)) {
                                        echo '<li><a class="dropdown-item" href="?category='.urlencode($cat['name']).'">'.$cat['name'].'</a></li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="stock-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Cost Price</th>
                                        <th>Sale Price</th>
                                        <th>Value</th>
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
                                                p.stock_quantity,
                                                p.cost_price,
                                                p.sale_price,
                                                p.image
                                            FROM products p
                                            WHERE 1=1";
                                    
                                    // Add filters
                                    if (isset($_GET['filter'])) {
                                        $filter = mysqli_real_escape_string($conn, $_GET['filter']);
                                        if ($filter == 'low') {
                                            $sql .= " AND p.stock_quantity > 0 AND p.stock_quantity < 10";
                                        } elseif ($filter == 'out') {
                                            $sql .= " AND p.stock_quantity <= 0";
                                        }
                                    }
                                    
                                    // Add category filter if specified
                                    if (isset($_GET['category'])) {
                                        $category_name = mysqli_real_escape_string($conn, $_GET['category']);
                                        $sql .= " AND p.category_name = '$category_name'";
                                    }
                                    
                                    $sql .= " ORDER BY p.stock_quantity ASC";
                                    
                                    $result = mysqli_query($conn, $sql);
                                    $no_results = true;
                                    $total_value = 0;

                                    if ($result && mysqli_num_rows($result) > 0) {
                                        $no_results = false;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $stock_value = $row['stock_quantity'] * $row['cost_price'];
                                            $total_value += $stock_value;
                                            
                                            // Determine status
                                            if ($row['stock_quantity'] <= 0) {
                                                $status_class = 'badge bg-danger';
                                                $status_text = 'Out of Stock';
                                            } elseif ($row['stock_quantity'] < 10) {
                                                $status_class = 'badge bg-warning';
                                                $status_text = 'Low Stock';
                                            } else {
                                                $status_class = 'badge bg-success';
                                                $status_text = 'In Stock';
                                            }
                                            
                                            echo "<tr>
                                                    <td>{$row['product_id']}</td>
                                                    <td>{$row['product_code']}</td>
                                                    <td>{$row['name']}</td>
                                                    <td>{$row['category_name']}</td>
                                                    <td>{$row['stock_quantity']}</td>
                                                    <td><span class='{$status_class}'>{$status_text}</span></td>
                                                    <td>".number_format($row['cost_price'], 2)."</td>
                                                    <td>".number_format($row['sale_price'], 2)."</td>
                                                    <td>".number_format($stock_value, 2)."</td>
                                                    <td>
                                                        <div class='btn-group' role='group'>
                                                            <a href='edit_product.php?id={$row['product_id']}' class='btn btn-sm btn-outline-primary' title='Edit'>
                                                                <i class='ti ti-edit'></i>
                                                            </a>
                                                            <a href='product_details.php?id={$row['product_id']}' class='btn btn-sm btn-outline-info' title='View'>
                                                                <i class='ti ti-eye'></i>
                                                            </a>
                                                            <a href='restock_product.php?id={$row['product_id']}' class='btn btn-sm btn-outline-success' title='Restock'>
                                                                <i class='ti ti-package'></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>";
                                        }
                                    }
                                    
                                    if ($no_results) {
                                        echo "<tr><td colspan='10' class='text-center'>No products found</td></tr>";
                                    }
                                    
                                    mysqli_close($conn);
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="8" class="text-end"><strong>Total Inventory Value:</strong></td>
                                        <td colspan="2"><strong><?php echo number_format($total_value, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
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
    // Initialize DataTable with export buttons
    $('#stock-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[4, 'asc']], // Default sort by stock quantity (ascending)
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            
            // Calculate total inventory value
            var totalValue = api
                .column(8, { search: 'applied' })
                .data()
                .reduce(function (a, b) {
                    return a + parseFloat(b.replace(/[^\d.-]/g, ''));
                }, 0);
            
            // Update footer
            $(api.column(8).footer()).html(
                '<strong>$' + totalValue.toFixed(2) + '</strong>'
            );
        }
    });
}); 
</script>

<?php include ('include/footer.php'); ?>