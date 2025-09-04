<?php
session_start();
require_once('../include/connect.php');

header('Content-Type: application/json');

// Check if request is POST and user is logged in
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get supplier ID from POST data
$supplier_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Validate supplier ID
if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid supplier ID']);
    exit;
}

try {
    // Check if supplier exists
    $check_stmt = $conn->prepare("SELECT supplier_id FROM supplier WHERE supplier_id = ?");
    $check_stmt->bind_param("i", $supplier_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        exit;
    }

    // Prepare delete statement
    $delete_stmt = $conn->prepare("DELETE FROM supplier WHERE supplier_id = ?");
    $delete_stmt->bind_param("i", $supplier_id);
    
    // Execute deletion
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete supplier']);
    }
    
    // Close statements
    $check_stmt->close();
    $delete_stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Close connection
$conn->close();
?>