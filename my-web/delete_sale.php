<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');

// Get sale ID from URL
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sale_id <= 0) {
    $_SESSION['message'] = "Invalid sale ID";
    $_SESSION['message_type'] = "error";
    header("Location: manage_sale.php");
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Get sale items to restore stock
    $items_query = "SELECT product_id, quantity FROM sale_items WHERE sale_id = ?";
    $stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($stmt, 'i', $sale_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
    
    // 2. Restore stock for each product
    while ($item = mysqli_fetch_assoc($items_result)) {
        $update_query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $item['quantity'], $item['product_id']);
        mysqli_stmt_execute($stmt);
    }
    
    // 3. Delete sale items
    $delete_items_query = "DELETE FROM sale_items WHERE sale_id = ?";
    $stmt = mysqli_prepare($conn, $delete_items_query);
    mysqli_stmt_bind_param($stmt, 'i', $sale_id);
    mysqli_stmt_execute($stmt);
    
    // 4. Delete sale record
    $delete_sale_query = "DELETE FROM sales WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sale_query);
    mysqli_stmt_bind_param($stmt, 'i', $sale_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['message'] = "Sale deleted successfully";
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $_SESSION['message'] = "Error deleting sale: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

header("Location: manage_sales.php");
exit();
?> 