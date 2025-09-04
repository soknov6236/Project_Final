<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

require_once('../include/connect.php');

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception("Invalid request method");
    }

    // Validate required fields (remove product_code from required fields)
    $required = [
        'name' => 'Product Name',
        'category_name' => 'Category',
        'supplier_name' => 'Supplier',
        'cost_price' => 'Cost Price',
        'sale_price' => 'Sale Price',
        'stock_quantity' => 'Stock Quantity'
    ];

    foreach ($required as $field => $name) {
        if (empty($_POST[$field])) {
            $response['errors'][$field] = "$name is required";
        }
    }

    if (!empty($response['errors'])) {
        $response['message'] = 'Please fill all required fields';
        echo json_encode($response);
        exit();
    }

    // Generate unique product code
    function generateProductCode($conn) {
        $prefix = 'PROD-';
        $query = "SELECT MAX(CAST(SUBSTRING(product_code, 6) AS UNSIGNED)) AS max_code 
                  FROM products 
                  WHERE product_code LIKE '{$prefix}%'";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $next_num = $row['max_code'] ? $row['max_code'] + 1 : 10000;
        return $prefix . str_pad($next_num, 5, '0', STR_PAD_LEFT);
    }
    
    $product_code = generateProductCode($conn);

    // Process data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $cost_price = floatval($_POST['cost_price']);
    $sale_price = floatval($_POST['sale_price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $size = isset($_POST['size']) ? mysqli_real_escape_string($conn, $_POST['size']) : null;
    $color = isset($_POST['color']) ? mysqli_real_escape_string($conn, $_POST['color']) : null;
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : 'Unisex';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : null;

    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception("Only JPG, PNG, and GIF images are allowed");
        }

        // Generate filename using product code
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = $product_code . '.' . $ext;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $file_name;
        }
    }

    // Insert into database using names instead of IDs
    $query = "INSERT INTO products (
        product_code, name, category_name, supplier_name, size, color, gender,
        cost_price, sale_price, stock_quantity, description, image, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssddiss',
        $product_code, $name, $category_name, $supplier_name, $size, $color, $gender,
        $cost_price, $sale_price, $stock_quantity, $description, $image_path
    );

    if (!mysqli_stmt_execute($stmt)) {
        // Delete uploaded file if insert failed
        if ($image_path && file_exists($upload_dir . $image_path)) {
            unlink($upload_dir . $image_path);
        }
        throw new Exception("Failed to add product: " . mysqli_error($conn));
    }

    // If we get here, the product was added successfully
    $response['status'] = 'success';
    $response['message'] = 'Product added successfully!';
    $response['redirect'] = 'products.php';
    
    // Set success message in session
    $_SESSION['success_message'] = 'Product added successfully!';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $_SESSION['form_errors'] = $response['errors'];
    $_SESSION['form_data'] = $_POST;
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    mysqli_close($conn);
    echo json_encode($response);
}