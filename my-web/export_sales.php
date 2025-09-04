<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');

// Get filter parameters from query string
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$invoice_number = isset($_GET['invoice_number']) ? trim($_GET['invoice_number']) : '';

// Build the same query as in mange_sale.php
$query = "SELECT s.invoice_number, s.date, c.customer_name, 
                 s.total, s.tax, s.discount, s.payment_method, s.payment_status
          FROM sales s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE 1=1";

// Add filters to the query
$params = [];
if (!empty($start_date)) {
    $query .= " AND s.date >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND s.date <= ?";
    $params[] = $end_date . ' 23:59:59';
}
if (!empty($status)) {
    $query .= " AND s.payment_status = ?";
    $params[] = $status;
}
if ($customer_id > 0) {
    $query .= " AND s.customer_id = ?";
    $params[] = $customer_id;
}
if (!empty($invoice_number)) {
    $query .= " AND s.invoice_number LIKE ?";
    $params[] = '%' . $invoice_number . '%';
}

$query .= " ORDER BY s.date DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Set CSV headers
$filename = 'sales_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Invoice Number', 
    'Date', 
    'Customer', 
    'Total', 
    'Tax', 
    'Discount', 
    'Payment Method',
    'Payment Status'
]);

// Format and add data rows
while ($row = mysqli_fetch_assoc($result)) {
    // Format date
    $row['date'] = date('M d, Y h:i A', strtotime($row['date']));
    
    // Format currency values
    $row['total'] = number_format($row['total'], 2);
    $row['tax'] = number_format($row['tax'], 2);
    $row['discount'] = number_format($row['discount'], 2);
    
    // Handle empty customer name
    $row['customer_name'] = $row['customer_name'] ?: 'Walk-in Customer';
    
    fputcsv($output, $row);
}

// Close the connection and exit
fclose($output);
mysqli_close($conn);
exit();