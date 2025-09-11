<?php
session_start();
require_once('../include/connect.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Validate required fields
if (empty($_POST['customer_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Customer name is required']);
    exit();
}

// Sanitize input data
$customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
$mobile_phone = isset($_POST['mobile_phone']) ? mysqli_real_escape_string($conn, trim($_POST['mobile_phone'])) : '';
$address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : '';

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit();
}

// Check if customer already exists
$check_query = "SELECT id FROM customers WHERE customer_name = '$customer_name'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer already exists']);
    exit();
}

// Insert new customer
$insert_query = "INSERT INTO customers (customer_name, email, mobile_phone, address, created_at) 
                 VALUES ('$customer_name', '$email', '$mobile_phone', '$address', NOW())";

if (mysqli_query($conn, $insert_query)) {
    $customer_id = mysqli_insert_id($conn);
    echo json_encode(['status' => 'success', 'customer_id' => $customer_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add customer: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>