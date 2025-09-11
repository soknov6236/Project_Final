<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

require_once('include/connect.php');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term)) {
    echo json_encode([]);
    exit();
}

$query = "SELECT 
            product_id, 
            product_code, 
            name AS product_name, 
            sale_price AS selling_price, 
            stock_quantity AS stock, 
            image,
            color,
            size,
            description
          FROM products 
          WHERE 
            product_code LIKE ? OR 
            name LIKE ?
          ORDER BY name 
          LIMIT 8";

$searchTerm = "%$term%";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode($products);