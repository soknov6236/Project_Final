<?php
include('../include/connect.php');
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_POST['id'] ?? 0;

$stmt = mysqli_prepare($conn, "DELETE FROM customers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $customer_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Customer deleted successfully!";
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>