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
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$status = trim($_POST['status'] ?? 'active');
$current_time = date('Y-m-d H:i:s');

// Validate inputs
$errors = [];

if (empty($supplier_id)) {
    $errors[] = 'Supplier ID is required';
}

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

// Prepare and execute the query
$query = "UPDATE supplier SET 
            name = ?, 
            phone = ?, 
            address = ?, 
            status = ?, 
            updated_at = ?
          WHERE supplier_id = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $address, $status, $current_time, $supplier_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Supplier updated successfully'
        ]);
    } else {
        error_log('Supplier update error: ' . mysqli_error($conn));
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update supplier. Please try again.'
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