<?php
include('../include/connect.php');
session_start();

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get form data
$category_name = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$created_by = $_SESSION['user_id'];
$created_at = date('Y-m-d H:i:s');

// Prepare the SQL statement with correct fields
$stmt = mysqli_prepare($conn, "INSERT INTO category (name, description, created_by, created_at) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssis", $category_name, $description, $created_by, $created_at);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>