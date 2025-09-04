<?php
include('../include/connect.php');
session_start();

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$mobile_phone = mysqli_real_escape_string($conn, $_POST['mobile_phone'] ?? '');
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$created_by = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "INSERT INTO customers (customer_name, email, mobile_phone, address, created_by) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssssi", $customer_name, $email, $mobile_phone, $address, $created_by);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Customer created successfully!";
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>