<?php
ob_start(); // Start output buffering
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Handle filters
$where = [];
$params = [];
$types = '';

if (isset($_GET['supplier_id']) && is_numeric($_GET['supplier_id'])) {
    $where[] = "p.supplier_id = ?";
    $params[] = $_GET['supplier_id'];
    $types .= 'i';
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $where[] = "p.purchase_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $where[] = "p.purchase_date <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get suppliers for filter dropdown
$suppliers = [];
$supplier_query = "SELECT supplier_id, name FROM supplier ORDER BY name";
$supplier_result = mysqli_query($conn, $supplier_query);
if ($supplier_result) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $row;
    }
}
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Purchase Items</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Inventory</a></li>
                    <li class="breadcrumb-item" aria-current="page">Purchase Items</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Purchase Items Records</h5>
                        <a href="purchase.php" class="btn btn-outline-primary">New Purchase</a>
                    </div>
                    <div class="card-body">
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">Supplier</label>
                                        <select class="form-select" id="supplier_id" name="supplier_id">
                                            <option value="">All Suppliers</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?php echo $supplier['supplier_id']; ?>" 
                                                    <?php echo isset($_GET['supplier_id']) && $_GET['supplier_id'] == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                            value="<?php echo $_GET['start_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                            value="<?php echo $_GET['end_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                                        <a href="purchase_list.php" class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped" id="purchase-items-table">
                                <thead>
                                    <tr>
                                        <th>Item ID</th>
                                        <th>Purchase ID</th>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT 
                                                pi.purchase_item_id,
                                                pi.purchase_id,
                                                p.purchase_date,
                                                s.name AS supplier_name,
                                                pr.name AS product_name,
                                                pr.product_code,
                                                pi.quantity,
                                                pi.unit_price,
                                                pi.subtotal
                                              FROM purchase_items pi
                                              JOIN purchases p ON pi.purchase_id = p.purchase_id
                                              JOIN supplier s ON p.supplier_id = s.supplier_id
                                              JOIN products pr ON pi.product_id = pr.product_id
                                              $where_clause
                                              ORDER BY p.purchase_date DESC";
                                    
                                    $stmt = mysqli_prepare($conn, $query);
                                    if ($where_clause) {
                                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                                    }
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                                    <td>{$row['purchase_item_id']}</td>
                                                    <td>{$row['purchase_id']}</td>
                                                    <td>" . date('M d, Y', strtotime($row['purchase_date'])) . "</td>
                                                    <td>{$row['supplier_name']}</td>
                                                    <td>{$row['product_code']} - {$row['product_name']}</td>
                                                    <td>{$row['quantity']}</td>
                                                    <td>" . number_format($row['unit_price'], 2) . "</td>
                                                    <td>" . number_format($row['subtotal'], 2) . "</td>
                                                    <td>
                                                        <a href='purchase_details.php?id={$row['purchase_id']}' class='btn btn-sm btn-outline-info'>
                                                            <i class='ti ti-eye'></i> View
                                                        </a>
                                                    </td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No purchase items found</td></tr>";
                                    }
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
    $('#purchase-items-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[2, 'desc']], // Sort by date descending by default
        pageLength: 25,
        responsive: true
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeTo(1000, 0).slideUp(1000, function(){
            $(this).alert('close');
        });
    }, 5000);
});
</script>

<?php 
ob_end_flush(); // End output buffering and flush
include('include/footer.php'); 
?>