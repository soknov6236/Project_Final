<?php
session_start();
require_once('../include/connect.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Validate product ID
$product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($product_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid product ID']));
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get product details before deletion
    $stmt = $conn->prepare("SELECT product_id, name, image FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Product not found");
    }
    
    $product = $result->fetch_assoc();
    $productName = $product['name'];
    $imagePath = $product['image'];
    $stmt->close();

    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete product");
    }
    $stmt->close();

    // Delete product image if exists
    if (!empty($imagePath)) {
        $fullImagePath = "../uploads/products/" . $imagePath;
        if (file_exists($fullImagePath)) {
            unlink($fullImagePath);
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Product '{$productName}' deleted successfully"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>