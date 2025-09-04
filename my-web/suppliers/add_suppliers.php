<?php
include('../include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Verify database connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Get form data
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$status = trim($_POST['status'] ?? 'active');
$current_time = date('Y-m-d H:i:s');

// Validate inputs
$errors = [];

if (empty($name)) {
    $errors[] = 'Supplier name is required';
} elseif (strlen($name) > 100) {
    $errors[] = 'Supplier name must be less than 100 characters';
}

if (!empty($phone) && !preg_match('/^[\d\s\-+()]{5,20}$/', $phone)) {
    $errors[] = 'Invalid phone number format';
}

if (!empty($address) && strlen($address) > 255) {
    $errors[] = 'Address must be less than 255 characters';
}

if (!in_array($status, ['active', 'inactive'])) {
    $errors[] = 'Invalid status value';
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit();
}

// Escape data
$name = mysqli_real_escape_string($conn, $name);
$phone = mysqli_real_escape_string($conn, $phone);
$address = mysqli_real_escape_string($conn, $address);
$status = mysqli_real_escape_string($conn, $status);

// Prepare and execute the query
$query = "INSERT INTO supplier (name, phone, address, status, created_at, updated_at) 
          VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $phone, $address, $status, $current_time, $current_time);
    
    if (mysqli_stmt_execute($stmt)) {
        $supplier_id = mysqli_insert_id($conn);
        echo json_encode([
            'status' => 'success',
            'message' => 'Supplier added successfully',
            'supplier_id' => $supplier_id
        ]);
    } else {
        error_log('Supplier add error: ' . mysqli_error($conn));
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to add supplier. Please try again.'
        ]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    error_log('Prepare statement error: ' . mysqli_error($conn));
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error. Please try again.'
    ]);
}

mysqli_close($conn);
?>