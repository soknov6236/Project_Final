<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

require_once('../include/connect.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request method']));
}

// Validate required fields
$required = ['product_id', 'product_code', 'name', 'category_name', 'supplier_name', 'cost_price', 'sale_price', 'stock_quantity'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => "Missing required field: $field"]));
    }
}

// Sanitize inputs
$product_id = intval($_POST['product_id']);
$product_code = $conn->real_escape_string(trim($_POST['product_code']));
$name = $conn->real_escape_string(trim($_POST['name']));
$category_name = $conn->real_escape_string(trim($_POST['category_name']));
$supplier_name = $conn->real_escape_string(trim($_POST['supplier_name']));
$cost_price = floatval($_POST['cost_price']);
$sale_price = floatval($_POST['sale_price']);
$stock_quantity = intval($_POST['stock_quantity']);

// Get current image
$current_image = '';
$query = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
$query->bind_param('i', $product_id);
$query->execute();
$query->bind_result($current_image);
$query->fetch();
$query->close();

// Handle file upload
$image_path = $current_image;
if (!empty($_FILES['image']['name'])) {
    $target_dir = "../uploads/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        die(json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed']));
    }

    if ($_FILES['image']['size'] > 5000000) {
        die(json_encode(['status' => 'error', 'message' => 'File size must be less than 5MB']));
    }

    $new_filename = "product_" . time() . ".$file_ext";
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Delete old image if it exists
        if (!empty($current_image) && file_exists($target_dir . $current_image)) {
            unlink($target_dir . $current_image);
        }
        $image_path = $new_filename;
    }
}

// Update product
$stmt = $conn->prepare("UPDATE products SET 
    product_code = ?,
    name = ?,
    category_name = ?,
    supplier_name = ?,
    cost_price = ?,
    sale_price = ?,
    stock_quantity = ?,
    image = ?
    WHERE product_id = ?");

$stmt->bind_param('ssssddssi', 
    $product_code, 
    $name, 
    $category_name, 
    $supplier_name, 
    $cost_price, 
    $sale_price, 
    $stock_quantity, 
    $image_path, 
    $product_id
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Product updated successfully";
    echo json_encode([
        'status' => 'success', 
        'message' => 'Product updated successfully',
        'redirect' => '../products.php'
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to update product: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>