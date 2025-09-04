<?php
include('../include/connect.php');
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_POST['id'] ?? 0;
$customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$mobile_phone = mysqli_real_escape_string($conn, $_POST['mobile_phone'] ?? '');
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');

$stmt = mysqli_prepare($conn, "UPDATE customers SET customer_name=?, email=?, mobile_phone=?, address=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "ssssi", $customer_name, $email, $mobile_phone, $address, $customer_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Customer updated successfully!";
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>