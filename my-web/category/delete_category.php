<?php
session_start();
require_once('../include/connect.php');

header('Content-Type: application/json');

// Check if user is logged in and has permission
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit();
}

$category_id = $_POST['id'];

try {
    // First, check if the category exists
    $check_query = "SELECT * FROM category WHERE category_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'i', $category_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit();
    }
    
    // Delete the category
    $delete_query = "DELETE FROM category WHERE category_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'i', $category_id);
    $success = mysqli_stmt_execute($delete_stmt);
    
    if ($success) {
        $_SESSION['success_message'] = 'Category deleted successfully';
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    mysqli_close($conn);
}
?>