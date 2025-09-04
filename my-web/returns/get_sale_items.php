<?php
require_once('../include/connect.php');

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['sale_id']) && is_numeric($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    
    try {
        // Updated query - change 'si.price' to whatever your actual column name is
        $query = "SELECT si.id, p.product_id, p.name as product_name, 
                         p.product_code, si.quantity, si.price as unit_price
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.product_id
                  WHERE si.sale_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $sale_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            while ($item = mysqli_fetch_assoc($result)) {
                echo '<tr class="return-item">';
                echo '<td>' . htmlspecialchars($item['product_name']) . '<br>';
                echo '<small class="text-muted">' . htmlspecialchars($item['product_code']) . '</small>';
                echo '<input type="hidden" name="product_id[]" value="' . $item['product_id'] . '">';
                echo '</td>';
                echo '<td>' . $item['quantity'] . '</td>';
                echo '<td><input type="number" class="form-control return-quantity" name="quantity[]" 
                     min="0" max="' . $item['quantity'] . '" value="0" data-max="' . $item['quantity'] . '"></td>';
                echo '<td><input type="text" class="form-control unit-price" name="unit_price[]" 
                     value="' . number_format($item['unit_price'], 2) . '" readonly></td>';
                echo '<td><input type="text" class="form-control subtotal" name="subtotal[]" value="0.00" readonly></td>';
                echo '<td><input type="text" class="form-control" name="item_reason[]" placeholder="Reason"></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr id="no-items"><td colspan="7" class="text-center text-muted">No items found for this sale</td></tr>';
        }
    } catch (Exception $e) {
        echo '<tr id="no-items"><td colspan="7" class="text-center text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
    }
} else {
    echo '<tr id="no-items"><td colspan="7" class="text-center text-danger">Invalid sale ID</td></tr>';
}
?>