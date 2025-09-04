<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');

// Get form data
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
$items = isset($_POST['items']) ? $_POST['items'] : [];

if (count($items) === 0) {
    $_SESSION['message'] = "No items in the sale";
    $_SESSION['message_type'] = 'danger';
    header("Location: add_new_sale.php");
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    $product_details = [];
    $subtotal = 0;
    $tax_rate = 0.10; // 10%

    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);

        // Get product with lock
        $query = "SELECT product_id, name, sale_price, stock_quantity 
                  FROM products 
                  WHERE product_id = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            throw new Exception("Product not found: $product_id");
        }

        if ($product['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock for product: " . $product['name']);
        }

        // Calculate line total
        $line_total = $product['sale_price'] * $quantity;
        $subtotal += $line_total;

        $product_details[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['sale_price'],
            'quantity' => $quantity,
            'stock_after' => $product['stock_quantity'] - $quantity
        ];
    }

    $tax = $subtotal * $tax_rate;
    $grand_total = $subtotal + $tax - $discount;

    // Generate invoice number
    $invoice_number = 'INV-' . date('YmdHis') . '-' . mt_rand(1000,9999);

    // Insert sale
    $query = "INSERT INTO sales (invoice_number, date, customer_id, total, tax, discount, payment_method, payment_status)
              VALUES (?, NOW(), ?, ?, ?, ?, ?, 'paid')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'siddds', $invoice_number, $customer_id, $grand_total, $tax, $discount, $payment_method);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error inserting sale: " . mysqli_error($conn));
    }
    $sale_id = mysqli_insert_id($conn);

    // Insert sale items and update stock
    foreach ($product_details as $product) {
        $item_total = $product['price'] * $product['quantity'];

        // Insert sale item
        $query = "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price, total)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt_item, 'iisidd', $sale_id, $product['id'], $product['name'], $product['quantity'], $product['price'], $item_total);
        if (!mysqli_stmt_execute($stmt_item)) {
            throw new Exception("Error inserting sale item: " . mysqli_error($conn));
        }

        // Update product stock
        $query = "UPDATE products SET stock_quantity = ? WHERE product_id = ?";
        $stmt_update = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt_update, 'ii', $product['stock_after'], $product['id']);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error updating product stock: " . mysqli_error($conn));
        }
    }

    // Commit transaction
    mysqli_commit($conn);

    // Set success message and redirect to print invoice
    $_SESSION['message'] = "Sale completed successfully! Invoice: $invoice_number";
    $_SESSION['message_type'] = 'success';
    $_SESSION['last_sale_id'] = $sale_id;
    header("Location: print_add_invoice.php?id=$sale_id");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Sale processing failed: " . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header("Location: add_new_sale.php");
    exit();
}