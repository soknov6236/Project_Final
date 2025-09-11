<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once('include/connect.php');

// Initialize variables
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
$payment_status = ($payment_method === 'cash') ? 'paid' : 'pending';
$subtotal = 0;
$tax_rate = 0.01; // 1% tax (changed from 10% to 1%)

// Get product details from POST data
$product_ids = isset($_POST['product_id']) ? $_POST['product_id'] : [];
$quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
$prices = isset($_POST['price']) ? $_POST['price'] : [];

// Validate that we have products to process
if (empty($product_ids) || empty($quantities) || empty($prices)) {
    $_SESSION['error_message'] = "No products in the sale. Please add products before checkout.";
    header("Location: sale_pos.php");
    exit();
}

// Calculate totals
$items = [];
foreach ($product_ids as $index => $product_id) {
    $quantity = intval($quantities[$index]);
    $price = floatval($prices[$index]);
    $total = $quantity * $price;
    
    $items[] = [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total
    ];
    
    $subtotal += $total;
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax - $discount;

// Generate invoice number
$invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Validate stock before processing
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        
        // Get current stock with row lock
        $stock_query = "SELECT stock_quantity FROM products WHERE product_id = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $stock_query);
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_stock);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        // Check if stock is sufficient
        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for product ID: $product_id. Available: $current_stock, Requested: $quantity");
        }
    }
    
    // 1. Insert sale record
    $sale_query = "INSERT INTO sales (invoice_number, customer_id, date, total, tax, discount, payment_method, payment_status) 
                   VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sale_query);
    mysqli_stmt_bind_param($stmt, 'sidddss', $invoice_number, $customer_id, $total, $tax, $discount, $payment_method, $payment_status);
    mysqli_stmt_execute($stmt);
    $sale_id = mysqli_insert_id($conn);
    
    // 2. Insert sale items and update product quantities
    foreach ($items as $item) {
        // Insert sale item
        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, price, total) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $item_query);
        mysqli_stmt_bind_param($stmt, 'iiidd', $sale_id, $item['product_id'], $item['quantity'], $item['price'], $item['total']);
        mysqli_stmt_execute($stmt);
        
        // Update product stock
        $update_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $item['quantity'], $item['product_id']);
        mysqli_stmt_execute($stmt);
        
        // Check if update was successful
        if (mysqli_affected_rows($conn) === 0) {
            throw new Exception("Failed to update stock for product ID: " . $item['product_id']);
        }
    }
    
    // If we got here, commit the transaction
    mysqli_commit($conn);
    
    // Set success message and redirect to print invoice
    $_SESSION['success_message'] = "Sale completed successfully! Invoice #: " . $invoice_number;
    header("Location: print_invoice_pos.php?id=" . $sale_id);
    exit();
    
} catch (Exception $e) {
    // Something went wrong, roll back
    mysqli_rollback($conn);
    
    // Log the error
    error_log("Sale processing error: " . $e->getMessage());
    
    // Set error message
    $_SESSION['error_message'] = "Error processing sale: " . $e->getMessage();
    header("Location: sale_pos.php");
    exit();
}